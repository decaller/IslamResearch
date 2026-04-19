# 🌙 IslamResearch

**IslamResearch** is a state-of-the-art research platform designed to transform classical Islamic texts into semantically-enriched, highly searchable data units. By combining the power of modern web frameworks with advanced AI orchestration, it provides scholars and researchers with an intelligent workspace for studying and classifying sacred knowledge.

> [!IMPORTANT]
> **Project Status:** This project is currently in **very early development**. Many features are in the mockup/blueprint stage. Please refer to the **[Development Roadmap (TODO.md)](./TODO.md)** for the implementation status and upcoming milestones.

---

## 🏗️ Core Concept

The platform acts as an **AI-driven factory** for Islamic corpus management. It automates the tedious process of text cleaning, segmentation, classification, and translation, while maintaining a human-in-the-loop scholarly review system.

### Key Workflows:
1.  **Ingestion**: Upload classical texts into the system where Prefect handles the heavy Harakat stripping, Zero-Shot taxonomy classifications, and structural translation via local GPUs.
2.  **Semantic Mapping**: Generate massive separated vectors tied to explicit translations mapped tightly via pgvectorscale.
3.  **The "Scholar IDE"**: Access data across an IDE-esque split-pane interface using isolated tabs, synchronized scrolling, and a robust `Livewire 4` Redis debounce configuration handling session tracking.
4.  **Hybrid RAG Search**: Exploit `Meilisearch` specifically for typographic completions while engaging complex `pgvector` indices for exact semantic similarity mappings.

---

## ✨ UI & UX: The Scholar Workspace

IslamResearch is built with a **"Scholarly IDE"** paradigm, discarding traditional flat-page design for a multi-dimensional, integrated research environment.

### 🌓 Progressive Disclosure & "Magic" Search
The interface starts with a clean, approachable search (like Google) but instantly unfolds into a powerful academic workspace. 
- **Hybrid Search**: Toggle between **AI Semantic**, **Exact Word**, and **Root Explorer** modes.
- **Dynamic Context**: Difficult (*Gharib*) words are automatically identified with hoverable tooltips for instant linguistic clarity.

### 🔗 Infinite Explorability: "Every Word is a Portal"
The core philosophy is that knowledge should never be a dead end. Every entity on the screen is a clickable gateway:
- **Linguistic Deep-Dives**: Click any Arabic word to reveal its **Root (جذر)**, grammatical role, and distribution across the entire corpus.
- **Breadcrumb Journeys**: Your research path is tracked across *Sentence ➔ Root ➔ Tag ➔ Category*, allowing for seamless mental re-tracing.

### 🛠️ The IDE Interaction Model
Built with **daisyUI v5** and **Tailwind CSS v4**, the workspace provides:
- **Split-Pane View**: Pin the Quran on the left and a Tafsir or Lexicon on the right with synchronized scrolling.
- **Tabbed Browsing**: Open multiple searches or books in isolated tabs, just like a code editor.
- **State Persistence**: Your exact layout, scroll positions, and open tabs are saved to Redis/PostgreSQL, ensuring you can resume your research exactly where you left off.

### 📖 The Intelligence Panel (5-Tab System)
Every text unit can be viewed through five specialized scholarly lenses:
1.  **Contemplations (التدبر)** — Deep reflections and questions.
2.  **Actionable Habits (العمل)** — Practical daily applications with progress tracking.
3.  **Directives (توجيه)** — Explicit guidance and wisdom blocks.
4.  **Lexicon (معاني)** — Analytical word-level morphology and roots.
5.  **Exegesis (التفاسير)** — In-depth classical and modern commentaries.

---

## 🛠️ Tech Stack

Built with the best-in-class Laravel ecosystem and modern AI tools:

-   **Backend Core**: [Laravel 13](https://laravel.com) / PHP 8.5
-   **Admin Control**: [Filament v5](https://filamentphp.com) 
-   **Frontend IDE UI**: [Livewire 4](https://livewire.laravel.com) + [Alpine.js](https://alpinejs.dev)
-   **Styling**: [TailwindCSS v4](https://tailwindcss.com) utilizing massive `@container` strategies.
-   **Testing Protocol**: [Pest PHP v4](https://pestphp.com/) (implementing isolated boundary fake-testing for LLMs).
-   **Queue Orchestration**: [Laravel Horizon](https://laravel.com/docs/horizon) + [Redis](https://redis.io).
-   **AI Architecture**: [Prefect](https://www.prefect.io) + Ollama GPU.
-   **Data Solutions**: [PostgreSQL](https://www.postgresql.org) `pgvector` (Vectors / Ltree) & [Meilisearch](https://www.meilisearch.com).

---

## 📚 Documentation

Detailed guides are available to help you understand and extend the platform:

-   📖 **[Architecture Blueprint](./docs/architecture.md)**: Deep dive into the system design, queue systems, and boundary integrations.
-   🧠 **[Technology Assumptions](./docs/technology_assumptions.md)**: Rationale behind the stack and the "Scholar IDE" philosophy.
-   🎨 **[Scholar IDE Workspace](./docs/ux/ide-workspace.md)**: The structural UI layouts and Redis tab states.
-   🤖 **[AI Pipeline Details](./docs/pipeline.md)**: Details on the NLP models and stage-by-stage transformations.
-   📦 **[Data Schema Logic](./docs/details.md)**: Extended reasoning into the DB schema, `ltree`, and vector index isolations.
-   🕸️ **[Knowledge Graph](./docs/knowledge_graph.md)**: Details on Entity Disambiguation and relationship mapping.
-   📝 **[Development Roadmap](./TODO.md)**: Current task list and implementation execution steps.

---

## 🏁 Getting Started

To get the project running locally, please follow the **[Installation & Setup Guide](./docs/installation.md)**.

---

*This project is dedicated to making Islamic knowledge more accessible through modern technology.*
