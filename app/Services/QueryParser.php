<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\LexiconRoot;

class QueryParser
{
    /**
     * Parses the given search query to identify intent and extract entities/roots.
     *
     * @return array{type: string, query: string, filter_key?: string, entities: array, roots: array}
     */
    public function parse(string $query): array
    {
        $startTime = microtime(true);
        $query = $this->normalize($query);

        // 1. Cache Check (handled in SearchService)

        $result = [
            'type' => 'hybrid',
            'query' => $query,
            'entities' => [],
            'roots' => [],
            'tokens' => [],
            'processing_time_ms' => 0,
        ];

        // 2. Lingustic Tokenization (Arabic Root Extraction)
        $result['roots'] = $this->extractRoots($query);

        // 3. Entity Tokenization (Knowledge Graph Match)
        $result['entities'] = $this->extractEntities($query);

        $result['processing_time_ms'] = (int) ((microtime(true) - $startTime) * 1000);

        return $result;
    }

    /**
     * Phase 1: Normalization (cleaned, lowercased, extra spaces removed).
     */
    private function normalize(string $query): string
    {
        $query = mb_strtolower(trim($query));
        $query = preg_replace('/\s+/', ' ', $query);

        return $query;
    }

    /**
     * Phase 1: Linguistic Tokenization (Root Extraction).
     * In production, this calls a Python/CAMeL Tools microservice.
     */
    private function extractRoots(string $query): array
    {
        // Remove Harakat
        $clean = preg_replace('/[\x{064B}-\x{0652}]/u', '', $query);

        // Split into tokens
        $tokens = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY);
        $roots = [];

        foreach ($tokens as $token) {
            if (mb_strlen($token) >= 3) {
                $match = LexiconRoot::where('root_value', $token)
                    ->orWhere('root_value', mb_substr($token, 0, 3)) // Fallback to prefix
                    ->first();

                if ($match) {
                    $roots[] = [
                        'id' => $match->id,
                        'root_value_ar' => $match->root_value,
                    ];
                }
            }
        }

        return $roots;
    }

    /**
     * Phase 1: Entity Tokenization (Knowledge Graph Match).
     * Rapidly checks words against entities table aliases/canonical names.
     */
    private function extractEntities(string $query): array
    {
        $tokens = explode(' ', $query);
        $entities = [];

        if (empty($tokens)) {
            return [];
        }

        // Search for matches in canonical_name or aliases
        return Entity::where(function ($q) use ($tokens) {
            foreach ($tokens as $token) {
                if (strlen($token) < 3) {
                    continue;
                }
                $q->orWhere('canonical_name', 'ilike', "%{$token}%")
                    ->orWhereJsonContains('aliases', $token);
            }
        })
            ->take(10)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'canonical_name' => $e->canonical_name,
                'entity_type' => $e->entity_type,
            ])
            ->toArray();
    }
}
