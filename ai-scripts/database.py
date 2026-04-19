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
            
            vector_ar = vectors.get("vector_ar")
            if needs_emb and vector_ar:
                if isinstance(vector_ar, list):
                    vector_ar = "[" + ",".join(map(str, vector_ar)) + "]"
                update_sql += ", embedding_ar = %s"
                params.append(vector_ar)
            
            update_sql += " WHERE id = %s"
            params.append(sentence_id)
            
            cur.execute(update_sql, tuple(params))
            
            # 2. Upsert translation if needed
            if needs_trans:
                vector_trans = vectors.get("vector_translation")
                if isinstance(vector_trans, list) and vector_trans:
                    vector_trans = "[" + ",".join(map(str, vector_trans)) + "]"
                else:
                    vector_trans = None

                cur.execute(
                    """
                    INSERT INTO sentence_translations (id, sentence_id, language, translation_text, embedding, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, NOW(), NOW())
                    ON CONFLICT (sentence_id, language) DO UPDATE
                    SET translation_text = EXCLUDED.translation_text, embedding = EXCLUDED.embedding, updated_at = NOW()
                    """,
                    (str(uuid.uuid4()), sentence_id, lang, translation, vector_trans)
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
def find_ayah_id_by_position(surah_number: int, ayah_number: int) -> str:
    """Find a sentence ID for a specific Quran ayah."""
    conn = get_db_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT id FROM sentences 
                WHERE resource_type = 'quran' 
                AND (metadata->>'surah_id')::int = %s 
                AND (metadata->>'ayah_number')::int = %s
                """,
                (surah_number, ayah_number)
            )
            result = cur.fetchone()
            return result[0] if result else None
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
                "surah_name_en_translation": verse['surah_name_en_translation'],
                "revelation_type": verse['revelation_type'],
                "number_of_ayahs": verse['number_of_ayahs'],
                "ayah_number": verse['ayah_number'],
                "ayah_number_global": verse['ayah_number_global'],
                "juz": verse['juz'],
                "manzil": verse['manzil'],
                "mushaf_page": verse['page'],
                "ruku": verse['ruku'],
                "hizb_quarter": verse['hizb_quarter'],
                "sajda": verse['sajda'],
                "arabic_edition": verse['arabic_edition'],
            }

            if verse.get('transliteration'):
                metadata['transliteration'] = verse['transliteration']

            # 2. Upsert Sentence (Ayah)
            sentence_id = str(uuid.uuid4())
            
            # Format vectors for pgvector
            vector_ar = vectors.get("vector_ar")
            if isinstance(vector_ar, list) and vector_ar:
                vector_ar = "[" + ",".join(map(str, vector_ar)) + "]"
            elif not vector_ar:
                vector_ar = None

            cur.execute(
                """
                INSERT INTO sentences (id, source_book_id, resource_type, sequence_number, sentence_text, metadata, embedding_ar, created_at, updated_at)
                VALUES (%s, %s, 'quran', %s, %s, %s, %s, NOW(), NOW())
                ON CONFLICT (source_book_id, sequence_number) DO UPDATE
                SET sentence_text = EXCLUDED.sentence_text, metadata = EXCLUDED.metadata, embedding_ar = EXCLUDED.embedding_ar, updated_at = NOW()
                RETURNING id
                """,
                (sentence_id, book_id, verse['ayah_number_global'], verse['arabic_text'], psycopg2.extras.Json(metadata), vector_ar)
            )
            sentence_id = cur.fetchone()[0]
            
            return sentence_id
    finally:
        conn.close()

@task
def get_or_create_root(root_value: str, language: str = 'ar') -> str:
    """Find or create a lexicon root."""
    if not root_value:
        root_value = "UNKNOWN"
    
    conn = get_db_connection()
    try:
        with conn, conn.cursor() as cur:
            cur.execute("SELECT id FROM lexicon_roots WHERE language = %s AND root_value = %s", (language, root_value))
            row = cur.fetchone()
            if row:
                return row[0]
            
            root_id = str(uuid.uuid4())
            cur.execute(
                "INSERT INTO lexicon_roots (id, language, root_value, created_at, updated_at) VALUES (%s, %s, %s, NOW(), NOW()) RETURNING id",
                (root_id, language, root_value)
            )
            return cur.fetchone()[0]
    finally:
        conn.close()

def _clean_ar(text: str) -> str:
    import re
    return re.sub(r'[\u0617-\u061A\u064B-\u0652]', '', text)

@task
def save_quran_word(sentence_id: str, word_data: dict, root_id: str = None) -> str:
    """Save a single word linked to an ayah."""
    conn = get_db_connection()
    try:
        # 1. Ensure we have a root_id
        if not root_id:
            # Try to get root from word_data if available (Quran Foundation sometimes has it)
            # Otherwise we'll use a placeholder or caller should provide it
            root_str = word_data.get('root_text') or "UNKNOWN"
            root_id = get_or_create_root(root_str)

        with conn, conn.cursor() as cur:
            word_id = str(uuid.uuid4())
            word_raw = word_data.get('text_uthmani') or word_data.get('text')
            word_clean = _clean_ar(word_raw)
            
            # Prepare metadata (glyphs, codes, audio)
            metadata = {
                "position": word_data.get('position'),
                "verse_key": word_data.get('verse_key'),
                "page_number": word_data.get('page_number'),
                "line_number": word_data.get('line_number'),
                "code_v1": word_data.get('code_v1'),
                "code_v2": word_data.get('code_v2'),
                "transliteration": word_data.get('transliteration', {}).get('text'),
                "translation": word_data.get('translation', {}).get('text'),
            }
            
            if word_data.get('audio_url'):
                metadata['audio_url'] = word_data['audio_url']
            
            # Upsert word
            cur.execute(
                """
                INSERT INTO lexicon_words (id, root_id, language, word_raw, word_clean, metadata, created_at, updated_at)
                VALUES (%s, %s, 'ar', %s, %s, %s, NOW(), NOW())
                ON CONFLICT (language, word_raw, word_clean) DO UPDATE
                SET updated_at = NOW()
                RETURNING id
                """,
                (
                    word_id,
                    root_id,
                    word_raw,
                    word_clean,
                    psycopg2.extras.Json(metadata)
                )
            )
            word_id = cur.fetchone()[0]
            
            # Link to sentence (no timestamps in pivot)
            cur.execute(
                """
                INSERT INTO sentence_word (sentence_id, word_id, source_type, positions)
                VALUES (%s, %s, 'quran_foundation', %s)
                ON CONFLICT (sentence_id, word_id) DO NOTHING
                """,
                (sentence_id, word_id, psycopg2.extras.Json([word_data['position']]))
            )
            
            return word_id
    finally:
        conn.close()

@task
def find_entity_by_name(name: str):
    """Search for an entity by canonical name or within aliases JSONB array."""
    conn = get_db_connection()
    try:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            # Search canonical name or inside aliases array
            cur.execute(
                """
                SELECT id, canonical_name, entity_type, last_enriched_at FROM entities
                WHERE canonical_name = %s OR aliases @> %s
                """,
                (name, psycopg2.extras.Json([name]))
            )
            return cur.fetchone()
    finally:
        conn.close()

@task
def create_entity(data: dict) -> str:
    """Create a new entity with optional Wikipedia enrichment data."""
    conn = get_db_connection()
    try:
        with conn, conn.cursor() as cur:
            entity_id = str(uuid.uuid4())
            cur.execute(
                """
                INSERT INTO entities (
                    id, canonical_name, entity_type, aliases, description, wikipedia_url, metadata, last_enriched_at, created_at, updated_at
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
                RETURNING id
                """,
                (
                    entity_id,
                    data['canonical_name'],
                    data.get('entity_type', 'concept'),
                    psycopg2.extras.Json(data.get('aliases', [])),
                    data.get('description'),
                    data.get('wikipedia_url'),
                    psycopg2.extras.Json(data.get('metadata', {})),
                    data.get('last_enriched_at')
                )
            )
            return cur.fetchone()[0]
    finally:
        conn.close()

@task
def link_sentence_to_entity(sentence_id: str, entity_id: str, confidence: float = 1.0, context: dict = None):
    """Link a sentence to an entity in the pivot table."""
    conn = get_db_connection()
    try:
        with conn, conn.cursor() as cur:
            cur.execute(
                """
                INSERT INTO sentence_entity (sentence_id, entity_id, confidence, context_metadata)
                VALUES (%s, %s, %s, %s)
                ON CONFLICT (sentence_id, entity_id) DO UPDATE
                SET confidence = EXCLUDED.confidence, context_metadata = EXCLUDED.context_metadata
                """,
                (sentence_id, entity_id, confidence, psycopg2.extras.Json(context or {}))
            )
    finally:
        conn.close()

@task
def save_entity_relationship(
    source_id: str, 
    target_id: str, 
    rel_type: str, 
    evidence_id: str = None, 
    confidence: float = 1.0
):
    """Save a relationship between two entities."""
    conn = get_db_connection()
    try:
        with conn, conn.cursor() as cur:
            rel_id = str(uuid.uuid4())
            cur.execute(
                """
                INSERT INTO entity_relationships (
                    id, source_entity_id, target_entity_id, relationship_type, evidence_sentence_id, confidence, created_at, updated_at
                ) VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
                ON CONFLICT (source_entity_id, target_entity_id, relationship_type, evidence_sentence_id) DO NOTHING
                """,
                (rel_id, source_id, target_id, rel_type, evidence_id, confidence)
            )
    finally:
        conn.close()

@task
def add_to_ambiguity_queue(sentence_id: str, surface_name: str, candidates: list, context_block: str):
    """Add an ambiguous match to the queue for manual review."""
    conn = get_db_connection()
    try:
        with conn, conn.cursor() as cur:
            cur.execute(
                """
                INSERT INTO entity_ambiguity_queue (
                    id, sentence_id, surface_name, candidate_entities, context_block, status, created_at, updated_at
                ) VALUES (%s, %s, %s, %s, %s, 'pending', NOW(), NOW())
                """,
                (str(uuid.uuid4()), sentence_id, surface_name, psycopg2.extras.Json(candidates), context_block)
            )
    finally:
        conn.close()
@task
def update_entity_enrichment_time(entity_id: str):
    """Update the last_enriched_at timestamp for an entity."""
    conn = get_db_connection()
    try:
        with conn, conn.cursor() as cur:
            cur.execute(
                "UPDATE entities SET last_enriched_at = NOW(), updated_at = NOW() WHERE id = %s",
                (entity_id,)
            )
    finally:
        conn.close()
