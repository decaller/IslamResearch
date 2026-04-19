from prefect import task, get_run_logger
import requests
import os
from typing import List, Dict, Any

BASE_URL = "https://api.quran.com/api/v4"

@task(retries=3, retry_delay_seconds=10)
def fetch_foundation_chapters(language: str = "en") -> List[Dict[Any, Any]]:
    """
    Fetch all chapters (surahs) from Quran Foundation API.
    """
    logger = get_run_logger()
    url = f"{BASE_URL}/chapters?language={language}"
    
    logger.info(f"Fetching all chapters from {url}")
    response = requests.get(url)
    response.raise_for_status()
    
    chapters = response.json().get('chapters', [])
    logger.info(f"Successfully fetched {len(chapters)} chapters.")
    return chapters

@task(retries=3, retry_delay_seconds=10)
def fetch_foundation_verses_by_chapter(
    chapter_id: int, 
    words: bool = True, 
    translations: str = None,
    tafsirs: str = None,
    language: str = "en"
) -> List[Dict[Any, Any]]:
    """
    Fetch verses for a specific chapter with optional words, translations, and tafsirs.
    """
    logger = get_run_logger()
    
    # We use verses/by_chapter/{chapter_id}
    url = f"{BASE_URL}/verses/by_chapter/{chapter_id}"
    
    params = {
        "language": language,
        "words": "true" if words else "false",
        "fields": "text_uthmani",
        "word_fields": "text_uthmani,text_indopak,v1_page,v2_page,code_v1,code_v2,qpc_uthmani_hafs",
    }
    
    if translations:
        params["translations"] = translations
    
    if tafsirs:
        params["tafsirs"] = tafsirs

    logger.info(f"Fetching verses for chapter {chapter_id} from {url}")
    
    all_verses = []
    page = 1
    
    while True:
        params["page"] = page
        response = requests.get(url, params=params)
        response.raise_for_status()
        
        data = response.json()
        verses = data.get('verses', [])
        all_verses.extend(verses)
        
        pagination = data.get('pagination', {})
        if page >= pagination.get('total_pages', 1):
            break
        page += 1
        
    logger.info(f"Successfully fetched {len(all_verses)} verses for chapter {chapter_id}.")
    return all_verses

@task(retries=3, retry_delay_seconds=10)
def fetch_foundation_recitations() -> List[Dict[Any, Any]]:
    """
    Fetch list of available recitations.
    """
    logger = get_run_logger()
    url = f"{BASE_URL}/resources/recitations"
    
    logger.info(f"Fetching recitations from {url}")
    response = requests.get(url)
    response.raise_for_status()
    
    recitations = response.json().get('recitations', [])
    return recitations

@task(retries=3, retry_delay_seconds=10)
def fetch_foundation_chapter_audio(reciter_id: int, chapter_id: int) -> Dict[Any, Any]:
    """
    Fetch audio information for a specific reciter and chapter.
    """
    logger = get_run_logger()
    url = f"{BASE_URL}/chapter_recitations/{reciter_id}/{chapter_id}"
    
    logger.info(f"Fetching audio for reciter {reciter_id}, chapter {chapter_id} from {url}")
    response = requests.get(url)
    response.raise_for_status()
    
    return response.json().get('audio_file', {})
