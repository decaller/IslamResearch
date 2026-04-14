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
        // Clear existing data as per user approval to avoid foreign key conflicts
        Schema::disableForeignKeyConstraints();
        DB::table('sentence_jobs')->truncate();
        Schema::enableForeignKeyConstraints();

        Schema::table('sentence_jobs', function (Blueprint $table) {
            // Drop old columns
            $table->dropForeign(['source_book_id']);
            $table->dropColumn(['source_book_id', 'raw_text']);

            // Add new columns
            $table->foreignUuid('sentence_id')->after('id')->index()->constrained('sentences')->cascadeOnDelete();

            // Requirement flags
            $table->boolean('needs_embedding')->default(false)->after('sentence_id');
            $table->boolean('needs_translation')->default(false)->after('needs_embedding');
            $table->boolean('needs_transliteration')->default(false)->after('needs_translation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sentence_jobs', function (Blueprint $table) {
            $table->dropForeign(['sentence_id']);
            $table->dropColumn(['sentence_id', 'needs_embedding', 'needs_translation', 'needs_transliteration']);

            $table->foreignUuid('source_book_id')->index()->nullable()->constrained('source_books')->cascadeOnDelete();
            $table->text('raw_text')->nullable();
        });
    }
};
