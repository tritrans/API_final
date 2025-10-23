<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeatsSeeder extends Seeder
{
    public function run(): void
    {
        // Initialize schedule_seats as available for upcoming schedules
        $schedules = DB::table('schedules')->where('start_time', '>', now())->get();
        foreach ($schedules as $schedule) {
            $seats = DB::table('seats')->where('theater_id', $schedule->theater_id)->get();
            foreach ($seats as $seat) {
                DB::table('schedule_seats')->updateOrInsert(
                    ['schedule_id' => $schedule->id, 'seat_id' => $seat->id],
                    ['status' => 'available', 'locked_until' => null]
                );
            }
        }
    }
}


