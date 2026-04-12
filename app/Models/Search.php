<?php

namespace App\Models;

use Database\Factories\SearchFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Search extends Model
{
    /** @use HasFactory<SearchFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function userSearches()
    {
        return $this->hasMany(UserSearch::class);
    }
}
