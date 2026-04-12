import os

import httpx
from prefect import flow, get_run_logger
from tasks import classify, text_prep, translate, vectorize
from database import fetch_pending_records, save_to_db


@flow(name="Islamic Text Ingestion")
def process_batch():
    logger = get_run_logger()

    # 1. Fetch raw SentenceJob records with status='pending'
    texts = fetch_pending_records()

    for text in texts:
        logger.info(f"Processing SentenceJob ID: {text['id']}")

        # 2. CPU: Strip Harakat + segment into sentences (SpaCy SBD)
        clean_sentences = text_prep.clean_arabic(text['content'])

        # 3. CPU: Extract Arabic roots (CAMeL Tools) for Global Lexicon
        root_data = text_prep.extract_roots(clean_sentences)

        for idx, sentence in enumerate(clean_sentences):
            # 4. CPU: Zero-shot classification (>60% confidence threshold)
            category = classify.zero_shot(sentence)

            # 5. GPU via Ollama: Translate Arabic → Indonesian (Aya model)
            indonesian = translate.run_ollama(sentence)

            # 6. CPU/GPU: Dual-language vectorization (mxbai-embed-large-v1 / 1024-dim)
            vectors = vectorize.create_embeddings(sentence, indonesian)

            # 7. Persist enriched sentence + lexicon data to PostgreSQL
            sentence_id = save_to_db(
                record_id=text['id'],
                sentence_text=sentence,
                category=category,
                translation=indonesian,
                vectors=vectors,
                lexicon_data=root_data[idx]['lexicon_data'],
            )

            # 8. Call Laravel Horizon webhook so IntegratePrefectData job
            #    can write the vectors & translations into the final schema.
            #    Returns 202 immediately — Horizon takes care of the rest.
            _notify_laravel(
                sentence_job_id=text['id'],
                sentence_id=sentence_id,
                category=category,
                vectors=vectors,
                lexicon_data=root_data[idx]['lexicon_data'],
            )


def _notify_laravel(
    sentence_job_id: str,
    sentence_id: str,
    category: str,
    vectors: dict,
    lexicon_data: list[dict],
) -> None:
    """
    POST enriched payload to the Laravel webhook endpoint.
    Laravel returns 202 immediately and queues an IntegratePrefectData Horizon job.
    See: docs/architecture.md §Stage 4
    """
    webhook_url = os.environ.get(
        'LARAVEL_WEBHOOK_URL',
        'http://laravel.test/api/webhooks/prefect/job-completed',
    )

    payload = {
        "sentence_job_id": sentence_job_id,
        "sentence_id": sentence_id,
        "status": "completed",
        "category": category,
        "embedding_ar": vectors["vector_ar"],
        "embedding_id": vectors["vector_id"],
        "lexicon_data": lexicon_data,
    }

    try:
        response = httpx.post(webhook_url, json=payload, timeout=10.0)
        response.raise_for_status()
    except httpx.HTTPError as exc:
        # Log but do not crash the Prefect flow — Laravel Horizon will
        # eventually reconcile any missing callbacks via SentenceJob status checks.
        import prefect
        logger = prefect.get_run_logger()
        logger.warning(f"Laravel webhook call failed for job {sentence_job_id}: {exc}")


if __name__ == "__main__":
    process_batch()
