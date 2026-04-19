<?php

namespace App\Livewire\Scholar;

use App\Models\LexiconRoot;
use App\Models\Sentence;
use App\Services\QueryParser;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class SearchPanel extends Component
{
    public string $query = '';

    public array $suggestions = [];

    public string $intentBadge = '';

    public function updatedQuery()
    {
        if (strlen($this->query) < 2) {
            $this->suggestions = [];
            $this->intentBadge = '';

            return;
        }

        // Determine intent badge immediately for UX
        $parser = app(QueryParser::class);
        $parsed = $parser->parse($this->query);
        $this->intentBadge = match ($parsed['type']) {
            'vector_semantic' => 'Semantic (AI)',
            'vector_root' => 'Morphology',
            'filter' => 'Filter',
            'fuzzy' => 'Fuzzy',
            default => 'Keyword',
        };

        // Hit our own API for autocomplete (keeps it unified)
        // Alternatively we can use Scout directly here
        // We'll use Scout directly to avoid internal HTTP overhead
        $roots = LexiconRoot::search($this->query)->take(3)->get();
        $sentences = Sentence::search($this->query)->take(3)->get();
        $entities = \App\Models\Entity::search($this->query)->take(5)->get();

        $this->suggestions = [
            'roots' => $roots->toArray(),
            'sentences' => $sentences->toArray(),
            'entities' => $entities->toArray(),
        ];
    }

    public function executeSearch()
    {
        if (empty(trim($this->query))) {
            return;
        }

        // We dispatch the event to the Alpine/Frontend layer which might manage its own UI
        // And we emit intent
        $this->dispatch('scholar:results-updated', query: $this->query);
        $this->dispatch('scholar:search-intent', intent: $this->intentBadge);

        // In a real Livewire 4 setup with Alpine, we'll clear suggestions after executing
        $this->suggestions = [];
    }

    public function render()
    {
        return view('livewire.scholar.search-panel');
    }
}
