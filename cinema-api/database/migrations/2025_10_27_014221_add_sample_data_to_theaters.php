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
        // Thêm dữ liệu mẫu vào bảng theaters
        DB::table('theaters')->insert([
            'name' => 'CGV Aeon Mall',
            'address' => 'Tầng 3, Aeon Mall, 30 Bờ Bao Tân Thắng, P. Sơn Kỳ, Q. Tân Phú, TP.HCM',
            'phone' => '028 7300 8888',
            'email' => 'info@cgv.vn',
            'description' => 'Rạp chiếu phim hiện đại với công nghệ IMAX',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Thêm dữ liệu mẫu vào bảng movies nếu chưa có
        if (DB::table('movies')->count() == 0) {
            DB::table('movies')->insert([
                'title' => 'Avengers: Endgame',
                'description' => 'Phim siêu anh hùng Marvel',
                'duration' => 180,
                'release_date' => '2019-04-26',
                'rating' => 'PG-13',
                'poster_url' => 'https://example.com/poster.jpg',
                'trailer_url' => 'https://example.com/trailer.mp4',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa dữ liệu mẫu
        DB::table('theaters')->where('name', 'CGV Aeon Mall')->delete();
    }
};
