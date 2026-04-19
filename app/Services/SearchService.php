<?php

namespace App\Services;

use App\Models\LexiconRoot;
use App\Models\Sentence;
use Illuminate\Support\Facades\Http;

class SearchService
{
    public function __construct(private QueryParser $parser) {}

    public function hybridSearch(string $query, array $filters = []): mixed
    {
        $parsed = $this->parser->parse($query);

        if ($parsed['type'] === 'vector_semantic') {
            return $this->vectorSearch($parsed['query']);
        }

        if ($parsed['type'] === 'vector_root') {
            return $this->vectorSearchByRoot($parsed['query']);
        }

        // Scout / Meilisearch fallbacks
        $scoutQuery = Sentence::search($parsed['query']);

        if ($parsed['type'] === 'filter' && isset($parsed['filter_key'])) {
            if ($parsed['filter_key'] === 'surah') {
                $scoutQuery->where('metadata.surah_name', $parsed['query']);
            }
            if ($parsed['filter_key'] === 'entity_id') {
                // When filtering by entity, specifically search sentences linked to that entity
                return Sentence::whereHas('entities', function($q) use ($parsed) {
                    $q->where('entities.id', $parsed['query']);
                })->with('entities')->paginate(20);
            }
        }

        // Apply additional standard filters
        foreach ($filters as $key => $value) {
            $scoutQuery->where($key, $value);
        }

        return $scoutQuery->query(fn($query) => $query->with('entities'))->paginate(20);
    }

    public function vectorSearch(string $query, int $limit = 20)
    {
        // For MVP: Hit Ollama to get the 1024-d embedding.
        $ollamaUrl = config('services.ollama.url', env('OLLAMA_URL', 'http://host.docker.internal:11434'));

        try {
            $response = Http::timeout(5)->post("$ollamaUrl/api/embeddings", [
                'model' => 'mxbai-embed-large',
                'prompt' => $query,
            ]);

            if ($response->successful()) {
                $embedding = $response->json('embedding');
                $vectorString = '['.implode(',', $embedding).']';

                // Use raw pgvector nearest-neighbor operator
                return Sentence::query()
                    ->with('entities')
                    ->select('sentences.*')
                    ->selectRaw('embedding_ar <-> ?::vector AS distance', [$vectorString])
                    ->orderBy('distance')
                    ->limit($limit)
                    ->get();
            }
        } catch (\Exception $e) {
            // Fallback gracefully to keyword search if Ollama is unreachable
            report($e);
        }

        // Fallback to keyword
        return Sentence::search($query)->paginate($limit);
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
