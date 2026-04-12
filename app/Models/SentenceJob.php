<?php

namespace App\Models;

use Database\Factories\SentenceJobFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentenceJob extends Model
{
    /** @use HasFactory<SentenceJobFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function sourceBook()
    {
        return $this->belongsTo(SourceBook::class);
    }
}
