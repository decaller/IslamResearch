# Implementation Details & Optimization

This document provides deep technical insights and implementation strategies for the more complex components of the IslamResearch Platform.

## SpaCy Optimization for Large Texts

Processing large historical Islamic texts (like multivolume Tafsirs or Hadith collections) can be memory-intensive. `spaCy` is optimized for performance but can consume significant RAM if not handled correctly.

### 1. The "Sentencizer" Hack (Zero-RAM Approach)
For initial segmentation (Stage 2), we only require **Sentence Boundary Detection (SBD)**. By using a rule-based Sentencizer instead of a full neural network model, we reduce memory usage to nearly zero.

```python
import spacy

# Load a blank model (super fast, no heavy neural networks)
nlp = spacy.blank("ar") # Use "ar" for Arabic, "en" for English

# Add the rule-based sentencizer to the pipeline
nlp.add_pipe("sentencizer")

# Process text
doc = nlp(raw_text)
sentences = [sent.text for sent in doc.sents]
```

### 2. Efficient Streaming with `nlp.pipe()`
When detailed AI analysis (Tagging, NER) is required in later stages, avoid passing giant strings. Use `nlp.pipe()` to stream data in batches and disable unnecessary pipeline components.

```python
# Process in batches of 50 to save RAM
for doc in nlp.pipe(paragraphs, batch_size=50, disable=["ner", "tagger", "lemmatizer"]):
    for sent in doc.sents:
        # Process individual sentences
```

### 3. Architecture Isolation (The Microservice Pattern)
Running Python ML libraries directly inside n8n containers can lead to instability. We isolate the processing into a separate microservice.

---

## Resource-Specific Processing (Stage 4 Deep Dive)

When the `SentenceJob` reaches the **SentenceProcessing** n8n workflow, a Switch Node routes the batch based on its `resource_type`.

### 1. Quran
-   **Stage 2 Override:** The primary split is strictly by **Ayah (Verse)** boundaries. However, since specific verses (e.g., *Ayah al-Dayn*, 2:282) can span an entire page, the microservice checks the word count. If an Ayah exceeds the optimal embedding limit (e.g., **> 40 words**), it triggers a secondary split using **Quranic pause marks (Waqf)** or SpaCy's sentencizer to create sub-Ayah chunks.
-   **Stage 4 Enrichment:**
    *   **Mapping:** Exact Surah_ID, Ayah_Number, Juzz, and **Ayah_Part_Index** (for chunks of long verses).
    *   **Translation Alignment:** Align translations strictly to the Arabic Ayah or the specific sub-Ayah chunk.
    *   **Categorization:** Apply ontological tags via the `Categorization` sub-flow.
    *   **Vectorization:** Dual-embedding (Arabic + Translation).

### 2. Hadits Book
-   **Stage 2 Override:** Standard SpaCy SBD + 5-sentence context.
-   **Stage 4 Enrichment:**
    *   **Information Extraction (LLM):** Separation of **Isnad** (Chain) and **Matn** (Text).
    *   **Metadata Mapping:** Kitab, Bab, Hadith_Number, and Authenticity Grace.
    *   **Categorization:** Categorize Matn under Fiqh or Akhlaq themes.

### 3. Tafsir Book
-   **Stage 2 Override:** Standard SpaCy SBD.
-   **Stage 4 Enrichment:**
    *   **Anchor Linking:** Identify the specific Surah and Ayah being explained.
    *   **Categorization:** Flag for *Asbab al-Nuzul*, Linguistic Analysis, or *Fiqh*.
    *   **Metadata:** Target_Surah_ID, Target_Ayah_Number.

### 4. Syarh Hadits Book
-   **Stage 2 Override:** Standard SpaCy SBD.
-   **Stage 4 Enrichment:**
    *   **Anchor Linking:** Link to primary Hadith number/chapter.
    *   **Categorization:** Flag for *Rijal* (Biography), *'Ilal* (Defects), or Legal derivation.
    *   **Metadata:** Target_Hadith_Collection, Target_Hadith_Number.

### 5. Language Tools Book (Dictionaries)
-   **Stage 2 Override:** Bypass standard SpaCy. Use **custom Regex or structured parser** to split by **Root Word / Lemma** entries.
-   **Stage 4 Enrichment:**
    *   **Root Extraction (LLM):** Extract the 3 or 4-letter Arabic root (**Jidhr**).
    *   **Metadata:** Root_Word, Derived_Forms, Definition_Type.
    *   **Vectorization:** Weighted towards the root word to enable cross-resource referencing.

### 6. Other Book (General Literature)
-   **Stage 2 Override:** Standard SpaCy SBD + 5-sentence context.
-   **Stage 4 Enrichment:**
    *   **Entity Extraction:** Extract People, Places, Battles, and Events.
    *   **Categorization:** Multi-label classification (e.g., #Trade, #Umayyad_Period).

---

## Unified Database Strategy (PostgreSQL JSONB)

The platform utilizes a unified items table to handle the diverse metadata requirements of different resource types.

| Field | Type | Description |
| :--- | :--- | :--- |
| **id** | UUID | Primary Key |
| **resource_type** | Enum | quran, hadith, tafsir, syarh, language, other |
| **sentence_text** | Text | The core sentence content |
| **context_text** | Text | The 5 sentences immediately before and after |
| **metadata** | **JSONB** | **Dynamic LLM extraction data (Isnad, Root, etc.)** |
| **embedding** | Vector | Indexed with StreamingDiskANN (pgvectorscale) |

This JSONB approach allows for strict typing in code while maintaining flexibility in the database storage.

---

## Search Technology

The platform utilizes **pgvectorscale** to enable **StreamingDiskANN** indexes. This allows us to perform high-accuracy vector searches across millions of sentences while keeping the index on disk rather than entirely in RAM, significantly reducing infrastructure costs while maintaining millisecond latency.
