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
        Schema::table('sentence_translations', function (Blueprint $table) {
            $table->unique(['sentence_id', 'language'], 'sentence_translations_sentence_id_language_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sentence_translations', function (Blueprint $table) {
            $table->dropUnique('sentence_translations_sentence_id_language_unique');
        });
    }
};
