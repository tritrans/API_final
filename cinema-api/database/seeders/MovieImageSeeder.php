<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;

class MovieImageSeeder extends Seeder
{
    public function run()
    {
        // Google Drive URLs cho các bộ phim (sử dụng direct image URLs)
        $movies = [
            [
                'title' => 'Fight Club',
                'slug' => 'fight-club',
                'poster_url' => 'https://drive.google.com/thumbnail?id=1pdGtFcQnNA7px-xcoSOL1c9vipQAxoLy&sz=w500-h750',
                'backdrop_url' => 'https://drive.google.com/thumbnail?id=1pdGtFcQnNA7px-xcoSOL1c9vipQAxoLy&sz=w1920-h1080',
            ],
            [
                'title' => 'Forrest Gump',
                'slug' => 'forrest-gump',
                'poster_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w500-h750',
                'backdrop_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w1920-h1080',
            ],
            [
                'title' => 'The Godfather',
                'slug' => 'the-godfather',
                'poster_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w500-h750',
                'backdrop_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w1920-h1080',
            ],
            [
                'title' => 'Goodfellas',
                'slug' => 'goodfellas',
                'poster_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w500-h750',
                'backdrop_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w1920-h1080',
            ],
            [
                'title' => 'The Matrix',
                'slug' => 'the-matrix',
                'poster_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w500-h750',
                'backdrop_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w1920-h1080',
            ],
            [
                'title' => 'Pulp Fiction',
                'slug' => 'pulp-fiction',
                'poster_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w500-h750',
                'backdrop_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w1920-h1080',
            ],
            [
                'title' => 'The Shawshank Redemption',
                'slug' => 'shawshank-redemption',
                'poster_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w500-h750',
                'backdrop_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w1920-h1080',
            ],
            [
                'title' => 'The Silence of the Lambs',
                'slug' => 'silence-of-the-lambs',
                'poster_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w500-h750',
                'backdrop_url' => 'https://drive.google.com/thumbnail?id=1AY1Xgo9VIPvalhX07usWRKbEadma0FzS&sz=w1920-h1080',
            ],
        ];

        foreach ($movies as $movieData) {
            Movie::updateOrCreate(
                ['slug' => $movieData['slug']],
                [
                    'title' => $movieData['title'],
                    'poster_url' => $movieData['poster_url'],
                    'backdrop_url' => $movieData['backdrop_url'],
                    'description' => 'Classic movie with high-quality images from Google Drive',
                    'release_date' => now()->subYears(rand(10, 30)),
                    'duration' => rand(90, 180),
                    'rating' => 'PG-13',
                    'genre' => 'Drama',
                    'director' => 'Classic Director',
                    'cast' => 'Famous Cast',
                    'trailer_url' => 'https://www.youtube.com/watch?v=example',
                    'status' => 'active',
                ]
            );
        }

        $this->command->info('Movie images updated with Google Drive URLs!');
    }
}
