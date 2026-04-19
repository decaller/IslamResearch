# IslamResearch Project Implementation Roadmap

This document outlines the step-by-step implementation plan for transforming the finalized architecture blueprints into a production-ready Laravel 13 Minimal Viable Product (MVP).

---

## 🚀 Phase 1: Database & Data Modeling (PostgreSQL)

The target is the exact translation of `docs/database_schema.dbml` into Laravel migrations.

- [x] **Core Migrations Generation**
    - [x] Publish Laravel Migrations. Require `pgvector` and `ltree` PostgreSQL extensions explicitly in a setup migration.
    - [x] Implement `taxonomies` table utilizing PostgreSQL `ltree` type and `gist` index for massive hierarchy optimizations.
    - [x] Implement polymorphic relationships for `collection_items` (`itemable_type`, `itemable_id`).
- [x] **Scaling & AI Safety Models**
    - [x] Add specific `embedding_ar` and `embedding_id` vector(1024) columns natively to the `sentences` table for maximum HNSW join performance.
    - [x] Create `sentence_jobs` tracking migration with `attempts` and `error_log` tracking loops.
    - [x] Add `deleted_at` timestamps (Soft Deletes) to structural content (`source_books`, `taxonomies`, `collections`).
    - [x] Add B-Tree indexing to chronological query tables (`user_journeys`, `user_habits`, `user_searches`).
- [x] **Filament MVP Scaffolding**
    - [x] Auto-generate base Filament V5 resources for `SourceBooks`, `Taxonomies`, and `Scholars`.
    - [x] Create deep relation managers (e.g., viewing Lexicon words mapped to `lexicon_roots`).
- [x] **Quran Data Ingestion (alquran.cloud API)**
    - [x] Build `AlQuranApiService` HTTP client wrapping `api.alquran.cloud/v1` (editions, surahs, meta).
    - [x] Implement `ImportQuranSurahJob` (×114 per-surah queued jobs) — upserts `sentences` with full ayah metadata (juz, page, hizb, ruku) and `sentence_translations` per edition.
    - [x] Create "Al Quran Cloud API" system Scholar record as author of imported translations.
    - [x] Build Filament `ImportQuran` page (`/admin/import-quran`) under "Data Sources" nav group with: Arabic edition selector, translation checkboxes, confirmation dialog, and Livewire progress bar (2s poll).

## 🤖 Phase 2: Hybrid AI Orchestration (Prefect + Horizon)

Implementing the GPU LLM pipeline seamlessly alongside Laravel webhooks.

- [x] **Python Pipeline (Prefect)**
    - [x] Bind CAMeL Tools or SpaCy into `tasks/text_prep.py` for Root extraction (`Jidhr`).
    - [x] Vectorize Arabic and translated text blocks into independent `embedding_ar` and `embedding_id` representations (`mxbai-embed-large`).
    - [x] Create `tasks/transliterate.py` — Ollama-powered ALA-LC romanisation per sentence (model & scheme configurable via `OLLAMA_TRANSLITERATE_MODEL` / `OLLAMA_TRANSLITERATE_SCHEME` in `.env`).
    - [x] Integrate `transliterate.run_ollama()` as **Step 6** in `main_pipeline.py`, between translation and vectorization.
    - [x] Persist transliteration via `database.save_transliteration()` upsert into `sentence_transliterations`.
    - [x] Include transliteration payload in the Laravel webhook (field `transliteration.{scheme, text}`).
- [x] **Laravel Horizon Queue Mapping**
    - [x] Install Laravel Horizon and define scaling configurations (Supervisors).
    - [x] Create `IntegratePrefectData` Job queued heavily on an `ai-callbacks` pipe.
    - [x] Build the `POST /api/webhooks/prefect/job-completed` endpoint to instantly return `202 Accepted` and offload mapping logic to Horizon.
- [x] **Testing Webhook Boundary**
    - [x] Implement **Pest PHP** test suites targeting the Webhook API. Use `Http::fake()` to throw random/malformed LLM schema blobs to guarantee Horizon fails elegantly rather than crashing.

## 🎨 Phase 3: The "Scholar IDE UI" (Livewire 4 + Tailwind v4 + daisyUI v5)

Constructing the split-pane, reactive study canvas using **daisyUI v5** (Tailwind v4 plugin) as the semantic component library, Alpine.js for client-side behaviour, and Livewire 4 for server-driven state.

