# Mockup Features & Implementation Roadmap

This document summarizes the UI/UX features identified in the high-fidelity mockups located in `docs/mockup/stitch_islamresearch_scholarly_ide/`. It serves as a breakdown of the visual and functional goals for the IslamResearch platform.

---

## 📂 Mockup Inventory

There are 29 specialized mockup components organized into functional groups:

### 1. 🏛️ Product Vision & Introduction
*   **Deep Dive & Vision**: (`about_product_deep_dive`, `about_vision_narrative`) High-impact storytelling pages explaining the "Why" behind the platform.
*   **Scholarly Focus**: (`about_scholarly_focus`) Dedicated landing page for academic and traditional scholars.
*   **Landing Pages**: (`homepage_high_impact_marketing`, `homepage_technical_focus`) Targeted marketing pages for different user personas.

### 2. 💻 Professional Scholar IDE
*   **Core Workspace**: (`research_workspace`, `scholarly_dashboard`) The primary multi-pane environment for deep study.
*   **Navigation & Explorer**: 
    *   `sidebar_file_system_explorer`: Traditional file/corpus tree.
    *   `sidebar_temporal_history_tree`: Chronological "Time Machine" view of research steps.
    *   `sidebar_unified_command_center`: Single entry point for all IDE actions.
*   **Views & Layouts**:
    *   `ide_tabbed_session_view`: VS Code style tab management for open corpora.
    *   `ide_tree_explorer_view`: Hierarchical navigation of Islamic science taxonomies.
    *   `ide_path_tracing_view`: Visual breadcrumbs of how a researcher arrived at a specific conclusion.

### 3. 🔍 Advanced Search & Discovery
*   **Magic Search**: (`magic_search_entry`) A centralized, AI-powered "Natural Language" search bar (CMD+K style).
*   **Discovery**: (`knowledge_tree_search_results`) Visual exploration of search results branching into related topics.

### 4. 🛠️ Specialized Research Tools
*   **Hadith Analysis**: (`hadith_isnad_graph`) Interactive graph visualization of Hadith chains (Isnad).
*   **Linguistic Suite**: 
    *   `lexicon_comparison_matrix`: Comparative view of multiple classical Arabic lexicons.
    *   `linguistic_analysis`: Deep morphological and semantic breakdown of verses/text.
*   **Content Creation**:
    *   `article_drafting_suite`: Integrated markdown editor for writing scholarly papers.
    *   `presentation_deck_builder`: System to turn research findings into presentable slides.
*   **Flow Tracking**: (`research_journey`) A "Google Maps" for your mind, tracking the evolving research path.

### 5. 📚 Corpus & Knowledge Hub (Scholarly Wiki)
*   **Corpus Hub**: (`wiki_scholarly_corpus_hub`) Centralized landing for all digital manuscripts and books.
*   **Categorization**: (`wiki_category_hub_jurisprudence`) Domain-specific hubs (e.g., Fiqh, Aqidah).
*   **Entity Mapping**: (`wiki_tag_entity_hub_taqwa`) Concept-based pages (e.g., "Taqwa") aggregating all related data.
*   **Obsidian Integration**: (`obsidian`) Personal knowledge management (PKM) style connections.

### 6. 🤝 Social & Collaborative Features
*   **Collections**: (`collection_manager`) Managing personal/public research sets.
*   **Social Feed**: (`social_collections_feed`) A feed of shared research and collections from the community.
*   **Network**: (`homepage_community_network`) Connecting scholars and students globally.

### 7. 🎨 Design System
*   **Brand Identity**: (`my_design_system`) Central repository for tokens, colors (dark-mode focused), and Islamic-inspired geometry/aesthetics.

---

## 📝 Implementation TODO List

The following list identifies missing or incomplete features based on the mockups.

### 🔳 Phase 1: IDE Enhancements
- [ ] **Temporal History Tree**: Implement the `sidebar_temporal_history_tree` to track user navigation history as a tree.
- [ ] **Unified Command Center**: Build the `sidebar_unified_command_center` as a centralized "Quick Action" palette.
- [ ] **Path Tracing**: Implement visual breadcrumbs/meta-paths for active research sessions.

### 🔳 Phase 2: Specialized Visualizations
- [ ] **Isnad Graph**: Develop the D3.js or Cytoscape.js component for `hadith_isnad_graph`.
- [ ] **Lexicon Matrix**: Build the dense comparison grid for classical dictionaries.
- [ ] **Knowledge Tree**: Create the branching search results view for exploration.

### 🔳 Phase 3: Content Creation Tools
- [ ] **Markdown Drafting Suite**: Create the `article_drafting_suite` with split-pane research references.
- [ ] **Deck Builder**: Basic implementation of exporting research snippets to a slide format.
- [ ] **Personal Wiki/PKM**: Implement the concept of "Entity Hubs" (Tags) acting as auto-generated wiki pages.

### 🔳 Phase 4: Social & Community
- [ ] **Social Feed**: Build the global "Research Feed" where scholars can share "Journeys".
- [ ] **Collection Sharing**: Implement public/private visibility and forking of collections.

### 🔳 Phase 5: Polish & Marketing
- [ ] **Marketing Pages**: Port the `homepage_high_impact_marketing` and `about_*` mockups into the main application.
- [ ] **Design System Sync**: Audit the current `app.css` and DaisyUI config to ensure 100% parity with `my_design_system`.
