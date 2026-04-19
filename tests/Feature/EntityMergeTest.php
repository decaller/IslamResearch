<?php

use App\Models\Entity;
use App\Models\EntityRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('entities can be merged correctly', function () {
    // 1. Create two entities
    $master = Entity::factory()->create([
        'canonical_name' => 'Salah',
        'aliases' => ['Prayer']
    ]);
    
    $other = Entity::factory()->create([
        'canonical_name' => 'Salat',
        'aliases' => ['Namaz']
    ]);

    // 2. Create a relationship for 'other'
    $target = Entity::factory()->create(['canonical_name' => 'Fard']);
    EntityRelationship::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'source_entity_id' => $other->id,
        'target_entity_id' => $target->id,
        'relationship_type' => 'is_type',
        'confidence' => 1.0
    ]);

    // 3. Merge 'other' into 'master'
    $master->mergeWith($other);

    // 4. Assertions
    $master->refresh();
    
    // Salat should be in aliases
    expect($master->aliases)->toContain('Salat');
    expect($master->aliases)->toContain('Prayer');
    expect($master->aliases)->toContain('Namaz');
    
    // Relationship should move to master
    expect(EntityRelationship::where('source_entity_id', $master->id)->count())->toBe(1);
    expect(EntityRelationship::where('source_entity_id', $other->id)->count())->toBe(0);
    
    // Entity 'other' should be deleted
    expect(Entity::find($other->id))->toBeNull();
});
