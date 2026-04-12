<?php

namespace App\Models;

use Database\Factories\SentenceTransliterationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentenceTransliteration extends Model
{
    /** @use HasFactory<SentenceTransliterationFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function sentence()
    {
        return $this->belongsTo(Sentence::class);
    }
}
