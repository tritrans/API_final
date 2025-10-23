<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Movie;
use App\Models\Theater;
use App\Models\Schedule;
use Carbon\Carbon;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users, movies, theaters, and schedules
        $users = User::where('role', 'user')->take(3)->get();
        $movies = Movie::take(5)->get();
        $theaters = Theater::take(2)->get();
        $schedules = Schedule::take(10)->get();

        if ($users->isEmpty() || $movies->isEmpty() || $theaters->isEmpty() || $schedules->isEmpty()) {
            $this->command->info('Not enough data to create tickets. Please run other seeders first.');
            return;
        }

        $tickets = [
            [
                'user_id' => $users[0]->id,
                'user_email' => $users[0]->email,
                'movie_id' => $movies[0]->id,
                'movie_title' => $movies[0]->title,
                'poster_url' => $movies[0]->poster,
                'schedule_id' => $schedules[0]->id,
                'seats' => ['A1', 'A2'],
                'total_amount' => 180000,
                'date_time' => Carbon::now()->addDays(1)->setTime(14, 0),
                'theater_id' => $theaters[0]->id,
                'status' => 'booked',
            ],
            [
                'user_id' => $users[0]->id,
                'user_email' => $users[0]->email,
                'movie_id' => $movies[1]->id,
                'movie_title' => $movies[1]->title,
                'poster_url' => $movies[1]->poster,
                'schedule_id' => $schedules[1]->id,
                'seats' => ['B3', 'B4', 'B5'],
                'total_amount' => 270000,
                'date_time' => Carbon::now()->addDays(2)->setTime(19, 30),
                'theater_id' => $theaters[0]->id,
                'status' => 'booked',
            ],
            [
                'user_id' => $users[0]->id,
                'user_email' => $users[0]->email,
                'movie_id' => $movies[2]->id,
                'movie_title' => $movies[2]->title,
                'poster_url' => $movies[2]->poster,
                'schedule_id' => $schedules[2]->id,
                'seats' => ['C6'],
                'total_amount' => 90000,
                'date_time' => Carbon::now()->subDays(1)->setTime(16, 0), // Past date - already watched
                'theater_id' => $theaters[1]->id,
                'status' => 'paid',
            ],
            [
                'user_id' => $users[1]->id ?? $users[0]->id,
                'user_email' => $users[1]->email ?? $users[0]->email,
                'movie_id' => $movies[3]->id,
                'movie_title' => $movies[3]->title,
                'poster_url' => $movies[3]->poster,
                'schedule_id' => $schedules[3]->id,
                'seats' => ['D7', 'D8'],
                'total_amount' => 180000,
                'date_time' => Carbon::now()->addHours(3)->setTime(20, 0),
                'theater_id' => $theaters[1]->id,
                'status' => 'booked',
            ],
            [
                'user_id' => $users[2]->id ?? $users[0]->id,
                'user_email' => $users[2]->email ?? $users[0]->email,
                'movie_id' => $movies[4]->id,
                'movie_title' => $movies[4]->title,
                'poster_url' => $movies[4]->poster,
                'schedule_id' => $schedules[4]->id,
                'seats' => ['E9'],
                'total_amount' => 90000,
                'date_time' => Carbon::now()->addDays(3)->setTime(15, 30),
                'theater_id' => $theaters[0]->id,
                'status' => 'booked',
            ],
        ];

        foreach ($tickets as $ticketData) {
            Ticket::create($ticketData);
        }

        $this->command->info('Created ' . count($tickets) . ' sample tickets.');
    }
}
