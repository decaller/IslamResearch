<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->comment('e.g., Default Workspace, Fiqh Project');
            $table->boolean('is_active')->default(false)->comment('Is this the currently loaded workspace?');
            $table->jsonb('layout_state')->nullable()->comment('Stores active tabs, split pane config, and scroll positions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_workspaces');
    }
};
