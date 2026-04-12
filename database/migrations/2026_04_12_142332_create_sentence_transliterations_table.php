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
        Schema::create('sentence_transliterations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sentence_id')->constrained('sentences')->cascadeOnDelete();
            $table->string('scheme')->comment('e.g., ala_lc, buckwalter');
            $table->text('transliteration_text');
            $table->timestamps();
            $table->unique(['sentence_id', 'scheme']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentence_transliterations');
    }
};
