<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityRelationship extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'source_entity_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'target_entity_id');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Sentence::class, 'evidence_sentence_id');
    }
}
