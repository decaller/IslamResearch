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
        Schema::create('sentence_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sentence_id')->index()->constrained('sentences')->cascadeOnDelete();
            $table->string('language')->comment('e.g., id, en, ur, fr');
            $table->foreignUuid('scholar_id')->nullable()->constrained('scholars')->nullOnDelete()->comment('Optional author/source of this translation');
            $table->text('translation_text');
            $table->timestamps();
        });
        DB::statement('ALTER TABLE sentence_translations ADD COLUMN embedding vector(1024);');
        Schema::table('sentence_translations', function (Blueprint $table) {
            $table->index(['sentence_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentence_translations');
    }
};
