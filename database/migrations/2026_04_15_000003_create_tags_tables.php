<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the normalised tags table and its language tables.
 *
 * Tags were previously stored as JSONB arrays in sentences.metadata['tags'].
 * They are now first-class entities with Arabic embeddings, translations,
 * and transliterations — enabling symmetric multilingual search.
 *
 * Search routing:
 *   Arabic query  → tags.embedding_ar
 *   Latin query   → tag_translations.embedding | tag_transliterations.embedding
 */
return new class extends Migration
{
    public function up(): void
    {
        // Core tags table (Arabic-first)
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_ar')->unique()->comment('Arabic canonical label (e.g. تزكية النفس)');
            $table->string('slug')->unique()->comment('URL-safe key from ALA-LC transliteration');
            $table->timestamps();
        });

        // pgvector embedding column (added separately for compatibility)
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE tags ADD COLUMN embedding_ar vector(1024) NULL'
        );
        \Illuminate\Support\Facades\DB::statement(
            'CREATE INDEX tags_embedding_ar_hnsw ON tags USING hnsw (embedding_ar vector_cosine_ops)'
        );

        // Translations of tags
        Schema::create('tag_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('language', 10)->comment('ISO code: id, en, ms, ur');
            $table->string('translation_text')->comment('e.g. "Purification of the Soul"');
            $table->timestamps();

            $table->unique(['tag_id', 'language']);
        });

        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE tag_translations ADD COLUMN embedding vector(1024) NULL'
        );
        \Illuminate\Support\Facades\DB::statement(
            'CREATE INDEX tag_translations_embedding_hnsw ON tag_translations USING hnsw (embedding vector_cosine_ops)'
        );

        // Transliterations of tags
        Schema::create('tag_transliterations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('scheme', 20)->comment('ala_lc | buckwalter | dmg');
            $table->string('transliteration_text')->comment('e.g. "Tazkiyat al-Nafs"');
            $table->timestamps();

            $table->unique(['tag_id', 'scheme']);
        });

        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE tag_transliterations ADD COLUMN embedding vector(1024) NULL'
        );
        \Illuminate\Support\Facades\DB::statement(
            'CREATE INDEX tag_transliterations_embedding_hnsw ON tag_transliterations USING hnsw (embedding vector_cosine_ops)'
        );

        // Pivot: sentence ↔ tag
        Schema::create('sentence_tag', function (Blueprint $table) {
            $table->foreignUuid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->foreignUuid('tag_id')->constrained('tags')->cascadeOnDelete();

            $table->primary(['sentence_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentence_tag');
        Schema::dropIfExists('tag_transliterations');
        Schema::dropIfExists('tag_translations');
        Schema::dropIfExists('tags');
    }
};
