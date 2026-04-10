# IslamResearch Project TODO List

This document tracks the implementation status of the IslamResearch platform, focusing on the transition from documentation-only architecture to a functional MVP.

## 🚀 MVP Implementation Plan (Step-by-Step)

### Phase 1: Laravel Foundation (Backend & Database)
- [ ] **Database Migrations**
    - [ ] Create `source_books` table (id, title, author, resource_type, language, status, raw_content).
    - [ ] Create `sentence_jobs` table (id, source_book_id, raw_text, status).
    - [ ] Create `items` table (id, source_book_id, resource_type, text, metadata [JSONB], text_vector [vector]).
- [ ] **Eloquent Models**
    - [ ] Implement `SourceBook` with `hasMany` relationships.
    - [ ] Implement `SentenceJob`.
    - [ ] Implement `Item` (Sentence) with JSON cast for metadata.
- [ ] **Filament Resources**
    - [ ] Create `SourceBookResource` with file/text upload.
    - [ ] Create `SentenceJobResource` (Read-only monitoring).
    - [ ] Create `ItemResource` with JSON form editor for metadata enrichment.

### Phase 2: AI Pipeline Integration
- [ ] **Python Database Connector (`ai-scripts/database.py`)**
    - [ ] Replace `fetch_pending_records` placeholder with real SQL to pull from `source_books` or `sentence_jobs`.
    - [ ] Replace `save_to_db` placeholder with real SQL to insert into `items`.
- [ ] **Prefect Orchestration**
    - [ ] Update `main_pipeline.py` to accept `source_book_id` as a parameter.
    - [ ] Ensure `vectorize.py` correctly handles the output structure of the selected embedding model.
- [ ] **Laravel -> Prefect Trigger**
    - [ ] Implement custom Filament Action "Process with AI" in `SourceBookResource`.
    - [ ] Use `Http` facade to send POST request to Prefect API.

### Phase 3: Completion & Feedback Loop
- [ ] **Webhook Endpoint**
    - [ ] Create `POST /api/webhooks/prefect/job-completed` in Laravel.
    - [ ] Update `SourceBook` status and record total processed count upon callback.
- [ ] **Manual UI Check**
    - [ ] Verify that processed items appear in the "Sentences" list with correct classification and translation.
    - [ ] Test the vector search manually via database query (until search UI is ready).

---

## 🛠️ Mockups & Placeholders Found

The following code sections are currently non-functional and require implementation:

### 🐍 AI Scripts (Python)
- [ ] **`ai-scripts/database.py`**: Functions `fetch_pending_records` and `save_to_db` are hardcoded mocks.
- [ ] **`ai-scripts/tasks/vectorize.py`**: Vector extraction logic (`ar_vector[0][0]`) is likely incorrect for the `Transformers` pipeline output.
- [ ] **`ai-scripts/tasks/classify.py`**: Classification categories are hardcoded; should potentially be dynamic or source-type dependent.

### 🐘 Laravel App (PHP)
- [ ] **Models & Migrations**: Essential models (`SourceBook`, `SentenceJob`, `Item`) mentioned in `architecture.md` are missing.
- [ ] **Filament Resources**: Admin interfaces for managing research data are missing.
- [ ] **API Logic**: Webhook handlers for Prefect are missing.

---

## 🔮 General Future Improvements (Post-MVP)

- [ ] **Advanced Scholarly Interface**: Side-by-side Arabic/Indonesian comparison with highlight-to-correct functionality.
- [ ] **Hierarchical Metadata**: Support for nested scholarly tags (e.g., Fiqh -> Shalah -> Arkan).
- [ ] **Multilingual Expansion**: Add support for English, Urdu, and French translations.
- [ ] **Streaming Search UI**: A high-performance frontend for researchers using Meilisearch and vector embeddings.
- [ ] **Batch Processing Optimization**: Shift from row-by-row saving to batch inserts in `ai-scripts`.
- [ ] **Ollama Model Fine-tuning**: Fine-tune the **Aya** model on specific classical Arabic corpora for better Indonesian nuance.

---

*Last Updated: 2026-04-10*
*Status: Architecture Documented / Code Skeleton Incomplete*
