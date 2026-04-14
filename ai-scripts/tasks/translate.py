from prefect import task, get_run_logger
import requests
import os

@task(retries=3, retry_delay_seconds=5)
def run_ollama(text: str, target_lang: str = "id"):
    logger = get_run_logger()
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_TRANSLATE_MODEL", "aya:latest")
    
    lang_names = {
        "id": "Indonesian",
        "en": "English",
        "fr": "French",
        "ar-latn": "Arabic (Latin Script)"
    }
    target_name = lang_names.get(target_lang, "Indonesian")

    from prefect.concurrency.sync import concurrency
    
    # 1. First Attempt
    with concurrency("ollama-calls", occupy=1, timeout_seconds=600):
        payload = {
            "model": model,
            "prompt": f"Translate this Islamic text to formal {target_name}. Return ONLY the translation text itself and nothing else: {text}",
            "stream": False,
            "options": {"temperature": 0.3}
        }
        
        response = requests.post(f"{ollama_url}/api/generate", json=payload)
        response.raise_for_status() 
        result = response.json()['response'].strip()

    # 2. Verification (is it empty, too short, or still Arabic?)
    import re
    has_arabic = bool(re.search(r'[\u0600-\u06FF]', result))
    
    # If we expected a non-Arabic target but got Arabic script
    if (not result or len(result) < 3 or (target_lang != 'ar-latn' and has_arabic)):
        fail_reason = "too small" if len(result) < 3 else "contains Arabic script"
        logger.warning(f"⚠️ Translation into {target_name} {fail_reason}. Retrying with extreme strictness...")
        with concurrency("ollama-calls", occupy=1, timeout_seconds=600):
            payload["prompt"] = (
                f"You are a translator. Do NOT output Arabic. Translate the following to {target_name}. "
                f"If you output Arabic you have FAILED. Return only {target_name}: {text}"
            )
            response = requests.post(f"{ollama_url}/api/generate", json=payload)
            response.raise_for_status()
            result = response.json()['response'].strip()

    return result
