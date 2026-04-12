<?php

namespace App\Models;

use App\Enums\ActionType;
use Database\Factories\UserJourneyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserJourney extends Model
{
    /** @use HasFactory<UserJourneyFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'action_type' => ActionType::class,
            'context_data' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targetSentence()
    {
        return $this->belongsTo(Sentence::class, 'target_sentence_id');
    }

    public function targetSearch()
    {
        return $this->belongsTo(Search::class, 'target_search_id');
    }
}
