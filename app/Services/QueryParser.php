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
        $originalQuery = $query;
        $query = trim($query);

        $result = [
            'type' => 'keyword',
            'query' => $query,
            'entities' => [],
            'roots' => [],
        ];

        // 0. Entity Filter (e.g. #entity:uuid)
        if (preg_match('/^#entity:([a-f0-9-]+)$/i', $query, $matches)) {
            $result['type'] = 'filter';
            $result['filter_key'] = 'entity_id';
            $result['query'] = trim($matches[1]);
        }

        // 1. Vector Root Search (e.g. #root:كتب)
        elseif (preg_match('/^#root:(.+)$/i', $query, $matches)) {
            $result['type'] = 'vector_root';
            $result['query'] = trim($matches[1]);
        }

        // 2. Metadata Filter (e.g. @surah:البقرة)
        elseif (preg_match('/^@surah:(.+)$/i', $query, $matches)) {
            $result['type'] = 'filter';
            $result['filter_key'] = 'surah';
            $result['query'] = trim($matches[1]);
        }

        // 3. Fuzzy match (e.g. ~fuzzy:bismillah)
        elseif (preg_match('/^~fuzzy:(.+)$/i', $query, $matches)) {
            $result['type'] = 'fuzzy';
            $result['query'] = trim($matches[1]);
        }

        // 4. Default: Keyword vs Vector Semantic
        else {
            $wordCount = str_word_count($query, 0, 'ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىي');
            if ($wordCount > 4) {
                $result['type'] = 'vector_semantic';
            }
        }

        // 5. Automatic Semantic Enrichment (Finding Tags & Roots)
        $metadata = $this->extractMetadata($query);
        $result['entities'] = $metadata['entities'];
        $result['roots'] = $metadata['roots'];

        return $result;
    }

    /**
     * Step 2 & 3: Extraction of implicit metadata from query string.
     */
    public function extractMetadata(string $query): array
    {
        return [
            'entities' => $this->extractEntities($query),
            'roots' => $this->extractRoots($query),
        ];
    }

    private function extractEntities(string $query): array
    {
        // Simple token matching against entities table.
        // In production, this list should be cached in Redis for < 1ms lookups.
        $tokens = explode(' ', $query);
        if (count($tokens) === 0) {
            return [];
        }

        return Entity::where(function ($q) use ($tokens) {
            foreach ($tokens as $token) {
                if (strlen($token) < 3) {
                    continue;
                }
                $q->orWhere('canonical_name', 'like', "%{$token}%");
            }
        })->take(5)->get()->toArray();
    }

    private function extractRoots(string $query): array
    {
        // Step 4: Linguistic Tokenization
        // 1. Remove Harakat
        $clean = preg_replace('/[\x{064B}-\x{0652}]/u', '', $query);

        // 2. Simple Rule-based Root identification (Mock logic for CAMeL tools)
        // If query is Arabic and matches known root patterns, find matches in Lexicon.
        $arabicTokens = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY);
        $roots = [];

        foreach ($arabicTokens as $token) {
            // Very basic heuristic: if it's 3 letters or more, check LexiconRoot
            if (mb_strlen($token) >= 3) {
                $match = LexiconRoot::where('root_value', $token)->first();
                if ($match) {
                    $roots[] = $match->toArray();
                }
            }
        }

        return $roots;
    }
}
