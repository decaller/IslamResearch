<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncWorkspaceOnLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        $workspace = \Illuminate\Support\Facades\DB::table('user_workspaces')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($workspace && $workspace->layout_state) {
            \Illuminate\Support\Facades\Redis::set("user_workspace_{$user->id}", $workspace->layout_state);
        } else {
            \Illuminate\Support\Facades\Redis::del("user_workspace_{$user->id}");
        }
    }
}
