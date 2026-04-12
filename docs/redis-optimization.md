# Database & Redis Optimization Strategy

As the IslamResearch platform scales to handle millions of vector embeddings, lexicon roots, and highly interactive IDE-like scholarly features, optimizing the database becomes critical. 

This document outlines strategies for leveraging **Redis** within the **Laravel / Filament** ecosystem, along with other advanced database optimization tips.

---

## 1. High-Frequency UI State (The Workspace Debounce)

With the new "IDE-Like Workspace", a user might resize panels, open tabs, and switch views rapidly. Writing every single UI interaction directly to PostgreSQL (`user_workspaces` or `user_journeys`) will cause severe database bottlenecking.

### Strategy: Redis Write-Behind Caching
Instead of hitting PostgreSQL on every tab click:
1. **Cache the State:** Save the active `layout_state` to Redis using the user's ID as the key:
   `Redis::set("user_workspace_{$userId}", $json_state, 'EX', 3600);`
2. **Debounce the Write:** Create an Artisan scheduled command (run every 1-5 minutes) that pulls the latest states from Redis and performs a bulk `UPSERT` into the PostgreSQL `user_workspaces` table.
3. **Result:** The user gets instant UI responses, and PostgreSQL only handles massive, optimized bulk writes.

---

## 2. Filament Admin Optimizations

Filament is incredibly powerful but can become sluggish if it tries to load too much relational data at once.

### A. Widget Caching
If your Filament dashboard has "Stats Overview" widgets (e.g., "Total Sentences Vectorized" or "Most Searched Root Words"), these count queries on massive tables will paralyze the database.
- **Fix:** Use Laravel's cache layer directly in the Filament widget.
```php
protected function getStats(): array
{
    $totalVectors = Cache::remember('stats.total_vectors', 3600, function () {
        return Sentence::count();
    });

    return [
        Stat::make('Total Sentences', $totalVectors),
    ];
}
```

### B. Eager Loading (Fixing N+1 Queries)
When displaying `Sentences` or `Lexicon_Words` in a Filament Table, always use eloquent eager loading for relationships to prevent executing a separate query for every row.
- **Fix:** In your Filament Resource, modify the eloquent query:
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->with(['source_book.scholar']);
}
```

### C. Searchable Select Menus
Never load thousands of records into a Filament select dropdown (e.g., assigning a `root_word` to an `item`). 
- **Fix:** Use Filament's `searchable()` method to trigger AJAX queries rather than rendering the entire `lexicon_roots` table into HTML.

---

## 3. Caching Static Hierarchies

### The Taxonomy & Scholar Tree
The `taxonomies` table (Fiqh -> Usul Fiqh etc.) and the `scholars` table rarely change. Querying nested self-referential tables (like taxonomy trees) in SQL is computationally expensive.
- **Strategy:** Cache the entire taxonomy tree in Redis permanently. Clear the cache only through a Filament Model Observer when an Admin explicitly creates or edits a Taxonomy record.
```php
class TaxonomyObserver
{
    public function saved(Taxonomy $taxonomy)
    {
        Cache::forget('taxonomy_tree_global');
    }
}
```

---

## 4. Vector Search & Dictionary Caching

Your application uses a dual-table structure: `searches` (the vector cache) and `user_searches` (the user log). 

### Strategy: Redis Hot-Swapping
1. **The "Hot" Dictionary:** Load the top 5,000 most common searches (`query` mapping to `search_id`) directly into a Redis Hash. 
2. **The Flow:** When a user searches "Hukum Zakat", Laravel checks Redis first. If it exists, it instantly gets the `search_id` and logs the `user_search` asynchronously (using Laravel Queues). It avoids hitting PostgreSQL completely just to verify if a search has been done before.

---

## 5. Other Advanced Tips

- **PostgreSQL Connection Pooling (PgBouncer):** Laravel opens a new database connection for every request. With high traffic, PostgreSQL will max out its connections. Place **PgBouncer** between Laravel and PostgreSQL to efficiently share a pool of connections.
- **Laravel Octane:** If your "IDE Workspace" is sending dozens of AJAX requests per minute per user, deploy the Laravel app via Laravel Octane (Swoole or FrankenPHP) to keep the framework booted in RAM, cutting API response times from 100ms down to ~10ms.
- **GIN Indexes for JSONB:** Ensure that your migrations explicitly define GIN indexes for any JSONB column you intend to filter on (e.g., `sentences.metadata` or `user_searches.filters`). PostgreSQL cannot perform fast nested JSON searches on standard B-Tree indexes.
