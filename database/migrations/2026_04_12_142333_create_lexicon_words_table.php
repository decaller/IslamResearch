<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lexicon_words', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Stores actual variations found in texts');
            $table->foreignUuid('root_id')->constrained('lexicon_roots')->cascadeOnDelete();
            $table->string('language')->comment("'ar' or 'id'");
            $table->string('word_raw')->comment('Exact word from text (with Harakat for Arabic)');
            $table->string('word_clean')->index()->comment('Stripped version (no Harakat)');
            $table->jsonb('metadata')->nullable()->comment('Root morphology, meanings, weights');
            $table->timestamps();
            $table->unique(['language', 'word_raw', 'word_clean']);
        });
        DB::statement('ALTER TABLE lexicon_words ADD COLUMN embedding_raw vector(1024);');
        DB::statement('ALTER TABLE lexicon_words ADD COLUMN embedding_clean vector(1024);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lexicon_words');
    }
};
