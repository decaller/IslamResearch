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

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(Taxonomy::class, 'parent_id');
    }
}
