<?php

namespace Database\Seeders;

use App\Models\Cast;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CastSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $casts = [
            'Brad Pitt',
            'Leonardo DiCaprio',
            'Tom Hanks',
            'Robert Downey Jr.',
            'Chris Evans',
            'Scarlett Johansson',
            'Emma Stone',
            'Ryan Gosling',
            'Jennifer Lawrence',
            'Chris Hemsworth',
            'Mark Ruffalo',
            'Jeremy Renner',
            'Samuel L. Jackson',
            'Tom Holland',
            'Benedict Cumberbatch',
            'Gal Gadot',
            'Henry Cavill',
            'Ben Affleck',
            'Amy Adams',
            'Will Smith',
            'Denzel Washington',
            'Morgan Freeman',
            'Al Pacino',
            'Robert De Niro',
            'Meryl Streep',
            'Cate Blanchett',
            'Natalie Portman',
            'Anne Hathaway',
            'Emma Watson',
            'Daniel Radcliffe',
            'Rupert Grint',
            'Johnny Depp',
            'Keanu Reeves',
            'Matthew McConaughey',
            'Christian Bale',
            'Heath Ledger',
            'Joaquin Phoenix',
            'Jake Gyllenhaal',
            'Ryan Reynolds',
            'Hugh Jackman',
            'Patrick Stewart',
            'Ian McKellen',
            'Michael Fassbender',
            'James McAvoy',
            'Jennifer Aniston',
            'Courteney Cox',
            'Lisa Kudrow',
            'Matt LeBlanc',
            'Matthew Perry',
            'David Schwimmer'
        ];

        foreach ($casts as $castName) {
            Cast::firstOrCreate(
                ['name' => $castName],
                [
                    'name' => $castName,
                    'slug' => Str::slug($castName),
                    'bio' => "Professional actor known for various roles in film and television.",
                    'nationality' => 'American',
                ]
            );
        }

        $this->command->info('Cast data seeded successfully!');
    }
}