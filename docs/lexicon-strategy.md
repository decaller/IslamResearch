# Linguistic Lexicon & Vector Strategy

To achieve ultimate precision in cross-lingual and morphological search, the architecture employs a **Global Lexicon** database model. Instead of only vectorizing full sentences, the system breaks down texts into their foundational linguistic components (**Roots** and **Words**) and vectorizes them individually.

---

## 1. The Strategy Overview

For every sentence processed by the AI Factory (Prefect), the text is passed through **CAMeL Tools** (for Arabic) or **SpaCy** (for Latin/Indonesian).

The system extracts:
- **The Root (Jidhr / Kata Dasar):** The base morphological root (e.g., ع ل م or *tulis*).
- **The Surface Word:** The actual word as it appears in the text.
    - **For Arabic:** It saves two versions: **Diacritized** (with Harakat, عِلْمٌ) and **Undiacritized** (without Harakat, علم).

Both the Roots and the Surface Words are vectorized (embedded) and saved in their own dedicated tables.

---

## 2. Database Schema (PostgreSQL)

To prevent database bloat, we use a normalized dictionary approach. We do not save a new row every time a common word appears. We save it once in the words table and use a pivot table to link it to the sentences.

### A. Table: `lexicon_roots`
Stores the foundational base of words. Vectorizing this allows for deep semantic mapping (e.g., linking the Indonesian vector for "Pendidikan" to the Arabic root vector for ع ل م).

| Field | Type | Description |
| :--- | :--- | :--- |
| **id** | UUID | Primary Key |
| **language** | String | 'ar' or 'id' |
| **root_value** | String | e.g., 'ع ل م', 'tulis' |
| **embedding** | Vector | pgvector |

**Index:** Unique constraint on `(language, root_value)`.

### B. Table: `lexicon_words`
Stores the actual variations found in the texts, linked to their parent root.

| Field | Type | Description |
| :--- | :--- | :--- |
| **id** | UUID | Primary Key |
| **root_id** | UUID | Foreign Key -> `lexicon_roots.id` |
| **language** | String | 'ar' or 'id' |
| **word_raw** | String | Exact word from text (with Harakat for Arabic, e.g., 'يَعْلَمُونَ') |
| **word_clean** | String | Stripped version (no Harakat for Arabic, e.g., 'يعلمون') |
| **embedding_raw** | Vector | pgvector for exact matches |
| **embedding_clean** | Vector | pgvector for generalized meaning search |

**Index:** Unique constraint on `(language, word_raw, word_clean)`.

### C. Pivot Table: `sentence_word`
Connects the `sentences` table to the `lexicon_words` table for deep highlighting matrices.

| Field | Type | Description |
| :--- | :--- | :--- |
| **sentence_id** | UUID | Foreign Key -> `sentences.id` |
| **word_id** | UUID | Foreign Key -> `lexicon_words.id` |
| **source_type** | Enum | Identifies if the word originated from the raw text or the translation JSON |
| **positions** | Int[] | Array of coordinate indexes (e.g., `[3, 14]`) for instant UI matching |

---

## 3. Implementation: CAMeL Tools integration

In the Prefect pipeline (`tasks/text_prep.py`), the integration with CAMeL tools populates these tables in one pass:

```python
from camel_tools.morphology.analyzer import Analyzer
from camel_tools.utils.dediac import dediac_ar

# Load CAMeL morphological analyzer
analyzer = Analyzer()

def process_arabic_sentence(sentence_text):
    words = sentence_text.split()
    lexicon_data = []
    
    for word in words:
        # 1. Analyze the word
        analyses = analyzer.analyze(word)
        
        if analyses:
            best_analysis = analyses[0] # Take the most likely morphological analysis
            
            # 2. Extract Data
            word_raw = word                      # e.g., يُؤْمِنُونَ
            word_clean = dediac_ar(word)         # e.g., يؤمنون
            root = best_analysis['root']         # e.g., أ م ن
            
            lexicon_data.append({
                "root": root,
                "word_raw": word_raw,
                "word_clean": word_clean
            })
            
    return lexicon_data
```

---

## 4. Why Vectorize Both Tables? (The Search Advantage)

By storing vector embeddings for both `lexicon_roots` and `lexicon_words`, the backend unlocks **Semantic Lexicon Search**.

### Scenario 1: The "Messy" Search
**User types:** `يؤمنون` (No harakat).
**Result:** System instantly matches `word_clean` in the `lexicon_words` table, pulls the `word_id`, and fetches all sentences containing that exact word configuration, highlighting the harakat version `يُؤْمِنُونَ` on the frontend.

### Scenario 2: The "Root Explorer" (Advanced Scholar Mode)
**User searches for the root:** `ع ق د` (Contracts/Knots).
**Result:** Because roots are vectorized, the AI can show a "Semantic Cloud" of related roots (e.g., `ر ب ط` - to bind). The system queries `lexicon_words` where `root_id = [id of ع ق د]`. It shows all variations found in the database (`عَقْدٌ`, `يَعْقِدُ`, `مُعَاقَدَة`) and allows filtering across thousands of books based on the exact grammatical form.

### Scenario 3: Cross-Lingual Concept Bridging
If the AI translation model struggles with a highly technical Fiqh term, the system can rely on the vector of the `root_value` to bridge the gap between Arabic and Indonesian, ensuring meaning is preserved even if the translation is slightly imperfect.

---

## 5. Administrative Management (Filament)

To manage this complex linguistic data, the Laravel backend provides two key Filament resources:

1.  **LexiconRootResource:**
    *   **Purpose:** Search and audit base linguistic roots.
    *   **Visualization:** Lists all `lexicon_words` associated with a root.
    *   **Scholarly Action:** Allows experts to manually refine root-to-root semantic links if the AI's vector proximity is insufficient.

2.  **LexiconWordResource:**
    *   **Purpose:** Individual word analysis.
    *   **Key Data:** Shows both `word_raw` (Harakat) and `word_clean` (Search-friendly) versions.
    *   **Traceability:** Includes a relation manager to see every `Sentence` where this specific word was used, providing immediate context for linguistic research.
