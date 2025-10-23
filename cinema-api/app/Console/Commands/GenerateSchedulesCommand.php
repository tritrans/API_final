<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ScheduleGenerationService;
use App\Models\Movie;

class GenerateSchedulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:generate 
                            {--movie= : Generate schedules for specific movie ID}
                            {--all : Generate schedules for all movies}
                            {--regenerate : Regenerate existing schedules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate movie schedules automatically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scheduleService = new ScheduleGenerationService();
        
        if ($this->option('movie')) {
            $this->generateForSpecificMovie($scheduleService);
        } elseif ($this->option('all')) {
            $this->generateForAllMovies($scheduleService);
        } else {
            $this->showHelp();
        }
    }

    /**
     * Generate schedules for a specific movie
     */
    private function generateForSpecificMovie(ScheduleGenerationService $scheduleService)
    {
        $movieId = $this->option('movie');
        $movie = Movie::find($movieId);
        
        if (!$movie) {
            $this->error("Movie with ID {$movieId} not found.");
            return;
        }
        
        $this->info("Generating schedules for movie: {$movie->title} (ID: {$movie->id})");
        
        try {
            if ($this->option('regenerate')) {
                $schedules = $scheduleService->regenerateSchedulesForMovie($movie);
                $this->info("✅ Regenerated " . count($schedules) . " schedules for {$movie->title}");
            } else {
                $schedules = $scheduleService->generateSchedulesForMovie($movie);
                $this->info("✅ Generated " . count($schedules) . " schedules for {$movie->title}");
            }
            
            $this->displayScheduleSummary($schedules);
            
        } catch (\Exception $e) {
            $this->error("❌ Error generating schedules: " . $e->getMessage());
        }
    }

    /**
     * Generate schedules for all movies
     */
    private function generateForAllMovies(ScheduleGenerationService $scheduleService)
    {
        $this->info("Generating schedules for all movies...");
        
        try {
            $results = $scheduleService->generateSchedulesForAllMovies();
            
            $this->info("✅ Schedule generation completed!");
            $this->newLine();
            
            // Display summary table
            $headers = ['Movie ID', 'Movie Title', 'Schedules Generated', 'Status'];
            $rows = [];
            
            foreach ($results as $movieId => $result) {
                $rows[] = [
                    $movieId,
                    $result['movie'],
                    $result['schedules_generated'],
                    $result['success'] ? '✅ Success' : '❌ Failed'
                ];
            }
            
            $this->table($headers, $rows);
            
            // Show errors if any
            $errors = array_filter($results, function($result) {
                return !$result['success'];
            });
            
            if (!empty($errors)) {
                $this->newLine();
                $this->error("Errors encountered:");
                foreach ($errors as $movieId => $result) {
                    $this->error("- {$result['movie']}: {$result['error']}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error generating schedules: " . $e->getMessage());
        }
    }

    /**
     * Display schedule summary
     */
    private function displayScheduleSummary(array $schedules)
    {
        if (empty($schedules)) {
            $this->warn("No schedules were generated.");
            return;
        }
        
        $this->newLine();
        $this->info("Schedule Summary:");
        
        // Group by theater
        $theaterGroups = [];
        foreach ($schedules as $schedule) {
            $theaterName = $schedule->theater->name ?? 'Unknown Theater';
            if (!isset($theaterGroups[$theaterName])) {
                $theaterGroups[$theaterName] = [];
            }
            $theaterGroups[$theaterName][] = $schedule;
        }
        
        foreach ($theaterGroups as $theaterName => $theaterSchedules) {
            $this->info("  🎭 {$theaterName}: " . count($theaterSchedules) . " schedules");
            
            // Group by date
            $dateGroups = [];
            foreach ($theaterSchedules as $schedule) {
                $date = $schedule->start_time->format('Y-m-d');
                if (!isset($dateGroups[$date])) {
                    $dateGroups[$date] = [];
                }
                $dateGroups[$date][] = $schedule;
            }
            
            foreach ($dateGroups as $date => $dateSchedules) {
                $this->info("    📅 {$date}: " . count($dateSchedules) . " showtimes");
            }
        }
    }

    /**
     * Show help information
     */
    private function showHelp()
    {
        $this->info("Movie Schedule Generator");
        $this->newLine();
        $this->info("Usage:");
        $this->line("  php artisan schedules:generate --movie=1");
        $this->line("  php artisan schedules:generate --movie=1 --regenerate");
        $this->line("  php artisan schedules:generate --all");
        $this->newLine();
        $this->info("Options:");
        $this->line("  --movie=ID     Generate schedules for specific movie");
        $this->line("  --all          Generate schedules for all movies");
        $this->line("  --regenerate   Delete existing schedules and create new ones");
        $this->newLine();
        $this->info("Examples:");
        $this->line("  php artisan schedules:generate --movie=1");
        $this->line("  php artisan schedules:generate --movie=1 --regenerate");
        $this->line("  php artisan schedules:generate --all");
    }
}