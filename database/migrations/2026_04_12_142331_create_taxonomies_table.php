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
        DB::statement('CREATE EXTENSION IF NOT EXISTS ltree;');

        Schema::create('taxonomies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable()->comment('For nested categories');
            $table->string('name')->comment('e.g., Fiqh, Tafsir');
            $table->string('slug')->unique();
            $table->jsonb('metadata')->nullable()->comment('Flexible categorization data');
            $table->timestamps();
            $table->softDeletes()->comment('Soft Deletes');
        });

        Schema::table('taxonomies', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('taxonomies')->nullOnDelete();
        });

        DB::statement('ALTER TABLE taxonomies ADD COLUMN path ltree;');
        DB::statement('CREATE INDEX taxonomies_path_gist_idx ON taxonomies USING gist(path);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxonomies');
    }
};
