<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Taxonomy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin->value,
            ]
        );

        // Structural Data from Reference
        $this->seedTaxonomies();
    }

    private function seedTaxonomies(): void
    {
        $jsonPath = base_path('reference/taxonomy/main.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $data = json_decode(File::get($jsonPath), true);
        if (! isset($data['taxonomy'])) {
            return;
        }

        foreach ($data['taxonomy'] as $item) {
            $this->createTaxonomy($item, null);
        }
    }

    private function createTaxonomy(array $item, ?Taxonomy $parent): void
    {
        $nameAr             = $item['name_ar'] ?? '';
        $nameEn             = $item['name_en'] ?? '';
        $nameTransliteration = $item['name_transliteration'] ?? '';

        // Arabic-first: the canonical `name` column stores the Arabic label.
        // English and transliteration are display/URL helpers only.
        $name = $nameAr ?: $nameTransliteration ?: $nameEn;
        if (! $name) {
            return;
        }

        // Slug derived from transliteration (URL-safe Latin script).
        // Falls back to English, then slugified Arabic if nothing else is available.
        $slugSource = $nameTransliteration ?: $nameEn ?: $nameAr;
        $slug = Str::slug($slugSource);

        // Ensure slug is unique globally
        $slugBase = $slug;
        $counter = 1;
        while (Taxonomy::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$counter++;
        }

        $taxonomy = Taxonomy::create([
            'parent_id'           => $parent?->id,
            'name'                => $name,              // Arabic (canonical)
            'name_ar'             => $nameAr,
            'name_en'             => $nameEn,
            'name_transliteration' => $nameTransliteration,
            'slug'                => $slug,
            'metadata'            => [
                // Keep in metadata too for JSONB queries in classify.py / UI filters
                'name_ar'             => $nameAr,
                'name_en'             => $nameEn,
                'name_transliteration' => $nameTransliteration,
                'level'               => $item['level'] ?? ($parent ? ($parent->metadata['level'] ?? 0) + 1 : 1),
            ],
        ]);

        // Generate ltree path
        $uuidLabel = str_replace('-', '_', $taxonomy->id);
        $path = $parent ? $parent->path.'.'.$uuidLabel : $uuidLabel;
        $taxonomy->update(['path' => $path]);

        // Recursively handle children
        $childrenKeys = ['chapters', 'detailed_taxonomy', 'sections', 'abwaab'];
        foreach ($childrenKeys as $key) {
            if (isset($item[$key]) && is_array($item[$key])) {
                foreach ($item[$key] as $child) {
                    $this->createTaxonomy($child, $taxonomy);
                }
            }
        }
    }
}
