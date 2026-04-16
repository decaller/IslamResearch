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

        # 3. Transliterate (User requested ONLY transliteration, no AI translation)
        # We use a scholarly scheme (ALA-LC by default)
        scheme = book.get('metadata', {}).get('transliteration_scheme', 'ala_lc')
        tl_result = transliterate.run_ollama(arabic_text, scheme=scheme)

        # 4. Vectorize Arabic (translation is skipped)
        vectors = vectorize.create_embeddings(arabic_text, "")

        # 5. Save Ayah to DB
        sentence_id = save_quran_verse(
            book_id=book_id,
            verse=verse,
            vectors=vectors,
            lexicon_data=lexicon_data,
        )
        
        # 6. Save Transliteration
        save_transliteration(
            sentence_id=sentence_id,
            scheme=tl_result['scheme'],
            transliteration_text=tl_result['transliteration_text']
        )

    logger.info("✅ Quran ingestion complete. Alhamdulillah.")
