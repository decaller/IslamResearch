"""
Database access layer for the AI ingestion pipeline.

Provides connection helpers and Prefect tasks that read from / write to
PostgreSQL so the pipeline tasks stay clean and serialisable.
"""

import os
import uuid
import psycopg2
from psycopg2.extras import RealDictCursor
from prefect import task

def get_db_connection():
    """Return a raw psycopg2 connection using Sail environment variables."""
    return psycopg2.connect(
        host=os.environ.get("DB_HOST", "localhost"),
        database=os.environ.get("DB_NAME", "laravel"),
        user=os.environ.get("DB_USER", "sail"),
        password=os.environ.get("DB_PASS", "password"),
        port=os.environ.get("DB_PORT", "5432")
    )

@task
def fetch_pending_records() -> list[dict]:
    """Fetch rows from `sentence_jobs` where status='pending'."""
    conn = get_db_connection()
    try:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute(
                "SELECT id, raw_text as content FROM sentence_jobs WHERE status = 'pending' LIMIT 50"
            )
            return cur.fetchall()
    finally:
        conn.close()

@task
def save_to_db(
    record_id: str,
    sentence_text: str,
    sequence_number: int,
    category: str,
    translation: str,
    vectors: dict,
    lexicon_data: list[dict],
) -> str:
    """
    Persist an enriched sentence and its translation to PostgreSQL.
    """
    conn = get_db_connection()
    sentence_id = str(uuid.uuid4())
    
    try:
        with conn, conn.cursor() as cur:
            # 1. Insert or update sentences (using sentence_text + source_book_id as identity)
            # Note: resource_type is pulled from the job's source book
            cur.execute(
                """
                INSERT INTO sentences (id, source_book_id, resource_type, sequence_number, sentence_text, embedding_ar, status, created_at, updated_at)
                SELECT %s, sb.id, sb.resource_type, %s, %s, %s, 'active', NOW(), NOW()
                FROM sentence_jobs sj
                JOIN source_books sb ON sj.source_book_id = sb.id
                WHERE sj.id = %s
                ON CONFLICT (source_book_id, sentence_text) DO UPDATE 
                SET updated_at = NOW(), embedding_ar = EXCLUDED.embedding_ar, sequence_number = EXCLUDED.sequence_number
                RETURNING id
                """,
                (sentence_id, sequence_number, sentence_text, vectors["vector_ar"], record_id)
            )
            row = cur.fetchone()
            if row:
                sentence_id = row[0]
            
            # 2. Insert or update sentence_translations
            cur.execute(
                """
                INSERT INTO sentence_translations (id, sentence_id, language, translation_text, embedding, created_at, updated_at)
                VALUES (%s, %s, 'id', %s, %s, NOW(), NOW())
                ON CONFLICT (sentence_id, language) DO UPDATE
                SET translation_text = EXCLUDED.translation_text, embedding = EXCLUDED.embedding, updated_at = NOW()
                """,
                (str(uuid.uuid4()), sentence_id, translation, vectors["vector_id"])
            )
            
            # 3. Update job status
            cur.execute(
                "UPDATE sentence_jobs SET status = 'completed', completed_at = NOW() WHERE id = %s",
                (record_id,)
            )
            
        return sentence_id
    finally:
        conn.close()

@task
def save_transliteration(sentence_id: str, scheme: str, transliteration_text: str) -> None:
    """Upsert a row into `sentence_transliterations`."""
    conn = get_db_connection()
    try:
        with conn, conn.cursor() as cur:
            cur.execute(
                """
                INSERT INTO sentence_transliterations (id, sentence_id, scheme, transliteration_text, created_at, updated_at)
                VALUES (%s, %s, %s, %s, NOW(), NOW())
                ON CONFLICT (sentence_id, scheme)
                DO UPDATE SET transliteration_text = EXCLUDED.transliteration_text, updated_at = NOW()
                """,
                (str(uuid.uuid4()), sentence_id, scheme, transliteration_text),
            )
    finally:
        conn.close()
