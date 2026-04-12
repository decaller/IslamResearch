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
        Schema::create('sentence_word', function (Blueprint $table) {
            $table->foreignUuid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->foreignUuid('word_id')->constrained('lexicon_words')->cascadeOnDelete();
            $table->string('source_type')->comment('Identifies if the word was found in the main sentence_text or a translation');
            $table->json('positions')->nullable()->comment('Array of exact word indices (e.g., [3,14])');
            $table->unique(['word_id', 'sentence_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentence_word');
    }
};
