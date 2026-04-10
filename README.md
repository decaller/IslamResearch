# 🌙 IslamResearch

**IslamResearch** is a state-of-the-art research platform designed to transform classical Islamic texts into semantically-enriched, highly searchable data units. By combining the power of modern web frameworks with advanced AI orchestration, it provides scholars and researchers with an intelligent workspace for studying and classifying sacred knowledge.

> [!IMPORTANT]
> **Project Status:** This project is currently in **very early development**. Many features are in the mockup/blueprint stage. Please refer to the **[Development Roadmap (TODO.md)](./TODO.md)** for the implementation status and upcoming milestones.

---

## 🏗️ Core Concept

The platform acts as an **AI-driven factory** for Islamic corpus management. It automates the tedious process of text cleaning, segmentation, classification, and translation, while maintaining a human-in-the-loop scholarly review system.

### Key Workflows:
1.  **Ingestion**: Upload classical Arabic texts (Harakat-rich) into the system.
2.  **AI Factory**: Trigger Prefect-orchestrated pipelines that segment text, strip harakat, classify topics (Fiqh, Aqidah, etc.), and generate translations.
3.  **Semantic Enrichment**: Generate multilingual vector embeddings to support concept-based search rather than just keyword matching.
4.  **Scholarly Review**: Use a premium Filament-powered dashboard to verify, correct, and annotate AI-generated data.

---

## 🛠️ Tech Stack

Built with the best-in-class Laravel ecosystem and modern AI tools:

-   **Backend**: [Laravel 13](https://laravel.com) (The PHP Framework for Web Artisans)
-   **Admin UI**: [Filament v5](https://filamentphp.com) (TALL Stack framework for beautiful dashboards)
-   **Reactivity**: [Livewire 4](https://livewire.laravel.com)
-   **AI Orchestration**: [Prefect](https://www.prefect.io) (Python-based data orchestration)
-   **Database**: [PostgreSQL](https://www.postgresql.org) with `pgvector` for high-performance vector search.
-   **Search Engine**: [Meilisearch](https://www.meilisearch.com) for lightning-fast typo-tolerant results.
-   **Containerization**: [Laravel Sail](https://laravel.com/docs/sail) + Docker Compose.

---

## 📚 Documentation

Detailed guides are available to help you understand and extend the platform:

-   📖 **[Architecture Blueprint](./docs/architecture.md)**: Deep dive into the system design and data flow.
-   🤖 **[AI Pipeline Details](./docs/pipeline.md)**: Details on the NLP models and stage-by-stage transformations.
-   📦 **[Data Sources](./docs/data-sources.md)**: List of supported resources and ingestion strategies.
-   🚀 **[Installation Guide](./docs/installation.md)**: Step-by-step setup for your local development environment.
-   📝 **[Development Roadmap](./TODO.md)**: Current task list and implementation status.

---

## 🏁 Getting Started

To get the project running locally, please follow the **[Installation & Setup Guide](./docs/installation.md)**.

---

*This project is dedicated to making Islamic knowledge more accessible through modern technology.*
