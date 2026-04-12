<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\LexiconRoot;
use App\Models\LexiconWord;
use App\Models\Scholar;
use App\Models\Search;
use App\Models\Sentence;
use App\Models\SentenceJob;
use App\Models\SentenceTranslation;
use App\Models\SentenceTransliteration;
use App\Models\SourceBook;
use App\Models\Taxonomy;
use App\Models\User;
use App\Models\UserFeedback;
use App\Models\UserHabit;
use App\Models\UserJourney;
use App\Models\UserSearch;
use App\Models\UserWorkspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin->value,
            ]
        );

        $scholars = Scholar::factory(5)->create();
        $roots = LexiconRoot::factory(10)->create();

        $roots->each(function ($root) {
            LexiconWord::factory(3)->create(['root_id' => $root->id]);
        });

        $words = LexiconWord::all();

        $scholars->each(function ($scholar) use ($words) {
            $books = SourceBook::factory(2)->create(['scholar_id' => $scholar->id]);

            $books->each(function ($book) use ($words, $scholar) {
                SentenceJob::factory(1)->create(['source_book_id' => $book->id]);

                $sentences = Sentence::factory(5)->create(['source_book_id' => $book->id]);

                $sentences->each(function ($sentence) use ($words, $scholar) {
                    SentenceTranslation::factory(2)->create([
                        'sentence_id' => $sentence->id,
                        'scholar_id' => $scholar->id,
                    ]);
                    SentenceTransliteration::factory(1)->create([
                        'sentence_id' => $sentence->id,
                    ]);

                    $sentence->words()->attach(
                        $words->random(2)->pluck('id')->toArray(),
                        ['source_type' => 'main_text', 'positions' => json_encode([1, 2])]
                    );
                });
            });
        });

        $taxonomies = Taxonomy::factory(5)->create();

        SourceBook::all()->each(function ($book) use ($taxonomies) {
            DB::table('source_book_taxonomy')->insert([
                'book_id' => $book->id,
                'taxonomy_id' => $taxonomies->random()->id,
            ]);
        });

        UserWorkspace::factory(2)->create(['user_id' => $admin->id]);
        $search = Search::factory()->create();
        UserSearch::factory()->create(['user_id' => $admin->id, 'search_id' => $search->id]);
        UserJourney::factory(5)->create(['user_id' => $admin->id]);

        $collection = Collection::factory()->create(['user_id' => $admin->id]);
        $sentenceId = Sentence::inRandomOrder()->value('id');
        if ($sentenceId) {
            CollectionItem::factory(3)->create([
                'collection_id' => $collection->id,
                'itemable_type' => Sentence::class,
                'itemable_id' => $sentenceId,
            ]);

            UserFeedback::factory(2)->create([
                'user_id' => $admin->id,
                'sentence_id' => $sentenceId,
            ]);

            UserHabit::factory(2)->create([
                'user_id' => $admin->id,
                'sentence_id' => $sentenceId,
            ]);
        }
    }
}
