<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\User;
use App\Models\Movie;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users and movies for creating reviews
        $users = User::take(5)->get();
        $movies = Movie::take(10)->get();

        if ($users->isEmpty() || $movies->isEmpty()) {
            $this->command->info('No users or movies found. Please run UserSeeder and MovieSeeder first.');
            return;
        }

        $sampleReviews = [
            [
                'rating' => 5.0,
                'comment' => 'Phim rất hay! Diễn viên diễn xuất xuất sắc, cốt truyện hấp dẫn.',
            ],
            [
                'rating' => 4.5,
                'comment' => 'Tuyệt vời! Hiệu ứng hình ảnh đẹp, âm thanh sống động.',
            ],
            [
                'rating' => 4.0,
                'comment' => 'Phim tốt, đáng xem. Có một số điểm nhỏ cần cải thiện.',
            ],
            [
                'rating' => 3.5,
                'comment' => 'Phim ổn, giải trí được. Không quá xuất sắc nhưng cũng không tệ.',
            ],
            [
                'rating' => 5.0,
                'comment' => 'Kiệt tác! Một trong những phim hay nhất năm nay.',
            ],
            [
                'rating' => 4.0,
                'comment' => 'Phim hay, đáng để xem. Cốt truyện logic và hấp dẫn.',
            ],
            [
                'rating' => 4.5,
                'comment' => 'Rất thích phim này! Diễn viên chính diễn xuất rất tốt.',
            ],
            [
                'rating' => 3.0,
                'comment' => 'Phim tạm được, có thể xem để giải trí.',
            ],
            [
                'rating' => 4.0,
                'comment' => 'Phim tốt, đáng xem. Hiệu ứng đặc biệt ấn tượng.',
            ],
            [
                'rating' => 5.0,
                'comment' => 'Tuyệt vời! Một tác phẩm nghệ thuật đích thực.',
            ],
        ];

        foreach ($movies as $index => $movie) {
            // Create 2-3 reviews for each movie
            $numReviews = rand(2, 3);
            
            for ($i = 0; $i < $numReviews; $i++) {
                $user = $users->random();
                $reviewData = $sampleReviews[array_rand($sampleReviews)];
                
                // Check if user already reviewed this movie
                $existingReview = Review::where('user_id', $user->id)
                    ->where('movie_id', $movie->id)
                    ->first();
                
                if (!$existingReview) {
                    Review::create([
                        'user_id' => $user->id,
                        'movie_id' => $movie->id,
                        'rating' => $reviewData['rating'],
                        'comment' => $reviewData['comment'],
                    ]);
                }
            }
        }

        $this->command->info('Sample reviews created successfully!');
    }
}
