<?php

namespace App\Models;

use Database\Factories\LexiconWordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LexiconWord extends Model
{
    /** @use HasFactory<LexiconWordFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function root()
    {
        return $this->belongsTo(LexiconRoot::class, 'root_id');
    }

    public function sentences()
    {
        return $this->belongsToMany(Sentence::class, 'sentence_word', 'word_id', 'sentence_id')
            ->withPivot('source_type', 'positions');
    }
}
