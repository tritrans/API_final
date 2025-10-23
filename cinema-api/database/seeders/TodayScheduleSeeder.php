<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TodayScheduleSeeder extends Seeder
{
    public function run()
    {
        $today = date('Y-m-d');
        
        // Add schedules for today
        $schedules = [
            [
                'movie_id' => 1,
                'theater_id' => 1,
                'room_name' => 'Room 1',
                'start_time' => $today . ' 10:00:00',
                'end_time' => $today . ' 12:32:00',
                'price' => 100000,
                'available_seats' => json_encode(range(1, 100)),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'movie_id' => 1,
                'theater_id' => 1,
                'room_name' => 'Room 2',
                'start_time' => $today . ' 13:00:00',
                'end_time' => $today . ' 15:32:00',
                'price' => 120000,
                'available_seats' => json_encode(range(1, 100)),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'movie_id' => 1,
                'theater_id' => 2,
                'room_name' => 'Room 1',
                'start_time' => $today . ' 16:00:00',
                'end_time' => $today . ' 18:32:00',
                'price' => 110000,
                'available_seats' => json_encode(range(1, 100)),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($schedules as $schedule) {
            DB::table('schedules')->insert($schedule);
        }
        
        echo "Added schedules for today: $today\n";
    }
}
