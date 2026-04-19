import wikipediaapi
from prefect import task, get_run_logger

@task(retries=2, retry_delay_seconds=5)
def enrich_entity_from_wikipedia(canonical_name: str, language: str = 'en'):
    """
    Fetch summaries, canonical names, and community-curated tags from Wikipedia.
    """
    logger = get_run_logger()
    
    # 1. Initialize API with required User-Agent
    # Using a generic descriptive user agent for the platform
    wiki = wikipediaapi.Wikipedia(
        user_agent='ScholarAI/1.0 (scholar@example.com)',
        language=language
    )

    # 2. Fetch the page
    logger.info(f"Pinging Wikipedia API for '{canonical_name}' in language '{language}'")
    page = wiki.page(canonical_name)

    if not page.exists():
        logger.warning(f"Wikipedia page not found for '{canonical_name}'")
        return None

    # 3. Extract Core Data for the Database
    enriched_data = {
        "canonical_name": page.title,
        "wikipedia_url": page.fullurl,
        "summary": page.summary[0:500] + "..." if len(page.summary) > 500 else page.summary,
        "semantic_tags": [],
        "langlinks": {}
    }
    
    # 4. Extract Interlanguage Links (Multilingual Strategy)
    try:
        lang_links = page.langlinks
        if 'ar' in lang_links:
            enriched_data['langlinks']['ar'] = lang_links['ar'].fullurl
        if 'id' in lang_links:
            enriched_data['langlinks']['id'] = lang_links['id'].fullurl
    except Exception as e:
        logger.warning(f"Failed to fetch langlinks for '{canonical_name}': {str(e)}")
    
    # 5. Extract and Clean Categories (Noise Reduction)
    hidden_keywords = [
        "Articles", "CS1", "Wikipedia", "Webarchive", "Pages", 
        "Use dmy dates", "Use mdy dates", "All stub articles",
        "Short description", "Accuracy disputes", "Template", "Wikidata"
    ]
    
    for category_title in page.categories.keys():
        # Strip the "Category:" prefix
        clean_tag = category_title.replace("Category:", "")
        
        # Filter out administrative tags
        if not any(keyword in clean_tag for keyword in hidden_keywords):
            enriched_data["semantic_tags"].append(clean_tag)

    logger.info(f"Successfully enriched '{canonical_name}' from Wikipedia with {len(enriched_data['semantic_tags'])} tags.")
    return enriched_data
