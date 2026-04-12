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
        Schema::create('source_book_taxonomy', function (Blueprint $table) {
            $table->foreignUuid('book_id')->constrained('source_books')->cascadeOnDelete();
            $table->foreignUuid('taxonomy_id')->constrained('taxonomies')->cascadeOnDelete();
            $table->unique(['book_id', 'taxonomy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_book_taxonomy');
    }
};
