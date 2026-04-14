<?php

namespace App\Models;

use Database\Factories\SentenceJobFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentenceJob extends Model
{
    /** @use HasFactory<SentenceJobFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'needs_embedding' => 'boolean',
            'needs_translation' => 'boolean',
            'needs_transliteration' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function sentence()
    {
        return $this->belongsTo(Sentence::class);
    }
}
