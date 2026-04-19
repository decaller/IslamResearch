# Technology Assumptions & Design Philosophy

This document outlines the core architectural assumptions and design decisions that drive the **IslamResearch** platform. It explains "Why" specific technologies were chosen and the underlying philosophy of the "Scholar IDE."

---

## 🏗️ 1. Project Thesis: The "Atomization" of Knowledge

Traditional Islamic digital libraries often present books as flat text or static scans. **IslamResearch** assumes that for meaningful research, classical texts must be **atomized** into semantically-enriched units (sentences/segments) enriched with morphological, thematic, and linguistic metadata.

---

## 🧠 2. Core Architectural Assumptions

### A. The "Scholarly IDE" Paradigm
Research is rarely linear. A scholar often needs to look at a Quranic verse, its exegesis (Tafsir), a related Hadith, and a linguistic root simultaneously.
- **Decision:** Use a **Multi-Tab, Split-Pane interface** similar to VS Code or IntelliJ.
- **Assumption:** High-performance state persistence is critical. We use **Redis-backed Livewire state** to remember scroll positions, open tabs, and active filters across sessions.

### B. AI as an "Automated Factory," Not just a Chatbot
Processing the vast corpus of Islamic literature manually is impossible. 
- **Decision:** Decouple the AI pipeline from the web application using **Prefect**.
- **Assumption 1 (Asynchronous Factory):** Large-scale NLP should happen in a specialized background environment to prevent blocking the web server.
- **Assumption 2 (Stage-based Granularity):** The pipeline separates **"Fast CPU"** tasks (segmentation, harakat stripping) from **"Slow GPU"** tasks (translation, vectorization). This allows CPU tasks to be horizontally scaled independently of expensive GPU resources.
- **Assumption 3 (Zero-Hallucination Guardrails):** For primary sources like the Quran, the system assumes a "Certified Ingestion" flow. AI is **never** used to generate the base Arabic or Translation text from scratch; it is only used for *enrichment* (tagging, transliteration, analytics).

### C. Hybrid Search: Dimensions vs. Keywords
Vector search (`pgvector`) is excellent for concepts but poor for specific spellings or typos. Keyword search (`Meilisearch`) is the opposite.
- **Decision:** Use **Hybrid Search**. 
- **Assumption 1 (Language Routing):** Users expect "Google-like" speed. We use a **Script Detection Cheat** (Regex) to route queries instantly to either `embedding_ar` (Arabic) or `embedding_id` (Latin), skipping expensive model-based classification.
- **Assumption 2 (Morphological Expansion):** Search is not a simple "Contains" operation. We assume a **Mu'jam Strategy**: searching for a Root word SHOULD expand to all its morphological derivatives across different books.
- **Assumption 3 (Conceptual Anchors):** If an LLM-generated translation is theologically nuance-poor, the system relies on the **Root Vector** as a conceptual bridge between languages to maintain semantic accuracy.

### D. Local-First AI (Privacy & Sovereignty)
Theological data is sensitive, and API costs for massive corpora are prohibitive.
- **Decision:** Use **Ollama** for local GPU-accelerated LLMs.
- **Assumption:** Running models like **Aya-23** locally ensures that the research platform remains independent of commercial API changes, maintains data privacy, and scales without per-token costs.

---

## 🛠️ 3. The Tech Stack Rationale

| Component | Technology | Rationale |
| :--- | :--- | :--- |
| **Backend** | Laravel 13 | The gold standard for modern PHP development, providing robust queuing (Horizon) and first-class admin tooling (Filament). |
| **Frontend** | Livewire 4 / Alpine.js | Allows for "Desktop-class" reactivity while keeping complex scholarly logic in PHP. Avoids the "SPA Bloat" of React/Vue for content-heavy research. |
| **Database** | PostgreSQL + pgvector | A unified database that handles Relational data, Hierarchical structures (`ltree`), and AI Vectors simultaneously. |
| **Styling** | Tailwind v4 + daisyUI v5 | Modern, container-aware styling that scales perfectly from a single phone screen to an ultra-wide scholar workstation. |
| **Orchestrator**| Prefect + Python | Python's NLP ecosystem (CAMeL Tools, SpaCy) is unrivaled. Prefect allows us to manage these scripts with visibility and retry logic. |

---

## 💾 4. Database-Level Logic Assumptions

Our PostgreSQL schema is not just a storage layer; it is an active participant in research logic.

