# Knowledge Graph & Entity Resolution

This document explains the architecture for solving name collisions and building a semantic Knowledge Graph within the IslamResearch platform.

## 1. Entity Disambiguation (The "Umar" Problem)

In classical Islamic texts, names are often abbreviated or shared (e.g., "Umar" could be the 2nd Caliph or Umar bin Abdul Aziz). Our system uses **Entity Resolution** to map ambiguous surface names to unique database IDs.

### The Pipeline Flow
1. **NER (Named Entity Recognition)**: A fast CPU model (or spaCy) identifies potential entities in raw text.
2. **Context Extraction**: The system pulls the 5 preceding and 5 succeeding sentences surrounding the entity.
3. **LLM Disambiguation**: Ollama (model: `aya`) processes the context and surface name to return a canonical identity.
4. **Wikipedia Enrichment**: If the entity is new, the system pings the Wikipedia API to fetch a bio, canonical name, and semantic categories.

## 2. Unifying Tags into Entities

We have retired "flat tags" in favor of typed **Entities**. Every tag is now a node in the graph with one of the following types:
- `person`: Historical figures, narrators, authors.
- `location`: Cities, regions, battlegrounds.
- `event`: Battles, treaties, historical milestones.
- `concept`: Theological or Fiqh concepts (e.g., Sabr, Zakat).
- `book`: The source texts themselves.

## 3. Relationship Extraction (S-P-O Triplets)

The Knowledge Graph connects entities via semantic relationships. 
- **Format**: `Subject` ➔ `Predicate` ➔ `Object`
- **Example**: `[Umar]` ➔ `(Migrated To)` ➔ `[Madinah]`
- **Evidence**: Every relationship is linked back to an `evidence_sentence_id` to ensure accountability.

## 4. Admin Guardrails (Filament)

To handle AI hallucinations, we use:
- **Ambiguity Queue**: Low-confidence (e.g., <80%) AI matches are sent here for manual review.
- **Entity Manager**: Admins can merge entities, update aliases, and manage Wikipedia links.

## 5. UI/UX: Smart Search & Tooltips

- **Disambiguation Dropdown**: Searching for "Umar" triggers an autocomplete asking "Did you mean Umar bin Khattab?".
- **Inline Tooltips**: In reading mode, entities are underlined. Hovering reveals a mini-card with the Wikipedia summary and a link to "Search all texts mentioning this person".
- **Graph View**: (Upcoming) Interactive visualization of entity networks using `vis-network`.
