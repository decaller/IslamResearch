# User Personalization & Relevance Feedback System

To elevate the "Scholar UI" into a true academic workspace, the platform implements a deep personalization layer. This system tracks user research journeys, enables custom collections, and utilizes crowdsourced feedback to fine-tune AI search accuracy.

---

## 1. The "Scholar's Journey" (History & Breadcrumbs)

Instead of a simple flat list of past searches, the system tracks a continuous **Research Journey**, connecting semantic searches to the specific roots and texts explored.

### A. Database Schema (`user_journeys` table)
| Field | Type | Description |
| :--- | :--- | :--- |
| **id** | UUID | Primary Key |
| **user_id** | UUID | Foreign Key -> `users.id` |
| **action_type** | Enum | `search_query`, `view_item`, `explore_root`, `apply_filter` |
| **context_data** | JSONB | Stores query string, root value, or item ID |
| **created_at** | Timestamp | Creation time for chronological sequencing |

### B. Frontend Implementation (The Breadcrumb UI)
Located just below the Omni-Search Bar, the UI dynamically renders the user's session history as a clickable breadcrumb trail.
- **Example:** 🔍 "Rules of Zakat" ➔ 📖 Sahih Bukhari #142 ➔ 🌱 Root: ز ك و ➔ 📚 Tafsir Ibn Kathir 2:43
- **Behavior:** Clicking any node in the trail instantly restores the full application state (Left/Right panes), allowing users to safely explore "research rabbit holes" without losing context.

---

## 2. Custom Collections (Bookmarks, Saved Queries & Workspaces)

Users can curate their own research libraries, saving static excerpts, dynamic search queries, and entire workspace states (including breadcrumbs).

### A. Database Schema
- **`collections` table:** `id`, `user_id`, `name` (e.g., "Ramadan Prep"), `is_public` (for sharing).
- **`collection_items` table:** `id`, `collection_id`, `item_type` (`static_item`, `dynamic_query`, `workspace_state`), `target_id_or_query`, `metadata`.
  - *Note on workspace_state:* The `metadata` JSONB column stores the exact UI configuration, including the breadcrumb array and panel views, guaranteeing perfect session restoration.

### B. Frontend Integration
- **Card-Level Saving:** Every card in the results pane features a "🔖 Save" icon to add the entry to a collection.
- **Saved Queries:** A "Save this Search" button on the search bar allows users to bookmark a specific query to see refreshed results later.
- **Save Workspace:** A "Save Current Session" button in the header saves the complete dual-pane layout and breadcrumb history into the `metadata` column as a `workspace_state`.

---

## 3. The Algorithmic Feedback Loop (Relevance Tuning)

AI semantic search can occasionally hallucinate connections due to linguistic nuances. This feedback loop allows users and admins to manually tune the system.

### A. The User Experience (Frontend Tuning)
- **Relevance Slider:** Every result card features a slider (0% to 100%).
- **Local Hiding:** If a score falls below 20%, the card is hidden from the current session.
- **Persistence:** Laravel passed hidden item IDs to Meilisearch to ensure they remain hidden for that specific user: `filter: ["NOT id IN ['uuid-1', 'uuid-2']"]`.

### B. Database Schema (`user_feedbacks` table)
Tracks feedback for potential global search tuning.
| Field | Type | Description |
| :--- | :--- | :--- |
| **id** | UUID | Primary Key |
| **user_id** | UUID | Reviewer ID |
| **item_id** | UUID | The text being evaluated |
| **search_query** | String | The exact query used to find this item |
| **relevance_score** | Integer | 0 to 100 |
| **status** | Enum | `pending`, `approved`, `rejected` |

### C. The Filament Admin Workflow (Global Tuning)
Admins use the **FeedbackResource** to approve or reject low-relevance reports.
- **Reject:** AI was correct; feedback is ignored.
- **Approve (Global Hide):** Admin agrees the AI made a mistake.

### D. Applying Approved Feedback (Negative Tagging)
When an admin approves a global hide, the system severs the semantic link using **Negative Metadata Tagging**.
1.  **Backend Update:** Laravel adds the problematic query to the `negative_queries` JSONB array in the `items` table.
2.  **Meilisearch Sync:** The item is re-indexed.
3.  **Search Controller Logic:** The controller automatically appends a filter to future searches: `NOT negative_queries = [current_query]`.

**Result:** The system learns globally from a single admin action, permanently correcting AI hallucinations.

---

## 4. IDE-Like Workspace & Timeline

To support complex scholarly research, the Scholar UI behaves dynamically like a code editor (e.g., VS Code), allowing users to maintain multiple active tabs and split panes without losing state.

### A. The "Last State" (Active Workspace)
- **Database Table:** `user_workspaces` stores the `layout_state` (JSONB) indicating exactly which tabs are open, which panes are split, and current scroll positions.
- **Auto-Save:** Every time a user opens a tab or moves a pane, the `layout_state` is updated in the database. This guarantees that if a scholar closes their browser and returns later, their exact research environment is perfectly restored.
- **Multiple Projects:** Users can switch between isolated workspaces (e.g., "Quran Study" vs "Fiqh Research") without cluttering their tabs.

### B. The Audit Timeline (Action History)
- **Extending Journeys:** The `user_journeys` table has been extended to act as an automatic audit log.
- **New Actions:** Enum actions now include `open_tab`, `close_tab`, and `split_pane`.
- **The Timeline UI:** As the user interacts with the IDE-like interface, a sequential timeline is naturally generated (e.g., *10:00 AM - Opened Search*, *10:05 AM - Split Pane: Tafsir Ibn Kathir*). This provides a historical path so scholars can review exactly how they arrived at a specific conclusion.
