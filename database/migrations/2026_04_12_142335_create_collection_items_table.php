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
        Schema::create('collection_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->uuidMorphs('itemable');
            $table->text('raw_query')->nullable()->comment('Used if this item is a saved dynamic search/workspace');
            $table->jsonb('metadata')->nullable()->comment('Stores UI state like breadcrumbs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_items');
    }
};
