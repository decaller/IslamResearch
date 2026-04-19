from prefect import task, get_run_logger
import requests
import os


@task(retries=3, retry_delay_seconds=5)
def create_embeddings(arabic_text: str, translation_text: str = "") -> dict:
    """
    Generate semantic embeddings for an Arabic source text and its translation.

    Arabic-first principle:
    - `vector_ar` is ALWAYS computed from the original Arabic source text.
    - `vector_translation` is computed from the translation text in its own language
      (Indonesian, English, etc.), ONLY when a translation is provided.
    - Classification, tags, and all AI metadata are stored in Arabic — embeddings
      must match: we never embed English/Indonesian *labels* as the primary vector.

    Model: mxbai-embed-large (1024-dim) via local Ollama GPU endpoint.
    """
    logger = get_run_logger()
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_EMBED_MODEL", "mxbai-embed-large:latest")

    from prefect.concurrency.sync import concurrency
    try:
        with concurrency("ollama-calls", occupy=1, timeout_seconds=10):
            # 1. Arabic embedding (always required)
            ar_resp = requests.post(f"{ollama_url}/api/embed", json={
                "model": model,
                "input": arabic_text,
            }, timeout=5.0)
            ar_resp.raise_for_status()
            ar_vector = ar_resp.json().get("embeddings", [[]])[0]

            # 2. Translation embedding (only when translation is available)
            translation_vector = []
            if translation_text and translation_text.strip():
                id_resp = requests.post(f"{ollama_url}/api/embed", json={
                    "model": model,
                    "input": translation_text,
                }, timeout=5.0)
                id_resp.raise_for_status()
                translation_vector = id_resp.json().get("embeddings", [[]])[0]
    except Exception as e:
        logger.warning(f"⚠️ Ollama embedding failed: {str(e)}. Proceeding with NULL vectors.")
        ar_vector = None
        translation_vector = None

    # Validate expected dimension
    if ar_vector and len(ar_vector) != 1024:
        logger.warning(
            f"Vector dimension mismatch! Expected 1024, got {len(ar_vector)}. "
            "Ensure mxbai-embed-large or snowflake-arctic-embed-v1.5 is used."
        )

    return {
        "vector_ar": ar_vector,
        "vector_translation": translation_vector,  # Empty list if no translation
    }


@task(retries=3, retry_delay_seconds=5)
def embed_text(text: str) -> list[float]:
    """
    Embed a single text string. Used when only one embedding is needed
    (e.g., quran_translation_flow — embedding the translation text only).
    """
    logger = get_run_logger()
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_EMBED_MODEL", "mxbai-embed-large:latest")

    from prefect.concurrency.sync import concurrency
    try:
        with concurrency("ollama-calls", occupy=1, timeout_seconds=10):
            resp = requests.post(f"{ollama_url}/api/embed", json={
                "model": model,
                "input": text,
            }, timeout=5.0)
            resp.raise_for_status()
            vector = resp.json().get("embeddings", [[]])[0]
    except Exception as e:
        logger.warning(f"⚠️ Ollama single embedding failed: {str(e)}. Proceeding with NULL vector.")
        vector = None

    if vector and len(vector) != 1024:
        logger.warning(f"Single embed dimension mismatch: got {len(vector)}, expected 1024.")

    return vector
