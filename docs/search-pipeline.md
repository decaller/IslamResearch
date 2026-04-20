# Search Architecture: The Complete Execution Pipeline

This document outlines the exact chronological journey of a single search request. The system is designed to take a raw string, enrich it, query the database, apply post-retrieval refinements, and synthesize a final structured MCP JSON payload—all in **under 60 milliseconds**.

---

## 🏗️ The 4-Phase Pipeline

### Phase 1: Query Pre-Processing & Intent Analysis (0-15ms)
Before searching the database, the system must understand the researcher's intent.
1.  **Normalization & Cache Check:** Query is cleaned and checked against Redis. If a hit occurs, the cached vector is retrieved, skipping vectorization.
2.  **Linguistic Tokenization (Root Extraction):** Arabic text is stripped of Harakat to extract 3-letter roots (e.g., ز ك و).
3.  **Entity Tokenization (Knowledge Graph Match):** Words are rapidly checked against the `entities` table `canonical_name` and `aliases` JSONB array.
4.  **Vectorization:** If a cache miss occurs, the query is sent to the local `mxbai-embed-large` model to generate a 1,024-dimension semantic vector.

### Phase 2: Core Retrieval (15-30ms)
The system mathematical vector and recognized entities are used to query PostgreSQL (via pgvector) and Meilisearch.
1.  **Hybrid Query Formulation:** Combines Semantic Vector Search with Keyword/Tag Boosting.
2.  **Personalization Filters:** Applies global "negative query" filters and user-specific "hidden ID" filters to ensure result quality.
3.  **Execution:** Retrieves the top 100 raw `Sentence` rows.

### Phase 3: The Refinement Bridge (30-50ms)
Raw rows are processed into advanced JSON structures through specialized "engines."
1.  **Category Clustering:** Aggregates hits by `parent_category` and fetches pre-cached `related_queries`.
2.  **Semantic Sliding Window (Micro-Targeting):** Finds the exact 8-word fragment in a paragraph that best matches the query vector.
3.  **Knowledge Graph Hydration:** Pulls canonical names and first-degree recursive relationships for all entities mentioned in the results.
4.  **Verification Engine (Takhrij):** Verifies citations in "Scholarly Books" (Tafsir/Fiqh) to determine if quotes are **Verified (Green)** or **Unverified (Yellow)**.
5.  **I'rab Engine:** Attaches grammatical syntax analysis for any results where `resource_type == 'quran'`.

### Phase 4: JSON Payload Synthesis (50-60ms)
Data is synthesized into the strictly typed **Master MCP JSON Payload**.

---

## 💎 Master MCP Payload Architecture

The output is divided into four distinct semantic layers, allowing the frontend to render complex UI components without client-side logic.

```json
{
  "success": true,
  "data": {
    "structuredContent": {
      "query": "Umar Zakat",
      "processing_time_ms": 58,
      "category_clusters": { ... },
      "sentence_results": { ... },
      "entity_results": { ... },
      "root_word_results": { ... }
    }
  }
}
```

### 1. Specialized Highlighting (Resource-Specific)
-   **Quran:** Includes the `linguistics.irab` object for word-by-word syntax tooltips.
-   **Hadith:** Includes `hadith_anatomy` (Sanad vs Matn) to enable visual "Narration Trees."
-   **Scholarly Books:** Includes the `citations` array with `match_confidence` and `verification_status`.

### 2. Semantic Layering
-   **Category Clusters:** Aggregated counts and relevance scores for broad discovery.
-   **Entity Results:** Comprehensive "See Also" pills drawn from the Knowledge Graph.
-   **Root Word Results:** All morphological forms (e.g., زكاة، يزكي) found across the retrieved results.

---

## 📈 Search Latency Breakdown

| Phase | Goal | Actions |
|:---|:---|:---|
| **Phase 1** | < 15ms | Normalization, Tokenization, Vectorization |
| **Phase 2** | < 15ms | Hybrid Retrieval, Personalization |
| **Phase 3** | < 20ms | Clustering, Hydration, Verification, I'rab |
| **Phase 4** | < 10ms | JSON Synthesis & Formatting |
| **Total** | **< 60ms** | **Ready for Frontend Consumption** |

---

*Related Docs: [Knowledge Graph](./knowledge_graph.md), [Pipeline Architecture](./pipeline-architecture.md), [UX: Search Page](./ux/search-page.md).*
