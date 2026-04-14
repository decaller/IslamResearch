<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add unique constraints to prevent duplicates in the AI pipeline.
     */
    public function up(): void
    {
        Schema::table('sentences', function (Blueprint $table) {
            $table->unique(['source_book_id', 'sentence_text']);
        });

        Schema::table('sentence_translations', function (Blueprint $table) {
            $table->unique(['sentence_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sentences', function (Blueprint $table) {
            $table->dropUnique(['source_book_id', 'sentence_text']);
        });

        Schema::table('sentence_translations', function (Blueprint $table) {
            $table->dropUnique(['sentence_id', 'language']);
        });
    }
};
