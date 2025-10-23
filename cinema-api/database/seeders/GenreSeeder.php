<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genres = [
            ['name' => 'Action', 'name_vi' => 'Hành động', 'slug' => 'action', 'description' => 'Action movies'],
            ['name' => 'Comedy', 'name_vi' => 'Hài hước', 'slug' => 'comedy', 'description' => 'Comedy movies'],
            ['name' => 'Drama', 'name_vi' => 'Tâm lý', 'slug' => 'drama', 'description' => 'Drama movies'],
            ['name' => 'Horror', 'name_vi' => 'Kinh dị', 'slug' => 'horror', 'description' => 'Horror movies'],
            ['name' => 'Romance', 'name_vi' => 'Tình cảm', 'slug' => 'romance', 'description' => 'Romance movies'],
            ['name' => 'Sci-Fi', 'name_vi' => 'Khoa học viễn tưởng', 'slug' => 'sci-fi', 'description' => 'Science fiction movies'],
            ['name' => 'Thriller', 'name_vi' => 'Giật gân', 'slug' => 'thriller', 'description' => 'Thriller movies'],
            ['name' => 'Adventure', 'name_vi' => 'Phiêu lưu', 'slug' => 'adventure', 'description' => 'Adventure movies'],
            ['name' => 'Animation', 'name_vi' => 'Hoạt hình', 'slug' => 'animation', 'description' => 'Animation movies'],
            ['name' => 'Documentary', 'name_vi' => 'Tài liệu', 'slug' => 'documentary', 'description' => 'Documentary movies'],
        ];

        foreach ($genres as $genre) {
            \App\Models\Genre::firstOrCreate(['slug' => $genre['slug']], $genre);
        }
    }
}
