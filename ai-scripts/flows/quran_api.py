from prefect import flow, get_run_logger
from tasks import api_fetchers, text_prep, vectorize, transliterate
from database import save_quran_verse, get_book_metadata, save_transliteration
import os

@flow(name="quran_api_flow")
def process_quran(book_id: str):
    logger = get_run_logger()
    book = get_book_metadata(book_id)
    
    if not book:
        logger.error(f"Book {book_id} not found.")
        return

    # Default to Uthmani Mushaf
    arabic_edition = book.get('metadata', {}).get('arabic_edition', 'quran-uthmani')
    
    # 1. Fetch perfectly-structured data from API (No translation edition passed)
    verses = api_fetchers.get_quran_verses(
        arabic_edition=arabic_edition,
    )
    logger.info(f"Fetched {len(verses)} verses from API for Book ID: {book_id}")

    for verse in verses:
        arabic_text = verse['arabic_text']
        
        # 2. Morphological analysis (extract_roots accepts a list of strings)
        lexicon_data = text_prep.extract_roots([arabic_text])

        # 3. Fetch Transliteration from API (No embedding, per user request)
        transliteration = api_fetchers.get_ayah_transliteration(
            verse['surah_number'], 
            verse['ayah_number']
        )
        verse['transliteration'] = transliteration

        # 4. Vectorize Arabic (translation is skipped)
        vectors = vectorize.create_embeddings(arabic_text, "")

        # 5. Save Ayah to DB
        save_quran_verse(
            book_id=book_id,
            verse=verse,
            vectors=vectors,
            lexicon_data=lexicon_data,
        )

    logger.info("✅ Quran ingestion complete. Alhamdulillah.")
