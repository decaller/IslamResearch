from prefect import task
import spacy
import re

# Load SpaCy model once when worker starts
try:
    nlp = spacy.load('ar_core_news_sm')
except:
    # Fallback if model not found (though Dockerfile should handle it)
    nlp = spacy.blank('ar')
    nlp.add_pipe('sentencizer')

@task
def clean_arabic(text: str):
    # 1. Strip Harakat using blazing-fast Regex
    # This covers Fatha, Kasra, Damma, Sukun, and Tanwin
    clean = re.sub(r'[\u0617-\u061A\u064B-\u0652]', '', text)
    
    # 2. Segment sentences using SpaCy
    doc = nlp(clean)
    
    # Return as a clean list of sentences
    return [sent.text for sent in doc.sents]
