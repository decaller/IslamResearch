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
        $nameAr = $item['name_ar'] ?? '';
        $nameEn = $item['name_en'] ?? '';
        $nameTransliteration = $item['name_transliteration'] ?? '';

        $name = $nameEn ?: $nameTransliteration ?: $nameAr;
        if (! $name) {
            return;
        }

        $slug = Str::slug($name);

        // Ensure slug is unique globally
        $slugBase = $slug;
        $counter = 1;
        while (Taxonomy::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$counter++;
        }

        $taxonomy = Taxonomy::create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'slug' => $slug,
            'metadata' => [
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'name_transliteration' => $nameTransliteration,
                'level' => $item['level'] ?? ($parent ? $parent->metadata['level'] + 1 : 1),
            ],
            // Path will be updated after creation to include its own UUID
        ]);

        // Generate ltree path
        // ltree path labels must be Alphanumeric and underscores
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
