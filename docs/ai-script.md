# Python AI Factory: Multi-Pipeline Architecture

The AI ingestion engine uses a **modular multi-pipeline** design. Instead of one monolithic flow,
each `SourceBook` declares its own `pipeline_id` that routes it to a specialised Prefect deployment.
All pipelines share the same core task workers — only the orchestration flow differs.

> For the full architecture overview, see [`docs/pipeline-architecture.md`](./pipeline-architecture.md).
> For individual pipeline docs, see [`ai-scripts/docs/`](../ai-scripts/docs/).

---

## 📂 Folder Structure

```text
ai-scripts/
├── flows/                         # Custom Managers — one per source type
│   ├── quran_api.py               # Quran Arabic (API, no SpaCy, no LLM translate)
│   ├── quran_translation.py       # Translation editions via API
│   ├── quran_irob.py              # I'rab grammatical analysis books
│   ├── tafsir_amal.py             # Amali Tafseer (custom TXT parser)
│   └── standard_txt.py            # Generic .txt books (SpaCy + Ollama)
├── tasks/                         # Shared Workers — reused by all flows
│   ├── api_fetchers.py            # Quran API fetch (alquran.cloud)
│   ├── text_prep.py               # SpaCy + CAMeL: segmentation & morphology
│   ├── classify.py                # Ollama: taxonomy classification
│   ├── translate.py               # Ollama: Indonesian translation (Aya model)
│   ├── transliterate.py           # Ollama: ALA-LC romanisation
│   ├── vectorize.py               # mxbai-embed-large embeddings
│   ├── tags.py                    # Ollama: scholarly tag generation
│   └── ner.py                     # HuggingFace: Arabic Named Entity Recognition
├── database.py                    # PostgreSQL access layer (shared)
├── serve_pipeline.py              # Registers ALL flows with Prefect Server
└── main_pipeline.py               # DEPRECATED — logic in flows/standard_txt.py
```

---

## 🔀 How Routing Works

Every `SourceBook` in the database has a `pipeline_id` column. When an admin clicks
**"Start Ingestion"** in the Filament UI, Laravel's `PrefectService` reads this value
and dynamically calls the correct Prefect deployment:

```
source_book.pipeline_id = "quran_api_flow"
    → POST /api/deployments/name/quran_api_flow/create_flow_run
         parameters: { book_id: 1 }
```

---

## 🔄 Available Pipelines

| Pipeline ID | Flow File | Trigger Level | Description |
|:---|:---|:---|:---|
| `quran_api_flow` | `flows/quran_api.py` | Book-level | Quran Arabic text via alquran.cloud API |
| `quran_translation_flow` | `flows/quran_translation.py` | Book-level | Translation editions via API |
| `quran_irab_flow` | `flows/quran_irob.py` | Sentence-level | I'rab analysis books (TXT) |
| `tafsir_amal_flow` | `flows/tafsir_amal.py` | Sentence-level | Amali Tafseer (custom regex) |
| `standard_txt_flow` | `flows/standard_txt.py` | Sentence-level | Generic TXT books (SpaCy + Ollama) |

---

## 🛠️ Shared Task Workers

All flows import from `tasks/`. These workers are never duplicated — only the flow that calls them differs.

### `tasks/api_fetchers.py` — API Data Retrieval
Fetches structured JSON from the alquran.cloud REST API. Returns verse arrays with Arabic text, translations, and structural metadata (surah, ayah, juz, page).

### `tasks/text_prep.py` — Text Preparation
**Fast CPU tasks.** SpaCy `sentencizer` for sentence splitting; CAMeL Tools for morphological analysis (root extraction, harakat stripping). Used only in TXT-based flows.

### `tasks/classify.py` — Taxonomy Classification
Sends Arabic text + context window (2 prev, 2 next sentences) to Ollama (Aya model).
Both the **prompt and output are in Arabic** — the LLM is asked to classify in Arabic
from Arabic text. Returns a list of Arabic domain labels from the 35-node Islamic taxonomy
(e.g., `التفسير`, `الفقه`, `العقيدة والكلام`).

