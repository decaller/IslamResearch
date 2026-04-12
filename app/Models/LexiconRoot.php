<?php

namespace App\Models;

use Database\Factories\LexiconRootFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class LexiconRoot extends Model
{
    /** @use HasFactory<LexiconRootFactory> */
    use HasFactory, HasUuids, Searchable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function words()
    {
        return $this->hasMany(LexiconWord::class, 'root_id');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'root_value' => $this->root_value,
            'metadata' => $this->metadata,
        ];
    }
}
