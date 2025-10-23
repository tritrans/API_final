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
                'name' => 'Popcorn Large',
                'name_vi' => 'Bắp rang bơ lớn',
                'description' => 'Bắp rang bơ thơm ngon, size lớn',
                'price' => 45000,
                'image' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400',
                'available' => true,
            ],
            [
                'name' => 'Popcorn Medium',
                'name_vi' => 'Bắp rang bơ vừa',
                'description' => 'Bắp rang bơ thơm ngon, size vừa',
                'price' => 35000,
                'image' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400',
                'available' => true,
            ],
            [
                'name' => 'Coca Cola',
                'name_vi' => 'Coca Cola',
                'description' => 'Nước ngọt Coca Cola mát lạnh',
                'price' => 25000,
                'image' => 'https://images.unsplash.com/photo-1581636625402-29b2a704ef13?w=400',
                'available' => true,
            ],
            [
                'name' => 'Pepsi',
                'name_vi' => 'Pepsi',
                'description' => 'Nước ngọt Pepsi mát lạnh',
                'price' => 25000,
                'image' => 'https://images.unsplash.com/photo-1581636625402-29b2a704ef13?w=400',
                'available' => true,
            ],
            [
                'name' => 'Hot Dog',
                'name_vi' => 'Xúc xích nóng',
                'description' => 'Xúc xích nóng với bánh mì',
                'price' => 55000,
                'image' => 'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=400',
                'available' => true,
            ],
            [
                'name' => 'Nachos',
                'name_vi' => 'Nachos phô mai',
                'description' => 'Nachos giòn với phô mai nóng chảy',
                'price' => 65000,
                'image' => 'https://images.unsplash.com/photo-1513456852971-30c0b8199d4d?w=400',
                'available' => true,
            ],
        ];

        foreach ($snacks as $snackData) {
            Snack::create($snackData);
        }
    }
}