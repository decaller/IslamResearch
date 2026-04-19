<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityAmbiguityQueue extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'entity_ambiguity_queue';

    protected $guarded = [];

    protected $casts = [
        'candidate_entities' => 'array',
    ];

    public function sentence(): BelongsTo
    {
        return $this->belongsTo(Sentence::class);
    }
}
