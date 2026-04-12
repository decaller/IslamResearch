# Search Page UX & Interface Design (The "Scholar UI")

The goal of the search interface is **Progressive Disclosure**. It must look as clean and approachable as Google for a casual user, but instantly unfold into a powerful academic workspace (like Al-Tadabbur) for advanced researchers.

Here is the visual layout and interaction design for the main Search Page.

---

## 1. Visual Layout Architecture

The screen is divided into 4 main zones to maximize vertical reading space while keeping powerful filters accessible.

### Zone 1: The Omni-Header (Top Fixed Bar)
*   **The Search Bar:** A large, prominent input field. Accepts Arabic (with/without Harakat), Indonesian, and English seamlessly.
*   **Mode Selector (Dropdown/Pills inside the bar):**
    *   ✨ AI Semantic (Default)
    *   🔤 Exact Word
    *   🌱 Root Explorer
*   **"Save Search" Button:** A subtle 🔖 icon next to the search button to save dynamic queries to Custom Collections.
*   **The Breadcrumb Trail (Below Search Bar):** The "Scholar's Journey" tracker.
    *   **Visual:** 🔍 "Zakat" ➔ 📖 Bukhari #142 ➔ 🌱 Root: ز ك و
    *   **Interaction:** Clickable nodes that instantly rewind the screen to previous research states.

### Zone 2: The Faceted Sidebar (Left - Collapsible)
Powered directly by Meilisearch facets. Updates instantly without page reloads.
*   **My Collections:** Quick access to saved items (e.g., "Ramadan Prep", "Thesis Ideas").
*   **Source Filter:** Checkboxes for Al-Quran, Hadith, Tafsir, Linguistics.
*   **Books:** Specific titles (Sahih Bukhari, Tafsir As-Sa'di).
*   **Extracted Tags (AI-Generated):** A dynamic list of tags highly relevant to the current search (e.g., #Mekkah, #Rukun_Islam). Clicking one forces an exact metadata match.
*   **Smart Contextual Filters:** Detects search intent or category and dynamically injects domain-specific filters. For example, if the user searches for a "Fiqh" topic (or selects the Fiqh category), the sidebar proactively suggests a "Madzhab" filter (Syafii, Hanafi, Maliki, Hanbali) to further refine the results.

### Zone 3: The Results Feed (Center Main)
This is where the search hits are displayed as interactive cards.
*   **Bilingual View:** Arabic text on the right (RTL), Indonesian translation on the left (LTR).
*   **Highlighting:** Search terms or semantically related words are highlighted in soft yellow.
*   **Gharib (Difficult) Words:** Underlined with a dotted line.

### Zone 4: The Intelligence Panel (Right - Sliding/Dual-Pane)
When a user clicks on a search result card, the screen splits (or a right panel slides in) to reveal the deep-dive tools without leaving the search page.
*   **The Anchor Text:** The full paragraph or Ayah being viewed.
*   **The 5-Tab System:** (Inspired by Al-Tadabbur)
    1.  **التدبر (Contemplations / Questions)**
    2.  **العمل (Actionable Habits / Checkboxes)**
    3.  **توجيه (Directives & Guidance)**
    4.  **معاني (Word Lexicon)**
    5.  **التفاسير (Exegesis / Syarh)**

---

## 2. Core Interactions & "Magic Moments"

To make the app feel incredibly smart, implement these specific UX interactions on the Result Cards (Zone 3).

### A. The "Every Word is a Portal" Interaction
*   **Trigger:** User clicks any Arabic word in a search result card.
*   **Action:** A contextual tooltip/popover appears natively.
*   **Display:** 
    *   The 3-letter Root (e.g., ع ل م).
    *   The grammatical role (Noun, Verb).
    *   **Button:** "Search this Root". Clicking this changes the main Omni-Bar to Root Explorer mode and instantly fetches all 10,000 texts derived from that root.

### B. The Relevance Tuning Slider (Crowdsourced AI)
*   **Trigger:** Hovering over a search result card reveals a subtle control bar at the bottom of the card.
*   **Visual:** A slider (0% - 100%) labeled "How relevant is this result?" alongside a 🚫 "Not Related" button.
*   **Action:** 
    *   If the user drags it below 20%, the card smoothly fades out (Opacity 1 -> 0, then `display: none`) and is hidden from their session.
    *   Behind the scenes, Laravel logs this in `user_feedbacks` for Admin review.

### C. Semantic Action Checkboxes
*   **Trigger:** The user opens the **العمل** (Actions) tab in the right-hand panel for a specific verse or Hadith.
*   **Action:** Checking the box triggers a confetti animation or a satisfying green checkmark.
*   **UI Update:** A subtle progress bar at the top of the sidebar updates (e.g., "Daily Actions Completed: 2/5").

### D. "Show Context" Expansion
*   **Trigger:** Clicking a subtle ⤢ **Expand Context** button on a search result card.
*   **Action:** The card smoothly expands vertically to reveal the 5 sentences before and after the exact hit (fetched from the `context_text` PostgreSQL field). This saves the user from having to open a completely new page just to read the surrounding paragraph.

---

## 3. Empty State UX (When the user first opens the app)

The search page shouldn't be a blank white screen before they type. It should invite exploration.
*   **"Continue Your Journey":** Displays the user's last 3 Breadcrumb trails.
*   **"Today's Action":** Pulls a random actionable item (from the `quranic_action` resource type) for daily inspiration.
*   **"Trending Roots":** Shows a word cloud of Arabic roots that other scholars are currently researching.

---

## 4. Mobile Responsiveness Considerations

A complex Dual-Panel UI is difficult on mobile.
*   **Sidebar:** The Left Faceted Sidebar moves into a "Filter" bottom-sheet (drawer) that slides up.
*   **Intelligence Panel:** The Right 5-Tab panel becomes a full-screen overlay that covers the search results when a specific card is tapped. The user can easily swipe it away to return to their search feed.
