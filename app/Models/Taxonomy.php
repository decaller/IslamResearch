<?php

namespace App\Models;

use Database\Factories\TaxonomyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Taxonomy extends Model
{
    /** @use HasFactory<TaxonomyFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    /**
     * Arabic-first naming:
     *
     * - `name`                 → Arabic canonical label (used as classify.py taxonomy key)
     * - `name_ar`              → Dedicated Arabic column (fast SQL filter)
     * - `name_en`              → English display label (UI localisation only)
     * - `name_transliteration` → ALA-LC romanisation (slug source, URL-safe)
     * - `slug`                 → Derived from name_transliteration
     * - `metadata`             → Mirrors names + level for JSONB queries
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Taxonomy::class, 'parent_id')->orderBy('slug');
    }

    /**
     * Return the Arabic name, falling back gracefully for display.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_ar ?: $this->name ?: $this->name_en ?? '';
    }
}
