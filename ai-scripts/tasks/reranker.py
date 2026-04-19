import numpy as np
import requests
import os
import re

def generate_sliding_windows(text, window_size=8, stride=1):
    """
    Generates sliding windows of words from the text.
    Returns a list of dicts: {"text": window_text, "start": char_start, "end": char_end}
    """
    words = []
    # Using regex to find words and their positions
    for m in re.finditer(r'\S+', text):
        words.append({
            "word": m.group(0),
            "start": m.start(),
            "end": m.end()
        })
    
    windows = []
    if len(words) < window_size:
        return [{"text": text, "start": 0, "end": len(text)}]
    
    for i in range(0, len(words) - window_size + 1, stride):
        window_words = words[i:i + window_size]
        window_text = text[window_words[0]["start"]:window_words[-1]["end"]]
        windows.append({
            "text": window_text,
            "start": window_words[0]["start"],
            "end": window_words[-1]["end"]
        })
    
    return windows

def get_ollama_embeddings(texts, model="mxbai-embed-large:latest"):
    """
    Fetches embeddings for a list of texts using Ollama.
    """
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    embeddings = []
    
    # Batching could be better, but Ollama's /api/embed expects string or list of strings
    # and returns embeddings.
    try:
        resp = requests.post(f"{ollama_url}/api/embed", json={
            "model": model,
            "input": texts,
        })
        resp.raise_for_status()
        embeddings = resp.json().get("embeddings", [])
    except Exception as e:
        print(f"Error fetching embeddings from Ollama: {e}")
        return None
    
    return np.array(embeddings).astype('float32')

def semantic_rerank(query, documents, window_size=8, stride=1):
    """
    Stage-2 Re-ranker:
    1. Splits each document into sliding windows.
    2. Embeds the query and all windows.
    3. Finds the highest scoring window across all documents.
    """
    all_chunks = []
    for doc_id, doc_text in enumerate(documents):
        windows = generate_sliding_windows(doc_text, window_size, stride)
        for w in windows:
            w["doc_index"] = doc_id
            all_chunks.append(w)
            
    if not all_chunks:
        return None

    # Embed query and all chunks
    texts_to_embed = [query] + [chunk["text"] for chunk in all_chunks]
    embeddings = get_ollama_embeddings(texts_to_embed)
    
    if embeddings is None:
        return None
        
    query_vec = embeddings[0]
    chunk_vecs = embeddings[1:]
    
    # Calculate cosine similarity (simple dot product if vectors are normalized)
    # mxbai-embed-large usually returns normalized vectors or we can normalize them.
    def normalize(v):
        norm = np.linalg.norm(v, axis=-1, keepdims=True)
        return v / (norm + 1e-9)

    query_vec = normalize(query_vec)
    chunk_vecs = normalize(chunk_vecs)
    
    similarities = np.dot(chunk_vecs, query_vec)
    best_idx = np.argmax(similarities)
    
    best_match = all_chunks[best_idx]
    best_match["score"] = float(similarities[best_idx])
    
    return best_match
