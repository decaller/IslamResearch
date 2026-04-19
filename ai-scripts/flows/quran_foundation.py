from prefect import flow, get_run_logger
from tasks import quran_foundation, vectorize, text_prep
from database import save_quran_verse, get_book_metadata, save_quran_word, find_ayah_id_by_position
import os

@flow(name="quran_foundation_flow")
def process_foundation_quran(book_id: str, chapter_id: int):
    """
    Ingest a chapter from Quran Foundation API.
    """
    logger = get_run_logger()
    book = get_book_metadata(book_id)
    
    if not book:
        logger.error(f"Book {book_id} not found.")
        return

    # 1. Fetch verses with words
    verses = quran_foundation.fetch_foundation_verses_by_chapter(
        chapter_id=chapter_id,
        words=True
    )
    logger.info(f"Fetched {len(verses)} verses from Quran Foundation for Chapter: {chapter_id}")

    # Fetch chapter info for metadata
    chapters = quran_foundation.fetch_foundation_chapters()
    chapter_info = next((c for c in chapters if c['id'] == chapter_id), {})

    for verse in verses:
        # Prepare verse dictionary for save_quran_verse
        v_dict = {
            'surah_number': chapter_id,
            'surah_name_ar': chapter_info.get('name_arabic'),
            'surah_name_en': chapter_info.get('name_complex'),
            'surah_name_en_translation': chapter_info.get('translated_name', {}).get('name'),
            'revelation_type': chapter_info.get('revelation_place'),
            'number_of_ayahs': chapter_info.get('verses_count'),
            'ayah_number': verse['verse_number'],
            'ayah_number_global': verse['id'], # In V4, verse id is global number
            'juz': verse['juz_number'],
            'manzil': verse['manzil_number'],
            'page': verse['page_number'],
            'ruku': verse['ruku_number'],
            'hizb_quarter': verse['hizb_number'],
            'sajda': verse.get('sajdah_number') is not None,
            'arabic_text': verse['text_uthmani'],
            'arabic_edition': 'quran-foundation-v4'
        }

        # 2. Vectorize Arabic
        vectors = vectorize.create_embeddings(v_dict['arabic_text'], "")

        # 3. Save Ayah to DB
        sentence_id = save_quran_verse(
            book_id=book_id,
            verse=v_dict,
            vectors=vectors,
            lexicon_data=[] # We handle words separately
        )

        # 4. Save Words
        if 'words' in verse:
            for word in verse['words']:
                if word['char_type_name'] == 'word':
                    # Extract root for the specific word using internal helper if needed
                    # or let save_quran_word handle it via get_or_create_root
                    save_quran_word(sentence_id, word)

    logger.info(f"✅ Quran Foundation ingestion for Chapter {chapter_id} complete. Alhamdulillah.")

if __name__ == "__main__":
    # Example: Process Al-Fatiha
    # In a real scenario, book_id should be a valid UUID from your source_books table
    process_foundation_quran("foundation-quran-v4", 1)
