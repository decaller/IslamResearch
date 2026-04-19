<?php

namespace App\Services;

class QueryParser
{
    /**
     * Parses the given search query to identify intent based on cheat sheet regex.
     *
     * @return array{type: string, query: string, filter_key?: string}
     */
    public function parse(string $query): array
    {
        $query = trim($query);

        // 0. Entity Filter (e.g. #entity:uuid)
        if (preg_match('/^#entity:([a-f0-9-]+)$/i', $query, $matches)) {
            return [
                'type' => 'filter',
                'filter_key' => 'entity_id',
                'query' => trim($matches[1]),
            ];
        }

        // 1. Vector Root Search (e.g. #root:كتب)
        if (preg_match('/^#root:(.+)$/i', $query, $matches)) {
            return [
                'type' => 'vector_root',
                'query' => trim($matches[1]),
            ];
        }

        // 2. Metadata Filter (e.g. @surah:البقرة)
        if (preg_match('/^@surah:(.+)$/i', $query, $matches)) {
            return [
                'type' => 'filter',
                'filter_key' => 'surah',
                'query' => trim($matches[1]),
            ];
        }

        // 3. Fuzzy match (e.g. ~fuzzy:bismillah)
        if (preg_match('/^~fuzzy:(.+)$/i', $query, $matches)) {
            return [
                'type' => 'fuzzy',
                'query' => trim($matches[1]),
            ];
        }

        // 4. Default: Keyword vs Vector Semantic
        // If more than 4 words, assume philosophical/long query and use vector semantic
        $wordCount = str_word_count($query, 0, 'ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىي');
        if ($wordCount > 4) {
            return [
                'type' => 'vector_semantic',
                'query' => $query,
            ];
        }

        return [
            'type' => 'keyword',
            'query' => $query,
        ];
    }
}
