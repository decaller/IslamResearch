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
def run_ollama(arabic_text: str, scheme: str = "ala_lc") -> dict[str, str]:
    from prefect.concurrency.sync import concurrency

    """
    Call the Ollama API to generate a scholarly transliteration.
    """
    logger = get_run_logger()

    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_TRANSLITERATE_MODEL", "aya:latest")

    scheme_names = {
        "ala_lc": "ALA-LC (Library of Congress)",
        "iso": "ISO 233",
        "scientific": "Scientific Journal (ZDMG)"
    }
    scheme_name = scheme_names.get(scheme, "ALA-LC")

    with concurrency("ollama-calls", occupy=1, timeout_seconds=600):
        prompt = (
            f"You are an expert in Arabic romanisation following the {scheme_name} "
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
        response.raise_for_status()
        transliteration_text = response.json().get("response", "").strip()

    logger.info(f"Transliterated ({scheme}): {transliteration_text[:60]}…")

    return {
        "scheme": scheme,
        "transliteration_text": transliteration_text,
    }
