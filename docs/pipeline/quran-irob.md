# Pipeline: `quran_irab_flow` — Quranic I'rab (Grammar)

**Flow file:** `ai-scripts/flows/quran_irob.py`  
**Trigger level:** Sentence-level  
**Pipeline ID:** `quran_irab_flow`

## 📖 Overview
I'rab (إعراب) books provide deep morphological and syntactic analysis of the Quran. These are classical scholarly works uploaded as `.txt` files. This pipeline uses **SpaCy** for segmentation and **Ollama** for translation, while maintaining a strict biological link to the Quranic ayahs they explain.

```mermaid
graph TD
    A[Start Job] --> B[Fetch Sentence Context]
    B --> C[Regex-based Ayah Link Detection]
    C --> D[Ollama Translation (Nahwu Context)]
    C --> E[Entity Resolution (Scholars & Terms)]
    C --> F[Knowledge Graph Relation Extraction]
    G[Save to Sentences Table & Link Ayah]
    D --> G
    E --> G
    F --> G
```

---

## 🏗️ Technical Workflow

### 1. Ayah Reference Detection
The flow uses regex to detect Surah and Ayah identifiers (e.g., `سورة البقرة: آية ٢`) in the surrounding context. These are mapped to `surah_id` and `ayah_number` in the metadata to enable cross-referencing with the central Quran text.

### 2. Entity Resolution & Knowledge Graph
- **Linguistic Entities**: Resolves grammatical terms (e.g., *Marthu'*, *Majrur*, *Mudhaf*) into canonical concepts in the Master Entities table.
- **Scholar Linking**: Identifies mentions of grammarians (e.g., *Sibawayh*, *Al-Khalil*) and links them to the Knowledge Graph.
- **Relation Extraction**: Extracts triplets like `[Sentence ID] ➔ (Explains) ➔ [Ayah ID]`.

### 3. Translation with Grammatical Context
The LLM is prompted with "Nahwu context", ensuring that technical grammatical terms are translated in a way that respects their meaning in Arabic syntax rather than just literal dictionary meanings.

---

## ⚙️ Configuration
| Key | Logic |
| :--- | :--- |
| **Needs Translation** | ✅ Always Yes (Local ID translation) |
| **Categorization** | Constrained to `Nahwu` & `Quranic Sciences` |
| **Evidence** | Automatic linking to `sentence_entity` pivot |

---

## ✨ Advantages
- **Linguistic Precision**: Terms are canonicalized, enabling search across different books even if they use slightly different terminology.
- **Cross-Source Search**: Users can search for a Quran ayah and instantly see its grammatical breakdown in the sidebar.
- **Scholar Tracking**: Visually map which grammarians hold specific views on certain verses.
