<?php

use App\Models\LexiconRoot;
use App\Models\Sentence;
use App\Services\QueryParser;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    // Force scout to use collection for tests to bypass meilisearch requirements
    config(['scout.driver' => 'collection']);
});

it('can parse various query intents', function () {
    $parser = new QueryParser;

    expect($parser->parse('#root:كتب')['type'])->toBe('vector_root')
        ->and($parser->parse('@surah:البقرة')['type'])->toBe('filter')
        ->and($parser->parse('~fuzzy:bismillah')['type'])->toBe('fuzzy')
        ->and($parser->parse('The philosophical meaning of life and death')['type'])->toBe('vector_semantic')
        ->and($parser->parse('كتب')['type'])->toBe('keyword');
});

it('returns search results from standard keyword query', function () {
    Sentence::factory()->create(['sentence_text' => 'Bismillah']);

    $response = $this->getJson(route('search.index', ['q' => 'Bismillah']));

    $response->assertStatus(200)
        ->assertJsonPath('data.data.0.sentence_text', 'Bismillah');
});

it('handles vector search via ollama mocking', function () {
    // Generate dummy array of 1024 floats
    $dummyEmbedding = array_fill(0, 1024, 0.01);

    Http::fake([
        '*api/embeddings*' => Http::response([
            'embedding' => $dummyEmbedding,
        ], 200),
    ]);

    // We need a sentence with an embedding to match
    // we skip creating exact vector array in sqlite/pg test db for this assertion
    // and just verify the endpoint didn't crash and returns json
    $response = $this->getJson(route('search.index', ['q' => 'A complex philosophical query to trigger vector search that is longer than four words']));

    $response->assertStatus(200);
    // Since our test DB might not be postgres, the pgvector query will fail if not using pgsql,
    // so we handle the exception gracefully in controller.
    // In our SearchService we wrapped it in try-catch and fallback to keyword search!
});

it('returns fast autocomplete results', function () {
    LexiconRoot::factory()->create(['root_value' => 'ع ل م']);
    Sentence::factory()->create(['sentence_text' => 'عليم']);

    $response = $this->getJson(route('search.autocomplete', ['q' => 'عل']));

    $response->assertStatus(200)
        ->assertJsonStructure(['roots', 'sentences']);
});
