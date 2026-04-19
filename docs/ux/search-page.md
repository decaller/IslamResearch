# Search Page UX & Interface Design (The "Scholar UI")

The goal of the search interface is **Progressive Disclosure**. It must look as clean and approachable as Google for a casual user, but instantly unfold into a powerful academic workspace (like Al-Tadabbur) for advanced researchers. All components are implemented with **daisyUI v5** (Tailwind v4 plugin) for semantic, theme-aware elements.

Here is the visual layout and interaction design for the main Search Page.

---

## 1. Visual Layout Architecture

The screen is divided into 4 main zones to maximize vertical reading space while keeping powerful filters accessible.

### Zone 1: The Omni-Header (Top Fixed Bar)
*Implemented with daisyUI **`navbar`** as the outermost shell.*
*   **The Search Bar:** A daisyUI **`input input-lg`** field. Accepts Arabic (with/without Harakat), Indonesian, and English seamlessly.
*   **Mode Selector:** A daisyUI **`dropdown`** component (pills/options inside the bar):
    *   ✨ AI Semantic (Default)
    *   🔤 Exact Word
    *   🌱 Root Explorer
*   **"Save Search" Button:** A daisyUI **`btn btn-ghost`** with a 🔖 icon, using a **`swap`** component to toggle its saved state.
*   **The Breadcrumb Trail (Below Search Bar):** Rendered with daisyUI **`breadcrumbs`** + `kbd` chips for journey nodes.
    *   **Visual:** 🔍 "Zakat" ➔ 📖 Bukhari #142 ➔ 🌱 Root: ز ك و
    *   **Interaction:** Clickable nodes that instantly rewind the screen to previous research states.

### Zone 2: The Faceted Sidebar (Left - Collapsible)
*Implemented with daisyUI **`drawer`** (desktop: open persistent, mobile: overlay). Inner navigation uses **`menu`** + **`collapse`** for accordion sections and **`checkbox`** for multi-select.*
Powered directly by Meilisearch facets. Updates instantly without page reloads.
*   **My Collections:** Quick access via a **`menu`** section to saved items (e.g., "Ramadan Prep", "Thesis Ideas").
*   **Source Filter:** daisyUI **`checkbox`** items for Al-Quran, Hadith, Tafsir, Linguistics.
*   **Books:** Specific titles inside a **`collapse`** (accordion) group.
*   **Extracted Tags (AI-Generated):** daisyUI **`badge`** chips list, highly relevant to the current search (e.g., #Mekkah, #Rukun_Islam). Clicking forces an exact metadata match.
*   **Smart Contextual Filters:** Detects search intent and dynamically injects domain-specific filter sections. For example, if the user searches for a "Fiqh" topic, the sidebar proactively suggests a **`collapse`**-wrapped "Madzhab" filter.

### Zone 3: The Results Feed: The "Knowledge Tree"
*Implemented with daisyUI **`collapse`** (accordions) for clusters and **`card`** + **`card-body`** for individual hits. While results are loading, **`skeleton`** placeholder clusters fill the space.*

Instead of an endless flat list, the center pane synthesizes findings into a deterministic **Knowledge Tree**.
*   **The Branches (Clusters):** daisyUI **`collapse-arrow`** containers group hits by source (e.g., "Quran: Surah Al-Baqarah", "Fiqh: Fasting").
*   **Cluster Summary:** Each branch shows a `badge` list of prominent Knowledge Graph entities (tags) shared by the group (e.g., #Safar, #Qada).
*   **Semantic Snipering (The Sniper):** Each result Card displays a surgically extracted 8-word fragment (`sniped_text`) wrapped in `<mark>` tags using the **Semantic Sliding Window** re-ranker. This ensures the user sees exactly why the result matched, even in 1000-word Hadiths.
*   **Bilingual Reading:** Arabic text (RTL) and Indonesian translation (LTR) inside a responsive card grid with `@container` queries.
*   **Highlighting:** Search terms highlighted using soft gold `<mark>` tags. Difficult words trigger daisyUI **`tooltip`** hints.

### Zone 4: The Intelligence Panel (Right - Sliding/Dual-Pane)
*Desktop: panel slides in as a resizable split pane. Mobile: daisyUI **`drawer`** from the bottom or a **`modal`** full-screen overlay.*
When a user clicks on a search result card, the screen splits to reveal the deep-dive tools.
*   **The Anchor Text:** The full paragraph or Ayah being viewed.
*   **The 5-Tab System:** Built with daisyUI **`tabs`** + **`tab`** + **`tab-content`**:
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
*   **Action:** A daisyUI **`tooltip`** / popover appears natively (styled via `[data-tip]`).
*   **Display:** 
    *   The 3-letter Root (e.g., ع ل م).
    *   The grammatical role (Noun, Verb).
    *   **Button:** A daisyUI **`btn btn-sm btn-primary`** — "Search this Root". Clicking changes the Omni-Bar to Root Explorer mode.

### B. The Relevance Tuning Slider (Crowdsourced AI)
*   **Trigger:** Hovering over a search result card reveals a subtle control bar at the bottom of the card.
*   **Visual:** A daisyUI **`range`** slider (0%–100%) labeled "How relevant is this result?" alongside a **`btn btn-ghost btn-xs`** 🚫 "Not Related" button.
*   **Action:** 
    *   If the user drags it below 20%, the card smoothly fades out (Opacity 1 → 0, then `display: none`) and is hidden from their session.
    *   Behind the scenes, Laravel logs this in `user_feedbacks` for Admin review.

### C. Semantic Action Checkboxes
*   **Trigger:** The user opens the **العمل** (Actions) tab in the right-hand panel for a specific verse or Hadith.
*   **Action:** Checking the daisyUI **`checkbox checkbox-success`** triggers a confetti animation or a satisfying green checkmark.
*   **UI Update:** A daisyUI **`progress`** bar at the top of the sidebar updates (e.g., "Daily Actions Completed: 2/5").

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
*   **Sidebar:** The Left Faceted Sidebar renders as a daisyUI **`drawer`** (bottom-sheet pattern), triggered by a floating **`btn`**.
*   **Intelligence Panel:** The Right 5-Tab panel becomes a daisyUI **`modal modal-bottom`** full-screen overlay when a card is tapped. The user can swipe or tap ✕ to dismiss and return to the search feed.
