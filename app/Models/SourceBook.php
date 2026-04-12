<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\SourceBookFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourceBook extends Model
{
    /** @use HasFactory<SourceBookFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'resource_type' => ResourceType::class,
            'metadata' => 'array',
        ];
    }

    public function scholar()
    {
        return $this->belongsTo(Scholar::class);
    }
}
