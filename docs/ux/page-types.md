# UX Architecture: Page Types & Explorability

In the "Scholar UI", a search result is just the beginning. The platform features dedicated page types for Sentences, Words (Lexicons), Categories, and Tags. All components use **daisyUI v5** (Tailwind v4 plugin) for semantic, theme-aware markup.

The golden rule of this UX is **Infinite Explorability**: Every entity on the screen must be a clickable portal that allows the user to traverse the database laterally (across disciplines) or vertically (deep into linguistics).

---

## 1. The Sentence Page (The "Deep Read" UX)
**URL Pattern:** `/read/item/{uuid}`  
**Primary Goal:** To provide absolute context and Al-Tadabbur-style deep exegesis without distraction.

### The Explorability Features:
*   **The Infinite Scroll (Vertical Context):** The user doesn't just see the single sentence/verse they clicked. The page automatically renders the sentences before and after it. Scrolling up or down seamlessly loads the rest of the book/chapter.
*   **The Dual-Pane Exegesis (Horizontal Context):** The right side shows the text in a daisyUI **`card`**. The left side holds the 5-Tab system built with daisyUI **`tabs`** + **`tab`** + **`tab-content`** (Tafsir, Actions, Directives, Lexicon).
*   **Explorability Hook:** When reading the 5-Tab panel, any related Hadith or Ayah mentioned in the Tafsir is rendered as a hoverable daisyUI **`card`** Preview Card. The user can peek at the referenced text without leaving their current page.

---

## 2. The Lexicon / Root Page (The "Linguistic" UX)
**URL Pattern:** `/lexicon/root/{root_id}` (e.g., `/lexicon/root/ع-ل-م`)  
**Primary Goal:** To act as a dynamic, AI-powered dictionary that shows how a single root concept evolves across the entire Islamic corpus.

### The Explorability Features:
*   **The Derivation Tree (Visual Hook):** The top of the page features a visual "Word Cloud" using daisyUI **`badge`** chips for surface words derived from the root (e.g., عِلْم, يَعْلَمُونَ, عَالِم). Clicking a `badge` instantly filters the page results to that grammatical form.
*   **Corpus Distribution Chart:** A **`stat`** + **`progress`** component group showing where this root is used (e.g., "Used 854× in the Quran, 200× in Bukhari"). Clicking a stat bar filters the feed.
*   **Explorability Hook: Semantic Synonyms.** At the bottom, the AI suggests "Related Roots" as daisyUI **`badge badge-outline`** chips based on vector similarity.

---

## 3. The Category Page (The "Library Aisle" UX)
**URL Pattern:** `/category/{category_slug}` (e.g., `/category/fiqh-muamalah`)  
**Primary Goal:** To provide highly structured, linear reading for users who want to study a discipline systematically.

### The Explorability Features:
*   **The Breadcrumb Hierarchy (Top):** daisyUI **`breadcrumbs`** clearly displays *Fiqh ➔ Muamalah ➔ Debt*. The user clicks any node to step up one level.
*   **Sub-Chapter Cards (Visual Navigation):** Instead of a dry list, the top of the page features large daisyUI **`card`** components for all child categories (e.g., "Rules of Interest", "Rules of Trade") with a **`badge`** showing article count.
*   **Explorability Hook: "Recommended Next".** Because categories are structural, the UX tracks reading progress. Upon finishing the "Zakat" category, the UI shows a daisyUI **`alert alert-info`** prompting the user to jump to "Fasting".

---

## 4. The Tag Page (The "Concept Hub" UX)
**URL Pattern:** `/tag/{tag_slug}` (e.g., `/tag/prophet-muhammad`)  
**Primary Goal:** To break the walls between traditional disciplines and show how a single concept permeates all of Islamic literature.

### The Explorability Features:
*   **The Cross-Disciplinary Feed:** Unlike a Category page, the Tag page feed is "chaotic" by design. A user sees a Quranic verse, followed by a Sirah event, followed by a Fiqh ruling—all unified by the tag `#Prophet_Muhammad`.
*   **Filter by Facet:** A sticky left daisyUI **`menu`** sidebar with **`checkbox`** items to tame the feed.
*   **Explorability Hook: "Co-occurring Tags" (The Wikipedia Effect).** On the right, daisyUI **`badge`** chips list co-occurring tags (e.g., `#Battle_of_Badr` → `#Angels`, `#Abu_Jahl`, `#Ramadan`), encouraging lateral traversal.

---

## 5. Universal UX Rules (What is the SAME?)

To ensure the user never feels lost while exploring these wildly different page types, three UX rules apply universally:

1.  **The "Save to Collection" Button:** Whether the user is looking at a Sentence, a Lexicon Root, a Tag, or a Category, there is always a ubiquitous 🔖 icon allowing them to save that specific entity to their personal "Scholar Workspace".
2.  **The Omni-Search Persistence:** The top search bar never disappears. If a user gets lost deep in a Root page, they can always type a new query into the top bar to reset their journey.
3.  **The "Breadcrumb Trail" Updates:** The `user_journeys` tracker continuously updates. A user can jump from *Sentence ➔ Word Root ➔ Tag ➔ Category*, and the breadcrumb trail will record that exact path, allowing them to click backwards safely.

---

## 6. Summary of Interaction Models

| Page Type | Psychological Goal | UI Style | Key Feature | daisyUI Components |
| :--- | :--- | :--- | :--- | :--- |
| **Sentence** | Deep Focus | Minimal, text-heavy | Infinite Scroll & Dual-Pane Exegesis | `card`, `tabs`, `tab` |
| **Word/Lexicon** | Morphological Tracing | Analytical, data-heavy | Derivation Trees & Distribution Charts | `badge`, `stat`, `progress` |
| **Category** | Structured Drilling | Linear, hierarchical | Sub-Chapter Cards & Progress Tracking | `card`, `breadcrumbs`, `alert` |
| **Tag** | Lateral Jumping | Dynamic, hub-like | Co-occurring Tags (Wikipedia Effect) | `badge`, `menu`, `checkbox` |
