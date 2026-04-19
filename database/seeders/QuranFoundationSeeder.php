<?php

namespace Database\Seeders;

use App\Models\SourceBook;
use Illuminate\Database\Seeder;

class QuranFoundationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SourceBook::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000000001'], // Fixed UUID for testing
            [
                'title' => 'Quran Foundation (Quran.com) V4',
                'author' => 'Quran Foundation',
                'resource_type' => 'quran',
                'language' => 'ar',
                'status' => 'active',
                'metadata' => [
                    'source_url' => 'https://api.quran.com/api/v4',
                    'arabic_edition' => 'quran-foundation-v4',
                ],
                'pipeline_id' => 'quran_foundation_flow',
            ]
        );
    }
}
