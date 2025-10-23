<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Theater;
use App\Models\Seat;
use App\Models\Schedule;
use App\Models\Movie;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create theaters
        $theaters = [
            [
                'name' => 'CGV Vincom Center',
                'address' => '72 Lê Thánh Tôn, Quận 1, TP.HCM',
                'phone' => '028 3822 8888',
                'email' => 'cgv.vincom@cgv.vn',
                'description' => 'Rạp chiếu phim hiện đại với 8 phòng chiếu',
                'is_active' => true,
            ],
            [
                'name' => 'Lotte Cinema Diamond Plaza',
                'address' => '34 Lê Duẩn, Quận 1, TP.HCM',
                'phone' => '028 3822 9999',
                'email' => 'diamond@lottecinema.vn',
                'description' => 'Rạp chiếu phim cao cấp với 6 phòng chiếu',
                'is_active' => true,
            ],
            [
                'name' => 'Galaxy Cinema Nguyễn Du',
                'address' => '116 Nguyễn Du, Quận 1, TP.HCM',
                'phone' => '028 3822 7777',
                'email' => 'nguyendu@galaxycine.vn',
                'description' => 'Rạp chiếu phim với 4 phòng chiếu',
                'is_active' => true,
            ],
        ];

        foreach ($theaters as $theaterData) {
            $theater = Theater::create($theaterData);
            
            // Create seats for each theater
            $this->createSeats($theater->id);
        }

        // Create schedules
        $this->createSchedules();
    }

    private function createSeats($theaterId)
    {
        $rooms = ['A', 'B', 'C', 'D'];
        $seatsPerRow = 12;
        
        foreach ($rooms as $room) {
            for ($row = 1; $row <= 8; $row++) {
                for ($seat = 1; $seat <= $seatsPerRow; $seat++) {
                    Seat::create([
                        'theater_id' => $theaterId,
                        'row_label' => $room,
                        'seat_number' => $seat,
                    ]);
                }
            }
        }
    }

    private function createSchedules()
    {
        $movies = Movie::take(5)->get();
        $theaters = Theater::all();
        
        $startTimes = [
            '09:00', '11:30', '14:00', '16:30', '19:00', '21:30'
        ];
        
        $rooms = ['A', 'B', 'C', 'D'];
        
        foreach ($movies as $movie) {
            foreach ($theaters as $theater) {
                // Create schedules for next 7 days
                for ($day = 0; $day < 7; $day++) {
                    $date = Carbon::now()->addDays($day);
                    
                    foreach ($startTimes as $time) {
                        $startTime = $date->copy()->setTimeFromTimeString($time);
                        $endTime = $startTime->copy()->addMinutes($movie->duration + 30); // Add 30 min buffer
                        
                        Schedule::create([
                            'movie_id' => $movie->id,
                            'theater_id' => $theater->id,
                            'room_name' => $rooms[array_rand($rooms)],
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'price' => rand(80000, 150000), // Random price between 80k-150k
                            'status' => 'active',
                        ]);
                    }
                }
            }
        }
    }
}