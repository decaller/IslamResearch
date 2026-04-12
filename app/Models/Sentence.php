<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\SentenceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sentence extends Model
{
    /** @use HasFactory<SentenceFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'resource_type' => ResourceType::class,
            'metadata' => 'array',
        ];
    }

    public function sourceBook()
    {
        return $this->belongsTo(SourceBook::class);
    }

    public function transliterations()
    {
        return $this->hasMany(SentenceTransliteration::class);
    }

    public function translations()
    {
        return $this->hasMany(SentenceTranslation::class);
    }

    public function words()
    {
        return $this->belongsToMany(LexiconWord::class, 'sentence_word', 'sentence_id', 'word_id')
            ->withPivot('source_type', 'positions');
    }
}
