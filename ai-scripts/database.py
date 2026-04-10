import os
import psycopg2
from prefect import task

def get_db_connection():
    return psycopg2.connect(
        host=os.environ.get('DB_HOST'),
        database=os.environ.get('DB_NAME'),
        user=os.environ.get('DB_USER'),
        password=os.environ.get('DB_PASS')
    )

@task
def fetch_pending_records():
    """Fetch rows from PostgreSQL where status='pending'"""
    # Placeholder for actual implementation
    return [{"id": 1, "content": "الزكاة ركن من أركان الإسلام"}]

@task
def save_to_db(record_id, text, category, translation, vectors):
    """Save enriched data back to PostgreSQL"""
    # Placeholder for actual implementation
    print(f"Saving record {record_id}: {category}")
    pass
