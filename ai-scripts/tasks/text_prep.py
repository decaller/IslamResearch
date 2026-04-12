from prefect import task
import spacy
import re

from camel_tools.morphology.analyzer import Analyzer
from camel_tools.utils.dediac import dediac_ar

# Load SpaCy model once when worker starts
try:
    nlp = spacy.load('ar_core_news_sm')
except Exception:
    # Fallback if model not found (though Dockerfile should handle it)
    nlp = spacy.blank('ar')
    nlp.add_pipe('sentencizer')

# Load CAMeL Morphological Analyzer once at module level for performance
try:
    _camel_analyzer = Analyzer.builtin_analyzer()
except Exception:
    _camel_analyzer = None


@task
def clean_arabic(text: str) -> list[str]:
    """Strip Harakat using Regex, then segment sentences using SpaCy."""
    # 1. Strip Harakat: Fatha, Kasra, Damma, Sukun, and Tanwin
    clean = re.sub(r'[\u0617-\u061A\u064B-\u0652]', '', text)

    # 2. Segment sentences using SpaCy SBD
    doc = nlp(clean)

    # Return as a clean list of sentence strings
    return [sent.text.strip() for sent in doc.sents if sent.text.strip()]


@task
def extract_roots(sentences: list[str]) -> list[dict]:
    """
    Run CAMeL Tools morphological analysis on each sentence to extract
    Arabic roots (Jidhr) and word surface forms for the Global Lexicon.

    Returns a list of dicts, one per sentence:
      {
        "sentence": str,
        "lexicon_data": [
          {"word_raw": str, "word_clean": str, "root": str | None},
          ...
        ]
      }
    """
    results = []

    for sentence in sentences:
        lexicon_data = _process_arabic_sentence(sentence)
        results.append({
            "sentence": sentence,
            "lexicon_data": lexicon_data,
        })

    return results


def _process_arabic_sentence(sentence_text: str) -> list[dict]:
    """
    Internal helper: analyze each word in a sentence with CAMeL Tools
    and return structured morphological data.

    See: docs/lexicon-strategy.md §3
    """
    words = sentence_text.split()
    lexicon_data = []

    for word in words:
        word_raw = word
        word_clean = dediac_ar(word)
        root = None

        if _camel_analyzer is not None:
            try:
                analyses = _camel_analyzer.analyze(word)
                if analyses:
                    # Take the highest-ranked morphological analysis
                    root = analyses[0].get('root')
            except Exception:
                # Gracefully skip unknown or malformed tokens
                pass

        lexicon_data.append({
            "word_raw": word_raw,    # e.g., يُؤْمِنُونَ (with Harakat)
            "word_clean": word_clean,  # e.g., يؤمنون (without Harakat)
            "root": root,              # e.g., أ م ن
        })

    return lexicon_data
