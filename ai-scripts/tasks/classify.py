from prefect import task
from transformers import pipeline

# Loaded once globally into RAM
classifier = pipeline("zero-shot-classification", model="MoritzLaurer/xlm-v-base-mnli-xnli")

@task
def zero_shot(text: str):
    categories = ["Fiqh", "Aqidah", "Sirah", "Tafsir"]
    
    result = classifier(text, categories)
    
    # Fallback Logic: Only accept if the AI is >60% confident
    if result["scores"][0] > 0.60:
        return result["labels"][0]
        
    return "Requires Review" # Sends to Filament waiting room
