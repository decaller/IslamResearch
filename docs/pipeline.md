# Islamic AI Ingestion Pipeline: Models, Libraries & Architecture

This document serves as the master blueprint for the AI ingestion pipeline. It details the specific machine learning models, the foundational NLP libraries, and the modular stage-by-stage workflow used to process classical Islamic texts.

---

## Part 1: NLP Libraries & Orchestration (The Engine)

These are the core Python packages that handle orchestration, text manipulation, and offline model execution inside the `ai-pipeline` container.

### 1. Prefect (`prefect`)
- **Function:** The overarching Data Orchestrator. 
- **Role:** Manages pipeline state, schedules background jobs, and handles automatic retries (e.g., waiting 5 seconds if Ollama crashes).

### 2. CAMeL Tools (`camel-tools`)
- **Function:** Advanced Arabic Morphology. 
- **Role:** Used for deep morphological analysis, including extracting the exact 3-letter Arabic Root (**Jidhr**), identifying Part-Of-Speech (POS), and Named Entity Recognition (NER) optimized for classical Arabic.

### 3. SpaCy (`spacy`)
- **Function:** Rule-based text processing. 
- **Role:** Uses the `ar_core_news_sm` pipeline for blazing-fast Sentence Boundary Detection (SBD) to chop massive paragraphs into manageable sentences.

### 4. Transformers (`transformers`)
- **Function:** The Hugging Face Python library. 
- **Role:** Allows the Python microservice to download, cache, and run classification and vectorization models locally on the CPU without needing external APIs.

### 5. Wikipedia API (`wikipedia-api`)
- **Function:** Community-structured knowledge retrieval.
- **Role:** Fetches canonical summaries, verified names, and semantic categories to enrich entities and provide tooltips for the "Scholar UI".

---

## Part 2: Transformers & LLMs (The Brains)

Below are the specific models utilized in the pipeline, their roles, and links for finding alternatives.

### 1. The Categorizer (Zero-Shot Classification)
- **Function:** Evaluates text against a rigid list of chapters (Fiqh, Aqidah, etc.) using probability math. 
- **Execution:** Extremely fast, runs on CPU.
- **Active Model:** `MoritzLaurer/xlm-v-base-mnli-xnli`
- **Find Alternatives:** Search for *zero-shot-classification + ar*

### 2. The Translator & Thinker (Generative LLM via Ollama)
- **Function:** Translates classical Arabic into formal Indonesian. 
- **Execution:** Runs on the host GPU via `llama.cpp` (Ollama) because generating text requires significant compute.
- **Active Model:** `CohereForAI/aya-23-8B` (Use GGUF format for Ollama)
- **Find Alternatives:** Search for *text-generation + ar + GGUF*

### 3. The Vectorizer (Multilingual Embeddings)
- **Function:** Converts both texts into completely separated semantic vectors (`embedding_ar`, `embedding_id`) to prevent cross-language concept dilution and drastically improve accurate recall.
- **Active Model:** `mixedbread-ai/mxbai-embed-large-v1`
- **Find Alternatives:** Search for *sentence-similarity + ar (Multilingual)*

### 4. The Tagger (Named Entity Recognition - NER)
- **Function:** Scans text to extract specific entities (People, Locations, Events) to populate JSON arrays for exact-match database filtering.
- **Active Model:** `Davlan/bert-base-multilingual-cased-ner-hrl`
- **Alternative:** CAMeL Tools can also handle this natively for Arabic.

---

## Part 3: The Pipeline Workflow

### 🧹 Stage 2: Preparation (`tasks/text_prep.py`)
**Fast CPU tasks.** Utilizes SpaCy for sentence splitting and standard Python/Regex for stripping Harakat.
- **Regex Stripping:** Efficiently removes non-essential marks.
- **SpaCy SBD:** Rule-based segmentation for high performance.
- **CAMeL Extraction:** Extracts the 3-letter root (**Jidhr**) for enhanced metadata tagging.
- **Lexicon Integration:** Populates the Global Lexicon (`lexicon_roots` and `lexicon_words`) to enable deep morphological search. See [Lexicon Strategy](./lexicon-strategy.md).

### 🏷️ Stage 3: Classification (`tasks/classify.py`)
Uses Hugging Face pipelines locally to assign rigid categories.
- **Model:** `MoritzLaurer/xlm-v-base-mnli-xnli`
- **Fallback Logic:** Only accepts classifications with **>60% confidence**; otherwise, marks for human review (sends to Filament Waiting Room).

### 🌍 Stage 4: Translation (`tasks/translate.py`)
Makes HTTP requests to the Ollama container (host GPU) using the **Aya** model.
- **Resilience:** Prefect handles automatic retries ($3 \times$) with a 5-second delay if Ollama times out.

### 🧠 Stage 4.1: Entity Resolution (`tasks/entity_resolution.py`)
Solving the "Umar" problem using historical and narrative context.
- **NER (Model):** Pre-scans for mentions of People, Places, and Books using `Davlan/bert-base-multilingual-cased-ner-hrl` or CAMeL tools.
- **Disambiguation:** Prefect pulls 5 sentences of context around hit and sends to Ollama (`aya`) to determine the exact historical figure.
- **Wikipedia Enrichment:** Pings Wikipedia (`tasks/wiki_enrich.py`) to fetch bios and category tags if the entity is not yet canonicalized.
- **Graphing:** Automatically generates S-P-O triplets ([Subject] -> [Predicate] -> [Object]) to populate the `entity_relationships` table.

### 🔤 Stage 4.5: Transliteration (`tasks/transliterate.py`)
Generates a **romanised (ALA-LC) transliteration** for every Arabic sentence via Ollama and stores it in the `sentence_transliterations` table.
- **Config:** `OLLAMA_URL`, `OLLAMA_TRANSLITERATE_MODEL`, `OLLAMA_TRANSLITERATE_SCHEME` in `.env` — the URL is shared with the translation task so you only configure it once.
- **Why AI?** Rule-based ALA-LC schemes fail on dialectal and classical Arabic; an LLM handles these edge cases gracefully.
- **Idempotent:** `ON CONFLICT (sentence_id, scheme) DO UPDATE` ensures pipeline re-runs are safe.

### 📐 Stage 5: Vectorization (`tasks/vectorize.py`)
Uses a multilingual embedding model.
- **Model:** `mxbai-embed-large`
- **Mechanism:** Vectorizes Arabic and translations individually, routing them to specific schema columns.

### 🗄️ Database Interaction (`database.py`)
Manages connections to PostgreSQL and Meilisearch, providing tasks for fetching pending records and saving enriched results.

---

## Part 4: Specialized Pipeline Flows

While the stages above follow a general pattern, certain resources require specialized logic:

- 📖 **[Quran Arabic Flow](pipeline/quran-arabic.md)**: Zero-hallucination API ingestion for the primary Quran text.
- 🔤 **[Quran Transliteration Flow](pipeline/quran-transliteration.md)**: Importing certified Latin-script editions.
- 🌍 **[Quran Translation Flow](pipeline/quran-translation.md)**: Adding multi-lingual layers to existing ayahs.
- 🕌 **[Amali Tafseer Flow](pipeline/tafsir-amal.md)**: Custom-segmented ingestion for structured commentary.
- ✍️ **[Quranic I'rab Flow](pipeline/quran-irob.md)**: Grammatical analysis linked to specific verses.
