<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CastLinkSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'the-dark-knight' => ['Christian Bale', 'Heath Ledger', 'Aaron Eckhart'],
            'inception' => ['Leonardo DiCaprio', 'Joseph Gordon-Levitt', 'Elliot Page'],
            'the-shawshank-redemption' => ['Tim Robbins', 'Morgan Freeman', 'Bob Gunton'],
            'pulp-fiction' => ['John Travolta', 'Uma Thurman', 'Samuel L. Jackson'],
            'the-godfather' => ['Marlon Brando', 'Al Pacino', 'James Caan'],
            'fight-club' => ['Brad Pitt', 'Edward Norton', 'Helena Bonham Carter'],
            'forrest-gump' => ['Tom Hanks', 'Robin Wright', 'Gary Sinise'],
            'the-matrix' => ['Keanu Reeves', 'Laurence Fishburne', 'Carrie-Anne Moss'],
            'goodfellas' => ['Robert De Niro', 'Ray Liotta', 'Joe Pesci'],
            'the-silence-of-the-lambs' => ['Jodie Foster', 'Anthony Hopkins', 'Scott Glenn'],
        ];

        $movies = DB::table('movies')->select('id', 'slug')->get();
        foreach ($movies as $movie) {
            $cast = $map[$movie->slug] ?? [];
            $order = 1;
            foreach ($cast as $name) {
                $personId = DB::table('people')->where('name', $name)->value('id');
                if (!$personId) {
                    $personId = DB::table('people')->insertGetId([
                        'name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('movie_people')->updateOrInsert(
                    ['movie_id' => $movie->id, 'person_id' => $personId, 'role' => 'actor'],
                    ['billing_order' => $order++, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}


