# AI Pipeline Architecture: Multi-Pipeline System

## Overview

The IslamResearch AI ingestion system is built on **modular, interchangeable pipelines**. Each `SourceBook` in the database declares exactly which Prefect pipeline processes it. This means a Quran ayah and a Tafsir paragraph are handled by completely different workflows — optimised for their unique structures — yet share the same reusable Python task workers.

---

## The Core Problem with a Single Pipeline

The original architecture had one flow (`Islamic Text Enrichment`) that was designed around Arabic `.txt` books. This breaks down because:

| Source Type | SpaCy Needed? | Ollama Translate? | Source Format | Failure Mode |
|:---|:---:|:---:|:---|:---|
| Quran Arabic | ❌ | ❌ | API (perfect JSON) | Redundant CPU work, LLM hallucination risk |
| Quran Translations | ❌ | ❌ | API (pre-translated) | Same as above |
| Standard Tafsir .txt | ✅ | ✅ | Raw text file | Works correctly |
| I'rab Books | ✅ | ✅ | Structured TXT | May need custom regex |

**The solution:** One pipeline per source type. Shared task workers. No duplicated logic.

---

## Architecture Diagram

```
Admin Filament UI
      │
      │  Assigns pipeline_id on SourceBook creation
      ▼
┌─────────────────────────────────────────────────┐
│              source_books table                  │
│   id | title         | pipeline_id              │
│   1  | Al-Quran      | quran_api_flow           │
│   2  | Tafsir Amal   | tafsir_amal_flow         │
│   3  | Tafsir Tabari | standard_txt_flow        │
└─────────────────────────────────────────────────┘
      │
      │  "Start Ingestion" button reads pipeline_id
      ▼
┌─────────────────────────────────────────────────┐
│           PrefectService (Laravel)               │
│   triggerBookIngestion($book)                    │
│     → POST /api/deployments/name/               │
│          {pipeline_id}/create_flow_run           │
└─────────────────────────────────────────────────┘
      │
      ▼
┌────────────────────────────────────────────────────────────────┐
│                    Prefect Worker (Python)                       │
│                                                                  │
│  quran_api_flow           standard_txt_flow    tafsir_amal_flow │
│  ┌─────────────┐          ┌──────────────┐    ┌─────────────┐  │
│  │ api_fetcher │          │ text_prep    │    │ custom_prep │  │
│  │ morphology  │          │ classify     │    │ classify    │  │
│  │ vectorize   │          │ translate    │    │ translate   │  │
│  │ save_verse  │          │ transliterate│    │ vectorize   │  │
│  └─────────────┘          │ vectorize    │    └─────────────┘  │
│                           │ save_sentence│                       │
│                           └──────────────┘                       │
└────────────────────────────────────────────────────────────────┘
      │
      │  Webhook: POST /api/webhooks/prefect/job-completed
      ▼
   Laravel updates SourceBook.status → completed
   Filament shows success notification
```

---

## Python Folder Structure (Target State)

```text
ai-scripts/
├── flows/                         # CUSTOM MANAGERS — one per source type
│   ├── quran_api.py               # Quran Arabic text via API
│   ├── quran_translation.py       # Quran translations via API
│   ├── quran_irob.py              # I'rab (grammatical analysis) books
│   ├── tafsir_amal.py             # Custom: Amali Tafseer TXT parser
│   └── standard_txt.py            # Generic .txt books (SpaCy + Ollama)
├── tasks/                         # SHARED WORKERS — never duplicated
│   ├── api_fetchers.py            # NEW: Quran API fetch (alquran.cloud)
│   ├── text_prep.py               # SpaCy segmentation + CAMeL morphology
│   ├── classify.py                # Ollama taxonomy classification
│   ├── translate.py               # Ollama translation (Aya model)
│   ├── transliterate.py           # Ollama ALA-LC transliteration
│   ├── vectorize.py               # mxbai-embed-large embeddings
│   ├── tags.py                    # Ollama scholarly tag generation
│   └── ner.py                     # HuggingFace Arabic NER
├── database.py                    # PostgreSQL access layer
├── serve_pipeline.py              # Registers ALL flows with Prefect
└── main_pipeline.py               # DEPRECATED → moved to flows/standard_txt.py
```

---

## Laravel Components

### Database

