from prefect import task

_classifier = None

def get_classifier():
    global _classifier
    if _classifier is None:
        from transformers import pipeline
        _classifier = pipeline("zero-shot-classification", model="MoritzLaurer/xlm-v-base-mnli-xnli")
    return _classifier

@task
def zero_shot(text: str):
    categories = ["Fiqh", "Aqidah", "Sirah", "Tafsir"]
    
    classifier = get_classifier()
    result = classifier(text, categories)
    
    # Fallback Logic: Only accept if the AI is >60% confident
    if result["scores"][0] > 0.60:
        return result["labels"][0]
        
    return "Requires Review" # Sends to Filament waiting room
