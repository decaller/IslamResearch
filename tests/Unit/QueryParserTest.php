<?php

use App\Services\QueryParser;

it('identifies vector root search', function () {
    $parser = new QueryParser();
    $result = $parser->parse('#root:كتب');
    
    expect($result)->toBe([
        'type' => 'vector_root',
        'query' => 'كتب',
    ]);
});

it('identifies surah filter search', function () {
    $parser = new QueryParser();
    $result = $parser->parse('@surah:البقرة');
    
    expect($result)->toBe([
        'type' => 'filter',
        'filter_key' => 'surah',
        'query' => 'البقرة',
    ]);
});

it('identifies fuzzy search', function () {
    $parser = new QueryParser();
    $result = $parser->parse('~fuzzy:bismillah');
    
    expect($result)->toBe([
        'type' => 'fuzzy',
        'query' => 'bismillah',
    ]);
});

it('identifies semantic search for long queries', function () {
    $parser = new QueryParser();
    $result = $parser->parse('In the name of Allah the most gracious the most merciful');
    
    expect($result['type'])->toBe('vector_semantic');
});

it('defaults to keyword search for short queries', function () {
    $parser = new QueryParser();
    $result = $parser->parse('bismillah');
    
    expect($result['type'])->toBe('keyword');
});
