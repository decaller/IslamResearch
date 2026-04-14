"""
Stage 4.5 — Transliteration (`tasks/transliterate.py`)

Generates romanised transliteration for each Arabic sentence using the
Ollama LLM configured via the OLLAMA_URL / OLLAMA_TRANSLITERATE_MODEL
environment variables.

The result is persisted to the `sentence_transliterations` table via
`database.save_transliteration()` using the scheme stored in
OLLAMA_TRANSLITERATE_SCHEME (default: 'ala_lc').

See: docs/pipeline.md §Stage 4.5
"""

import os

import requests
from prefect import get_run_logger, task


@task(retries=3, retry_delay_seconds=5)
def run_ollama(arabic_text: str) -> dict[str, str]:
    # Limit parallelism to avoid overwhelming Ollama
    from prefect.concurrency.sync import rate_limit
    rate_limit("ollama-calls", occupy=1, timeout_seconds=600)

    """
    Call the Ollama API to generate an ALA-LC transliteration of the given
    Arabic sentence.

    Returns:
        {
            "scheme": "ala_lc",          # or whatever OLLAMA_TRANSLITERATE_SCHEME is set to
            "transliteration_text": str, # the romanised output
        }
    """
    logger = get_run_logger()

    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_TRANSLITERATE_MODEL", "aya-23-8b")
    scheme = os.environ.get("OLLAMA_TRANSLITERATE_SCHEME", "ala_lc")

    prompt = (
        "You are an expert in Arabic romanisation following the ALA-LC (Library of Congress) "
        "transliteration standard. Transliterate the following classical Arabic text into "
        "Latin script using that standard. Return ONLY the transliteration, nothing else.\n\n"
        f"Arabic text: {arabic_text}"
    )

    payload = {
        "model": model,
        "prompt": prompt,
        "stream": False,
    }

    response = requests.post(f"{ollama_url}/api/generate", json=payload, timeout=30)

    # Prefect will retry up to 3× with 5 s delay if Ollama is unavailable.
    response.raise_for_status()

    transliteration_text = response.json().get("response", "").strip()

    logger.info(f"Transliterated ({scheme}): {transliteration_text[:60]}…")

    return {
        "scheme": scheme,
        "transliteration_text": transliteration_text,
    }
