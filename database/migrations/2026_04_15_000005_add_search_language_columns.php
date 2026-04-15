<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds embedding column to sentence_transliterations.
 *
 * Previously this table stored only the transliteration text.
 * Adding a vector embedding allows Latin search queries to be
 * compared against transliterated Arabic via ANN similarity.
 *
 * Also adds script_type to the searches table so the search
 * engine knows which embedding indexes to query.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Embedding on sentence_transliterations
        DB::statement(
            'ALTER TABLE sentence_transliterations ADD COLUMN embedding vector(1024) NULL'
        );
        DB::statement(
            'CREATE INDEX sentence_transliterations_embedding_hnsw ON sentence_transliterations USING hnsw (embedding vector_cosine_ops)'
        );

        // 2. script_type on searches (arabic | latin)
        Schema::table('searches', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('script_type', 10)
                ->nullable()
                ->after('query')
                ->comment('Detected script of the query: arabic | latin — determines which ANN indexes are queried');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS sentence_transliterations_embedding_hnsw');

        Schema::table('sentence_transliterations', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn('embedding');
        });

        Schema::table('searches', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn('script_type');
        });
    }
};
