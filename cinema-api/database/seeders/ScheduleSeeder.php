<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Movie;
use App\Models\Theater;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first movie and theater
        $movie = Movie::first();
        $theater = Theater::first();
        
        if (!$movie || !$theater) {
            $this->command->info('No movies or theaters found. Please seed movies and theaters first.');
            return;
        }

        // Create schedules for today and next 7 days
        $today = Carbon::today();
        
        for ($i = 0; $i < 7; $i++) {
            $date = $today->copy()->addDays($i);
            
            // Create 3 schedules per day
            for ($j = 0; $j < 3; $j++) {
                $startTime = $date->copy()->setHour(14 + ($j * 3))->setMinute(0)->setSecond(0);
                $endTime = $startTime->copy()->addMinutes($movie->duration ?? 120);
                
                Schedule::create([
                    'movie_id' => $movie->id,
                    'theater_id' => $theater->id,
                    'room_name' => 'Phòng ' . ($j + 1),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'price' => 80000 + ($j * 10000), // Different prices for different rooms
                    'available_seats' => json_encode([]),
                    'status' => 'active',
                ]);
            }
        }
        
        $this->command->info('Schedules created successfully!');
    }
}