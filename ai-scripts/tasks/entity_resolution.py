import requests
import json
from prefect import task, get_run_logger
import os

OLLAMA_URL = os.environ.get("OLLAMA_URL", "http://localhost:11434")

@task
def resolve_entity_with_ollama(surface_name: str, context: str, model: str = "aya"):
    """
    Use Ollama to disambiguate a name based on context.
    Returns the canonical name of the figure.
    """
    logger = get_run_logger()
    
    prompt = f"""
    You are a professional Islamic Scholar and Historian.
    
    Context:
    \"\"\"{context}\"\"\"
    
    The name referenced in the center of this context is: "{surface_name}".
    
    Identify which specific historical figure, location, or concept this refers to. 
    Examples: 
    - "Umar" in a 2nd Caliph context -> "Umar bin Al-Khattab"
    - "Umar" in an 8th century context -> "Umar bin Abdul Aziz"
    - "Sabr" -> "Sabr (Patience)"
    
    Reply ONLY with the full canonical name. No explanations.
    Canonical Name:
    """
    
    try:
        response = requests.post(
            f"{OLLAMA_URL}/api/generate",
            json={
                "model": model,
                "prompt": prompt,
                "stream": False
            }
        )
        response.raise_for_status()
        result = response.json()
        canonical_name = result.get("response", "").strip()
        
        logger.info(f"Ollama resolved '{surface_name}' to '{canonical_name}'")
        return canonical_name
    except Exception as e:
        logger.error(f"Failed to resolve entity via Ollama: {str(e)}")
        return surface_name # Fallback to surface name

@task
def extract_relationships_with_ollama(text: str, model: str = "aya"):
    """
    Extract S-P-O triplets from text using Ollama.
    """
    logger = get_run_logger()
    
    prompt = f"""
    Analyze the following sentence from an Islamic text and extract historical or conceptual relationships.
    Format your answer as a JSON list of objects: [{{ "subject": "...", "predicate": "...", "object": "..." }}]
    
    Sentence: "{text}"
    
    Extract triplets like (Umar, Migrated To, Madinah) or (Zakat, Requires, Nisab).
    Answer:
    """
    
    try:
        response = requests.post(
            f"{OLLAMA_URL}/api/generate",
            json={
                "model": model,
                "prompt": prompt,
                "stream": False,
                "format": "json"
            }
        )
        response.raise_for_status()
        result = response.json()
        
        # Try to parse the JSON output
        try:
            triplets = json.loads(result.get("response", "[]"))
            logger.info(f"Extracted {len(triplets)} triplets from text.")
            return triplets
        except json.JSONDecodeError:
            logger.warning("Failed to parse triplets JSON from Ollama.")
            return []
            
    except Exception as e:
        logger.error(f"Failed to extract relationships via Ollama: {str(e)}")
        return []
