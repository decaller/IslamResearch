<?php

namespace App\Models;

use Database\Factories\LexiconRootFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LexiconRoot extends Model
{
    /** @use HasFactory<LexiconRootFactory> */
    use HasFactory, HasUuids;

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
}
