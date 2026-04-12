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
        Schema::create('searches', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Caches query embeddings to minimize API costs');
            $table->string('query')->unique()->comment('The raw search text');
            $table->timestamps();
        });
        DB::statement('ALTER TABLE searches ADD COLUMN embedding vector(1024);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('searches');
    }
};
