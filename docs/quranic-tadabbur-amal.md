# Integrating "Al-Quran Tadabbur wa 'Amal" & The Scholar UI

This feature bridges the gap between classical exegesis (Tafsir) and modern, actionable self-improvement. By ingesting the book *"Al-Quran Tadabbur wa 'Amal"*, the platform creates an unparalleled interactive Quranic experience using an advanced **"Dual-Panel"** UI layout.

---

## 1. Pipeline Processing (Backend)

We introduce a new `resource_type` in the database: `quranic_action`.

### 🧹 Stage 2: Custom Python Parsing
Unlike standard prose, this book is structured by **Mushaf Pages (1-604)**. The Python worker uses Regex to segment the text into functional blocks:
- **[Page Number]**
- **[Waqafat التدبر]** (Contemplations)
- **[Al-Amal العمل]** (Actions)
- **[Al-Tawjihat التوجيهات]** (Directives)

### 🏷️ Stage 4: Ollama LLM Enrichment
Since the book explicitly references specific Ayahs, we use Ollama for **Anchor Linking** and **Translation**.
- **Prompt:** "Read this 'Amal (Action) point. Translate it into formal, actionable Indonesian. Then, identify the exact Surah and Ayah number it is referencing."
- **Vectorization:** We generate multilingual embeddings for the Indonesian versions of the Action and Contemplation text.

### JSONB Schema (`items` table)
```json
{
  "id": "uuid-9999",
  "resource_type": "quranic_action",
  "content": {
    "arabic_raw": "طبق سنة من سنن النبي ﷺ في هذا اليوم",
    "indonesian": "Terapkan satu sunnah Nabi ﷺ pada hari ini."
  },
  "metadata": {
    "target_surah": 2,
    "target_ayah": 15,
    "type": "action", 
    "mushaf_page": 4
  }
}
```

---

## 2. Frontend Architecture: The "Dual-Panel" Layout

The UI utilizes a **Split-Pane Design** optimized for deep study.

### The Right Pane (The Anchor)
Displays the static, visual Mushaf page or standard verse-by-verse view. Includes navigation to jump to specific Surahs, Juz, or Pages.

### The Left Pane (The Intelligence)
A dynamic, tabbed interface that updates instantly based on the Ayahs currently visible on the Right Pane. It features a **5-Tab System**:

1.  **الوقفات التدبرية (Contemplations):** Queries `resource_type: quranic_action` where `metadata.type = 'contemplation'`.
2.  **توجيه (Directives & Guidance):** Queries `metadata.type = 'directive'`. Displays broad moral lessons.
3.  **الأعمال (Actions - The Habit Tracker):** Queries `metadata.type = 'action'`. 
    - **Interactive UI:** Rendered as clickable checkboxes. Checking an action (e.g., "Give charity based on Ayah 261") saves to a `user_habits` table for spiritual progress tracking.
4.  **معاني الكلمات (Word Meanings / Lexicon):** Hits the `lexicon_words` and `lexicon_roots` tables. Clicking a word opens a popup with its 3-letter root and a button to search the entire library for that root.
5.  **التفاسير (Tafsirs / Exegesis):** Queries `resource_type: tafsir`. Includes an accordion to switch between scholars (e.g., As-Sa'di vs. Ibn Kathir) seamlessly.

---

## 3. Interactive UI Components

### Feature A: Global Audio Syncing
A pinned global audio player (e.g., Reciter Wadih Al-Yamani) uses timestamp metadata to highlight the current Ayah in the Right Pane and auto-scroll the Left Pane to the relevant Action or Contemplation.

### Feature B: Semantic Action Linking (AI Magic)
Because Action items are vectorized, we can run a similarity search against the entire database.
- **Implementation:** Beneath an Action card, a "Related Hadith" accordion pulls entries from *Sahih Bukhari* that share a semantic vector match with that specific action.

### Feature C: Card-Level Social Sharing
Every snippet (Tafsir, Action, or Contemplation) is a distinct row with a UUID, allowing users to share direct links to specific scholarly insights or actionable tasks.

---

## 4. Scholar UI Updates (Expanded Search)

- **New Resource Toggles:** Add `Tadabbur` and `Amal` to the global sidebar filters.
- **"Find me an Action" Search:** Users can query the Omni-Bar for practical advice (e.g., *"How to control anger?"*).
- **Backend logic:** Meilisearch searches the vectors, specifically filtering for `type: action`, returning practical steps rather than just dry legal definitions.