- [x] **Workspace Layout & Alpine Initialization**
    - [x] Install `daisyui@5` as a Tailwind v4 plugin and configure the Islamic theme (dark/light tokens via daisyUI `data-theme`).
    - [x] Scaffold the Activity Bar using daisyUI `menu menu-vertical` + `tooltip` components (50px fixed strip).
    - [x] Build the Explorer/Sidebar using daisyUI `drawer` as the collapsible shell; populate with a `menu` tree for `taxonomies` (ltree paths).
    - [x] Render the Editor Tab Bar using daisyUI `tabs` + `tab` components; persist active tab index in Alpine `x-data`.
    - [x] Embed the complex Split Pane dragging logic natively in Alpine.js `x-data`, using a `divider` sentinel element as the resize handle, avoiding DOM rehydration delays.
- [x] **daisyUI Container Layouts**
    - [x] Build result/content cards using daisyUI `card` + `card-body` with `@container` queries so grid columns adapt dynamically to the surrounding pane width.
    - [x] Use daisyUI `stat` components for Lexicon distribution charts and `badge` for AI-generated tags on result cards.
    - [x] Apply daisyUI `skeleton` loading placeholders while Livewire dispatches async search queries.
- [x] **State Emitting (Write-Behind Redis Cache)**
    - [x] Connect Alpine UI state (pane widths, active tab, scroll `Y`) with Livewire generic listeners via `$dispatch`.
    - [x] Implement the 2-second debounce patch requests securely storing active tabs, horizontal scrolls, and split IDs in Redis.
- [x] **Syncing UI to Database**
    - [x] Map the Redis cached json blob to load and persist smoothly into the `user_workspaces` table on login/logout triggers.

## 🔍 Phase 4: Hybrid Search Integration

Activating high performance discovery pipelines.

- [x] **Laravel Scout Indexing (Meilisearch)**
    - [x] Deploy Meilisearch container within Sail.
    - [x] Expose Lexicon and basic titles to Scout for instant, typo-tolerant Autocomplete API endpoints.
- [x] **Semantic Vector Targeting (pgvector)**
    - [x] Implement the Regex Language "Cheat" in the central Search Controller.
    - [x] Execute native PostgreSQL `ORDER BY embedding_ar <-> [Query_Vector]` exclusively addressing philosophical or "murky" sentence searches.
- [x] **Knowledge Tree & Clustered Architecture**
    - [x] Implement deterministic metadata clustering (Surah/Chapter).
    - [x] Build the "Semantic Sniper" bridge for surgically highlighted fragments.
    - [x] Integrated User Feedback loop and Global Negative Query guardrails.
- [x] **Action Highlighting UI**
    - [x] Bind the returned `positions integer[]` array across sentences to highlight identical morphology roots generated by CAMeL.

---

## ✅ Tactical Implementation Checklist (Manual Check)

This section tracks the granular progress across all phases, mapping the high-level roadmap to specific, verifiable implementation steps.

### 🚀 Phase 1: Database & Foundation (Verified)

- [x] **Schema Integrity**
    - [x] Extensions `pgvector` and `ltree` enabled.
    - [x] `taxonomies` table using `ltree` for adjacency list hierarchy.
    - [x] `sentences` table with `embedding_ar` and `embedding_id` vector(1024) columns.
- [x] **Management Layer**
    - [x] Filament resources for `SourceBooks`, `Scholars`, and `Taxonomies`.
    - [x] Seeder for base Quranic data structure.
- [x] **Quran Import (alquran.cloud)**
    - [x] `app/Services/AlQuranApiService.php` — HTTP client with timeout, retry, gzip.
    - [x] `app/Jobs/ImportQuranSurahJob.php` — per-surah job with full ayah metadata, translations, scholar attribution.
    - [x] `app/Filament/Pages/ImportQuran.php` — admin import UI at `/admin/import-quran`.
    - [x] `resources/views/filament/pages/import-quran.blade.php` — live progress bar (2s Livewire poll).
    - [x] "Al Quran Cloud API" system Scholar auto-created on first translation import.

### 🤖 Phase 2: AI Pipeline & Webhooks (Verified)

