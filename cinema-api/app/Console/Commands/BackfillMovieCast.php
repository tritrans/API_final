<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillMovieCast extends Command
{
    protected $signature = 'backfill:cast {--force}';
    protected $description = 'Backfill people and movie_people from movies.cast JSON if missing';

    public function handle(): int
    {
        $movies = DB::table('movies')->select('id', 'title', 'cast')->get();
        $created = 0; $linked = 0; $skippedMovies = 0;

        foreach ($movies as $movie) {
            $cast = $movie->cast;
            if (is_string($cast)) {
                $decoded = json_decode($cast, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $cast = $decoded;
                }
            }
            if (!is_array($cast) || empty($cast)) {
                $skippedMovies++;
                continue;
            }

            foreach ($cast as $index => $name) {
                $name = trim((string)$name);
                if ($name === '') continue;

                $personId = DB::table('people')->where('name', $name)->value('id');
                if (!$personId) {
                    $personId = DB::table('people')->insertGetId([
                        'name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $created++;
                }

                DB::table('movie_people')->updateOrInsert(
                    ['movie_id' => $movie->id, 'person_id' => $personId, 'role' => 'actor'],
                    ['billing_order' => $index + 1, 'created_at' => now(), 'updated_at' => now()]
                );
                $linked++;
            }
        }

        $this->info("People created: {$created}, links upserted: {$linked}, movies skipped: {$skippedMovies}");
        return Command::SUCCESS;
    }
}


