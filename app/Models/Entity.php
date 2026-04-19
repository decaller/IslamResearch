<?php

namespace App\Models;

use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;

class Entity extends Model
{
    use HasFactory, HasUuids, Searchable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'entity_type' => EntityType::class,
            'aliases' => 'array',
            'metadata' => 'array',
            'last_enriched_at' => 'datetime',
        ];
    }

    /**
     * Sentences associated with this entity.
     */
    public function sentences(): BelongsToMany
    {
        return $this->belongsToMany(Sentence::class, 'sentence_entity')
            ->withPivot('confidence', 'context_metadata')
            ->withTimestamps();
    }

    /**
     * Outgoing relationships from this entity.
     */
    public function relationships(): HasMany
    {
        return $this->hasMany(EntityRelationship::class, 'source_entity_id');
    }

    /**
     * Incoming relationships to this entity.
     */
    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(EntityRelationship::class, 'target_entity_id');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'canonical_name' => $this->canonical_name,
            'entity_type' => $this->entity_type->value,
            'aliases' => $this->aliases,
            'description' => $this->description,
        ];
    }

    /**
     * Merge another entity into this one.
     */
    public function mergeWith(Entity $other): void
    {
        DB::transaction(function () use ($other) {
            // 1. Move outgoing relationships
            EntityRelationship::where('source_entity_id', $other->id)
                ->update(['source_entity_id' => $this->id]);

            // 2. Move incoming relationships
            EntityRelationship::where('target_entity_id', $other->id)
                ->update(['target_entity_id' => $this->id]);

            // 3. Move sentence links
            DB::table('sentence_entity')
                ->where('entity_id', $other->id)
                ->update(['entity_id' => $this->id]);

            // 4. Update aliases
            $currentAliases = $this->aliases ?? [];
            if (! in_array($other->canonical_name, $currentAliases)) {
                $currentAliases[] = $other->canonical_name;
            }
            if ($other->aliases) {
                $currentAliases = array_unique(array_merge($currentAliases, $other->aliases));
            }
            $this->update(['aliases' => $currentAliases]);

            // 5. Delete the other entity
            $other->delete();
        });
    }

    /**
     * Get all connected entities recursively with PostgreSQL cycle protection.
     */
    public function getRecursiveRelationships(int $maxDepth = 3)
    {
        return DB::select('
            WITH RECURSIVE graph_search AS (
                -- Base Case
                SELECT id, canonical_name, 0 as depth
                FROM entities 
                WHERE id = :entity_id
                
                UNION ALL
                
                -- Recursive Step
                SELECT e.id, e.canonical_name, gs.depth + 1
                FROM entities e
                JOIN entity_relationships er ON e.id = er.target_entity_id
                JOIN graph_search gs ON er.source_entity_id = gs.id
                WHERE gs.depth < :max_depth
            ) CYCLE id SET is_cycle USING path
            SELECT * FROM graph_search WHERE NOT is_cycle;
        ', [
            'entity_id' => $this->id,
            'max_depth' => $maxDepth,
        ]);
    }
}
