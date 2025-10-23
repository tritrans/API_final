<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use App\Models\Theater;
use App\Models\Schedule;
use Carbon\Carbon;

class GenerateSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:generate {--movie-id=} {--days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate schedules for movies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $movieId = $this->option('movie-id');
        $days = $this->option('days');

        if ($movieId) {
            $movie = Movie::find($movieId);
            if (!$movie) {
                $this->error("Movie with ID {$movieId} not found.");
                return;
            }
            $this->generateSchedulesForMovie($movie, $days);
        } else {
            $movies = Movie::all();
            foreach ($movies as $movie) {
                $this->generateSchedulesForMovie($movie, $days);
            }
        }

        $this->info('Schedules generated successfully!');
    }

    private function generateSchedulesForMovie($movie, $days = 30)
    {
        $this->info("Generating schedules for: {$movie->title}");

        // Get all active theaters
        $theaters = Theater::where('is_active', true)->get();
        
        if ($theaters->isEmpty()) {
            $this->warn('No active theaters found.');
            return;
        }

        // Generate schedules starting from release date or today
        $startDate = $movie->release_date ? Carbon::parse($movie->release_date) : Carbon::today();
        $endDate = $startDate->copy()->addDays($days);

        $schedulesCreated = 0;

        foreach ($theaters as $theater) {
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                // Generate 4 schedules per day
                $scheduleTimes = [
                    ['hour' => 14, 'minute' => 0], // 2:00 PM
                    ['hour' => 17, 'minute' => 30], // 5:30 PM
                    ['hour' => 20, 'minute' => 0], // 8:00 PM
                    ['hour' => 22, 'minute' => 30], // 10:30 PM
                ];

                foreach ($scheduleTimes as $index => $time) {
                    $startTime = $currentDate->copy()
                        ->setHour($time['hour'])
                        ->setMinute($time['minute'])
                        ->setSecond(0);
                    
                    $endTime = $startTime->copy()->addMinutes($movie->duration ?? 120);
                    
                    // Skip if the schedule is in the past
                    if ($startTime->isPast()) {
                        continue;
                    }

                    // Check if schedule already exists
                    $existingSchedule = Schedule::where('movie_id', $movie->id)
                        ->where('theater_id', $theater->id)
                        ->where('start_time', $startTime)
                        ->first();

                    if (!$existingSchedule) {
                        Schedule::create([
                            'movie_id' => $movie->id,
                            'theater_id' => $theater->id,
                            'room_name' => 'Phòng ' . ($index + 1),
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'price' => $this->calculatePrice($time['hour'], $index),
                            'available_seats' => json_encode([]),
                            'status' => 'active',
                        ]);
                        $schedulesCreated++;
                    }
                }
                
                $currentDate->addDay();
            }
        }

        $this->info("Created {$schedulesCreated} schedules for {$movie->title}");
    }

    private function calculatePrice($hour, $roomIndex)
    {
        $basePrice = 80000;
        
        // Evening premium (after 6 PM)
        if ($hour >= 18) {
            $basePrice += 20000;
        }
        
        // Weekend premium (Friday-Sunday)
        if (in_array(now()->dayOfWeek, [5, 6, 0])) {
            $basePrice += 10000;
        }
        
        // Room premium (VIP rooms)
        if ($roomIndex >= 2) {
            $basePrice += 15000;
        }
        
        return $basePrice;
    }
}

