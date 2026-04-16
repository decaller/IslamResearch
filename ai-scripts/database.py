"""
Database access layer for the AI ingestion pipeline.
"""

import os
import uuid
import psycopg2
from psycopg2.extras import RealDictCursor
from prefect import task

def get_db_connection():
    return psycopg2.connect(
        host=os.environ.get("DB_HOST", "localhost"),
        database=os.environ.get("DB_NAME", "laravel"),
        user=os.environ.get("DB_USER", "sail"),
        password=os.environ.get("DB_PASS", "password"),
        port=os.environ.get("DB_PORT", "5432")
    )

@task
def fetch_job_details(job_id: str) -> dict:
    """Fetch the specific sentence and its scholarly context (prev/next)."""
    conn = get_db_connection()
    try:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            # 1. First get the target sentence details
            cur.execute(
                """
                SELECT sj.id as job_id, s.id as sentence_id, s.source_book_id,
                       s.sequence_number, s.sentence_text as content,
                       sj.needs_embedding, sj.needs_translation, sj.needs_transliteration,
                       s.metadata as sentence_metadata
                FROM sentence_jobs sj
                JOIN sentences s ON sj.sentence_id = s.id
                WHERE sj.id = %s
                """,
                (job_id,)
            )
            job = cur.fetchone()
            if not job:
                return None

            # 2. Fetch context (2 previous and 2 next sentences)
            cur.execute(
                """
                SELECT sequence_number, sentence_text
                FROM sentences
                WHERE source_book_id = %s
                AND sequence_number BETWEEN %s AND %s
                ORDER BY sequence_number ASC
                """,
                (job['source_book_id'], job['sequence_number'] - 2, job['sequence_number'] + 2)
            )
            context = cur.fetchall()
            
            # Group context for AI
            job['context_prev'] = [c['sentence_text'] for c in context if c['sequence_number'] < job['sequence_number']]
            job['context_next'] = [c['sentence_text'] for c in context if c['sequence_number'] > job['sequence_number']]
            
            return job
    finally:
        conn.close()

@task
def save_to_db(
    sentence_id: str,
    categories: list,
    translation: str,
    vectors: dict,
    needs_emb: bool,
    needs_trans: bool,
    lang: str = "id",
    tags: list = None,
    existing_metadata: dict = None
) -> None:
    """Enrich an existing sentence record."""
    conn = get_db_connection()
    try:
        with conn, conn.cursor() as cur:
            # 1. Prepare Metadata
            metadata = existing_metadata or {}
            if tags:
                metadata['tags'] = tags
            metadata['categories'] = categories
            if categories:
                metadata['category'] = categories[0] # Legacy support for single category view

            # 2. Update sentence (Embedding, Metadata)
            update_sql = "UPDATE sentences SET metadata = %s, updated_at = NOW()"
            params = [psycopg2.extras.Json(metadata)]
            
            if needs_emb and vectors.get("vector_ar"):
                update_sql += ", embedding_ar = %s"
                params.append(vectors.get("vector_ar"))  # Always Arabic source embedding
            
            update_sql += " WHERE id = %s"
            params.append(sentence_id)
            
            cur.execute(update_sql, tuple(params))
            
            # 2. Upsert translation if needed
            if needs_trans:
                cur.execute(
                    """
                    INSERT INTO sentence_translations (id, sentence_id, language, translation_text, embedding, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, NOW(), NOW())
                    ON CONFLICT (sentence_id, language) DO UPDATE
                    SET translation_text = EXCLUDED.translation_text, embedding = EXCLUDED.embedding, updated_at = NOW()
                    """,
                    (str(uuid.uuid4()), sentence_id, lang, translation, vectors.get("vector_translation") or None)
                )
    finally:
        conn.close()

@task
def save_transliteration(sentence_id: str, scheme: str, transliteration_text: str) -> None:
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

@task
def get_book_metadata(book_id: str) -> dict:
    """Fetch metadata for a specific source book."""
    conn = get_db_connection()
    try:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("SELECT * FROM source_books WHERE id = %s", (book_id,))
            return cur.fetchone()
    finally:
        conn.close()

@task
def save_quran_verse(book_id: str, verse: dict, vectors: dict, lexicon_data: list) -> str:
    """Save a single Quran ayah and its linked lexicon entries."""
    conn = get_db_connection()
    try:
        with conn, conn.cursor() as cur:
            # 1. Prepare Metadata
            metadata = {
                "surah_id": verse['surah_number'],
                "surah_name_ar": verse['surah_name_ar'],
                "surah_name_en": verse['surah_name_en'],
                "ayah_number": verse['ayah_number'],
                "ayah_number_global": verse['ayah_number_global'],
                "juz": verse['juz'],
                "mushaf_page": verse['page'],
                "arabic_edition": verse['arabic_edition'],
            }

            # 2. Upsert Sentence (Ayah)
            sentence_id = str(uuid.uuid4())
            cur.execute(
                """
                INSERT INTO sentences (id, source_book_id, resource_type, sequence_number, sentence_text, metadata, embedding_ar, created_at, updated_at)
                VALUES (%s, %s, 'quran', %s, %s, %s, %s, NOW(), NOW())
                ON CONFLICT (source_book_id, sequence_number) DO UPDATE
                SET sentence_text = EXCLUDED.sentence_text, metadata = EXCLUDED.metadata, embedding_ar = EXCLUDED.embedding_ar, updated_at = NOW()
                RETURNING id
                """,
                (sentence_id, book_id, verse['ayah_number_global'], verse['arabic_text'], psycopg2.extras.Json(metadata), vectors.get("vector_ar"))
            )
            sentence_id = cur.fetchone()[0]
            
            return sentence_id
    finally:
        conn.close()
