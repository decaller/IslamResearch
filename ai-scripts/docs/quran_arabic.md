# Pipeline: `quran_api_flow` — Quran Arabic Text (via API)

**Flow file:** `ai-scripts/flows/quran_api.py`  
**Trigger level:** Book-level (one trigger → all 6,236 ayahs)  
**Pipeline ID:** `quran_api_flow`

The Quran requires absolute structural perfection. Instead of uploading a raw `.txt` file
and relying on AI to guess sentence boundaries, this pipeline fetches data from the
**alquran.cloud** REST API — which already provides verse-by-verse JSON with full metadata.

> **Theological safety principle:** This flow never calls Ollama for translation.
> Indonesian translations (e.g., Kemenag) are fetched directly from the same trusted API.

---

## 1. Filament Setup

When an admin creates the Quran source book in Filament:

| Field | Value | Purpose |
|:---|:---|:---|
| **Title** | Al-Quran Al-Kareem | Display name |
| **Resource Type** | `quran` | Frontend rendering hint |
| **Pipeline ID** | `quran_api_flow` | Routes to this Prefect deployment |
| **Language** | `ar` | Primary language of the source |

**The "Start Ingestion" button** on the EditSourceBook page calls `PrefectService::triggerBookIngestion()`.
No sentence-level jobs are created beforehand — this flow pulls everything from the external API itself.

---

## 2. Trigger Flow (Laravel → Prefect)

| Step | Component | Action | Result |
|:---|:---|:---|:---|
| 1 | Filament Action | Admin clicks "Start Ingestion" | Dispatches Laravel background job |
| 2 | `DispatchBookIngestion` Job | Calls `PrefectService::triggerBookIngestion($book)` | Keeps admin browser responsive |
| 3 | `PrefectService` | Reads `$book->pipeline_id` = `quran_api_flow` | Sends `POST /api/deployments/name/quran_api_flow/create_flow_run` |
| 4 | Payload | `{ book_id: 1, arabic_edition: "quran-uthmani", translation_edition: "id.indonesian" }` | Prefect receives and queues the flow |
| 5 | Database | `SourceBook.status` → `processing` | Admin panel shows processing state |

---

## 3. AI Processing Flow (Prefect Worker)

No SpaCy. No Ollama. Pure API + CPU math.

| Step | Task | Engine | Action | Output |
|:---|:---|:---|:---|:---|
| 1 | `api_fetchers.get_quran_verses()` | alquran.cloud API | Fetches verse JSON: Arabic text, Indonesian translation, structural metadata | Array of 6,236 verses |
| 2 | `text_prep.extract_roots()` | CAMeL Tools (CPU) | Per-word morphological analysis: extracts 3-letter Jidhr root, strips harakat | Word-level lexicon arrays |
| 3 | `vectorize.create_embeddings()` | mxbai-embed-large (CPU) | Converts Arabic verse + Indonesian translation to dense semantic vectors | `vector_ar`, `vector_id` |
| 4 | `database.save_quran_verse()` | PostgreSQL | Upserts unified payload into DB | Saved record with full metadata |

---

## 4. Database Schema

### A. Items Table (per ayah)

| Column | Type | Value |
|:---|:---|:---|
| `id` | UUID | Auto-generated |
| `resource_type` | string | `quran` |
| `source_book_id` | UUID | FK → `source_books.id` |
| `content_ar_raw` | text | Arabic WITH harakat: `بِسْمِ اللَّهِ` |
| `content_ar_clean` | text | Arabic WITHOUT harakat: `بسم الله` |
| `content_translation` | text | Official Indonesian translation |
| `embedding_ar` | vector | Semantic embedding of Arabic text |
| `embedding_id` | vector | Semantic embedding of Indonesian text |
| `metadata` | jsonb | See below |

### B. Metadata JSONB (per ayah)

| Key | Example | Purpose |
|:---|:---|:---|
| `surah_id` | `1` | Filter by Surah |
| `surah_name_ar` | `"الفاتحة"` | Display data for UI |
| `surah_name_en` | `"Al-Fatiha"` | Latin display |
| `ayah_number` | `1` | Sorting and ordering |
| `juz` | `1` | Navigation by Juz |
| `mushaf_page` | `1` | Tadabbur dual-panel pagination |
| `arabic_edition` | `"quran-uthmani"` | Which mushaf edition was used |
| `translation_edition` | `"id.indonesian"` | Which translation was stored |

### C. Lexicon Tables (per word)

For every word in every ayah, the worker populates the root-word lexicon:

1. **`lexicon_roots`** — Insert root (e.g., `ب س م`) if not exists, then vectorize it.
2. **`lexicon_words`** — Insert surface word (e.g., `بِسْمِ` / clean: `بسم`) if not exists.
3. **`item_word`** *(pivot)* — Link word → ayah.

All inserts use `ON CONFLICT DO NOTHING` (idempotent).

---

## 5. Flow Code Sketch

```python
# flows/quran_api.py
from prefect import flow, get_run_logger
from tasks import api_fetchers, text_prep, vectorize
from database import save_quran_verse, get_book_metadata
import httpx, os

@flow(name="quran_api_flow")
def process_quran(book_id: int):
    logger = get_run_logger()
    book = get_book_metadata(book_id)

    # 1. Fetch perfectly-structured data from API
    verses = api_fetchers.get_quran_verses(
        arabic_edition=book['arabic_edition'],
        translation_edition=book['translation_edition'],
    )
    logger.info(f"Fetched {len(verses)} verses from API")

    for verse in verses:
        # 2. Morphological analysis (no SpaCy splitting needed — verse IS the unit)
        lexicon_data = text_prep.extract_roots([verse['arabic']])

        # 3. Vectorize Arabic + translation
        vectors = vectorize.create_embeddings(verse['arabic'], verse['translation'])

        # 4. Save to DB with full metadata
        save_quran_verse(
            book_id=book_id,
            verse=verse,
            vectors=vectors,
            lexicon_data=lexicon_data,
        )

    _notify_laravel(book_id, "completed")
    logger.info("✅ Quran ingestion complete. Alhamdulillah.")
```

---

## 6. Completion Flow

| Step | Component | Action | Result |
|:---|:---|:---|:---|
| 1 | Prefect | Sends `POST /api/webhooks/prefect/job-completed` | Payload: `{ book_id, verses_processed, status }` |
| 2 | Laravel Webhook | Updates `source_books.status` → `completed` | |
| 3 | Filament | Dispatches system notification | Green "Ingestion Finished" alert for active admins |

---

## 7. Why This Pipeline Exists

| Advantage | Detail |
|:---|:---|
| **Zero Hallucinations** | No LLM translates the Quran — API provides certified translations |
| **Speed** | Entire Quran processes in minutes, not days — no GPU needed |
| **Perfect Lexicons** | Clean API text means CAMeL extracts 100% accurate roots without noise |
| **Structural Trust** | API guarantees every ayah is correctly numbered, surah is correctly split |
