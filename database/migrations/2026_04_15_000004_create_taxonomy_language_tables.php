<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds translation and transliteration tables for taxonomies,
 * plus embedding_ar column on the taxonomies table itself.
 *
 * Search routing:
 *   Arabic query → taxonomies.embedding_ar
 *   Latin query  → taxonomy_translations.embedding | taxonomy_transliterations.embedding
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Arabic embedding on the taxonomies table itself
        DB::statement(
            'ALTER TABLE taxonomies ADD COLUMN embedding_ar vector(1024) NULL'
        );
        DB::statement(
            'CREATE INDEX taxonomies_embedding_ar_hnsw ON taxonomies USING hnsw (embedding_ar vector_cosine_ops)'
        );

        // 2. Translations of taxonomy names
        Schema::create('taxonomy_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('taxonomy_id')->constrained('taxonomies')->cascadeOnDelete();
            $table->string('language', 10)->comment('ISO code: id, en, ms');
            $table->string('translation_text')->comment('e.g. "Tafsir (Exegesis)" or "Ilmu Tafsir"');
            $table->timestamps();

            $table->unique(['taxonomy_id', 'language']);
        });

        DB::statement(
            'ALTER TABLE taxonomy_translations ADD COLUMN embedding vector(1024) NULL'
        );
        DB::statement(
            'CREATE INDEX taxonomy_translations_embedding_hnsw ON taxonomy_translations USING hnsw (embedding vector_cosine_ops)'
        );

        // 3. Transliterations of taxonomy names
        Schema::create('taxonomy_transliterations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('taxonomy_id')->constrained('taxonomies')->cascadeOnDelete();
            $table->string('scheme', 20)->comment('e.g. ala_lc');
            $table->string('transliteration_text')->comment('e.g. "Ulum al-Quran"');
            $table->timestamps();

            $table->unique(['taxonomy_id', 'scheme']);
        });

        DB::statement(
            'ALTER TABLE taxonomy_transliterations ADD COLUMN embedding vector(1024) NULL'
        );
        DB::statement(
            'CREATE INDEX taxonomy_transliterations_embedding_hnsw ON taxonomy_transliterations USING hnsw (embedding vector_cosine_ops)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomy_transliterations');
        Schema::dropIfExists('taxonomy_translations');

        Schema::table('taxonomies', function (Blueprint $table) {
            $table->dropColumn('embedding_ar');
        });
    }
};
