<?php

namespace Database\Seeders;

use App\Models\Theater;
use Illuminate\Database\Seeder;

class TheaterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $theaters = [
            [
                'name' => 'CGV Aeon Mall',
                'address' => 'Tầng 3, Aeon Mall, 30 Bờ Bao Tân Thắng, P. Sơn Kỳ, Q. Tân Phú, TP.HCM',
                'phone' => '028 7300 8888',
                'email' => 'info@cgv.vn',
                'description' => 'Rạp chiếu phim hiện đại với công nghệ IMAX',
                'is_active' => true,
            ],
            [
                'name' => 'Lotte Cinema Diamond',
                'address' => 'Tầng 13, Diamond Plaza, 34 Lê Duẩn, Q.1, TP.HCM',
                'phone' => '028 7300 9999',
                'email' => 'info@lottecinema.vn',
                'description' => 'Rạp chiếu phim cao cấp với ghế VIP',
                'is_active' => true,
            ],
            [
                'name' => 'Galaxy Cinema',
                'address' => 'Tầng 4, Vincom Center, 72 Lê Thánh Tôn, Q.1, TP.HCM',
                'phone' => '028 7300 7777',
                'email' => 'info@galaxycine.vn',
                'description' => 'Rạp chiếu phim với nhiều phòng chiếu',
                'is_active' => true,
            ],
        ];

        foreach ($theaters as $theater) {
            Theater::create($theater);
        }
    }
}
