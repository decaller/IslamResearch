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
        // CASCADE ensures the base 'vector' extension is also installed automatically
        DB::statement('CREATE EXTENSION IF NOT EXISTS vectorscale CASCADE;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS vectorscale CASCADE;');
    }
};
