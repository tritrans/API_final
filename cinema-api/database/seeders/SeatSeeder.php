<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        // For each theater, create a simple 8x12 grid A-H rows, seats 1-12
        $theaters = DB::table('theaters')->select('id')->get();
        foreach ($theaters as $theater) {
            $rows = range('A', 'H');
            foreach ($rows as $row) {
                for ($n = 1; $n <= 12; $n++) {
                    DB::table('seats')->updateOrInsert(
                        ['theater_id' => $theater->id, 'row_label' => $row, 'seat_number' => $n],
                        []
                    );
                }
            }
        }
    }
}


