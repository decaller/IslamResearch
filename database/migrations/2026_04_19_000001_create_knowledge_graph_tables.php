<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Unified Master Entities Table
        Schema::create('entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('canonical_name')->unique();
            $table->string('entity_type')->index(); // person, location, event, concept, book
            $table->jsonb('aliases')->nullable()->comment('Array of common abbreviations or alternative names');
            $table->text('description')->nullable()->comment('Enriched description from AI or Wikipedia');
            $table->string('wikipedia_url')->nullable();
            $table->jsonb('metadata')->nullable()->comment('Rich metadata (e.g. birth/death, coordinates)');
            $table->timestamps();
        });

        // Add vector column for semantic search on entities
        DB::statement('ALTER TABLE entities ADD COLUMN embedding vector(1024) NULL');
        DB::statement('CREATE INDEX entities_embedding_hnsw ON entities USING hnsw (embedding vector_cosine_ops)');

        // 2. Entity Relationships (Knowledge Graph Edges)
        Schema::create('entity_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('source_entity_id')->constrained('entities')->cascadeOnDelete();
            $table->foreignUuid('target_entity_id')->constrained('entities')->cascadeOnDelete();
            $table->string('relationship_type')->index()->comment('e.g. "Migrated To", "Narrated From"');
            $table->foreignUuid('evidence_sentence_id')->nullable()->constrained('sentences')->nullOnDelete();
            $table->float('confidence')->default(1.0);
            $table->timestamps();

            $table->unique(['source_entity_id', 'target_entity_id', 'relationship_type', 'evidence_sentence_id'], 'entity_relationship_unique');
        });

        // 3. Sentence Entity Pivot (Replacing sentence_tag)
        Schema::create('sentence_entity', function (Blueprint $table) {
            $table->foreignUuid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->foreignUuid('entity_id')->constrained('entities')->cascadeOnDelete();
            $table->float('confidence')->default(1.0);
            $table->jsonb('context_metadata')->nullable()->comment('Specific context about this hit');
            
            $table->primary(['sentence_id', 'entity_id']);
        });

        // 4. Ambiguity Queue (Review table for low-confidence AI matches)
        Schema::create('entity_ambiguity_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->string('surface_name')->comment('The name as it appears in text (e.g. "Umar")');
            $table->jsonb('candidate_entities')->comment('List of potential entity matches with confidence scores');
            $table->text('context_block')->comment('5 sentences before and after for context');
            $table->string('status')->default('pending')->index(); // pending, approved, rejected, manual_mapped
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_ambiguity_queue');
        Schema::dropIfExists('sentence_entity');
        Schema::dropIfExists('entity_relationships');
        Schema::dropIfExists('entities');
    }
};
