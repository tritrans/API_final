<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Snack;

class SnackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $snacks = [
            [
                'name' => 'Combo 1 - Popcorn + Drink',
                'name_vi' => 'Combo 1 - Bắp + Nước',
                'description' => 'Medium popcorn + Medium drink',
                'description_vi' => 'Bắp rang bơ vừa + Nước ngọt vừa',
                'price' => 55000,
                'image' => 'Snack/Combo.jpg',
                'category' => 'combo',
                'available' => true,
                'stock' => 100
            ],
            [
                'name' => 'Coca Cola',
                'name_vi' => 'Coca Cola',
                'description' => 'Large Coca Cola',
                'description_vi' => 'Coca Cola lớn',
                'price' => 35000,
                'image' => 'Snack/coca.jpg',
                'category' => 'drink',
                'available' => true,
                'stock' => 200
            ],
            [
                'name' => 'Mineral Water',
                'name_vi' => 'Nước suối',
                'description' => 'Pure mineral water',
                'description_vi' => 'Nước suối tinh khiết',
                'price' => 15000,
                'image' => 'Snack/MineralWater.jpg',
                'category' => 'drink',
                'available' => true,
                'stock' => 150
            ],
            [
                'name' => 'Popcorn',
                'name_vi' => 'Bắp rang bơ',
                'description' => 'Large buttered popcorn',
                'description_vi' => 'Bắp rang bơ lớn',
                'price' => 45000,
                'image' => 'Snack/Popcorn.jpg',
                'category' => 'food',
                'available' => true,
                'stock' => 80
            ]
        ];

        foreach ($snacks as $snack) {
            Snack::updateOrCreate(
                ['name' => $snack['name']],
                $snack
            );
        }
    }
}