### `tasks/translate.py` — Translation
HTTP request to the Ollama container (GPU host). Input: Arabic text + context window. Output: Indonesian translation. Uses 3 retries with 5 s delay. Not used in API-based flows.

### `tasks/transliterate.py` — ALA-LC Transliteration
Sends Arabic text to Ollama to generate Library of Congress (ALA-LC) romanised transliteration. Writes to `sentence_transliterations` with `ON CONFLICT DO UPDATE`.

### `tasks/vectorize.py` — Embedding Generation
Uses `mxbai-embed-large` (local Ollama) to create dense semantic vectors.
- `vector_ar` — always the **Arabic source text** (primary semantic anchor for all search).
- `vector_translation` — the translation in its target language (Indonesian, English, etc.), only when a translation exists. Enables cross-lingual search.
- `embed_text()` helper for single-text embedding (used by translation-only flows).
Arabic embeddings are always computed first; translation embeddings are secondary and optional.

### `tasks/tags.py` — Scholarly Tags
Generates 3–5 Arabic scholarly tags per sentence (e.g., `التوحيد`, `فقه الصلاة`) using Ollama.
Prompt and output are **Arabic-only**.

> [!IMPORTANT]
> Tags are **no longer stored** in `sentences.metadata['tags']` (JSONB array).
> They are now first-class entities in the `tags` table with `name_ar` + `embedding_ar`.
> `database.py` upserts each tag into `tags` and links it to the sentence via `sentence_tag` pivot.
> `tag_translations` and `tag_transliterations` tables hold language variants with embeddings
> for Latin-query search.

### `tasks/ner.py` — Named Entity Recognition
HuggingFace `hatmimoha/arabic-ner` model. Extracts persons, locations, and organisations as structured tags (e.g., `PERS:محمد`).

---

## 🗄️ Database Layer (`database.py`)

Shared PostgreSQL access for all flows:

| Function | Purpose |
|:---|:---|
| `get_db_connection()` | Reads `DB_*` env vars, returns psycopg2 connection |
| `fetch_job_details(job_id)` | Fetches sentence + 2-prev/2-next context window |
| `save_to_db(...)` | Upserts embedding + categories on `sentences` |
| `save_transliteration(...)` | Upserts to `sentence_transliterations` (with embedding) |
| `save_tag_with_embeddings(...)` | *(Planned)* Upserts tag to `tags` + links via `sentence_tag` |
| `save_quran_verse(...)` | *(Planned)* Upserts Quran ayah with metadata JSONB |

### Search Routing (Query Side)

When a user searches, Laravel detects script type and queries different indexes:

```
Arabic query  → sentences.embedding_ar
               taxonomies.embedding_ar
               tags.embedding_ar

Latin query   → sentence_translations.embedding
               sentence_transliterations.embedding
               taxonomy_translations.embedding
               taxonomy_transliterations.embedding
               tag_translations.embedding
               tag_transliterations.embedding
```

The `searches` table stores: `query`, `script_type` (`arabic`\|`latin`), and `embedding`.

---

## 🚀 Serving (Prefect Deployments)

`serve_pipeline.py` registers all flows with the Prefect Server as separately named deployments.
Each deployment maps 1-to-1 with a `pipeline_id` value in the database:

```python
from prefect import serve
from flows.quran_api import process_quran
from flows.standard_txt import process_single_job

serve(
    process_quran.to_deployment(name="quran_api_flow"),
    process_single_job.to_deployment(name="standard_txt_flow"),
    # ... more flows
)
```

---

## 📋 Completion & Reporting

Every flow (API-based and TXT-based) ends with:
1. **Prefect Markdown Artifact** — enrichment summary visible in the Prefect UI dashboard.
2. **Laravel Webhook** — `POST /api/webhooks/prefect/job-completed` with `{ status, book_id, error }`.
3. **Filament Notification** — status indicator updates in the admin panel.
