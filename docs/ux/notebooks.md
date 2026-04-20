# Live Markdown Notebooks: The Research Canvas

The **Live Markdown Notebook** is the primary interface for synthesis and publication in `IslamResearch`. It replaces traditional static bookmark folders with dynamic, interactive documents that blend personal notes with live database entities.

---

## 1. The "Live Document" Architecture

Each notebook is a living document. Unlike traditional text files, these utilize a "Live Embed" system that keeps the content synchronized with the master database.

### Data Model (`user_notebooks`)
- **`content_md`**: Raw Markdown string containing text and `{{ embed:... }}` tags.
- **`view_mode`**: Toggles between `article` (reading/writing) and `presentation` (slide deck).
- **`is_published`**: Controls public visibility via a unique URL.

---

## 2. The Editing Experience (MDX-Style)

The editor is a customized rich-text environment (built with TipTap) that provides a "command-palette" first experience.

### ⌨️ The Slash Command (`/`)
Typing `/` triggers a global search overlay within the editor. 
- **User selects**: A specific Hadith, Quranic Verse, or Arabic Root.
- **System inserts**: A semantic shortcode like `{{ embed:hadith:uuid-12345 }}`.

### 🖼️ Live Embed Rendering
When the editor (or the public page) renders Markdown, it parses the `{{ embed }}` tags and replaces them with **Interactive Cards**:
- **Hadith Embeds**: Renders bilingual text, narrator chains, and grade.
- **Root Embeds**: Renders a word cloud and links to the full Lexicon.
- **Query Embeds**: Renders a dynamic list of search results (e.g., `{{ embed:query:"rules of zakat" }}`).

---

## 3. Automated Academic Citations (Breadcrumbs)

Every embed can carry its "discovery history". If enabled, the system appends the `breadcrumb_trail` from the `user_journeys` table.

**Example Raw Markdown:**
```markdown
{{ embed:sentence:uuid-789 | show_breadcrumb=true }}
```

**Resulting Footnote:**
> "And seek help through patience..." (Quran 2:45)
> 🔗 Discovered via: 🔍 "How to deal with grief" ➔ 📂 Tafsir As-Sa'di

---

## 4. Modes of Consumption

### A. Article Mode (The Scholarly Paper)
A clean, typography-focused layout (daisyUI `prose`) where embeds act as interactive sidebars or popups. Perfect for sharing research papers.

### B. Presentation Mode (The Khutbah/Lecture)
Uses horizontal rules (`---`) to split content into slides. 
- **Slide 1**: Scholar's speaking notes (plain text).
- **Slide 2**: A "Live Embed" Hadith rendered in hero-style typography for the audience.
- **Engine**: Powered by `reveal.js` or a similar lightweight slide bridge.

---

## 5. Portability (Export)

- **Markdown Export**: Downloads a `.md` file. Live embeds are converted to blockquotes or static text for compatibility with Obsidian/Notion.
- **JSON Export**: Contains the raw notebook structure and metadata.

---

## 6. Dynamic Queries (Live Feeds)

The most advanced use-case is the living document. A notebook can contain a search query:
`{{ embed:query:"latest rulings on zakat" | limit:5 }}`

Every time the notebook is viewed, it fetches the latest data via the [Search Pipeline](../search-pipeline.md), ensuring the research never goes stale.
