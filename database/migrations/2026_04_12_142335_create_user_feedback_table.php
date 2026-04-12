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
        Schema::create('user_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Tracks feedback for potential global search tuning');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->string('search_query')->comment('The exact query used to find this sentence');
            $table->integer('relevance_score')->comment('0 to 100');
            $table->string('status');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->comment('Which admin resolved this?');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedback');
    }
};
