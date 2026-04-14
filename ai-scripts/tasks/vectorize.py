from prefect import task, get_run_logger
import requests
import os

@task(retries=3, retry_delay_seconds=5)
def create_embeddings(arabic_text: str, indo_text: str) -> dict:
    """
    Vectorize Arabic and Indonesian texts via Ollama, offloading to the GPU
    on the AI PC. By default it uses the mxbai-embed-large/1024-dim model.
    """
    logger = get_run_logger()
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_EMBED_MODEL", "mxbai-embed-large:latest")

    from prefect.concurrency.sync import concurrency
    with concurrency("ollama-calls", occupy=1, timeout_seconds=600):
        # 1. Arabic vector
        # Uses the new /api/embed endpoint which is the Ollama standard
        ar_resp = requests.post(f"{ollama_url}/api/embed", json={
            "model": model,
            "input": arabic_text
        })
        ar_resp.raise_for_status()
        ar_vector = ar_resp.json().get("embeddings", [[]])[0] # /api/embed returns 'embeddings' (list of lists)

        # 2. Indonesian vector
        id_resp = requests.post(f"{ollama_url}/api/embed", json={
            "model": model,
            "input": indo_text
        })
        id_resp.raise_for_status()
        id_vector = id_resp.json().get("embeddings", [[]])[0]

    # Validation: Ensure it matches the database schema (1024)
    if len(ar_vector) != 1024:
        logger.warning(
            f"Vector dimension mismatch! Expected 1024, got {len(ar_vector)}. "
            "Please ensure you follow the 'mxbai-embed-large' or 'snowflake-arctic-embed-v1.5' "
            "recommendation to match pgvector(1024)."
        )

    return {
        "vector_ar": ar_vector,
        "vector_id": id_vector,
    }
