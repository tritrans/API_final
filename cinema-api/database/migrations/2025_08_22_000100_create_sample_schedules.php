<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert sample schedules
        $movie = DB::table('movies')->first();
        $theater = DB::table('theaters')->first();
        
        if ($movie && $theater) {
            $today = now()->startOfDay();
            
            for ($i = 0; $i < 7; $i++) {
                $date = $today->copy()->addDays($i);
                
                for ($j = 0; $j < 3; $j++) {
                    $startTime = $date->copy()->setHour(14 + ($j * 3))->setMinute(0)->setSecond(0);
                    $endTime = $startTime->copy()->addMinutes(120);
                    
                    DB::table('schedules')->insert([
                        'movie_id' => $movie->id,
                        'theater_id' => $theater->id,
                        'room_name' => 'Phòng ' . ($j + 1),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => 80000 + ($j * 10000),
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('schedules')->truncate();
    }
};

