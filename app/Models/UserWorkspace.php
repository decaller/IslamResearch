<?php

namespace App\Models;

use Database\Factories\UserWorkspaceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWorkspace extends Model
{
    /** @use HasFactory<UserWorkspaceFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'layout_state' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
