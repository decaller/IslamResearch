from prefect import task
from transformers import pipeline

# Load the Multilingual Embedding model
# Note: Using xlm-v or similar for base embeddings, user suggested mxbai
embedder = pipeline("feature-extraction", model="mxbai-embed-large")

@task
def create_embeddings(arabic_text: str, indo_text: str):
    # Vectorize both languages for robust search
    ar_vector = embedder(arabic_text)
    id_vector = embedder(indo_text)
    
    return {
        "vector_ar": ar_vector[0][0], # Extract the raw array (assuming base result structure)
        "vector_id": id_vector[0][0]
    }
