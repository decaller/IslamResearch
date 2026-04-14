import os
from transformers import pipeline
from prefect import task, get_run_logger

# Singleton for the pipeline to avoid reloading on every task execution
_ner_pipeline = None

def get_ner_pipeline():
    global _ner_pipeline
    if _ner_pipeline is None:
        # Using a highly popular and public Arabic NER model
        model_name = "hatmimoha/arabic-ner"
        _ner_pipeline = pipeline(
            "token-classification", 
            model=model_name, 
            aggregation_strategy="simple"
        )
    return _ner_pipeline

@task
def extract_entities(text: str) -> list:
    """Extract Named Entities (Person, Location, Organization, etc.) using Token Classification."""
    logger = get_run_logger()
    try:
        nlp = get_ner_pipeline()
        results = nlp(text)
        
        # Extract unique entity names and types as tags
        # Example: {'entity_group': 'PERS', 'word': 'محمد'}
        entities = []
        for res in results:
            entities.append(f"{res['entity_group']}:{res['word']}")
        
        return list(set(entities))
    except Exception as e:
        logger.warning(f"NER failed: {e}")
        return []
