<?php

namespace App\Models;

use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
