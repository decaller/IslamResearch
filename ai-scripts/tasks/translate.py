from prefect import task
import requests
import os

@task(retries=3, retry_delay_seconds=5)
def run_ollama(text: str):
    # Limit parallelism to avoid overwhelming Ollama (Max 3 concurrent calls)
    from prefect.concurrency.sync import rate_limit
    rate_limit("ollama-calls", occupy=1, timeout_seconds=600)
    
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    
    payload = {
        "model": "aya-23-8b",
        "prompt": f"Translate to formal Indonesian. Return ONLY the translation: {text}",
        "stream": False
    }
    
    # Call the local Ollama container
    response = requests.post(f"{ollama_url}/api/generate", json=payload)
    
    # If Ollama crashes, this triggers Prefect to wait 5 seconds and retry
    response.raise_for_status() 
    
    return response.json()['response'].strip()
