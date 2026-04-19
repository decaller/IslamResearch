# UX Design: The IDE-Like Scholarly Workspace

To support the immense depth of the `IslamResearch` platform, the User Interface discards the traditional "Search Engine" flat-page design in favor of an **Integrated Development Environment (IDE)** paradigm—highly inspired by tools like VS Code or IntelliJ. UI components are built with **daisyUI v5** (Tailwind v4 plugin) for semantic, theme-aware markup.

This layout allows scholars to cross-reference multiple texts, deeply analyze roots, and maintain complex states without losing their train of thought.

---

## 1. The Anatomy of the UI

The workspace is divided into four distinct resizable areas:

### A. The Activity Bar (Far Left, 50px wide)
A thin vertical strip containing global tool icons built with the daisyUI **`menu menu-vertical`** component and **`tooltip`** (data-tip) for icon labels. Clicking these changes the contents of the Explorer panel.
- 📁 **Explorer:** Your active books and workspace history.
- 📝 **Notebooks:** Create and manage Markdown research journals (replaces static collections).
- 🔍 **Search:** Global semantic search across `sentences`.
- 📖 **Lexicon:** Direct access to `lexicon_roots`.
- 📈 **Analytics/Tadabbur:** Habit trackers and progress for `user_habits`.
- ⚙️ **Settings:** Dark mode, text size, and UI preferences (stored in `users.metadata`). Dark mode toggled via daisyUI `data-theme` attribute swap using a daisyUI **`swap`** component.

### B. The Explorer (Sidebar, Collapsible)
The primary navigation panel implemented with the daisyUI **`drawer`** component. Its contents change based on the Activity Bar selection.
- **Tree View:** Renders the `taxonomies` table (ltree paths) as a collapsible **`menu`** + **`collapse`** structure (e.g., *📁 Fiqh > 📁 Usul Fiqh*).
- **Infinite Scroll:** Loads `scholars` and `source_books` instantly via virtual scrolling inside the drawer content area.

### C. The Editor Group (The Main Canvas)
This is where the actual texts are rendered. Unlike a standard webpage, this area supports **Tabs**.
- **Tab Bar:** Built using daisyUI **`tabs`** + **`tab`** components. Each search query, specific Surah, or Hadith chapter opens as a distinct tab at the top.
- **Sticky States:** If a user scrolls 50% down a Tafsir tab, switches to a Search tab, and then switches back, the scroll position is perfectly maintained (stored in Alpine `x-data`).
- **Breadcrumb Trail:** Rendered with a daisyUI **`breadcrumbs`** component directly below the tabs, leveraging the `user_journeys` table to show *exactly* what path the scholar took.

### D. The Split Pane (Contextual Intelligence)
The true power of the Scholar UI. The Editor Group can be split vertically or horizontally.
- **Dual-Pane Study:** A user can pin an Arabic Mushaf (Quran) on the Left Pane, and click a verse to instantly load *Tafsir Ibn Kathir* on the Right Pane. The daisyUI **`divider`** element (vertical) acts as the visual drag-handle sentinel.
- **Lexicon Popup/Pane:** Clicking an Arabic word triggers the Right Pane to load `lexicon_roots` details inside a daisyUI **`card`** without losing sight of the source sentence.
- **Container Queries:** To handle unpredictable pane dimensions (e.g. 300px dragged pane), the CSS uses `@container` queries so daisyUI **`card`** grids reflow relative to their surrounding pane, not the global viewport.

---

## 2. Core Workspace Interactions

### 🗂️ Tab Management
- **Middle-Click to Open:** Behaving exactly like a browser or code editor, clicking a reference with the middle mouse button opens it silently in a background tab.
- **Drag and Drop:** Tabs can be detached into Split Panes simply by dragging them to the edge of the screen.

### 🔗 Synchronized Scrolling
When analyzing a primary text (e.g., Hadith Matn) and a commentary (Syarh), the UI features a "Sync Scroll" lock. Scrolling the primary text automatically scrolls the commentary to the corresponding `sequence_number`.

---

## 3. Data Persistence Architecture (Backend Link)

This highly complex frontend state is useless if a user loses their work upon closing the browser tab. The application uses a robust "Write-Behind" architecture to save the exact UI state.

### The `layout_state` JSONB
Every single UI interaction (opening a tab, resizing the sidebar to 300px, splitting a pane) instantly updates a local frontend JSON object:

```json
{
  "active_workspace": "Fiqh Research",
  "sidebar_width": 320,
  "left_pane": {
    "type": "search_results",
    "query": "Hukum Zakat",
    "scroll_y": 1450
  },
  "right_pane": {
    "type": "lexicon_root",
    "root_id": "uuid-999"
  },
  "background_tabs": [
    {"type": "source_book", "id": "uuid-888"}
  ]
}
```

### The Auto-Save Flow (Livewire 4 "Islands" & Alpine)
To prevent constant DOM re-renders during intense dragging or tab managing, the UI depends on highly localized reactive islands:
1. **Alpine State:** Complex pane splitting and dragging is mapped fully in Alpine.js `x-data` avoiding continuous Livewire round-trips. daisyUI components accept standard HTML attributes so Alpine bindings (`x-bind`, `x-on`) attach cleanly without conflicts.
2. **Debounce (Frontend):** After 2 idle seconds, Alpine fires a singular Livewire `$dispatch` event.
3. **Redis Cache:** The minimal Livewire component catches the payload and safely writes it to Redis `user_workspace_{$id}`.
4. **Database Flush:** A Laravel Horizon job pulls from Redis and flushes it to PostgreSQL silently.

**Result:** A scholar can close their laptop on Friday, open it on Monday, and their exact tabs, search results, dual-panes, and scroll positions are restored instantly.
