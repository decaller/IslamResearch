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
        Schema::table('sentence_jobs', function (Blueprint $table) {
            $table->string('target_language')->default('id')->after('needs_transliteration');
            $table->string('transliteration_scheme')->default('ala_lc')->after('target_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sentence_jobs', function (Blueprint $table) {
            $table->dropColumn(['target_language', 'transliteration_scheme']);
        });
    }
};
