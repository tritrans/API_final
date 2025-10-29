<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\User;
use App\Models\Review;
use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    use \App\Traits\ApiResponse;

    /**
     * Get movies statistics
     */
    public function getMoviesStats()
    {
        try {
            $totalMovies = Movie::count();
            
            // Check if is_featured column exists, otherwise use featured field
            $featuredMovies = 0;
            try {
                $featuredMovies = Movie::where('is_featured', true)->count();
            } catch (\Exception $e) {
                // Fallback to featured field if is_featured doesn't exist
                $featuredMovies = Movie::where('featured', true)->count();
            }
            
            $thisMonthMovies = Movie::whereMonth('created_at', Carbon::now()->month)
                                  ->whereYear('created_at', Carbon::now()->year)
                                  ->count();

            return $this->successResponse([
                'total' => $totalMovies,
                'featured' => $featuredMovies,
                'this_month' => $thisMonthMovies
            ]);
        } catch (\Exception $e) {return $this->successResponse([
                'total' => 0,
                'featured' => 0,
                'this_month' => 0
            ]);
        }
    }

    /**
     * Get users statistics
     */
    public function getUsersStats()
    {
        try {
            $totalUsers = User::count();
            $thisMonthUsers = User::whereMonth('created_at', Carbon::now()->month)
                                 ->whereYear('created_at', Carbon::now()->year)
                                 ->count();
            $activeUsers = User::where('is_active', true)->count();

            return $this->successResponse([
                'total' => $totalUsers,
                'this_month' => $thisMonthUsers,
                'active' => $activeUsers
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get users statistics', 500);
        }
    }

    /**
     * Get reviews statistics
     */
    public function getReviewsStats()
    {
        try {
            $totalReviews = Review::count();
            $averageRating = Review::avg('rating') ?? 0;
            $thisMonthReviews = Review::whereMonth('created_at', Carbon::now()->month)
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->count();

            return $this->successResponse([
                'total' => $totalReviews,
                'average' => round($averageRating, 1),
                'this_month' => $thisMonthReviews
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get reviews statistics', 500);
        }
    }

    /**
     * Get bookings statistics
     */
    public function getBookingsStats()
    {
        try {
            // Check if bookings table exists and has data
            if (!\Schema::hasTable('bookings')) {
                return $this->successResponse([
                    'total' => 0,
                    'revenue' => 0,
                    'today' => 0
                ]);
            }

            $totalBookings = Booking::count();
            $thisMonthRevenue = Booking::whereMonth('created_at', Carbon::now()->month)
                                      ->whereYear('created_at', Carbon::now()->year)
                                      ->sum('total_price') ?? 0;
            $todayBookings = Booking::whereDate('created_at', Carbon::today())->count();

            return $this->successResponse([
                'total' => $totalBookings,
                'revenue' => $thisMonthRevenue,
                'today' => $todayBookings
            ]);
        } catch (\Exception $e) {return $this->successResponse([
                'total' => 0,
                'revenue' => 0,
                'today' => 0
            ]);
        }
    }

    /**
     * Get most viewed movies
     */
    public function getMostViewedMovies()
    {
        try {
            $mostViewedMovies = Movie::withCount('reviews')
                                   ->orderBy('reviews_count', 'desc')
                                   ->limit(10)
                                   ->get()
                                   ->map(function ($movie) {
                                       return [
                                           'id' => $movie->id,
                                           'title' => $movie->title,
                                           'title_vi' => $movie->title_vi,
                                           'poster_url' => $movie->poster_url,
                                           'reviews_count' => $movie->reviews_count,
                                           'rating' => $movie->reviews()->avg('rating') ?? 0
                                       ];
                                   });

            return $this->successResponse($mostViewedMovies);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get most viewed movies', 500);
        }
    }

    /**
     * Get monthly revenue data
     */
    public function getMonthlyRevenue()
    {
        try {
            $monthlyRevenue = [];
            
            // Check if bookings table exists
            if (!\Schema::hasTable('bookings')) {
                // Return empty data for last 12 months
                for ($i = 11; $i >= 0; $i--) {
                    $date = Carbon::now()->subMonths($i);
                    $monthlyRevenue[] = [
                        'month' => $date->format('M Y'),
                        'revenue' => 0
                    ];
                }
                return $this->successResponse($monthlyRevenue);
            }
            
            // Get last 12 months
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $revenue = Booking::whereMonth('created_at', $date->month)
                                 ->whereYear('created_at', $date->year)
                                 ->sum('total_price') ?? 0;
                
                $monthlyRevenue[] = [
                    'month' => $date->format('M Y'),
                    'revenue' => $revenue
                ];
            }

            return $this->successResponse($monthlyRevenue);
        } catch (\Exception $e) {return $this->successResponse([]);
        }
    }
}