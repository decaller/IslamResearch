# Pipeline: `quran_irab_flow` — Quranic I'rab Books (TXT)

**Flow file:** `ai-scripts/flows/quran_irob.py`  
**Trigger level:** Sentence-level (via `sentence_jobs` queue)  
**Pipeline ID:** `quran_irab_flow`

I'rab (إعراب) books provide word-by-word grammatical analysis of the Quran.
These are scholarly books uploaded as `.txt` files — not fetched from an API.
They require SpaCy sentence splitting and LLM-assisted translation, but have a
**strong link to specific Quran ayahs** that must be preserved in the metadata.

**Example sources:**
- إعراب القرآن وبيانه — Muhyiddin Darwish
- إعراب القرآن الكريم — Mahmoud Safwat

---

## 1. Filament Setup

| Field | Value | Notes |
|:---|:---|:---|
| **Title** | إعراب القرآن وبيانه | Full book title in Arabic |
| **Resource Type** | `quran_irab` | Frontend rendering hint |
| **Pipeline ID** | `quran_irab_flow` | Routes to this Prefect deployment |
| **Language** | `ar` | Source language |
| **Metadata** | `{ "linked_book_id": "<uuid of Quran Arabic source>" }` | Links I'rab sentences to their corresponding Quran ayahs |

---

## 2. How It Differs from `standard_txt_flow`

| Aspect | `standard_txt_flow` | `quran_irab_flow` |
|:---|:---|:---|
| SpaCy Splitting | ✅ Yes | ✅ Yes |
| Ollama Translation | ✅ Yes | ✅ Yes |
| Ollama Transliteration | ✅ Yes | ✅ Yes |
| Classification | ✅ Full taxonomy | Hardcoded: `Quranic Sciences` / `Nahwu` |
| Ayah Linking | ❌ No | ✅ Yes — each sentence linked to `surah:ayah` |
| Resource Type | Dynamic | Always `quran_irab` |

The key extra step: after segmentation, the flow **attempts to detect Quran ayah references**
(e.g., `سورة البقرة: آية ٢`) near each sentence block and attaches `surah_id` + `ayah_number`
to the sentence metadata so it can be cross-referenced with the main Quran source.

---

## 3. AI Processing Flow (Prefect Worker)

This flow processes one `sentence_job` at a time (same as `standard_txt_flow`).

| Step | Task | Engine | Action | Output |
|:---|:---|:---|:---|:---|
| 1 | `fetch_job_details()` | PostgreSQL | Loads sentence text + 2-prev/2-next context | Sentence content |
| 2 | `text_prep.clean_arabic()` | SpaCy + Regex | Strips harakat, sentence boundaries | Clean Arabic text |
| 3 | Ayah Reference Detection | Regex | Searches sentence block for `سورة X: آية Y` patterns | `surah_id`, `ayah_number` |
| 4 | `classify.zero_shot()` | Ollama (Aya) | Classification — constrained to `Nahwu` + `Quranic Sciences` | Category list |
| 5 | `translate.run_ollama()` | Ollama (Aya) | Indonesian translation with grammatical context | Translated text |
| 6 | `vectorize.create_embeddings()` | mxbai-embed-large | Embeddings for Arabic + translation | `vector_ar`, `vector_id` |
| 7 | `save_to_db()` | PostgreSQL | Upserts sentence with metadata including ayah link | Saved record |

---

## 4. Metadata JSONB (per sentence)

In addition to the standard sentence metadata, I'rab sentences include ayah link data:

| Key | Example | Description |
|:---|:---|:---|
| `category` | `"Nahwu"` | Primary classification |
| `categories` | `["Nahwu", "Quranic Sciences"]` | Full classification list |
| `tags` | `["إعراب", "فعل مضارع"]` | Scholarly tags |
| `surah_id` | `2` | Linked Surah (if detected) |
| `ayah_number` | `255` | Linked Ayah (Ayah al-Kursi, e.g.) |
| `linked_quran_sentence_id` | UUID | FK to the actual Quran ayah record |

---

## 5. Flow Code Sketch

```python
# flows/quran_irob.py
from prefect import flow, get_run_logger
from tasks import text_prep, classify, translate, transliterate, vectorize, tags, ner
from database import fetch_job_details, save_to_db, save_transliteration
import re, httpx, os

IRAB_CATEGORIES = ["Quranic Sciences", "Nahwu (Syntax & Grammar)"]

def detect_ayah_reference(text: str) -> dict:
    """Detect surah:ayah reference patterns in the text block."""
    pattern = r'سورة\s+\S+.*?آية\s+(\d+)'
    match = re.search(pattern, text)
    if match:
        return {"ayah_number": int(match.group(1))}
    return {}

@flow(name="quran_irab_flow")
def process_irab(sentence_job_id: str, lang: str = "id", scheme: str = "ala_lc"):
    logger = get_run_logger()
    job = fetch_job_details(sentence_job_id)
    if not job:
        return

    raw_text = job['content']
    context_prev = "\n".join(job.get('context_prev', []))
    context_next = "\n".join(job.get('context_next', []))

    # Detect ayah link from this sentence block
    ayah_meta = detect_ayah_reference(raw_text)

    # Classification constrained to I'rab-relevant domains
    categories = IRAB_CATEGORIES  # Can still call classify() for sub-categories

    translation = translate.run_ollama(raw_text, target_lang=lang)
    tl_result = transliterate.run_ollama(raw_text, scheme=scheme)
    vectors = vectorize.create_embeddings(raw_text, translation)

    metadata = {**job.get('sentence_metadata', {}), **ayah_meta}

    save_to_db(
        sentence_id=job['sentence_id'],
        categories=categories,
        translation=translation,
        vectors=vectors,
        needs_emb=True,
        needs_trans=True,
        lang=lang,
        tags=tags.generate_scholarly_tags(raw_text, context_prev, context_next),
        existing_metadata=metadata,
    )

    if tl_result:
        save_transliteration(job['sentence_id'], tl_result['scheme'], tl_result['transliteration_text'])

    _notify_laravel(sentence_job_id, "completed")
```

---

## 6. Completion Flow

Same as `standard_txt_flow` — webhook to Laravel, status update, Filament notification.
