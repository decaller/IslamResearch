# UX Architecture: Page Types & Explorability

In the "Scholar UI", a search result is just the beginning. The platform features dedicated page types for Sentences, Words (Lexicons), Categories, and Tags.

The golden rule of this UX is **Infinite Explorability**: Every entity on the screen must be a clickable portal that allows the user to traverse the database laterally (across disciplines) or vertically (deep into linguistics).

---

## 1. The Sentence Page (The "Deep Read" UX)
**URL Pattern:** `/read/item/{uuid}`  
**Primary Goal:** To provide absolute context and Al-Tadabbur-style deep exegesis without distraction.

### The Explorability Features:
*   **The Infinite Scroll (Vertical Context):** The user doesn't just see the single sentence/verse they clicked. The page automatically renders the sentences before and after it. Scrolling up or down seamlessly loads the rest of the book/chapter.
*   **The Dual-Pane Exegesis (Horizontal Context):** The right side shows the text. The left side holds the 5-Tab system (Tafsir, Actions, Directives, Lexicon).
*   **Explorability Hook:** When reading the 5-Tab panel, any related Hadith or Ayah mentioned in the Tafsir is rendered as a hoverable "Preview Card". The user can peek at the referenced text without leaving their current page.

---

## 2. The Lexicon / Root Page (The "Linguistic" UX)
**URL Pattern:** `/lexicon/root/{root_id}` (e.g., `/lexicon/root/ع-ل-م`)  
**Primary Goal:** To act as a dynamic, AI-powered dictionary that shows how a single root concept evolves across the entire Islamic corpus.

### The Explorability Features:
*   **The Derivation Tree (Visual Hook):** The top of the page features a visual "Word Cloud" or tree showing all surface words derived from the root (e.g., عِلْم, يَعْلَمُونَ, عَالِم). Clicking a branch instantly filters the page results to that specific grammatical form.
*   **Corpus Distribution Chart:** A beautiful bar chart showing where this root is used (e.g., "Used 854 times in the Quran, 200 times in Bukhari, 50 times in Lisan al-Arab"). Clicking a bar filters the feed to that specific book.
*   **Explorability Hook: Semantic Synonyms.** At the bottom of the root page, the AI suggests "Related Roots" based on vector similarity (e.g., if looking at "Wealth" م-و-ل, it suggests "Gold" ذ-ه-ب). This allows scholars to jump between linguistically distinct but conceptually identical roots.

---

## 3. The Category Page (The "Library Aisle" UX)
**URL Pattern:** `/category/{category_slug}` (e.g., `/category/fiqh-muamalah`)  
**Primary Goal:** To provide highly structured, linear reading for users who want to study a discipline systematically.

### The Explorability Features:
*   **The Breadcrumb Hierarchy (Top):** Clearly displays *Fiqh ➔ Muamalah ➔ Debt*. The user can click 'Muamalah' to instantly step up one level and see a broader view.
*   **Sub-Chapter Cards (Visual Navigation):** Instead of a dry list of texts, the top of the page features large, clickable cards for all child categories (e.g., "Rules of Interest", "Rules of Trade").
*   **Explorability Hook: "Recommended Next".** Because categories are structural, the UX tracks the user's reading progress. If they finish reading the texts in the "Zakat" category, the UI smoothly suggests jumping to the "Fasting" category, replicating the natural flow of a classical textbook.

---

## 4. The Tag Page (The "Concept Hub" UX)
**URL Pattern:** `/tag/{tag_slug}` (e.g., `/tag/prophet-muhammad`)  
**Primary Goal:** To break the walls between traditional disciplines and show how a single concept permeates all of Islamic literature.

### The Explorability Features:
*   **The Cross-Disciplinary Feed:** Unlike a Category page, the Tag page feed is "chaotic" by design. A user sees a Quranic verse, followed by a Sirah event, followed by a Fiqh ruling—all unified by the tag `#Prophet_Muhammad`.
*   **Filter by Facet:** A sticky left-sidebar allows the user to tame the feed. They can check a box to say, "Only show me #Prophet_Muhammad intersecting with the Tafsir Category."
*   **Explorability Hook: "Co-occurring Tags" (The Wikipedia Effect).** On the right sidebar, the UI lists tags that frequently appear alongside the current tag (e.g., If browsing `#Battle_of_Badr`, the UI suggests `#Angels`, `#Abu_Jahl`, `#Ramadan`). This encourages the user to continuously click laterally through historical and conceptual links.

---

## 5. Universal UX Rules (What is the SAME?)

To ensure the user never feels lost while exploring these wildly different page types, three UX rules apply universally:

1.  **The "Save to Collection" Button:** Whether the user is looking at a Sentence, a Lexicon Root, a Tag, or a Category, there is always a ubiquitous 🔖 icon allowing them to save that specific entity to their personal "Scholar Workspace".
2.  **The Omni-Search Persistence:** The top search bar never disappears. If a user gets lost deep in a Root page, they can always type a new query into the top bar to reset their journey.
3.  **The "Breadcrumb Trail" Updates:** The `user_journeys` tracker continuously updates. A user can jump from *Sentence ➔ Word Root ➔ Tag ➔ Category*, and the breadcrumb trail will record that exact path, allowing them to click backwards safely.

---

## 6. Summary of Interaction Models

| Page Type | Psychological Goal | UI Style | Key Feature |
| :--- | :--- | :--- | :--- |
| **Sentence** | Deep Focus | Minimal, text-heavy | Infinite Scroll & Dual-Pane Exegesis |
| **Word/Lexicon** | Morphological Tracing | Analytical, data-heavy | Derivation Trees & Distribution Charts |
| **Category** | Structured Drilling | Linear, hierarchical | Sub-Chapter Cards & Progress Tracking |
| **Tag** | Lateral Jumping | Dynamic, hub-like | Co-occurring Tags (Wikipedia Effect) |
