<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\LexiconRoot;
use App\Models\Sentence;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(private SearchService $searchService) {}

    public function index(SearchRequest $request): JsonResponse
    {
        $results = $this->searchService->hybridSearch(
            $request->validated('q'),
            $request->validated('filters') ?? []
        );

        return response()->json([
            'data' => $results,
        ]);
    }

    public function autocomplete(SearchRequest $request): JsonResponse
    {
        // For fast autocomplete, skip vector search and only use Scout
        $query = $request->validated('q');

        $roots = LexiconRoot::search($query)->take(5)->get();
        // Since we didn't inject the SearchService hybrid for autocomplete, we directly use Scout on Sentence too.
        $sentences = Sentence::search($query)->take(5)->get();

        return response()->json([
            'roots' => $roots,
            'sentences' => $sentences,
        ]);
    }
}
