from prefect import task, get_run_logger
import requests
import os

@task(retries=3, retry_delay_seconds=10)
def get_quran_verses(arabic_edition: str = "quran-uthmani", translation_edition: str = None):
    """
    Fetch Quran data from alquran.cloud API.
    If translation_edition is None, it returns ONLY Arabic text.
    """
    logger = get_run_logger()
    
    # Base URL for the complete Quran with specific edition
    url = f"https://api.alquran.cloud/v1/quran/{arabic_edition}"
    
    logger.info(f"Fetching Quran Arabic text from {url}")
    response = requests.get(url)
    response.raise_for_status()
    
    arabic_data = response.json().get('data', {})
    surahs = arabic_data.get('surahs', [])
    
    # Structural list to hold all 6236 verses
    all_verses = []
    
    for surah in surahs:
        for ayah in surah.get('ayahs', []):
            all_verses.append({
                'surah_number': surah['number'],
                'surah_name_ar': surah['name'],
                'surah_name_en': surah['englishName'],
                'ayah_number': ayah['numberInSurah'],
                'ayah_number_global': ayah['number'],
                'juz': ayah['juz'],
                'page': ayah['page'],
                'arabic_text': ayah['text'],
                'arabic_edition': arabic_edition,
            })
            
    logger.info(f"Successfully fetched {len(all_verses)} verses.")
    return all_verses
