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
        Schema::create('sentence_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Read-Only monitoring table for the AI worker');
            $table->foreignUuid('source_book_id')->index()->constrained('source_books')->cascadeOnDelete();
            $table->text('raw_text');
            $table->string('status')->comment('pending, processing, failed, completed');
            $table->integer('attempts')->default(0);
            $table->text('error_log')->nullable()->comment('API errors');
            $table->timestamp('completed_at')->nullable()->comment('AI timeframe calc');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentence_jobs');
    }
};
