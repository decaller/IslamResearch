<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\SentenceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Sentence extends Model
{
    /** @use HasFactory<SentenceFactory> */
    use HasFactory, HasUuids, Searchable;

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

    public function sentenceJobs()
    {
        return $this->hasMany(SentenceJob::class);
    }

    public function words()
    {
        return $this->belongsToMany(LexiconWord::class, 'sentence_word', 'sentence_id', 'word_id')
            ->withPivot('source_type', 'positions');
    }

    public function toSearchableArray(): array
    {
        $array = [
            'id' => $this->id,
            'text_ar' => $this->sentence_text,
            'resource_type' => $this->resource_type?->value,
            'source_book_id' => $this->source_book_id,
            'metadata' => collect($this->metadata)->except(['Isnad', 'Root', 'Target_Ayah'])->toArray(), // filter what we need
        ];

        // Eager load translations if needed, but let's just use the relation if it's loaded or pluck
        if ($this->relationLoaded('translations')) {
            $array['translations'] = $this->translations->pluck('translation_text')->implode(' | ');
        }

        return $array;
    }

    public function shouldBeSearchable(): bool
    {
        return ! empty($this->sentence_text);
    }
}