```
source_books
├── id (uuid)
├── title
├── resource_type           ← ResourceType enum (quran, tafsir, hadith, etc.)
├── pipeline_id             ← PipelineId enum string (routes to Prefect)
├── language
├── status                  ← pending | processing | completed | failed
└── metadata (jsonb)

taxonomies
├── name                    ← Arabic canonical label (primary search key)
├── name_ar                 ← Dedicated column for SQL filtering
├── name_en / name_transliteration
├── embedding_ar vector(1024)  ← ANN index for Arabic queries
├── taxonomy_translations   ← embedding per language
└── taxonomy_transliterations ← embedding per scheme

tags                        ← Normalised table (was sentences.metadata['tags'])
├── name_ar                 ← Arabic canonical label
├── embedding_ar vector(1024)
├── tag_translations        ← embedding per language
└── tag_transliterations    ← embedding per scheme

sentences
├── embedding_ar vector(1024)
├── sentence_translations   ← embedding per language
└── sentence_transliterations ← embedding per scheme (NOW with embedding)

searches
└── script_type             ← 'arabic' | 'latin' — query routing key
```

### Enum: `App\Enums\PipelineId`

| Enum Case | String Value | Description |
|:---|:---|:---|
| `QuranApi` | `quran_api_flow` | Quran Arabic text via alquran.cloud API |
| `QuranTranslation` | `quran_translation_flow` | Translation editions via API |
| `QuranIrab` | `quran_irab_flow` | I'rab (grammatical annotation) books |
| `TafsirAmal` | `tafsir_amal_flow` | Amali Tafseer (custom TXT parser) |
| `StandardTxt` | `standard_txt_flow` | Generic TXT books (SpaCy + Ollama) |

### PrefectService: Two Trigger Modes

| Mode | Method | When Used |
|:---|:---|:---|
| **Book-level** | `triggerBookIngestion(SourceBook $book)` | `quran_api_flow`, API-based flows |
| **Sentence-level** | `triggerEnrichment(SentenceJob $job)` | `standard_txt_flow`, TXT-based flows |

The key difference: API-based flows pull their own data from external sources. TXT-based flows operate on `sentence_jobs` records that Laravel has already created from uploaded files.

---

## Pipeline Comparison Table

| Feature | `quran_api_flow` | `standard_txt_flow` | `tafsir_amal_flow` |
|:---|:---:|:---:|:---:|
| Data Source | alquran.cloud API | DB sentence_jobs | DB sentence_jobs |
| SpaCy Splitting | ❌ | ✅ | ❌ (custom regex) |
| Ollama Translation | ❌ (API provides it) | ✅ | ✅ |
| Ollama Transliteration | Optional | ✅ | ✅ |
| CAMeL Morphology | ✅ (per word) | ✅ (per sentence) | ✅ |
| NER Tagging | Optional | ✅ | ✅ |
| Trigger Level | Book-level | Sentence-level | Sentence-level |
| Classification | ❌ (علوم القرآن fixed) | ✅ Arabic labels | ✅ Arabic labels |
| Saves Arabic Tags | ✅ via `tags` table | ✅ via `tags` table | ✅ via `tags` table |
| Saves Taxonomy Embeddings | ✅ (per book) | ✅ (per sentence) | ✅ (per sentence) |

---

## Arabic-First Principle

To maximise linguistic authenticity and embedding consistency, all AI operations work
in Arabic wherever the source text is Arabic. Non-Arabic languages appear **only** where
they are the natural output (translation, transliteration).

| Operation | Language | Rationale |
|:---|:---|:---|
| **Taxonomy classification** | ✅ Arabic | Stored as Arabic domain labels — no translation layer |
| **Scholarly tags** | ✅ Arabic | e.g., `التفسير الموضوعي`, `فقه الصلاة` |
| **Prompts to LLM** | ✅ Arabic | Aya model performs better classifying Arabic IN Arabic |
| **Primary embedding (`vector_ar`)** | ✅ Arabic source text | Core semantic anchor for search |
| **Translation output** | 🌍 Target language | e.g., Indonesian, English — by definition non-Arabic |
| **Translation embedding (`vector_translation`)** | 🌍 Target language | Cross-lingual search support |
| **Transliteration output** | 🔤 Latin script | ALA-LC romanisation — by definition non-Arabic |
| **NER entity words** | ✅ Arabic | Extracted from Arabic text, stored as-is |
| **NER entity type codes** | 🔤 PERS/LOC/ORG | Model output codes — language-neutral |

### Taxonomy Labels (Arabic)

All 35 taxonomy nodes are stored in Arabic. `classify.py` exports `EN_TO_AR_DOMAIN`
for any UI layer that needs to display English labels.

