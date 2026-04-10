# Implementation Details & Optimization

This document provides deep technical insights and implementation strategies for the more complex components of the IslamResearch Platform.

---

## 🏛️ Resource-Based Processing Flows (Stage 4 Deep Dive)

When a `SentenceJob` is picked up by the Prefect Worker, the Python code uses conditional routing based on its `resource_type`. Each branch uses specific Python tasks or LLM (Ollama) prompts to extract metadata unique to that text type.

### 1. The Quran (The Core Text)
Requires absolute precision and sub-verse segmentation for long entries.
-   **Stage 2 Customization:** Primary splitting by Ayah boundaries. If a verse exceeds **40 words** (e.g., *Ayah al-Dayn*), it triggers secondary splitting using Quranic *waqaf* marks or SpaCy SBD.
-   **Stage 4 Enrichment:**
    *   **Mapping:** Precise alignment for Surah_ID, Ayah_Number, Juzz, and **Ayah_Part_Index**.
    *   **Theme Tagging:** Zero-Shot model categorizes the segment (e.g., #Eschatology, #Prophet_Stories, #Fiqh).
    *   **Vectorization:** Dual-embedding (Arabic + Translation).

### 2. Hadith Books (Narrations)
Focuses on the separation of the chain (Sanad) from the text (Matn).
-   **Stage 2 Customization:** Standard SpaCy SBD with 5-sentence context to preserve the full narration context.
-   **Stage 4 Enrichment:**
    *   **Ollama Extraction:** The LLM extracts the narrator chain into an array. 
    *   *Prompt:* "Read this text. Does it contain a Sanad? Extract the narrators into an array."
    *   **Metadata Mapping:** Book, Chapter, Hadith_Number, and Authenticity_Grade.

### 3. Tafsir Books (Exegesis)
Connecting explanations back to the source Quranic verses.
-   **Stage 2 Customization:** Standard SpaCy SBD.
-   **Stage 4 Enrichment:**
    *   **Anchor Linking:** The LLM identifies exactly which Surah/Ayah is being discussed.
    *   **Categorization:** Tagging for *Asbabun Nuzul*, Linguistic Analysis, or Jurisprudence.
    *   **Schema:** Target_Surah_ID, Target_Ayah_Number, Author_ID.

### 4. Hadith Syarh Books (Commentaries)
Explanations of Hadith (e.g., Fath al-Bari).
-   **Stage 4 Enrichment:**
    *   **Anchor Linking:** Identify the specific Hadith number or core Matn phrase.
    *   **Categorization:** Tagging Narrator biographies (*Rijal*), Hidden defects (*'Ilal*), or Legal derivation.
    *   **Schema:** Target_Hadith_Collection, Target_Hadith_Number.

### 5. Language Tools (Linguistics)
Dictionaries (Lisan al-Arab) and grammar books.
-   **Stage 2 Customization:** Bypass standard SpaCy. Uses custom Regex or structured parsers to split by **Root Word / Lemma** entries.
-   **Stage 4 Enrichment:**
    *   **Jidhr Extraction:** Uses **CAMeL Tools** or LLM to extract the exact 3 or 4-letter Arabic root.
    *   **Vectorization Strategy:** Heavily weighted on the root word to enable cross-resource referencing.

### 6. Other Books (General Literature)
General literature covering faith, history, and ethics.
-   **Stage 4 Enrichment:**
    *   **NER Extraction:** Ollama extracts Named Entities: People, Places, Battles, and Events.
    *   **Theme Tagging:** Standard multi-label classification (#Trade, #Umayyad_Dynasty).

---

## 🗄️ Unified Database Strategy (PostgreSQL JSONB)

Because each resource type generates different metadata, PostgreSQL handles this via a unified `items` table with a strictly typed **JSONB** column.

| Field | Type | Description |
| :--- | :--- | :--- |
| **id** | UUID | Primary Key |
| **resource_type** | Enum | quran, hadith, tafsir, syarh, language, other |
| **sentence_text** | Text | The core sentence content |
| **context_text** | Text | The 5 sentences immediately before and after |
| **metadata** | **JSONB** | **Dynamic LLM data (Isnad, Root, Target_Ayah, etc.)** |
| **embedding** | Vector | Indexed with HNSW (pgvector) |

---

## 🔍 Search Technology

The platform utilizes **pgvector** with **HNSW** indexes. We implement a **Language Detection "Cheat"** to maintain millisecond latency:
Instead of an expensive AI model for language detection, we use a blazing-fast PHP Regex in the search controller:
```php
preg_match('/\p{Arabic}/u', $query)
```
Arabic queries route to the `arabic_embedder` index, while Latin queries (Indo/English) route to the `multilingual_embedder`.

---

## 🤖 Prefect Pipeline Workflow (`main_pipeline.py`)

The pipeline handles the division of labor between "Fast" (CPU) and "Slow" (GPU) tasks using a modular architecture.

```python
@flow(name="Islamic Text Ingestion")
def process_batch():
    texts = fetch_pending_records()
    for text in texts:
        # Fast CPU: Prep & Categorize
        clean_sentences = text_prep.clean_arabic(text['content'])
        for sentence in clean_sentences:
            category = classify.zero_shot(sentence)
            # Slow GPU: Translate (Ollama)
            indonesian = translate.run_ollama(sentence)
            # Math/Vector: Embeddings
            vectors = vectorize.create_embeddings(sentence, indonesian)
            # Save Enriched Data
            save_to_db(text['id'], sentence, category, indonesian, vectors)
```

---

## 🐳 Docker Setup (Prefect)

The AI factory runs in a dedicated Python container sharing the `sail` network.

### Python Dockerfile (`docker/python/Dockerfile`)
```dockerfile
FROM python:3.11-slim
RUN apt-get update && apt-get install -y gcc g++ curl libpq-dev
RUN pip install prefect transformers torch requests spacy psycopg2-binary camel-tools
RUN python -m spacy download ar_core_news_sm
WORKDIR /app
CMD ["tail", "-f", "/dev/null"]
```

### Sail Service Extension
```yaml
    prefect-server:
        image: prefecthq/prefect:2-python3.11
        command: prefect server start --host 0.0.0.0
        ports: ["4200:4200"]
        networks: [sail]

    ai-pipeline:
        build: { context: ./docker/python }
        volumes: ["./ai-scripts:/app"]
        environment:
            - PREFECT_API_URL=http://prefect-server:4200/api
            - DB_HOST=pgsql
            - OLLAMA_HOST=http://host.docker.internal:11434
        networks: [sail]
```
