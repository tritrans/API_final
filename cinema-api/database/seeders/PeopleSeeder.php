<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeopleSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Robert Downey Jr.', 'Chris Evans', 'Scarlett Johansson', 'Chris Hemsworth',
            'Leonardo DiCaprio', 'Tom Hanks', 'Morgan Freeman', 'Brad Pitt'
        ];

        foreach ($names as $name) {
            DB::table('people')->updateOrInsert(['name' => $name], []);
        }

        // Map some people to first few movies as actors
        $movies = DB::table('movies')->select('id')->limit(5)->get();
        $people = DB::table('people')->pluck('id', 'name');

        $idx = 1;
        foreach ($movies as $movie) {
            foreach (['Robert Downey Jr.', 'Scarlett Johansson'] as $pname) {
                $pid = $people[$pname] ?? null;
                if ($pid) {
                    DB::table('movie_people')->updateOrInsert(
                        ['movie_id' => $movie->id, 'person_id' => $pid, 'role' => 'actor'],
                        ['billing_order' => $idx++]
                    );
                }
            }
        }
    }
}


