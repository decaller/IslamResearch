from prefect import task, get_run_logger
import requests
import os

@task(retries=3, retry_delay_seconds=5)
def zero_shot(text: str, context_prev: str = "", context_next: str = "") -> list[str]:
    """
    Classify text using Ollama (Aya model) into MULTIPLE Taxonomy domains.
    """
    logger = get_run_logger()
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_TRANSLATE_MODEL", "aya:latest")

    context_block = f"PREVIOUS CONTEXT:\n{context_prev}\n\nTARGET TEXT:\n{text}\n\nFOLLOWING CONTEXT:\n{context_next}"

    # The 40 Level 1 and 2 Taxonomy Nodes
    valid_domains = [
        "Quranic Sciences", "Tafsir (Exegesis)", "Qira'at (Recitations)", "Asbab al-Nuzul (Occasions of Revelation)",
        "Hadith Sciences", "Mustalah al-Hadith (Hadith Terminology)", "Takhrij (Referencing Methodology)",
        "Jurisprudence", "Ibadat (Acts of Worship)", "Muamalat (Transactions & Commerce)", "Munakahat (Family Law)", 
        "Jinayat wa Hudud (Criminal Law)", "Usul al-Fiqh (Principles of Jurisprudence)", "Maqasid al-Shariah (Objectives of the Law)",
        "Creed & Theology", "Tawhid", "Al-Firaq wa al-Kalam (Sects & Scholastic Theology)",
        "Biography & History", "Seerah Nabawiyyah (Prophetic Biography)", "Tarikh al-Islam (Islamic History)", "Al-Tarajim wa al-Tabaqat (Biographical Dictionaries)",
        "Spirituality & Ethics", "Tazkiyah al-Nafs (Purification of the Soul)", "Akhlaq wa Adab (Ethics and Etiquette)", "Dhikr wa Dua (Remembrance and Supplication)",
        "Language & Linguistics", "Nahwu (Syntax & Grammar)", "Sarf (Morphology)", "Balaghah (Eloquent & Rhetoric)", "Fiqh al-Lughah wa al-Ma'ajim (Philology & Lexicons)",
        "Contemporary Issues", "Al-Iqtisad al-Islami (Islamic Finance & Economics)", "Al-Nawazil al-Tibbiyyah (Modern Medical Bioethics)", "Al-Siyasah al-Shar'iyyah (Political Thought & Governance)"
    ]

    prompt = (
        "You are an expert Islamic Scholar. Classify the TARGET TEXT into ONE OR MORE of these scholarly chapters: "
        f"{', '.join(valid_domains)}. "
        "Use the PREVIOUS and FOLLOWING context to be precise. "
        "Return the categories as a comma-separated list. Return ONLY the category names. "
        "If truly unknown, return 'Other'.\n\n"
        f"{context_block}"
    )

    payload = {
        "model": model,
        "prompt": prompt,
        "stream": False,
        "options": {"temperature": 0} 
    }

    import re
    from prefect.concurrency.sync import concurrency
    with concurrency("ollama-calls", occupy=1, timeout_seconds=600):
        response = requests.post(f"{ollama_url}/api/generate", json=payload)
        response.raise_for_status()
        raw_response = response.json().get("response", "Other").strip().strip("[]'\"")
        
        # Robust cleaning for multiple labels using Regex Word Boundaries
        found_categories = []
        for domain in valid_domains:
            # Use regex to find the domain as a distinct phrase/word
            # Escape domain for safety
            pattern = rf"\b{re.escape(domain)}\b"
            if re.search(pattern, raw_response, re.IGNORECASE):
                found_categories.append(domain)
        
    if not found_categories:
        # Fallback: if it mentioned "Fiqh" but the list says "Jurisprudence", 
        # let LLM be smart, but here we enforce taxonomy.
        found_categories = ["Other"]
        
    logger.info(f"Classified sentence into: {', '.join(found_categories)}")
    return found_categories
