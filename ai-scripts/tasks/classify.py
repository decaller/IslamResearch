from prefect import task, get_run_logger
import requests
import os
import re

# ─── Arabic Taxonomy (Single Source of Truth) ────────────────────────────────
# All classification output is stored and queried in Arabic.
# UI display can translate these labels client-side if needed.
VALID_DOMAINS_AR = [
    # علوم القرآن
    "علوم القرآن", "التفسير", "القراءات", "أسباب النزول",
    # علوم الحديث
    "علوم الحديث", "مصطلح الحديث", "التخريج",
    # الفقه
    "الفقه", "العبادات", "المعاملات", "المناكحات",
    "الجنايات والحدود", "أصول الفقه", "مقاصد الشريعة",
    # العقيدة
    "العقيدة والكلام", "التوحيد", "الفرق والكلام",
    # السيرة والتاريخ
    "السيرة والتاريخ", "السيرة النبوية", "تاريخ الإسلام", "التراجم والطبقات",
    # الروحانيات
    "الروحانيات والأخلاق", "تزكية النفس", "الأخلاق والآداب", "الذكر والدعاء",
    # اللغة
    "اللغة واللسانيات", "النحو", "الصرف", "البلاغة", "فقه اللغة والمعاجم",
    # القضايا المعاصرة
    "القضايا المعاصرة", "الاقتصاد الإسلامي", "النوازل الطبية", "السياسة الشرعية",
]

# Mapping for display/legacy support: English → Arabic
EN_TO_AR_DOMAIN: dict[str, str] = {
    "Quranic Sciences": "علوم القرآن",
    "Tafsir (Exegesis)": "التفسير",
    "Qira'at (Recitations)": "القراءات",
    "Asbab al-Nuzul (Occasions of Revelation)": "أسباب النزول",
    "Hadith Sciences": "علوم الحديث",
    "Mustalah al-Hadith (Hadith Terminology)": "مصطلح الحديث",
    "Takhrij (Referencing Methodology)": "التخريج",
    "Jurisprudence": "الفقه",
    "Ibadat (Acts of Worship)": "العبادات",
    "Muamalat (Transactions & Commerce)": "المعاملات",
    "Munakahat (Family Law)": "المناكحات",
    "Jinayat wa Hudud (Criminal Law)": "الجنايات والحدود",
    "Usul al-Fiqh (Principles of Jurisprudence)": "أصول الفقه",
    "Maqasid al-Shariah (Objectives of the Law)": "مقاصد الشريعة",
    "Creed & Theology": "العقيدة والكلام",
    "Tawhid": "التوحيد",
    "Al-Firaq wa al-Kalam (Sects & Scholastic Theology)": "الفرق والكلام",
    "Biography & History": "السيرة والتاريخ",
    "Seerah Nabawiyyah (Prophetic Biography)": "السيرة النبوية",
    "Tarikh al-Islam (Islamic History)": "تاريخ الإسلام",
    "Al-Tarajim wa al-Tabaqat (Biographical Dictionaries)": "التراجم والطبقات",
    "Spirituality & Ethics": "الروحانيات والأخلاق",
    "Tazkiyah al-Nafs (Purification of the Soul)": "تزكية النفس",
    "Akhlaq wa Adab (Ethics and Etiquette)": "الأخلاق والآداب",
    "Dhikr wa Dua (Remembrance and Supplication)": "الذكر والدعاء",
    "Language & Linguistics": "اللغة واللسانيات",
    "Nahwu (Syntax & Grammar)": "النحو",
    "Sarf (Morphology)": "الصرف",
    "Balaghah (Eloquent & Rhetoric)": "البلاغة",
    "Fiqh al-Lughah wa al-Ma'ajim (Philology & Lexicons)": "فقه اللغة والمعاجم",
    "Contemporary Issues": "القضايا المعاصرة",
    "Al-Iqtisad al-Islami (Islamic Finance & Economics)": "الاقتصاد الإسلامي",
    "Al-Nawazil al-Tibbiyyah (Modern Medical Bioethics)": "النوازل الطبية",
    "Al-Siyasah al-Shar'iyyah (Political Thought & Governance)": "السياسة الشرعية",
    "Other": "أخرى",
}


@task(retries=3, retry_delay_seconds=5)
def zero_shot(text: str, context_prev: str = "", context_next: str = "") -> list[str]:
    """
    Classify Islamic text into taxonomy domains.

    Input:  Arabic text + context window.
    Output: List of Arabic domain labels from VALID_DOMAINS_AR.

    The LLM is prompted entirely in Arabic to maximise classification accuracy.
    All stored categories are in Arabic; UI layers may translate for display.
    """
    logger = get_run_logger()
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_TRANSLATE_MODEL", "aya:latest")

    context_block = (
        f"السياق السابق:\n{context_prev}\n\n"
        f"النص المستهدف:\n{text}\n\n"
        f"السياق التالي:\n{context_next}"
    )

    domains_list = "، ".join(VALID_DOMAINS_AR)

    prompt = (
        "أنت عالم إسلامي متخصص. صنّف النص المستهدف في واحد أو أكثر من الأقسام العلمية الآتية:\n"
        f"{domains_list}.\n"
        "استخدم السياق السابق والتالي لتكون دقيقاً. "
        "أعد الأقسام فقط مفصولةً بفاصلة. لا تضف أي شرح. "
        "إن كان التصنيف مجهولاً تماماً، أعد: أخرى.\n\n"
        f"{context_block}"
    )

    payload = {
        "model": model,
        "prompt": prompt,
        "stream": False,
        "options": {"temperature": 0},
    }

    from prefect.concurrency.sync import concurrency
    with concurrency("ollama-calls", occupy=1, timeout_seconds=600):
        response = requests.post(f"{ollama_url}/api/generate", json=payload)
        response.raise_for_status()
        raw_response = response.json().get("response", "أخرى").strip().strip("[]'\"")

        # Match against valid Arabic domain labels
        found_categories = [
            domain for domain in VALID_DOMAINS_AR
            if re.search(re.escape(domain), raw_response)
        ]

    if not found_categories:
        found_categories = ["أخرى"]

    logger.info(f"Classified into: {', '.join(found_categories)}")
    return found_categories
