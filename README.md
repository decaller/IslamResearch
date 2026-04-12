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
-   🎨 **[Scholar IDE Workspace](./docs/ux/ide-workspace.md)**: The structural UI layouts and Redis tab states.
-   🤖 **[AI Pipeline Details](./docs/pipeline.md)**: Details on the NLP models and stage-by-stage transformations.
-   📦 **[Data Schema Logic](./docs/details.md)**: Extended reasoning into the DB schema, `ltree`, and vector index isolations.
-   📝 **[Development Roadmap](./TODO.md)**: Current task list and implementation execution steps.

---

## 🏁 Getting Started

To get the project running locally, please follow the **[Installation & Setup Guide](./docs/installation.md)**.

---

*This project is dedicated to making Islamic knowledge more accessible through modern technology.*
