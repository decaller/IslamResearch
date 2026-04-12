<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use 'vector' extension which is available in pgvector/pgvector image
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector CASCADE;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS vector CASCADE;');
    }
};
