from prefect import flow, task, get_run_logger
from datetime import datetime, timedelta
from tasks import wiki_enrich, entity_resolution
from database import find_entity_by_name, create_entity, update_entity_enrichment_time, link_sentence_to_entity

@flow(name="Entity Enrichment & Auto-Building")
def enrich_entity_recursive(canonical_name: str, sentence_id: str = None, hop_count: int = 0, max_hops: int = 2):
    """
    Enrich an entity and autonomously discover connected concepts.
    Guardrails implemented:
    - Guardrail 1: Check last_enriched_at (30-day cache)
    - Guardrail 2: Max Hops limit (depth)
    """
    logger = get_run_logger()
    logger.info(f"🌿 Processing Entity: {canonical_name} (Hop: {hop_count})")

    # 1. Check database for existing entity
    existing = find_entity_by_name(canonical_name)
    
    # Guardrail 1: Time-based skip
    if existing and existing.get('last_enriched_at'):
        # Check if enriched within last 30 days
        last_enriched = existing['last_enriched_at']
        if datetime.now() - last_enriched < timedelta(days=30):
            logger.info(f"⏩ Skipping {canonical_name}: Enriched recently ({last_enriched})")
            
            # Even if skipped, ensure sentence is linked
            if sentence_id:
                link_sentence_to_entity(sentence_id, existing['id'])
            return

    # 2. Wikipedia Enrichment
    wikipedia_data = wiki_enrich.enrich_entity_from_wikipedia(canonical_name)
    if not wikipedia_data:
        return

    # 3. Persist Enrichment
    entity_id = None
    if existing:
        entity_id = existing['id']
        # Update existing entity details (Simplified update logic)
        # In a real app, you might want a separate update_entity task
        update_entity_enrichment_time(entity_id)
    else:
        # Create new entity
        wikipedia_data['last_enriched_at'] = datetime.now()
        entity_id = create_entity(wikipedia_data)

    # Link to source sentence if provided
    if sentence_id:
        link_sentence_to_entity(sentence_id, entity_id)

    # Guardrail 2: Maximum Depth Limit (Max Hops)
    if hop_count >= max_hops:
        logger.info(f"🛑 Max depth reached for {canonical_name}. Stopping autonomous build.")
        return

    # 4. The Autonomous Step (Recursive Queuing)
    # Extract categories from Wikipedia tags and treat them as potential new entities
    discovered_tags = wikipedia_data.get('semantic_tags', [])
    for tag_name in discovered_tags:
        # Recursive call for each connected concept
        # Note: In a production Prefect environment, this might be a background sub-flow
        enrich_entity_recursive(
            canonical_name=tag_name,
            sentence_id=None, # Discovered tags aren't linked to the original sentence directly
            hop_count=hop_count + 1,
            max_hops=max_hops
        )

if __name__ == "__main__":
    import os
    enrich_entity_recursive(os.environ.get("ENTITY_NAME", "Umar"))
