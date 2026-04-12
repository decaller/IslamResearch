<?php

namespace App\Models;

use Database\Factories\SentenceTranslationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentenceTranslation extends Model
{
    /** @use HasFactory<SentenceTranslationFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function sentence()
    {
        return $this->belongsTo(Sentence::class);
    }

    public function scholar()
    {
        return $this->belongsTo(Scholar::class);
    }
}
