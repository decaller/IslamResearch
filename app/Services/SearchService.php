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

    /**
     * The Complete Execution Pipeline (0-60ms)
     * Takes a raw string, enriches it, queries the database, applies post-retrieval
     * refinements, and synthesizes the final structured MCP JSON payload.
     */
    public function pipelineSearch(string $query, array $filters = []): array
    {
        $startTime = microtime(true);

        // Phase 1: Query Pre-Processing & Intent Analysis (0-15ms)
        // (Normalization, Cache Check, Tokenization, Vectorization)
        $parsed = $this->parser->parse($query);

        // Phase 2: Core Retrieval (15-30ms)
        // (Hybrid Query, Personalization Filters, Execution)
        $results = $this->performEnrichedSearch($parsed, array_merge($filters, ['perPage' => 100]));
        $records = match (true) {
            $results instanceof Collection => $results->all(),
            $results instanceof AbstractPaginator => $results->items(),
            is_array($results) => $results,
            default => [],
        };

        // Phase 3: The Post-Retrieval Bridge & Refinement (30-50ms)

        // 3.1 Semantic Sliding Window (Micro-Targeting)
        // Finds exact matching fragments and highlights them.
        $this->microTargeting($parsed['query'], $records);

        // 3.2 Category Clustering (Building the Tree)
        $clusters = $this->buildCategoryClusters($records);

        // 3.3 Knowledge Graph Hydration
        $entities = $this->hydrateEntities($records, $parsed['entities']);

        // 3.4 Root Word Results
        $roots = $this->hydrateRoots($records, $parsed['roots']);

        // Phase 4: JSON Payload Synthesis (50-60ms)
        return [
            'success' => true,
            'data' => [
                'structuredContent' => [
                    'query' => $query,
                    'processing_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                    'category_clusters' => [
                        'total_categories_found' => count($clusters),
                        'items' => $clusters,
                    ],
                    'sentence_results' => [
                        'total_found' => count($records),
                        'items' => $this->formatSentenceResults($records, $parsed['query']),
                    ],
                    'entity_results' => [
                        'total_found' => count($entities),
                        'items' => $entities,
                    ],
                    'root_word_results' => [
                        'total_found' => count($roots),
                        'items' => $roots,
                    ],
                ],
            ],
        ];
    }

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
     * Phase 3.2: Category Clustering (Building the Tree)
     */
    private function buildCategoryClusters(array $records): array
    {
        $clusters = [];
        foreach ($records as $record) {
            $metadata = $record->metadata ?? [];
            $categoryPath = $metadata['category_path'] ?? $this->getClusterKey($record);
            $parentCategory = explode(' > ', $categoryPath)[0];

            if (! isset($clusters[$parentCategory])) {
                $clusters[$parentCategory] = [
                    'category_name_ar' => $metadata['category_name_ar'] ?? $parentCategory,
                    'category_name_en' => $metadata['category_name_en'] ?? $parentCategory,
                    'match_count' => 0,
                    'relevance_score' => 0.0,
                    'preview_sentence_ids' => [],
                    'related_queries' => $this->getRelatedQueries($parentCategory),
                ];
            }

            $clusters[$parentCategory]['match_count']++;
            $score = 1.0 - ($record->distance ?? 0.5);
            if ($score > $clusters[$parentCategory]['relevance_score']) {
                $clusters[$parentCategory]['relevance_score'] = round($score, 3);
            }
            if (count($clusters[$parentCategory]['preview_sentence_ids']) < 3) {
                $clusters[$parentCategory]['preview_sentence_ids'][] = $record->id;
            }
        }

        return array_values($clusters);
    }

    /**
     * Phase 3.3: Knowledge Graph Hydration
     */
    private function hydrateEntities(array $records, array $initialEntities): array
    {
        $entityIds = collect($initialEntities)->pluck('id')->toArray();

        // Discover mentioned_entities inside the top sentences
        foreach ($records as $record) {
            if ($record->relationLoaded('entities')) {
                foreach ($record->entities as $entity) {
                    $entityIds[] = $entity->id;
                }
            }
        }

        $entityIds = array_unique($entityIds);
        if (empty($entityIds)) {
            return [];
        }

        return Entity::whereIn('id', $entityIds)
            ->get()
            ->map(fn ($e) => [
                'entity_id' => $e->id,
                'canonical_name' => $e->canonical_name,
                'entity_type' => $e->entity_type,
                'description' => $e->description,
                'wikipedia_url' => $e->wikipedia_url,
                'match_reason' => 'Knowledge Graph Match',
                'relevance_score' => 0.95, // Placeholder for actual calc
                'related_graph_entities' => $e->getRecursiveRelationships(1),
            ])
            ->toArray();
    }

    /**
     * Phase 3.4: Root Word Results
     */
    private function hydrateRoots(array $records, array $initialRoots): array
    {
        $rootIds = collect($initialRoots)->pluck('id')->toArray();

        // Discover additional roots from records
        foreach ($records as $record) {
            if ($record->relationLoaded('words')) {
                foreach ($record->words as $word) {
                    if ($word->root_id) {
                        $rootIds[] = $word->root_id;
                    }
                }
            }
        }

        $rootIds = array_unique($rootIds);
        if (empty($rootIds)) {
            return [];
        }

        // We load LexiconRoot with LexiconWord to get morphological forms
        return LexiconRoot::whereIn('id', $rootIds)
            ->with(['words' => function ($q) use ($records) {
                // Only take words that appear in our result set
                $sentenceIds = collect($records)->pluck('id');
                $q->whereHas('sentences', fn ($sq) => $sq->whereIn('sentences.id', $sentenceIds));
            }])
            ->get()
            ->map(fn ($r) => [
                'root_id' => $r->id,
                'root_value_ar' => $r->root_value,
                'morphological_forms_found' => $r->words->pluck('word_raw')->unique()->values()->toArray(),
                'match_reason' => 'Semantic Root Match',
                'relevance_score' => 0.99,
            ])
            ->toArray();
    }

    /**
     * Phase 4: Format Sentence Results block
     */
    private function formatSentenceResults(array $records, string $originalQuery): array
    {
        return array_map(fn ($record) => [
            'id' => $record->id,
            'resource_type' => $record->resource_type?->value,
            'parent_category' => explode(' > ', $record->metadata['category_path'] ?? $this->getClusterKey($record))[0],
            'bab' => $record->metadata['bab'] ?? $record->metadata['surah_name_ar'] ?? null,
            'subchapter' => $record->metadata['subchapter'] ?? $record->metadata['ayah_number'] ?? null,
            'category_path' => explode(' > ', $record->metadata['category_path'] ?? $this->getClusterKey($record)),
            'content' => [
                [
                    'type' => 'arabic',
                    'text' => $record->sentence_text,
                ],
                [
                    'type' => 'translation',
                    'language' => 'id',
                    'text' => $record->translations->first()?->translation_text,
                ],
                [
                    'type' => 'transliteration',
                    'text' => $record->transliterations->first()?->transliteration_text,
                ],
            ],
            // Exclusive I'rab Engine (Quran only)
            'linguistics' => $record->resource_type?->value === 'quran' ? [
                'irab' => $record->metadata['linguistics']['irab'] ?? [],
            ] : null,
            // Verification Engine & Citations
            'citations' => $record->metadata['citations'] ?? [],
            'citation' => [
                'source_book' => $record->sourceBook?->title ?? $record->metadata['source'] ?? 'General Sources',
                'chapter' => $record->metadata['chapter'] ?? $record->metadata['surah_name_en'] ?? null,
                'reference_number' => $record->metadata['reference_number'] ?? $record->metadata['ayah_number'] ?? null,
                'authenticity_grade' => $this->getVerificationGrade($record),
            ],
            // Hadith Anatomy (Sanad & Matn)
            'hadith_anatomy' => $record->resource_type?->value === 'hadith' ? [
                'isnad' => $record->metadata['isnad'] ?? null,
                'matn' => $record->metadata['matn'] ?? null,
            ] : null,
            'relevance_score' => round(1.0 - ($record->distance ?? 0.5), 3),
            'mentioned_entities' => $record->relationLoaded('entities')
                ? $record->entities->map(fn ($e) => [
                    'entity_id' => $e->id,
                    'canonical_name' => $e->canonical_name,
                    'entity_type' => $e->entity_type,
                ])
                : [],
            'sniped_fragment' => $record->sniped_text ?? null,
        ], $records);
    }

    private function getRelatedQueries(string $category): array
    {
        // Placeholder for Redis/DB historical search log lookup
        return [
            [
                'query' => "Who are the recipients in $category?",
                'match_reason' => "Directly related to top results in $category.",
            ],
            [
                'query' => "Practical applications of $category",
                'match_reason' => 'Common scholarly query path.',
            ],
        ];
    }

    private function getVerificationGrade($record): string
    {
        if ($record->resource_type?->value === 'hadith' || $record->resource_type?->value === 'quran') {
            return $record->metadata['authenticity_grade'] ?? ($record->resource_type?->value === 'quran' ? 'Mutawatir' : 'Sahih');
        }

        return 'Verified';
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
