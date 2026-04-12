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

## 🤖 Phase 2: Hybrid AI Orchestration (Prefect + Horizon)
Implementing the GPU LLM pipeline seamlessly alongside Laravel webhooks.

- [x] **Python Pipeline (Prefect)**
  - [x] Bind CAMeL Tools or SpaCy into `tasks/text_prep.py` for Root extraction (`Jidhr`).
  - [x] Vectorize Arabic and translated text blocks into independent `embedding_ar` and `embedding_id` representations (`mxbai-embed-large`).
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
- [ ] **Syncing UI to Database**
  - [ ] Map the Redis cached json blob to load and persist smoothly into the `user_workspaces` table on login/logout triggers.

## 🔍 Phase 4: Hybrid Search Integration
Activating high performance discovery pipelines.

- [ ] **Laravel Scout Indexing (Meilisearch)**
  - [ ] Deploy Meilisearch container within Sail.
  - [ ] Expose Lexicon and basic titles to Scout for instant, typo-tolerant Autocomplete API endpoints.
- [ ] **Semantic Vector Targeting (pgvector)**
  - [ ] Implement the Regex Language "Cheat" in the central Search Controller.
  - [ ] Execute native PostgreSQL `ORDER BY embedding_ar <-> [Query_Vector]` exclusively addressing philosophical or "murky" sentence searches.
- [ ] **Action Highlighting UI**
  - [ ] Bind the returned `positions integer[]` array across sentences to highlight identical morphology roots generated by CAMeL.

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

### 🤖 Phase 2: AI Pipeline & Webhooks (Verified)
- [x] **Ingestion Pipeline**
  - [x] Prefect worker configured with `tasks/text_prep.py`.
  - [x] Morphological root extraction via CAMeL Tools.
- [x] **Laravel Integration**
  - [x] `POST /api/webhooks/prefect/job-completed` endpoint functional.
  - [x] `IntegratePrefectData` job handling async data mapping.
  - [x] Horizon supervisors active on `ai-callbacks` queue.

### 🎨 Phase 3: Scholar IDE UI (Current Active Focus)
- [x] **Infrastructure Setup**
  - [x] `npm install -D daisyui@latest` (v5 compatibility).
  - [x] Tailwind v4 `@import "tailwindcss";` setup in `app.css`.
  - [x] Islamic theme tokens defined in daisyUI config.
- [x] **Core Layout Components**
  - [x] **Activity Bar:** Vertical slim nav with tooltips.
  - [x] **Explorer Sidebar:** Drawer component with `ltree` recursion.
  - [x] **Tabs & Editor:** Livewire 4 dynamic tabs with state persistence.
  - [x] **Split Pane Handle:** Alpine.js drag logic for dynamic resizing.
- [x] **Content & Search UI**
  - [x] **Result Cards:** Container-query aware cards for search results.
  - [x] **Detail Panel:** Slide-over or side-pane for word-by-word analysis.

### 🔍 Phase 4: Search & Discovery (Pending)
- [ ] **Engine Setup**
  - [ ] Meilisearch container running and synchronized via Scout.
  - [ ] Vector search controller using `<->` cosine distance on `pgvector`.
- [ ] **Advanced Features**
  - [ ] Regex "Cheat Sheet" parsing for hybrid queries.
  - [ ] Root highlighting based on `positions` integer array.
  - [ ] Fuzzy matching for transliterations.

### 🧪 Global Verification Checkpoints
- [ ] **Cross-Browser:** Does the split-pane and grid system work in Chrome/Firefox/Safari?
- [ ] **Mobile Adapation:** Is the sidebar collapsible and usable on mobile?
- [ ] **AI Latency:** Are webhook callbacks processed correctly under load?
- [ ] **Data Persistence:** Does workspace state survive session logout/login?
