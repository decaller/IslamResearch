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
        Schema::create('sentences', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('The final, AI-enriched result');
            $table->foreignUuid('source_book_id')->index()->constrained('source_books')->cascadeOnDelete();
            $table->string('resource_type');
            $table->integer('sequence_number')->comment('Sequential order of sentence within the resource');
            $table->text('sentence_text')->comment('The core sentence content');
            $table->jsonb('metadata')->nullable()->comment('Dynamic LLM data (Isnad, Root, Target_Ayah, etc.)');
            $table->timestamps();
        });
        DB::statement('ALTER TABLE sentences ADD COLUMN embedding_ar vector(1024);');
        DB::statement('CREATE INDEX idx_sentences_metadata ON sentences USING gin(metadata);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentences');
    }
};
