<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Theater;
use App\Models\Schedule;
use App\Models\Seat;
use App\Models\ScheduleSeat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleGenerationService
{
    /**
     * Generate schedules for a movie
     */
    public function generateSchedulesForMovie(Movie $movie): array
    {
        Log::info('Generating schedules for movie: ' . $movie->id . ' - ' . $movie->title);
        
        $generatedSchedules = [];
        
        try {
            // Get all active theaters
            $theaters = Theater::where('is_active', true)->get();
            
            Log::info('Found ' . $theaters->count() . ' active theaters');
            
            if ($theaters->isEmpty()) {
                Log::warning('No active theaters found');
                return $generatedSchedules;
            }

            // Parse release date
            $releaseDate = Carbon::parse($movie->release_date);
            $endDate = $releaseDate->copy()->addDays(30); // Generate for 30 days
            
            Log::info('Release date: ' . $releaseDate->format('Y-m-d'));
            Log::info('End date: ' . $endDate->format('Y-m-d'));

            foreach ($theaters as $theater) {
                Log::info('Processing theater: ' . $theater->id . ' - ' . $theater->name);
                
                $theaterSchedules = $this->generateSchedulesForTheater($movie, $theater, $releaseDate, $endDate);
                $generatedSchedules = array_merge($generatedSchedules, $theaterSchedules);
            }

            Log::info('Total schedules generated: ' . count($generatedSchedules));
            return $generatedSchedules;

        } catch (\Exception $e) {
            Log::error('Error generating schedules for movie ' . $movie->id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate schedules for a specific theater
     */
    private function generateSchedulesForTheater(Movie $movie, Theater $theater, Carbon $releaseDate, Carbon $endDate): array
    {
        $schedules = [];
        $currentDate = $releaseDate->copy();
        
        while ($currentDate->lte($endDate)) {
            Log::info('Processing date: ' . $currentDate->format('Y-m-d'));
            
            // Get schedule times for this date
            $scheduleTimes = $this->getScheduleTimes($currentDate);
            
            foreach ($scheduleTimes as $index => $time) {
                $schedule = $this->createSchedule($movie, $theater, $currentDate, $time, $index);
                if ($schedule) {
                    $schedules[] = $schedule;
                }
            }
            
            $currentDate->addDay();
        }
        
        return $schedules;
    }

    /**
     * Get schedule times for a specific date
     */
    private function getScheduleTimes(Carbon $date): array
    {
        $dayOfWeek = $date->dayOfWeek;
        
        // Different schedules for weekdays vs weekends
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Monday to Friday
            return [
                ['hour' => 14, 'minute' => 0], // 2:00 PM
                ['hour' => 17, 'minute' => 30], // 5:30 PM
                ['hour' => 20, 'minute' => 0], // 8:00 PM
            ];
        } else { // Weekend (Saturday, Sunday)
            return [
                ['hour' => 10, 'minute' => 0], // 10:00 AM
                ['hour' => 14, 'minute' => 0], // 2:00 PM
                ['hour' => 17, 'minute' => 30], // 5:30 PM
                ['hour' => 20, 'minute' => 0], // 8:00 PM
                ['hour' => 22, 'minute' => 30], // 10:30 PM
            ];
        }
    }

    /**
     * Create a single schedule
     */
    private function createSchedule(Movie $movie, Theater $theater, Carbon $date, array $time, int $roomIndex): ?Schedule
    {
        try {
            $startTime = $date->copy()
                ->setHour($time['hour'])
                ->setMinute($time['minute'])
                ->setSecond(0);
            
            $endTime = $startTime->copy()->addMinutes($movie->duration ?? 120);
            
            Log::info('Creating schedule: ' . $startTime->format('Y-m-d H:i:s') . ' - ' . $endTime->format('Y-m-d H:i:s'));
            
            // Skip if the schedule is in the past
            if ($startTime->isPast()) {
                Log::info('Skipping past schedule');
                return null;
            }

            // Check if schedule already exists
            $existingSchedule = Schedule::where('movie_id', $movie->id)
                ->where('theater_id', $theater->id)
                ->where('start_time', $startTime)
                ->first();

            if ($existingSchedule) {
                Log::info('Schedule already exists, skipping');
                return $existingSchedule;
            }

            // Create new schedule
            $schedule = Schedule::create([
                'movie_id' => $movie->id,
                'theater_id' => $theater->id,
                'room_name' => 'Phòng ' . ($roomIndex + 1),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $this->calculatePrice($time['hour'], $roomIndex, $date),
                'status' => 'active',
            ]);

            // Initialize seats for this schedule
            $this->initializeScheduleSeats($schedule, $theater);

            Log::info('Schedule created successfully with ID: ' . $schedule->id);
            return $schedule;

        } catch (\Exception $e) {
            Log::error('Error creating schedule: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Initialize seats for a schedule
     */
    private function initializeScheduleSeats(Schedule $schedule, Theater $theater): void
    {
        try {
            // Get all seats for this theater
            $seats = Seat::where('theater_id', $theater->id)->get();
            
            Log::info('Initializing ' . $seats->count() . ' seats for schedule ' . $schedule->id);
            
            foreach ($seats as $seat) {
                ScheduleSeat::create([
                    'schedule_id' => $schedule->id,
                    'seat_id' => $seat->id,
                    'status' => 'available',
                    'locked_until' => null,
                ]);
            }
            
            // Update available_seats in schedules table
            $availableSeats = $seats->map(function($seat) {
                return [
                    'id' => $seat->id,
                    'row_label' => $seat->row_label,
                    'seat_number' => $seat->seat_number,
                    'status' => 'available'
                ];
            })->toArray();
            
            $schedule->update([
                'available_seats' => json_encode([
                    'total_seats' => $seats->count(),
                    'booked_seats' => 0,
                    'available_seats' => $seats->count(),
                    'seats' => $availableSeats
                ])
            ]);
            
            Log::info('Seats initialized successfully for schedule ' . $schedule->id);
            
        } catch (\Exception $e) {
            Log::error('Error initializing seats for schedule ' . $schedule->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Calculate price based on time, room, and date
     */
    private function calculatePrice(int $hour, int $roomIndex, Carbon $date): int
    {
        $basePrice = 80000; // Base price: 80,000 VND
        
        // Evening premium (after 6 PM)
        if ($hour >= 18) {
            $basePrice += 20000;
        }
        
        // Weekend premium (Friday-Sunday)
        if (in_array($date->dayOfWeek, [5, 6, 0])) {
            $basePrice += 10000;
        }
        
        // Room premium (VIP rooms)
        if ($roomIndex >= 2) {
            $basePrice += 15000;
        }
        
        // Peak hours premium (7-9 PM)
        if ($hour >= 19 && $hour <= 21) {
            $basePrice += 10000;
        }
        
        return $basePrice;
    }

    /**
     * Regenerate schedules for a movie (delete existing and create new)
     */
    public function regenerateSchedulesForMovie(Movie $movie): array
    {
        Log::info('Regenerating schedules for movie: ' . $movie->id);
        
        try {
            // Delete existing schedules
            $deletedCount = Schedule::where('movie_id', $movie->id)->delete();
            Log::info('Deleted ' . $deletedCount . ' existing schedules');
            
            // Generate new schedules
            return $this->generateSchedulesForMovie($movie);
            
        } catch (\Exception $e) {
            Log::error('Error regenerating schedules for movie ' . $movie->id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate schedules for all movies
     */
    public function generateSchedulesForAllMovies(): array
    {
        Log::info('Generating schedules for all movies');
        
        $results = [];
        $movies = Movie::all();
        
        foreach ($movies as $movie) {
            try {
                $schedules = $this->generateSchedulesForMovie($movie);
                $results[$movie->id] = [
                    'movie' => $movie->title,
                    'schedules_generated' => count($schedules),
                    'success' => true
                ];
            } catch (\Exception $e) {
                $results[$movie->id] = [
                    'movie' => $movie->title,
                    'schedules_generated' => 0,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}
