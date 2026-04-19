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

## 4. Auto-Knowledge Building (The Autonomous Graph)
Auto-Knowledge Building occurs when the system discovers a new concept and automatically branches out to learn its surrounding context.
- **Trigger**: The LLM extracts a new entity (e.g., `[Treaty of Hudaybiyyah]`).
- **Recursive Queuing**: The pipeline autonomously queries Wikipedia for that entity's categories and adds them as new entities in a recursive loop.

## 5. Cyclic Dependencies & Guardrails
In a Knowledge Graph, cycles are beneficial for navigability but dangerous for the backend. We implement three strict guardrails:

### Guardrail 1: Visited Hash Set (`last_enriched_at`)
Before querying Wikipedia or an LLM for enrichment, the system checks the `last_enriched_at` timestamp. 
- **Rule**: If enriched within the last 30 days, skip the API call.

### Guardrail 2: Maximum Depth Limit (Max Hops = 2)
The autonomous crawler is limited to a depth of 2 hops from the original source sentence. This prevents infinite internet crawls while still mapping immediate context.

### Guardrail 4: PostgreSQL Cycle Detection
Recursive graph queries (WITH RECURSIVE) use the `CYCLE` clause to prevent infinite loops at the database level.
```sql
WITH RECURSIVE graph_search AS (...) 
CYCLE id SET is_cycle USING path 
SELECT * FROM graph_search WHERE NOT is_cycle;
```

## 6. Administrative Tools (Filament)
- **Entity Merge Tool**: Admins can select duplicate entities (e.g., `[Salah]` and `[Salat]`) and merge them. The system re-maps all relationships and sentence links to the master node and updates the aliases.
- **Ambiguity Queue**: Low-confidence (e.g., <80%) AI matches are sent here for manual review.

## 7. UI/UX: Smart Search & Tooltips
- **Disambiguation Dropdown**: Searching for "Umar" triggers an autocomplete asking "Did you mean Umar bin Khattab?".
- **Inline Tooltips**: In reading mode, entities are underlined. Hovering reveals a mini-card with the Wikipedia summary and a link to "Search all texts mentioning this person".
- **Graph View**: (Upcoming) Interactive visualization of entity networks using `vis-network`.

