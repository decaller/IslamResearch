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

## 2. Custom Collections (Bookmarks & Saved Queries)

Users can curate their own research libraries, saving both static excerpts and dynamic search queries.

### A. Database Schema
- **`collections` table:** `id`, `user_id`, `name` (e.g., "Ramadan Prep"), `is_public` (for sharing).
- **`collection_items` table:** `id`, `collection_id`, `item_type` (`static_item`, `dynamic_query`), `target_id_or_query`.

### B. Frontend Integration
- **Card-Level Saving:** Every card in the results pane features a "🔖 Save" icon to add the entry to a collection.
- **Saved Queries:** A "Save this Search" button on the search bar allows users to bookmark a specific query to see refreshed results later.

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
