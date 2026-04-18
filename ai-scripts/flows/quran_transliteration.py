from prefect import flow, get_run_logger
from tasks import api_fetchers
from database import get_book_metadata, find_ayah_id_by_position, save_transliteration
import os

@flow(name="quran_transliteration_flow")
def process_quran_transliteration(book_id: str):
    logger = get_run_logger()
    book = get_book_metadata(book_id)
    
    if not book:
        logger.error(f"Book {book_id} not found.")
        return

    # Edition typically stored in metadata, e.g., 'en.transliteration'
    edition = book.get('metadata', {}).get('edition', 'en.transliteration')
    scheme = book.get('metadata', {}).get('scheme', 'ala_lc')
    
    # 1. Fetch perfectly-structured transliteration data from API
    verses = api_fetchers.get_quran_edition(edition)
    logger.info(f"Fetched {len(verses)} transliterations from API for edition: {edition}")

    processed_count = 0
    for verse in verses:
        # 2. Find the existing Arabic ayah record by position
        sentence_id = find_ayah_id_by_position(verse['surah_number'], verse['ayah_number'])
        
        if not sentence_id:
            logger.warning(f"Could not find Arabic ayah for {verse['surah_number']}:{verse['ayah_number']}. Skipping.")
            continue

        # 3. Save Transliteration
        save_transliteration(
            sentence_id=sentence_id,
            scheme=scheme,
            transliteration_text=verse['text']
        )
        processed_count += 1

    logger.info(f"✅ Quran transliteration sync complete. {processed_count} ayahs updated. Alhamdulillah.")
