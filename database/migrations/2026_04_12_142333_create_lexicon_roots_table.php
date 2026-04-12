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
        Schema::create('lexicon_roots', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Stores the foundational base of words');
            $table->string('language')->comment("'ar' or 'id'");
            $table->string('root_value')->comment('e.g., qaf-waw-lam');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['language', 'root_value']);
        });
        DB::statement('ALTER TABLE lexicon_roots ADD COLUMN embedding vector(1024);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lexicon_roots');
    }
};
