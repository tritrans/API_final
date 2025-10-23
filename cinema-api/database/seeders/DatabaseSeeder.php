<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            GenreSeeder::class,
            MovieSeeder::class,
            TheaterSeeder::class,
            ScheduleSeeder::class,
            SeatSeeder::class,
            PeopleSeeder::class,
            CastLinkSeeder::class,
            ScheduleSeatsSeeder::class,
            TicketSeeder::class,
        ]);
    }
}
