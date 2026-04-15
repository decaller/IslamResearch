import os
import json
import requests
from prefect import task, get_run_logger


@task
def generate_scholarly_tags(text: str, context_prev: str = "", context_next: str = "") -> list[str]:
    """
    Generate scholarly tags for an Islamic text passage.

    Input:  Arabic text + surrounding context.
    Output: List of Arabic scholarly tags (3–5 per sentence).

    Tags are stored in Arabic to stay authentic to the source language.
    Example output: ["التفسير الموضوعي", "الفقه الحنبلي", "العقيدة"]
    """
    logger = get_run_logger()
    ollama_url = os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434")
    model = os.environ.get("OLLAMA_TRANSLATE_MODEL", "aya:latest")

    prompt = f"""أنت عالم إسلامي. حلّل النص الإسلامي الآتي وسياقه.
أنشئ من 3 إلى 5 وسوم علمية وصفية باللغة العربية تُعبّر عن موضوع النص.

السياق السابق: {context_prev}
النص المستهدف: {text}
السياق التالي: {context_next}

أمثلة على الوسوم: التوحيد، فقه الصلاة، تاريخ الإسلام، الأخلاق، علوم الحديث.

أعد مصفوفة JSON فقط من السلاسل النصية، لا تضف أي شرح.
"""

    payload = {
        "model": model,
        "prompt": prompt,
        "stream": False,
        "options": {"temperature": 0},
    }

    try:
        response = requests.post(f"{ollama_url}/api/generate", json=payload)
        response.raise_for_status()

        raw_output = response.json().get("response", "").strip()
        if "[" in raw_output and "]" in raw_output:
            start = raw_output.find("[")
            end = raw_output.find("]") + 1
            tags = json.loads(raw_output[start:end])
            logger.info(f"Generated {len(tags)} Arabic tags.")
            return tags

        return []
    except Exception as e:
        logger.warning(f"Tag generation failed: {e}")
        return []