- [x] **Ingestion Pipeline**
    - [x] Prefect worker configured with `tasks/text_prep.py`.
    - [x] Morphological root extraction via CAMeL Tools.
    - [x] `tasks/transliterate.py` — Ollama generates ALA-LC romanisation per sentence.
    - [x] `database.save_transliteration()` — idempotent upsert to `sentence_transliterations`.
    - [x] **`OLLAMA_URL` set in `.env`** (Tailscale IP: `100.75.239.40`).
    - [x] `OLLAMA_LLM_MODEL` (Transliteration/Aya) configured in `.env`.
    - [x] **Ollama Remote Accessibility:** Configured `OLLAMA_HOST=0.0.0.0` and verified connectivity from local machine.
    - [x] **Essential AI Models Pulled:** `llama3.1`, `nomic-embed-text` (embeddings), and `aya` (translation).
- [x] **Laravel Integration**
    - [x] `POST /api/webhooks/prefect/job-completed` endpoint functional.
    - [x] `IntegratePrefectData` job handling async data mapping (now includes `transliteration` field).
    - [x] Horizon supervisors active on `ai-callbacks` queue.

### 🎨 Phase 3: Scholar IDE UI (Verified)

- [x] **Infrastructure Setup**
    - [x] `daisyui@latest` (v5 compatibility) verified in package.json.
    - [x] Tailwind v4 setup in `app.css`.
    - [x] Islamic theme tokens defined in daisyUI config.
- [x] **Core Layout Components**
    - [x] **Activity Bar:** Vertical slim nav with tooltips.
    - [x] **Explorer Sidebar:** Drawer component with dynamic `Taxonomy` recursion.
    - [x] **Tabs & Editor:** Livewire 4 dynamic tabs with state persistence.
    - [x] **Split Pane Handle:** Alpine.js drag logic for dynamic resizing.
- [x] **Content & Search UI**
    - [x] **Result Cards:** Container-query aware cards with live data integration.
    - [x] **Detail Panel:** Contextual Intel panel with Tadabbur and Lexicon tabs.

### 🔍 Phase 4: Search & Discovery (Verified)

- [x] **Engine Setup**
    - [x] Meilisearch container running and synchronized via Scout.
    - [x] Vector search controller using `<->` cosine distance on `pgvector`.
- [x] **Clustered Tree Search (Knowledge Tree)**
    - [x] **Phase 1: Query Enrichment:** Automatic Entity extraction and Arabic Root detection.
    - [x] **Phase 2: Database Retrieval:** Injected User Personalization (Hidden items) and Global Negative Query filters.
    - [x] **Phase 3: The Refinement Bridge (Sniper):** Integrated Semantic Sliding Window re-ranker to "snipe" text fragments.
    - [x] **Phase 4: Clustered Synthesis:** Deterministic grouping engine for "Folder-based" results UI.
    - [x] **Semantic Caching:** Redis-backed query vector cache (< 1ms hits).

### 🧪 Global Verification Checkpoints

- [x] **Cross-Browser:** Does the split-pane and grid system work in Chrome/Firefox/Safari?
- [x] **Mobile Adapation:** Is the sidebar collapsible and usable on mobile?
- [x] **AI Latency:** Are webhook callbacks processed correctly under load?
- [x] **Data Persistence:** Does workspace state survive session logout/login?

---

## 🚀 Phase 5: DevOps & Deployment (Envoy + Docker)

Automation of production lifecycle.

- [x] **Laravel Envoy Configuration**
    - [x] Install `laravel/envoy` via Composer.
    - [x] Configure `Envoy.blade.php` with Docker-based deployment strategy.
    - [x] Implement `initial_setup` (CLONE + Docker Bootstrap).
    - [x] Implement `deploy` story (PULL + BUILD + MIGRATE + OPTIMIZE).
    - [x] Setup SSH key-based authentication for root on `100.92.183.79`.
- [x] **Infrastructure Monitoring**
    - [x] Integrate Slack notifications for deployment status in Envoy `@finished` hook.

### 🚢 Phase 5: DevOps & Deployment (In Progress)

- [x] **Envoy Automation**
    - [x] `Envoy.blade.php` initialized and tested for `initial_setup`.
    - [x] Production server (`100.92.183.79`) bootstrapped with SSH keys.
    - [x] Docker/Sail lifecycle managed via Envoy tasks.
    - [x] `.env` synchronization strategy implemented (`sync_env`).
- [ ] **Production Validation**
    - [ ] Verify first successful build completion on LXC (GPU/AI Pipeline dependencies).
    - [ ] Test Slack/Discord webhook notifications.

---

## 🎨 Future UI Roadmap (Mockup Driven)

For a detailed breakdown of UI/UX features and future interface implementations derived from high-fidelity mockups, see [mockup-features.md](docs/mockup-features.md).
