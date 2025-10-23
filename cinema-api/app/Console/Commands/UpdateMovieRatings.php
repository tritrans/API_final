<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\Review;
use Illuminate\Console\Command;

class UpdateMovieRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:update-ratings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all movie ratings based on user reviews';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating movie ratings...');

        $movies = Movie::all();
        $updatedCount = 0;

        foreach ($movies as $movie) {
            $averageRating = Review::where('movie_id', $movie->id)->avg('rating');
            
            if ($averageRating !== null) {
                $oldRating = $movie->rating;
                $movie->update(['rating' => round($averageRating, 1)]);
                
                $this->line("Movie: {$movie->title}");
                $this->line("  Old rating: {$oldRating}");
                $this->line("  New rating: {$movie->rating}");
                $this->line("  Reviews count: " . Review::where('movie_id', $movie->id)->count());
                $this->line("---");
                
                $updatedCount++;
            } else {
                $this->line("Movie: {$movie->title} - No reviews yet");
            }
        }

        $this->info("Updated {$updatedCount} movies!");
        
        return Command::SUCCESS;
    }
}
