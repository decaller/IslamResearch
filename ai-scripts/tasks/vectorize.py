from prefect import task
from sentence_transformers import SentenceTransformer

# Model: mixedbread-ai/mxbai-embed-large-v1 — 1024-dim multilingual embeddings
# See: docs/pipeline.md §5 "The Vectorizer"
_embedder = SentenceTransformer("mixedbread-ai/mxbai-embed-large-v1")


@task
def create_embeddings(arabic_text: str, indo_text: str) -> dict:
    """
    Vectorize Arabic and Indonesian texts independently into 1024-dim vectors.

    Keeping embeddings language-separated prevents cross-lingual concept
    dilution and maximizes recall accuracy for both Arabic and Indonesian queries.
    See: docs/pipeline.md §5 & docs/lexicon-strategy.md §4
    """
    ar_vector: list[float] = _embedder.encode(arabic_text).tolist()
    id_vector: list[float] = _embedder.encode(indo_text).tolist()

    return {
        "vector_ar": ar_vector,  # 1024 floats — maps to sentences.embedding_ar
        "vector_id": id_vector,  # 1024 floats — maps to sentences.embedding_id
    }
