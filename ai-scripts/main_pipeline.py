import os
import httpx
from prefect import flow, get_run_logger
from tasks import classify, text_prep, translate, transliterate, vectorize
from database import fetch_pending_records, save_to_db, save_transliteration


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
        # This is fast so it runs once per job
        root_data = text_prep.extract_roots(clean_sentences)

        # ----------------------------------------------------------------------
        # TURBO PARALLEL STAGE: Process all sentences concurrently
        # ----------------------------------------------------------------------
        sentence_pipelines = []

        for sentence in clean_sentences:
            # A. Submit independent tasks for THIS sentence
            cat_f = classify.zero_shot.submit(sentence)
            trans_f = translate.run_ollama.submit(sentence)
            tl_f = transliterate.run_ollama.submit(sentence)

            # B. Submit vectorizer (depends on translation)
            # Prefect is smart: it won't start 'vec_f' until 'trans_f' completes.
            vec_f = vectorize.create_embeddings.submit(sentence, trans_f)

            sentence_pipelines.append({
                "sentence": sentence,
                "cat_f": cat_f,
                "trans_f": trans_f,
                "tl_f": tl_f,
                "vec_f": vec_f
            })

        # 4. Final Stage: Collecting results and persisting
        for idx, pipe in enumerate(sentence_pipelines):
            # .result() waits for these specific tasks to finish
            category = pipe["cat_f"].result()
            indonesian = pipe["trans_f"].result()
            transliteration = pipe["tl_f"].result()
            vectors = pipe["vec_f"].result()

            # Persist enriched sentence + lexicon data to PostgreSQL
            sentence_id = save_to_db(
                record_id=text['id'],
                sentence_text=pipe["sentence"],
                category=category,
                translation=indonesian,
                vectors=vectors,
                lexicon_data=root_data[idx]['lexicon_data'],
            )

            # Persist AI-generated transliteration
            save_transliteration(
                sentence_id=sentence_id,
                scheme=transliteration['scheme'],
                transliteration_text=transliteration['transliteration_text'],
            )

            # Notify Laravel
            _notify_laravel(
                sentence_job_id=text['id'],
                sentence_id=sentence_id,
                category=category,
                vectors=vectors,
                transliteration=transliteration,
                lexicon_data=root_data[idx]['lexicon_data'],
            )


def _notify_laravel(
    sentence_job_id: str,
    sentence_id: str,
    category: str,
    vectors: dict,
    transliteration: dict,
    lexicon_data: list[dict],
) -> None:
    """POST enriched payload to the Laravel webhook endpoint."""
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
        "transliteration": {
            "scheme": transliteration["scheme"],
            "text": transliteration["transliteration_text"],
        },
        "lexicon_data": lexicon_data,
    }

    try:
        response = httpx.post(webhook_url, json=payload, timeout=10.0)
        response.raise_for_status()
    except httpx.HTTPError as exc:
        import prefect
        logger = prefect.get_run_logger()
        logger.warning(f"Laravel webhook call failed for job {sentence_job_id}: {exc}")


if __name__ == "__main__":
    process_batch()
