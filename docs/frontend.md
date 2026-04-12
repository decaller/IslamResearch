# Frontend Architecture: The "Scholar UI"

To expose the massive power of the AI pipeline without overwhelming the user, the UI employs a **"Progressive Disclosure"** design. It remains clean and simple for general use while allowing advanced scholars to toggle granular search methods and morphological tools. 

> [!NOTE]
> For a detailed walkthrough of the visual layout and interaction design, see [Search Page UX](./ux/search-page.md) and [Page Types & Explorability](./ux/page-types.md).

---

## 1. UI Layout Overview

### A. The "Omni-Bar" (Top Navigation)
The central entry point for all queries.
- **Search Input:** A large text box that intelligently handles Arabic (diacritized or clean), Indonesian, and English.
- **Mode Selector (Dropdown/Toggle):** Attached to the search bar, letting the user explicitly choose how the engine should interpret their text:
    - **AI Semantic (Default):** "Find meaning" using vector search.
    - **Exact Word:** "Find this exact surface word" (ignoring Harakat if requested).
    - **Root Word Explorer:** "Find texts derived from this Arabic root" (Jidhr).
    - **Tag/Category Match:** "Find specific metadata" (Fiqh, History, etc.).

### B. Faceted Sidebar (Filters)
Driven instantly by the Meilisearch Faceting engine.
- **Sources (Books):** Multi-select checkboxes for specific titles (e.g., *Sahih Bukhari*, *Tafsir Ibn Kathir*).
- **Resource Type:** Filter by Quran, Hadith, Exegesis, Linguistics, etc.
- **Category Tree:** An accordion menu hierarchy (e.g., `Fiqh` -> `Muamalah` -> `Zakat`).
- **AI Tags:** A dynamic list of the most frequent tags found in the current results (e.g., `#Mekkah`, `#Rukun_Islam`).

### C. The Results Pane (Center)
- **Hits:** Displays the Arabic text and the Indonesian translation side-by-side in a responsive card layout.
- **Highlights:** Meilisearch natively highlights matched words. In **Root Mode**, all words sharing the searched root are highlighted.
- **Context Toggle:** A "Show Context" button that expands the view to display the 5 sentences before and after the result to preserve scholarly context.

---

## 2. Integrated Search Modes

The frontend changes the parameters of the Meilisearch API call based on the user's selected mode.

### Mode 1: AI Semantic (Meaning-Based)
- **User Goal:** Search for a concept when the exact wording is unknown.
- **Example:** *"Hukum bagi orang kaya yang tidak bayar zakat"*
- **Backend Action:** Laravel generates a vector using the **mxbai-embed-large-v1** model and performs a Hybrid/Vector search in Meilisearch.

### Mode 2: Exact Word (Surface Word)
- **User Goal:** Find every occurrence of a specific word (e.g., `يَعْلَمُونَ`).
- **Backend Action:** Laravel strips Harakat (creating `يعلمون`) and restricts the search to the `text_arabic_clean` or `words_clean` attributes in Meilisearch, bypassing the vector engine.

### Mode 3: Root Word Explorer (The Scholar Feature)
- **User Goal:** Study every occurrence of a linguistic root (e.g., `ع ق د`).
- **Frontend UI Transformation:** The results pane displays a **"Lexicon Cloud"** showing all surface words generated from that root found in the database.
- **Backend Action:** Laravel queries Meilisearch using a facet filter: `filter: ["roots = 'ع ق د'"]`. This retrieves every sentence containing a word derived from that root, regardless of the grammatical form.

### Mode 4: Category / Tag Force
- **User Goal:** Browse results by metadata rather than text.
- **Backend Action:** Executes a filtered Meilisearch query: `filter: ["tags_id = 'Perang_Badar'"]`.

---

## 3. Handling Source Filtering

When an Admin processes a book via Prefect, the metadata is synced to Meilisearch in a flattened structure.

### Meilisearch Document Structure:
```json
{
  "id": "uuid-123",
  "text": "...",
  "resource_type": "hadith",
  "source_book": "Sahih Bukhari",
  "hierarchy_root": "Fiqh",
  "roots": ["ع ل م"]
}
```

### Laravel Scout Implementation:
The frontend state updates an array of filters which is appended to the Scout query in the controller:

```php
$results = Sentence::search($userQuery, function ($meilisearch, $query, $options) use ($request) {
    $filters = [];
    
    if ($request->has('sources')) {
        $filters[] = "source_book IN [" . implode(',', $request->sources) . "]";
    }
    
    if ($request->has('resource_type')) {
        $filters[] = "resource_type = " . $request->resource_type;
    }

    if (!empty($filters)) {
        $options['filter'] = implode(' AND ', $filters);
    }

    return $meilisearch->search($query, $options);
})->get();
```
