import os
import json
import requests
from prefect import task

@task
def generate_scholarly_tags(text: str, context_prev: str = "", context_next: str = "") -> list:
    """Generate scholarly tags based on the sentence and its surrounding context."""
    
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_TRANSLATE_MODEL", "aya:latest")

    prompt = f"""
    Analyze the following Islamic text and its context. 
    Generate 3 to 5 descriptive scholarly tags in Indonesian (Topic/Category).
    
    PREVIOUS CONTEXT: {context_prev}
    TARGET TEXT: {text}
    NEXT CONTEXT: {context_next}
    
    Example tags: Akidah, Fikih Ibadah, Sejarah Islam, Akhlak, Hadis.
    
    Return ONLY a JSON array of strings.
    """
    
    payload = {
        "model": model,
        "prompt": prompt,
        "stream": False,
        "options": {"temperature": 0}
    }

    try:
        response = requests.post(f"{ollama_url}/api/generate", json=payload)
        response.raise_for_status()
        
        # Simple extraction logic
        raw_output = response.json().get("response", "").strip()
        if "[" in raw_output and "]" in raw_output:
            start = raw_output.find("[")
            end = raw_output.find("]") + 1
            return json.loads(raw_output[start:end])
        
        return []
    except Exception:
        return []
