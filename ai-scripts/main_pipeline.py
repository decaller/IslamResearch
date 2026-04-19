import os
import httpx
from prefect import flow, get_run_logger
from prefect.artifacts import create_markdown_artifact
from tasks import classify, translate, transliterate, vectorize, tags, ner
from flows.knowledge_graph import enrich_entity_recursive
from database import fetch_job_details, save_to_db, save_transliteration

@flow(name="Islamic Text Enrichment")
def process_single_job(sentence_job_id: str, lang: str = "id", scheme: str = "ala_lc"):
    logger = get_run_logger()
    logger.info(f"🚀 INCOMING PARAMETERS: JobID={sentence_job_id}, Lang={lang}, Scheme={scheme}")
    logger.info(f"🚀 Starting AI Enrichment flow (Language: {lang})")

    # 1. Fetch Job Details (5-Verse Window)
    job = fetch_job_details(sentence_job_id)
    if not job:
        logger.error(f"❌ Job {sentence_job_id} not found in database.")
        return

    sentence_id = job['sentence_id']
    raw_text = job['content']
    context_prev = "\n".join(job.get('context_prev', []))
    context_next = "\n".join(job.get('context_next', []))
    
    try:
        # 2. Logic: Only run what is requested in the job status
        category = "Other"
        translation = None
        tl_result = None
        vectors = {}
        ner_tags = []

        # A. Hybrid Tagging (Token Classification + LLM Thematic)
        categories = classify.zero_shot(raw_text, context_prev, context_next)
        
        # 1. NER Entities (Proper Names, Locations)
        found_entities = ner.extract_entities(raw_text)
        
        # 2. LLM Thematic Tags (Fiqh, Aqidah, etc.) using context
        thematic_tags = tags.generate_scholarly_tags(raw_text, context_prev, context_next)
        
        # Combine unique tags
        ner_tags = list(set(found_entities + thematic_tags))

        # B. Translation (Using 5-verse context for better flow)
        if job['needs_translation']:
            # We combine context into the prompt for the LLM
            context_prompt = f"CONTEXT PREV:\n{context_prev}\n\nTARGET:\n{raw_text}\n\nCONTEXT NEXT:\n{context_next}"
            translation = translate.run_ollama(context_prompt, target_lang=lang)
        
        # C. Transliteration
        if job['needs_transliteration']:
            tl_result = transliterate.run_ollama(raw_text, scheme=scheme)
        
        # D. Vectorization
        if job['needs_embedding']:
            vectors = vectorize.create_embeddings(arabic_text=raw_text, translation_text=translation or "")

        # 3. Persist results
        save_to_db(
            sentence_id=sentence_id,
            categories=categories,
            translation=translation or "",
            vectors=vectors,
            needs_emb=job['needs_embedding'],
            needs_trans=job['needs_translation'],
            lang=lang,
            tags=ner_tags,
            existing_metadata=job.get('sentence_metadata')
        )

        # E. Knowledge Graph Integration (Recursive Enrichment)
        for entity_tag in found_entities:
            # entities are in format "TYPE:Name", split them
            if ":" in entity_tag:
                entity_name = entity_tag.split(":", 1)[1]
                enrich_entity_recursive(canonical_name=entity_name, sentence_id=sentence_id)

        if tl_result:
            save_transliteration(
                sentence_id=sentence_id,
                scheme=tl_result['scheme'],
                transliteration_text=tl_result['transliteration_text'],
            )

        # 4. Create Detailed Markdown Artifact
        artifact_content = f"""
# 📜 Scholarly AI Enrichment Report
**Sentence ID:** `{sentence_id}`
**Job ID:** `{sentence_job_id}`

## 🔍 Context Window
- **Previous:** {context_prev or '_None_'}
- **Target (Arabic):** `{raw_text}`
- **Following:** {context_next or '_None_'}

## 🕌 Scholarly Categorization
- **Domains/Chapters:** {', '.join([f'`{c}`' for c in categories])}
- **Scholar Tags:** {', '.join([f'#{t}' for t in ner_tags])}

## 🌍 Multilingual Results
- **Translation ({lang}):** {translation or '_Skipped_'}
- **Transliteration ({scheme}):** {tl_result.get('transliteration_text') if tl_result else '_Skipped_'}
        """
        create_markdown_artifact(
            key=f"enrichment-{sentence_job_id}",
            markdown=artifact_content,
            description=f"AI Enrichment for Sentence {sentence_id}"
        )

        # 5. Notify Laravel Success
        _notify_laravel(sentence_job_id, "completed")
        logger.info(f"✅ Enriched sentence {sentence_id} (categories & tags in Arabic). Alhamdulillah.")

    except Exception as e:
        logger.error(f"❌ Failed to process job {sentence_job_id}: {str(e)}")
        _notify_laravel(sentence_job_id, "failed", str(e))
        raise e

def _notify_laravel(job_id: str, status: str, error: str = None) -> None:
    webhook_url = os.environ.get(
        'LARAVEL_WEBHOOK_URL',
        'http://localhost:8000/api/webhooks/prefect/job-completed',
    )
    payload = {
        "sentence_job_id": job_id,
        "status": status,
        "error": error
    }
    try:
        httpx.post(webhook_url, json=payload, headers={"Accept": "application/json"}, timeout=10.0)
    except Exception:
        pass

if __name__ == "__main__":
    process_single_job(os.environ.get("JOB_ID", ""))
