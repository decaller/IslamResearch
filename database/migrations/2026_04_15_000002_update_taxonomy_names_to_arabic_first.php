<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promote Arabic name to the primary `name` column in taxonomies.
     *
     * Previously the seeder stored the English name in `name` and Arabic
     * in `metadata->name_ar`. This migration flips that: `name` = Arabic,
     * and `name_en` + `name_transliteration` stay in metadata for UI display.
     *
     * Also adds a dedicated `name_ar` column so queries can filter on
     * the Arabic name without a JSONB extract.
     */
    public function up(): void
    {
        Schema::table('taxonomies', function (Blueprint $table) {
            $table->string('name_ar')
                ->nullable()
                ->after('name')
                ->comment('Primary Arabic name (single source of truth for classification)');

            $table->string('name_en')
                ->nullable()
                ->after('name_ar')
                ->comment('English display name (for UI localisation only)');

            $table->string('name_transliteration')
                ->nullable()
                ->after('name_en')
                ->comment('ALA-LC transliteration used for URL-safe slug generation');
        });

        // Backfill from existing metadata JSONB
        DB::statement("
            UPDATE taxonomies
            SET
                name_ar              = metadata->>'name_ar',
                name_en              = metadata->>'name_en',
                name_transliteration = metadata->>'name_transliteration',
                name                 = COALESCE(
                                           NULLIF(metadata->>'name_ar', ''),
                                           NULLIF(metadata->>'name_transliteration', ''),
                                           metadata->>'name_en',
                                           name
                                       )
            WHERE metadata IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Revert name column back to English
        DB::statement("
            UPDATE taxonomies
            SET name = COALESCE(
                           NULLIF(name_en, ''),
                           NULLIF(name_transliteration, ''),
                           name_ar,
                           name
                       )
            WHERE name_en IS NOT NULL OR name_transliteration IS NOT NULL
        ");

        Schema::table('taxonomies', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'name_en', 'name_transliteration']);
        });
    }
};
