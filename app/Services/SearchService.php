<?php

namespace App\Services;

use App\Enums\FeedbackStatus;
use App\Models\LexiconRoot;
use App\Models\Sentence;
use App\Models\UserFeedback;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SearchService
{
    public function __construct(private QueryParser $parser) {}

    public function hybridSearch(string $query, array $filters = []): mixed
    {
        $parsed = $this->parser->parse($query);

        // Advanced Hybrid Search: If semantic signals (long text, or implicit entities/roots) are present
        if ($parsed['type'] === 'vector_semantic' || ! empty($parsed['entities']) || ! empty($parsed['roots'])) {
            return $this->performEnrichedSearch($parsed, $filters);
        }

        if ($parsed['type'] === 'vector_root') {
            return $this->vectorSearchByRoot($parsed['query']);
        }

        // Scout / Meilisearch fallbacks for keyword/exact queries
        $scoutQuery = Sentence::search($parsed['query']);

        if ($parsed['type'] === 'filter' && isset($parsed['filter_key'])) {
            if ($parsed['filter_key'] === 'surah') {
                $scoutQuery->where('metadata.surah_name', $parsed['query']);
            }
            if ($parsed['filter_key'] === 'entity_id') {
                // When filtering by entity, specifically search sentences linked to that entity
                return Sentence::whereHas('entities', function ($q) use ($parsed) {
                    $q->where('entities.id', $parsed['query']);
                })->with('entities')->paginate(20);
            }
        }

        // Apply additional standard filters
        foreach ($filters as $key => $value) {
            $scoutQuery->where($key, $value);
        }

        return $scoutQuery->query(fn ($query) => $query->with(['entities', 'translations']))->paginate($filters['perPage'] ?? 20);
    }

    /**
     * Step 1 & 5: On-the-Fly Vectorization with Semantic Caching.
     */
    public function getEmbedding(string $query): ?array
    {
        $cacheKey = 'query_vector:'.md5(trim(strtolower($query)));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
            $ollamaUrl = config('services.ollama.url', env('OLLAMA_URL', 'http://host.docker.internal:11434'));

            try {
                $response = Http::timeout(5)->post("$ollamaUrl/api/embeddings", [
                    'model' => 'mxbai-embed-large',
                    'prompt' => $query,
                ]);

                if ($response->successful()) {
                    return $response->json('embedding');
                }
            } catch (\Exception $e) {
                report($e);
            }

            return null;
        });
    }

    public function vectorSearch(string $query, int $limit = 20)
    {
        $embedding = $this->getEmbedding($query);

        if ($embedding) {
            $vectorString = '['.implode(',', $embedding).']';

            // Use raw pgvector nearest-neighbor operator
            return Sentence::query()
                ->with(['entities', 'translations'])
                ->select('sentences.*')
                ->selectRaw('embedding_ar <-> ?::vector AS distance', [$vectorString])
                ->orderBy('distance')
                ->limit($limit)
                ->get();
        }

        // Fallback to keyword if embedding fails
        return Sentence::search($query)->paginate($limit);
    }

    /**
     * Advanced Combined Search: Vector + Entity Filtering + Root Boosting.
     */
    public function performEnrichedSearch(array $parsed, array $filters = []): mixed
    {
        $embedding = $this->getEmbedding($parsed['query']);
        $limit = $filters['perPage'] ?? 20;

        // Phase 2: Database Retrieval (The Radar)
        $dbQuery = Sentence::query()
            ->with(['entities', 'translations'])
            ->select('sentences.*');

        // CRITICAL INJECTION: User Feedback & Admin Filters
        $userId = auth()->id();
        if ($userId) {
            // Filter added: NOT id IN [user_hidden_ids]
            $hiddenIds = UserFeedback::where('user_id', $userId)
                ->where('status', FeedbackStatus::Rejected)
                ->pluck('sentence_id');

            if ($hiddenIds->isNotEmpty()) {
                $dbQuery->whereNotIn('id', $hiddenIds);
            }
        }

        // Filter added: Global negative query filters (Admin global bans)
        // For MVP, we check metadata for negative_queries list
        $dbQuery->whereRaw("NOT (metadata->'negative_queries' ?? ?)", [$parsed['query']]);

        // Step 2 & 3: Apply Entity and Root Filters
        if (! empty($parsed['entities'])) {
            $entityIds = array_column($parsed['entities'], 'id');
            $dbQuery->whereHas('entities', fn ($q) => $q->whereIn('entities.id', $entityIds));
        }

        if (! empty($parsed['roots'])) {
            $rootIds = array_column($parsed['roots'], 'id');
            $dbQuery->whereHas('words', fn ($q) => $q->whereIn('root_id', $rootIds));
        }

        if ($embedding) {
            $vectorString = '['.implode(',', $embedding).']';
            $dbQuery->selectRaw('embedding_ar <-> ?::vector AS distance', [$vectorString])
                ->orderBy('distance');
        } else {
            // If no vector available, fallback to some ordering
            $dbQuery->latest();
        }

        // Apply any manual filters passed in
        foreach ($filters as $key => $value) {
            if ($key === 'perPage') {
                continue;
            }
            $dbQuery->where($key, $value);
        }

        $results = $dbQuery->limit($limit)->get();

        // Optional Step 2 Re-ranking
        return $this->microTargeting($parsed['query'], $results);
    }

    /**
     * Stage 2: Micro-Targeting (Semantic Sliding Window)
     * Takes the top results and finds the exact semantic matching fragment.
     */
    public function microTargeting(string $query, mixed $results): mixed
    {
        $rerankUrl = config('services.rerank.url', env('RERANK_API_URL', 'http://localhost:8001/rerank'));

        // Prepare documents for re-ranking. We prioritize translations for semantic match if available.
        $documents = [];
        $records = match (true) {
            $results instanceof AbstractPaginator => $results->items(),
            $results instanceof Collection => $results->all(),
            default => $results,
        };

        foreach ($records as $record) {
            // Use the first translation text if available, otherwise fall back to Arabic text.
            // Note: In a real scenario, we might want to re-rank both or match language.
            $text = $record->translations->first()?->translation_text ?? $record->sentence_text;
            $documents[] = $text;
        }

        if (empty($documents)) {
            return $results;
        }

        try {
            $response = Http::timeout(10)->post($rerankUrl, [
                'query' => $query,
                'documents' => $documents,
                'window_size' => 8,
                'stride' => 1,
            ]);

            if ($response->successful()) {
                $bestMatch = $response->json();
                $docIndex = $bestMatch['doc_index'];

                if (isset($records[$docIndex])) {
                    // Update the primary text to use the highlighted fragment
                    // The frontend will render this as the "sniper" match
                    $records[$docIndex]->semantic_highlight = [
                        'phrase' => $bestMatch['text'],
                        'start' => $bestMatch['start'],
                        'end' => $bestMatch['end'],
                        'score' => $bestMatch['score'],
                    ];

                    // Inject highlight into text for the tree synthesis
                    $highlightedText = $bestMatch['text'];
                    $records[$docIndex]->sniped_text = $highlightedText;
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return $results;
    }

    /**
     * Clustered Tree Search (Zero Query-Time LLM)
     * Groups top N search results into clusters based on metadata or entity frequency.
     */
    public function clusteredSearch(string $query, array $filters = []): array
    {
        $startTime = microtime(true);

        // Phase 2: Database Retrieval (The Radar)
        // We set perPage to 50 to get enough documents for meaningful groups
        $results = $this->hybridSearch($query, array_merge($filters, ['perPage' => 50]));

        // Handle different result types from hybridSearch
        $records = match (true) {
            $results instanceof LengthAwarePaginator => $results->items(),
            $results instanceof Paginator => $results->items(),
            $results instanceof Collection => $results->all(),
            is_array($results) => $results,
            default => [],
        };

        if (empty($records)) {
            return [
                'query' => $query,
                'processing_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'tree' => [],
            ];
        }

        // Phase 3: The Refinement Bridge (The Sniper)
        // Before we group these results into a Tree, we must refine the exact text.
        // This find the exact 8-word sentence fragment matching the user's vector
        // and wraps it in <mark> tags for Semantic Highlighting.
        $this->microTargeting($query, $records);

        // Phase 4: Tree Synthesis (The Output)
        $clusters = [];

        foreach ($records as $record) {
            $groupKey = $this->getClusterKey($record);

            if (! isset($clusters[$groupKey])) {
                $clusters[$groupKey] = [
                    'node_type' => 'Category Cluster',
                    'node_title' => $groupKey,
                    'common_entities' => [],
                    'children_count' => 0,
                    'documents' => [],
                ];
            }

            // Format document for the tree output
            $clusters[$groupKey]['documents'][] = [
                'id' => $record->id,
                'text' => $record->sniped_text ?? $record->sentence_text, // Use sniped fragment if available
                'full_text' => $record->sentence_text,
                'translation' => $record->translations->first()?->translation_text,
                'metadata' => $record->metadata,
            ];
            $clusters[$groupKey]['children_count']++;

            // Collect entities for the cluster if loaded
            if ($record->relationLoaded('entities')) {
                foreach ($record->entities as $entity) {
                    $clusters[$groupKey]['common_entities'][] = $entity->canonical_name;
                }
            }
        }

        // Refine common entities (take top 3 unique prominent tags)
        foreach ($clusters as &$cluster) {
            $entityCounts = array_count_values($cluster['common_entities']);
            arsort($entityCounts);
            $cluster['common_entities'] = array_slice(array_keys($entityCounts), 0, 3);
        }

        return [
            'query' => $query,
            'processing_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
            'tree' => array_values($clusters),
        ];
    }

    /**
     * Deterministic Logic for finding a cluster anchor.
     */
    private function getClusterKey($record): string
    {
        $metadata = $record->metadata ?? [];
        $source = $record->sourceBook?->title ?? $metadata['source'] ?? 'General Sources';

        // Quran grouping
        if (isset($metadata['surah_name'])) {
            return "Quran: Surah {$metadata['surah_name']}";
        }

        // Hadith/Fiqh Chapter grouping
        if (isset($metadata['Chapter'])) {
            return "{$source}: {$metadata['Chapter']}";
        }

        if (isset($metadata['Sub_Chapter'])) {
            return "{$source}: {$metadata['Sub_Chapter']}";
        }

        // Fallback to Resource Type or Book Title
        return $record->resource_type?->value ? ucfirst($record->resource_type->value).": {$source}" : $source;
    }

    public function vectorSearchByRoot(string $rootQuery)
    {
        // Just directly search for Sentences that have the LexiconRoot.
        // We find the root via LexiconRoot, then inner join sentences.
        $root = LexiconRoot::where('root_value', $rootQuery)->first();

        if (! $root) {
            return collect();
        }

        return Sentence::whereHas('words', function ($q) use ($root) {
            $q->where('root_id', $root->id);
        })->paginate(20);
    }
}
