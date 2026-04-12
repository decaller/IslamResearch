<?php

namespace App\Models;

use Database\Factories\UserHabitFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHabit extends Model
{
    /** @use HasFactory<UserHabitFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sentence()
    {
        return $this->belongsTo(Sentence::class);
    }
}
