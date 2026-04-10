from prefect import flow, get_run_logger
from tasks import text_prep, classify, translate, vectorize
from database import fetch_pending_records, save_to_db

@flow(name="Islamic Text Ingestion")
def process_batch():
    logger = get_run_logger()
    
    # 1. Fetch raw data
    texts = fetch_pending_records()
    
    for text in texts:
        logger.info(f"Processing Record ID: {text['id']}")
        
        # 2. Fast CPU Tasks (Prep & Categorize)
        # Note: clean_arabic returns a list of sentences
        clean_sentences = text_prep.clean_arabic(text['content'])
        
        for sentence in clean_sentences:
            category = classify.zero_shot(sentence)
            
            # 3. Slow GPU Tasks (Translate)
            indonesian = translate.run_ollama(sentence)
            
            # 4. Math/Vector Tasks (Embeddings)
            vectors = vectorize.create_embeddings(sentence, indonesian)
            
            # 5. Save final enriched data
            save_to_db(text['id'], sentence, category, indonesian, vectors)

if __name__ == "__main__":
    process_batch()