```python
# From tasks/classify.py:
VALID_DOMAINS_AR = [
    "علوم القرآن", "التفسير", "القراءات", "أسباب النزول",
    "علوم الحديث", "مصطلح الحديث", "التخريج",
    "الفقه", "العبادات", "المعاملات", ...
]
```

---

## Search Architecture

The search system detects the script of the query and routes to a different set of ANN indexes.

```
User types query
      │
      ▼
 searches table (save query + embedding + script_type)
      │
      ├── script_type = 'arabic'
      │         ↓
      │   ANN search on:
      │   • sentences.embedding_ar
      │   • taxonomies.embedding_ar
      │   • tags.embedding_ar
      │
      └── script_type = 'latin'
                ↓
          ANN search on:
          • sentence_translations.embedding
          • sentence_transliterations.embedding
          • taxonomy_translations.embedding
          • taxonomy_transliterations.embedding
          • tag_translations.embedding
          • tag_transliterations.embedding
```

### Symmetric Embedding Coverage

Every searchable dimension (sentences, taxonomies, tags) has **both** Arabic and Latin embedding indexes:

| Dimension | Arabic Embedding | Latin Translation Embedding | Latin Transliteration Embedding |
|:---|:---:|:---:|:---:|
| **Sentences** | `sentences.embedding_ar` | `sentence_translations.embedding` | `sentence_transliterations.embedding` |
| **Taxonomies** | `taxonomies.embedding_ar` | `taxonomy_translations.embedding` | `taxonomy_transliterations.embedding` |
| **Tags** | `tags.embedding_ar` | `tag_translations.embedding` | `tag_transliterations.embedding` |

---

## Implementation Checklist

### Database Migrations (done)
- [x] `add_pipeline_id_to_source_books_table`
- [x] `update_taxonomy_names_to_arabic_first`
- [x] `create_tags_tables` — `tags`, `tag_translations`, `tag_transliterations`, `sentence_tag`
- [x] `create_taxonomy_language_tables` — `taxonomy_translations`, `taxonomy_transliterations`, `taxonomies.embedding_ar`
- [x] `add_search_language_columns` — `sentence_transliterations.embedding`, `searches.script_type`

### Laravel Side
- [ ] Create `App\Enums\PipelineId`
- [ ] Update `SourceBook` model — `pipeline_id` cast
- [ ] Update `SourceBookForm` — add pipeline Select field
- [ ] Update `PrefectService` — `triggerBookIngestion()`, dynamic dispatch
- [ ] Add "Start Ingestion" action to `EditSourceBook` page
- [ ] Create `Tag` model + `TagTranslation` + `TagTransliteration` models
- [ ] Create `TaxonomyTranslation` + `TaxonomyTransliteration` models

### Python Side
- [ ] Create `ai-scripts/flows/` directory
- [ ] Move `main_pipeline.py` logic → `flows/standard_txt.py`
- [ ] Create `tasks/api_fetchers.py`
- [ ] Create `flows/quran_api.py`
- [ ] Create `flows/quran_translation.py`
- [ ] Create `flows/quran_irob.py`
- [ ] Create `flows/tafsir_amal.py`
- [ ] Update `serve_pipeline.py` to register all flows
- [ ] Update `database.py`: `save_to_db()` → write tags to `tags` + `sentence_tag` instead of JSONB
- [ ] Update `database.py`: add `save_quran_verse()`, `save_tag_with_embeddings()`

### Docs
- [x] `docs/pipeline-architecture.md` — this file
- [x] `docs/ai-script.md`
- [x] `docs/database_schema.dbml`
- [x] `ai-scripts/docs/quran_arabic.md`
- [x] `ai-scripts/docs/quran_translation.md`
- [x] `ai-scripts/docs/quran_irob.md`
- [x] `ai-scripts/docs/tafsir_amal.md`

---

## Guiding Principles

1. **Golden Rule:** Custom Flows, Shared Tasks. A new book type = a new file in `flows/`.
2. **Theological Safety:** Never use an LLM to translate the Quran. API translations are used directly.
3. **Book-level vs Sentence-level:** API flows are triggered once per book. TXT flows process one sentence job at a time.
4. **Symmetric Search:** Every searchable entity (sentence, taxonomy, tag) has Arabic embedding AND Latin translation/transliteration embedding.
5. **Arabic-First Storage:** `name`, `name_ar`, tags in Arabic. UI layers handle display translation.
6. **Idempotency:** All DB writes use `ON CONFLICT DO UPDATE` so re-runs are safe.
7. **Observability:** Every flow creates a Prefect Markdown artifact and sends a webhook to Laravel.
