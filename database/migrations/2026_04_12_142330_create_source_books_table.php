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
        Schema::create('source_books', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('The entry point for all research data');
            $table->string('title');
            $table->foreignUuid('scholar_id')->nullable()->constrained('scholars')->nullOnDelete()->comment('Primary author/scholar');
            $table->string('resource_type');
            $table->string('language');
            $table->string('status');
            $table->jsonb('metadata')->nullable()->comment('Bibliographical details and source info');
            $table->timestamps();
            $table->softDeletes()->comment('Soft Deletes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_books');
    }
};
