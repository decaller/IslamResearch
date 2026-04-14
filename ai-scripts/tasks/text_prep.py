from prefect import task
import spacy
import re

from camel_tools.morphology.database import MorphologyDB
from camel_tools.morphology.analyzer import Analyzer
from camel_tools.utils.dediac import dediac_ar

# Load SpaCy Arabic blank model with sentencizer
try:
    nlp = spacy.blank('ar')
    nlp.add_pipe('sentencizer')
except Exception:
    nlp = None

_camel_analyzer = None

def get_camel_analyzer():
    global _camel_analyzer
    if _camel_analyzer is None:
        try:
            db = MorphologyDB.builtin_db()
            _camel_analyzer = Analyzer(db)
        except Exception:
            _camel_analyzer = None
    return _camel_analyzer


@task
def clean_arabic(text: str) -> list[str]:
    """Strip Harakat and segment text into sentences / verses."""
    # 1. Strip Harakat
    clean = re.sub(r'[\u0617-\u061A\u064B-\u0652]', '', text)

    # 2. Support for manual delimiters: Splitting by newlines or verse marker (۝)
    # This ensures Quranic verses stay separate if they were on separate lines.
    manual_splits = re.split(r'[\n\r\u06dd\u06de]+', clean)
    
    all_sentences = []
    for snippet in manual_splits:
        snippet = snippet.strip()
        if not snippet:
            continue
            
        # 3. Use SpaCy for natural sentence boundary detection within the snippet
        doc = nlp(snippet)
        all_sentences.extend([sent.text.strip() for sent in doc.sents if sent.text.strip()])

    return all_sentences


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

        analyzer = get_camel_analyzer()
        if analyzer is not None:
            try:
                analyses = analyzer.analyze(word)
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
