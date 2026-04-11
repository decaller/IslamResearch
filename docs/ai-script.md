# Python AI Factory: Modular Pipeline Architecture

To keep the ingestion engine maintainable, the codebase is split into an **Orchestrator** (the manager) and **Tasks** (the modular workers). This ensures that updating a specific component (e.g., changing the translation model) only requires editing one small file without breaking the rest of the pipeline.

## 📂 Folder Structure

The implementation follows a modular modular structure inside the `ai-pipeline` container:

```text
ai-scripts/
├── main_pipeline.py      # The "Manager" (Prefect Flow)
├── tasks/                # The "Workers"
│   ├── __init__.py
│   ├── text_prep.py      # SpaCy, RegEx, Harakat removal
│   ├── classify.py       # Hugging Face Zero-Shot & NER
│   ├── translate.py      # Ollama API calls
│   └── vectorize.py      # Embedding generation
├── database.py           # Postgres and Meilisearch connections
├── requirements.txt      # Python dependencies
└── Dockerfile            # Container configuration (inside docker/python/)
```

---

## 🛠️ Stage 1: The Orchestrator (`main_pipeline.py`)

The main Prefect flow coordinates all tasks, handles retries, and manages the batch state. It acts as the central brain of the ingestion process.

```python
@flow(name="Islamic Text Ingestion")
def process_batch():
    texts = fetch_pending_records()
    for text in texts:
        clean_sentences = text_prep.clean_arabic(text['content'])
        for sentence in clean_sentences:
            category = classify.zero_shot(sentence)
            indonesian = translate.run_ollama(sentence)
            vectors = vectorize.create_embeddings(sentence, indonesian)
            save_to_db(text['id'], sentence, category, indonesian, vectors)
```

---

## 🧹 Stage 2: Preparation (`tasks/text_prep.py`)

**Fast CPU tasks.** Utilizes SpaCy for sentence splitting and standard Python/Regex for stripping Arabic vowel marks (Harakat) to prepare the text for clean vectorization.

- **Regex Stripping:** Efficiently removes non-essential marks.
- **SpaCy SBD:** Rule-based segmentation for high performance.
- **Lexicon Mapping:** Uses CAMeL Tools to extract roots and diacritized/clean word pairs for the Global Lexicon.

---

## 🏷️ Stage 3: Classification (`tasks/classify.py`)

Uses Hugging Face pipelines locally to assign rigid categories using zero-shot classification.

- **Model:** `MoritzLaurer/xlm-v-base-mnli-xnli`
- **Fallback Logic:** Only accepts classifications with >60% confidence; otherwise, marks for human review.

---

## 🌍 Stage 4: Translation (`tasks/translate.py`)

Makes HTTP requests to the Ollama container (running on the host GPU) using the **Aya** model. 

- **Resilience:** Prefect handles automatic retries ($3 \times$) with a 5-second delay if Ollama times out or crashes.

---

## 📐 Stage 5: Vectorization (`tasks/vectorize.py`)

Uses a multilingual embedding model to convert both Arabic and Indonesian texts into semantic vectors.

- **Model:** `mxbai-embed-large`
- **Mechanism:** Vectorizes both languages to enable cross-lingual semantic search (e.g., searching in Indonesian finding Arabic source text).

---

## 🗄️ Database Interaction (`database.py`)

Manages connections to PostgreSQL and Meilisearch, providing tasks for fetching pending records and saving enriched results.
