"""
Database access layer for the AI ingestion pipeline.

Provides connection helpers and Prefect tasks that read from / write to
PostgreSQL so the pipeline tasks stay clean and serialisable.
"""

import os
import uuid

import psycopg2
from prefect import task


def get_db_connection():
    """Return a raw psycopg2 connection using Sail environment variables."""
    return psycopg2.connect(
        host=os.environ.get("DB_HOST"),
        database=os.environ.get("DB_NAME"),
        user=os.environ.get("DB_USER"),
        password=os.environ.get("DB_PASS"),
    )


@task
def fetch_pending_records() -> list[dict]:
    """Fetch rows from `sentence_jobs` where status='pending'."""
    # Placeholder — swap with real query once the schema is seeded.
    return [{"id": str(uuid.uuid4()), "content": "الزكاة ركن من أركان الإسلام"}]


@task
def save_to_db(
    record_id: str,
    sentence_text: str,
    category: str,
    translation: str,
    vectors: dict,
    lexicon_data: list[dict],
) -> str:
    """
    Persist an enriched sentence (and its translation) to PostgreSQL.

    Returns the new `sentences.id` so downstream tasks (transliteration,
    webhook) can reference it.

    Currently a placeholder — implement with psycopg2 INSERT / ON CONFLICT
    DO UPDATE once the schema migrations are verified.
    """
    sentence_id = str(uuid.uuid4())
    print(f"[save_to_db] record={record_id} category={category} sentence_id={sentence_id}")
    return sentence_id


@task
def save_transliteration(sentence_id: str, scheme: str, transliteration_text: str) -> None:
    """
    Upsert a row into `sentence_transliterations`.

    The table has a unique constraint on (sentence_id, scheme), so re-running
    the pipeline is idempotent — it just updates the existing transliteration.

    Schema reference: docs/database_schema.dbml → sentence_transliterations
    """
    print(
        f"[save_transliteration] sentence={sentence_id} "
        f"scheme={scheme} text={transliteration_text[:40]}…"
    )
    # TODO: Replace with real psycopg2 upsert once migrations are stable:
    #
    # conn = get_db_connection()
    # with conn, conn.cursor() as cur:
    #     cur.execute(
    #         """
    #         INSERT INTO sentence_transliterations (id, sentence_id, scheme, transliteration_text)
    #         VALUES (gen_random_uuid(), %s, %s, %s)
    #         ON CONFLICT (sentence_id, scheme)
    #         DO UPDATE SET transliteration_text = EXCLUDED.transliteration_text
    #         """,
    #         (sentence_id, scheme, transliteration_text),
    #     )
    # conn.close()
