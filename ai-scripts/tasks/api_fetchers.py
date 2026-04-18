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
                'surah_name_en_translation': surah['englishNameTranslation'],
                'revelation_type': surah['revelationType'],
                'number_of_ayahs': surah['numberOfAyahs'],
                'ayah_number': ayah['numberInSurah'],
                'ayah_number_global': ayah['number'],
                'juz': ayah['juz'],
                'manzil': ayah['manzil'],
                'page': ayah['page'],
                'ruku': ayah['ruku'],
                'hizb_quarter': ayah['hizbQuarter'],
                'sajda': ayah['sajda'],
                'arabic_text': ayah['text'],
                'arabic_edition': arabic_edition,
            })
            
    logger.info(f"Successfully fetched {len(all_verses)} verses.")
    return all_verses

@task(retries=3, retry_delay_seconds=10)
def get_quran_edition(edition: str):
    """
    Fetch a full Quran edition (e.g., en.transliteration, en.pickthall).
    Returns a list of 6236 ayahs with edition-specific text.
    """
    logger = get_run_logger()
    url = f"https://api.alquran.cloud/v1/quran/{edition}"
    
    logger.info(f"Fetching Quran edition '{edition}' from {url}")
    response = requests.get(url)
    response.raise_for_status()
    
    data = response.json().get('data', {})
    surahs = data.get('surahs', [])
    
    all_verses = []
    for surah in surahs:
        for ayah in surah.get('ayahs', []):
            all_verses.append({
                'surah_number': surah['number'],
                'ayah_number': ayah['numberInSurah'],
                'text': ayah['text'],
                'edition': edition
            })
            
    logger.info(f"Successfully fetched {len(all_verses)} verses for edition: {edition}")
    return all_verses

@task(retries=3, retry_delay_seconds=5)
def get_ayah_transliteration(surah: int, ayah: int, edition: str = "en.transliteration"):
    """
    Fetch transliteration for a specific ayah.
    """
    logger = get_run_logger()
    url = f"https://api.alquran.cloud/v1/ayah/{surah}:{ayah}/{edition}"
    
    logger.info(f"Fetching ayah transliteration from {url}")
    response = requests.get(url)
    response.raise_for_status()
    
    data = response.json().get('data', {})
    return data.get('text', '')
