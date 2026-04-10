# Data Sources

The IslamResearch Platform aggregates and categorizes data from multiple authoritative Islamic sources to facilitate deep research and semantic analysis.

## Source Categories

The platform categorizes data into the following main branches:

1.  **Quran**
    - The core text of the Holy Quran.
    - Includes multiple translations and recitation data.
    - Semantic indexing for thematic search.

2.  **Hadits Book**
    - Primary collections of Hadith (e.g., Sahih Bukhari, Sahih Muslim, Sunan Abi Dawud).
    - Focus on authentication (isnad) and primary text (matn).

3.  **Tafsir Book**
    - Exegesis and scholarly interpretations of the Quran.
    - Includes classical and modern Tafasir (e.g., Tafsir Ibn Kathir, Al-Jalalayn).

4.  **Syarh Hadits Book**
    - Detailed commentaries on Hadith collections.
    - Explanation of legal rulings, linguistic nuances, and historical context (e.g., Fath al-Bari).

5.  **Language Tools Book**
    - Linguistic resources including Arabic dictionaries (Lisan al-Arab).
    - Books on Nahw (Syntax), Sarf (Morphology), and Balagha (Eloquence).

6.  **Other Book**
    - General Islamic literature, faith (aqidah), jurisprudence (Fiqh), history (Sirah), and ethics (Akhlaq).

## Ingestion Workflows

We use the **Prefect-orchestrated AI Pipeline** to automate the synchronization of data from:

- External APIs (e.g., Quran.com API, Hadith Cloud).
- Structured datasets (JSON/CSV/SQL).
- OCR-processed texts and digital library exports.

## Adding a New Source

To add a new data source:

1.  **Define Source:** Assign the source to one of the six categories above.
2.  **Pipeline Setup:** Create or adapt a Prefect task to fetch and normalize the text.
3.  **Vectorization:** Trigger the embedding generation to enable semantic search across the collection.
