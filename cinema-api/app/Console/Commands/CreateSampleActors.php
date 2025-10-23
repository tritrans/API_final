<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Person;
use App\Models\Movie;

class CreateSampleActors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'actors:create-sample';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample actors and assign them to movies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating sample actors...');

        $sampleActors = [
            ['name' => 'Leonardo DiCaprio', 'country' => 'USA'],
            ['name' => 'Tom Hanks', 'country' => 'USA'],
            ['name' => 'Brad Pitt', 'country' => 'USA'],
            ['name' => 'Robert Downey Jr.', 'country' => 'USA'],
            ['name' => 'Scarlett Johansson', 'country' => 'USA'],
            ['name' => 'Chris Evans', 'country' => 'USA'],
            ['name' => 'Mark Ruffalo', 'country' => 'USA'],
            ['name' => 'Chris Hemsworth', 'country' => 'Australia'],
            ['name' => 'Samuel L. Jackson', 'country' => 'USA'],
            ['name' => 'Robert De Niro', 'country' => 'USA'],
        ];

        $sampleDirectors = [
            ['name' => 'Christopher Nolan', 'country' => 'UK'],
            ['name' => 'Steven Spielberg', 'country' => 'USA'],
            ['name' => 'Martin Scorsese', 'country' => 'USA'],
            ['name' => 'Quentin Tarantino', 'country' => 'USA'],
            ['name' => 'Ridley Scott', 'country' => 'UK'],
        ];

        // Create actors
        foreach ($sampleActors as $actorData) {
            Person::firstOrCreate(
                ['name' => $actorData['name']],
                [
                    'name' => $actorData['name'],
                    'country' => $actorData['country'],
                    'bio' => 'Famous actor'
                ]
            );
        }

        // Create directors
        foreach ($sampleDirectors as $directorData) {
            Person::firstOrCreate(
                ['name' => $directorData['name']],
                [
                    'name' => $directorData['name'],
                    'country' => $directorData['country'],
                    'bio' => 'Famous director'
                ]
            );
        }

        // Assign actors to movies
        $movies = Movie::all();
        $actors = Person::whereIn('name', array_column($sampleActors, 'name'))->get();
        $directors = Person::whereIn('name', array_column($sampleDirectors, 'name'))->get();

        foreach ($movies as $index => $movie) {
            // Assign 3-5 random actors
            $randomActors = $actors->random(rand(3, 5));
            foreach ($randomActors as $actorIndex => $actor) {
                $movie->movieCasts()->syncWithoutDetaching([
                    $actor->id => [
                        'character_name' => 'Character ' . ($actorIndex + 1),
                        'billing_order' => $actorIndex + 1,
                        'role' => 'actor'
                    ]
                ]);
            }

            // Assign 1 random director
            if ($directors->isNotEmpty()) {
                $director = $directors->random();
                $movie->movieCasts()->syncWithoutDetaching([
                    $director->id => [
                        'character_name' => null,
                        'billing_order' => 0,
                        'role' => 'director'
                    ]
                ]);
            }
        }

        $this->info('Sample actors and directors created successfully!');
        $this->info('Total actors: ' . Person::whereIn('name', array_column($sampleActors, 'name'))->count());
        $this->info('Total directors: ' . Person::whereIn('name', array_column($sampleDirectors, 'name'))->count());
    }
}

