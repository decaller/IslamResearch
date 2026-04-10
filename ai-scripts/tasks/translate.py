from prefect import task
import requests
import os

@task(retries=3, retry_delay_seconds=5)
def run_ollama(text: str):
    ollama_host = os.environ.get("OLLAMA_HOST", "http://ollama:11434")
    
    payload = {
        "model": "aya-23-8b",
        "prompt": f"Translate to formal Indonesian. Return ONLY the translation: {text}",
        "stream": False
    }
    
    # Call the local Ollama container
    response = requests.post(f"{ollama_host}/api/generate", json=payload)
    
    # If Ollama crashes, this triggers Prefect to wait 5 seconds and retry
    response.raise_for_status() 
    
    return response.json()['response'].strip()
