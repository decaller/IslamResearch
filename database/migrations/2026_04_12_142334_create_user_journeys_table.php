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
        Schema::create('user_journeys', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Tracks user research journeys and breadcrumbs');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action_type');
            // In migration, searches table doesn't exist yet! Wait.
            // searches table was migrated at _142334_ while this is also _142334_.
            // Alphabetical order: user_journeys comes after searches? Wait, `create_searches_table` vs `create_user_journeys_table` -> s comes before u.
            $table->foreignUuid('target_sentence_id')->nullable()->constrained('sentences')->nullOnDelete();
            $table->foreignUuid('target_search_id')->nullable()->constrained('searches')->nullOnDelete();
            $table->jsonb('context_data')->nullable()->comment('Strictly non-relational metadata');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_journeys');
    }
};