1.  **Hierarchical Taxonomy (`ltree`):** We assume Islamic knowledge is deeply hierarchical (e.g., *Fiqh ➔ Ibadah ➔ Salah*). We use the PostgreSQL `ltree` extension for materialized paths, allowing us to perform massive "sub-tree" searches with extreme performance.
2.  **Dense Semantic Vectors (`vector(1024)`):** We standardize on **1024-dimensional vectors**. This assumes a high-fidelity embedding model (like `mxbai-embed-large`) capable of capturing the nuance of classical Arabic.
3.  **Harakat-Agnosticism:** All Arabic text is stored in two forms: Raw (with vowel marks) and Cleaned (vowel marks stripped). Search hits both, but indexing happens primarily on the cleaned text to maximize recall.
4.  **Universal Polymorphic Translations:** Instead of adding "translation" columns to every table, we assume a **Unified Translation Engine**. The `translatables` pivot allows any entity (Sentence, Word, Tag) to be translated into any language without schema bloat.
5.  **Matrix Highlighting Logic:** To enable precise scholarly review, we store word-to-sentence mappings as an **Integer Array (`positions[]`)**. This assumes that highlighting must be "pixel-perfect" across different diacritization styles.
6.  **Historical Entity Resolution & Auto-Building:** The Knowledge Graph (`entities` table) assumes that names are ambiguous and knowledge is interconnected. 
    - **Assumption 1 (Disambiguation):** Surrounding context (5 sentences) is enough for an LLM to resolve a specific historical identity.
    - **Assumption 2 (Autonomous Discovery):** The system can autonomously build its ontology by following connections (e.g., categories) from identified nodes.
    - **Decision (Guardrails):** To prevent infinite crawl loops and API abuse, we implement a **30-day "visited" cache** (`last_enriched_at`) and a **Strict Depth Limit (Max 2 Hops)** for all autonomous building.
    - **Decision (Cycle Protection):** Recursive graph traversals must use PostgreSQL `CYCLE` protection to prevent recursive query crashes.


---

## 🌊 5. Pipeline Consistency & Human Review

1.  **Idempotent Upserts:** We assume the AI pipeline will be re-run (e.g., when moving from llama3 to a better model). Every task uses **Idempotent Logic** (`ON CONFLICT DO UPDATE`) to ensure database state remains consistent without duplication.
2.  **The "Waiting Room" (Ambiguity Queue):** We assume AI is fallible (especially for zero-shot categorization). Any classification with **<60% confidence** is automatically routed to a manual Scholar Review queue in Filament rather than being published.
3.  **Context preservation:** Unlike standard RAG systems that chunk text by arbitrary length, we assume that **scholarly context is sacred**. We segment by **Sentence Boundaries (SBD)** and maintain 5-sentence windows for every unit.
4.  **Linguistic Hierarchy:** We assume every Arabic word is linked to a **Root (Jidhr)**. Our `lexicon` strategy treats roots as the "connective tissue" between unrelated books.

---

## 🌊 6. Search Execution Flow & The Post-Retrieval Bridge

To maintain "Google-like" speed (< 60ms) while providing "Scholar-level" structure, the system uses a 4-phase chronological bridge.

1.  **Phase 1: Query Processing (The Input):**
    *   **Asymmetric Processing:** We assume processing a 5-word query is computationally trivial compared to processing millions of documents. 
    *   **Semantic Caching:** Every query vector is cached in Redis for 30 days. Repeat searches skip the embedding model entirely, reducing latency to **< 1ms**.
    *   **Metadata Enrichment:** The system automatically tokenizes queries to find implicit **Entities** (Knowledge Graph tags) and **Linguistic Roots** (Arabic morphology).

2.  **Phase 2: Database Retrieval (The Radar):**
    *   **Hybrid Injection:** Vector distance searches are combined with explicit filters derived from Phase 1.
    *   **Feedback Guardrails:** User personalization is injected here using `NOT id IN [user_hidden_ids]`. Globally banned items (via `negative_queries` metadata) are purged instantly.

3.  **Phase 3: The Refinement Bridge (The Sniper):**
    *   **Semantic Sliding Window:** We assume that even if a 300-word Hadith is retrieved, only a small part answers the user. We run a fast re-ranker to find the exact **8-word fragment** that matches the query vector.
    *   **Surgical Highlighting:** The chosen fragment is wrapped in `<mark>` tags before being passed to the UI.

4.  **Phase 4: Tree Synthesis (The Output):**
    *   **Deterministic Clustering:** To avoid LLM latency, grouping into "Knowledge Tree" folders happens via fast metadata faceting (Surah/Chapter) or mathematical clustering (K-Means).
    *   **Zero-Query LLM:** This ensures the "Clustered Syllabus" feel of the results is achieved without the cost or delay of a generative LLM.

---

*This document is a living record. As the platform evolves, these assumptions will be revisited based on user feedback and performance metrics.*
