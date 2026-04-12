<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncWorkspaceOnLogout
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
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        $userId = $event->user->id;
        $cachedState = \Illuminate\Support\Facades\Redis::get("user_workspace_{$userId}");

        if ($cachedState) {
            $workspace = \Illuminate\Support\Facades\DB::table('user_workspaces')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if ($workspace) {
                \Illuminate\Support\Facades\DB::table('user_workspaces')
                    ->where('id', $workspace->id)
                    ->update([
                        'layout_state' => $cachedState,
                        'updated_at' => now(),
                    ]);
            } else {
                \Illuminate\Support\Facades\DB::table('user_workspaces')->insert([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'user_id' => $userId,
                    'name' => 'Default Workspace',
                    'is_active' => true,
                    'layout_state' => $cachedState,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            \Illuminate\Support\Facades\Redis::del("user_workspace_{$userId}");
        }
    }
}
