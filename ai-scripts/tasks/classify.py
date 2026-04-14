from prefect import task, get_run_logger
import requests
import os

@task(retries=3, retry_delay_seconds=5)
def zero_shot(text: str) -> str:
    """
    Classify text using Ollama (Aya model) via a prompt-based zero-shot approach.
    This offloads classification to the GPU on the AI PC.
    """
    logger = get_run_logger()
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_TRANSLATE_MODEL", "aya:latest") # Use aya for its multilinguality

    prompt = (
        "Classify the following Islamic text into EXACTLY one of these categories: "
        "[Fiqh, Aqidah, Hadith, Tafsir, History, Ethics, Other]. "
        "Return ONLY the category name, nothing else.\n\n"
        f"Text: {text}"
    )

    payload = {
        "model": model,
        "prompt": prompt,
        "stream": False,
        "options": {"temperature": 0} # Strict output
    }

    from prefect.concurrency.sync import concurrency
    with concurrency("ollama-calls", occupy=1, timeout_seconds=600):
        response = requests.post(f"{ollama_url}/api/generate", json=payload)
        response.raise_for_status()
        category = response.json().get("response", "Other").strip().strip("[]'\"")
    
    logger.info(f"Classified sentence as: {category}")
    return category
