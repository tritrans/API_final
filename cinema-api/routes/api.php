<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TheaterController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ScheduleSeatController;
use App\Http\Controllers\Api\MovieCastController;
use App\Http\Controllers\Api\ViolationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/auth/change-password', [AuthController::class, 'changePassword'])->middleware('auth:api');

// Public movie routes (no authentication required)
Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/featured', [MovieController::class, 'featured']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::get('/movies/{id}/cast', [MovieCastController::class, 'index']);
Route::put('/movies/{id}/cast', [MovieCastController::class, 'update']);

// Schedule generation routes (Admin only)
Route::post('/movies/{id}/schedules/generate', [MovieController::class, 'generateSchedules'])->middleware(['auth:api', 'permission:manage movies']);
Route::post('/movies/{id}/schedules/regenerate', [MovieController::class, 'regenerateSchedules'])->middleware(['auth:api', 'permission:manage movies']);

// Public theater routes (no authentication required)
Route::get('/theaters', [TheaterController::class, 'index']);
Route::get('/theaters/{id}', [TheaterController::class, 'show']);
Route::get('/theaters/{theaterId}/schedules', [TheaterController::class, 'schedules']);
Route::get('/theaters/{theaterId}/movies/{movieId}/schedules', [TheaterController::class, 'movieSchedules']);
Route::delete('/theaters/{id}', [TheaterController::class, 'destroy']);

// Public schedule routes (no authentication required)
Route::get('/schedules', [ScheduleController::class, 'index']);
Route::get('/schedules/{id}', [ScheduleController::class, 'show']);
Route::get('/schedules/movie/{movieId}', [ScheduleController::class, 'movieSchedules']);
Route::get('/schedules/movie/{movieId}/flutter', [ScheduleController::class, 'movieSchedulesFlutter']);
Route::get('/schedules/movie/{movieId}/dates', [ScheduleController::class, 'availableDates']);
Route::get('/schedules/movie/{movieId}/dates/flutter', [ScheduleController::class, 'movieAvailableDatesFlutter']);
Route::get('/schedules/date', [ScheduleController::class, 'dateSchedules']);
Route::post('/schedules', [ScheduleController::class, 'store']);
Route::get('/schedules/{id}', [ScheduleController::class, 'show']);
Route::put('/schedules/{id}', [ScheduleController::class, 'update']);
Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy']);
// Seats per schedule
Route::get('/schedules/{scheduleId}/seats', [ScheduleSeatController::class, 'index']);
// Removed hold seats route to prevent race conditions

// Public reply routes (no authentication required)
Route::post('/comments/{id}/reply', [CommentController::class, 'createReply']); // Public reply to comment
Route::post('/reviews/{id}/reply', [ReviewController::class, 'createReply']); // Public reply to review
// Only use lockSeats -> createBooking flow
// Removed confirm seats route to prevent race conditions  
// Only use lockSeats -> createBooking flow

// Public admin routes (no authentication required for basic stats)
Route::get('/admin/users', [UserController::class, 'getAllUsersForAdmin']);
Route::get('/admin/reviews', [ReviewController::class, 'getAllReviews']);
Route::get('/admin/comments', [CommentController::class, 'getAllComments']);
Route::get('/admin/movies', [MovieController::class, 'index']);

// Public dropdown data endpoints
Route::get('/genres', function() {
    $genres = \App\Models\Genre::select('id', 'name')->get();
    return response()->json([
        'success' => true,
        'data' => $genres
    ]);
});

Route::get('/directors', function() {
    $directors = \App\Models\Person::whereHas('movieCasts', function($query) {
        $query->where('role', 'director');
    })
    ->select('id', 'name')
    ->orderBy('name')
    ->get()
    ->map(function($director) {
        return ['name' => $director->name];
    });
    
    return response()->json([
        'success' => true,
        'data' => $directors
    ]);
});

Route::get('/actors', function() {
    $actors = \App\Models\Person::whereHas('movieCasts', function($query) {
        $query->where('role', 'actor');
    })
    ->select('id', 'name')
    ->orderBy('name')
    ->get()
    ->map(function($actor) {
        return ['name' => $actor->name];
    });

    return response()->json([
        'success' => true,
        'data' => $actors
    ]);
});

// Public movie reviews and comments (no authentication required)
Route::get('/movies/{movieId}/reviews', [ReviewController::class, 'index']);
Route::get('/movies/{movieId}/comments', [CommentController::class, 'index']);

// Public routes for reviews and comments (no auth required)
Route::post('/movies/{movieId}/reviews', [ReviewController::class, 'store']);
Route::post('/movies/{movieId}/comments', [CommentController::class, 'store']);

// Public users endpoint for admin dashboard (must be before /users/{id} routes)
Route::get('/users', [UserController::class, 'getAllUsersForAdmin']);
Route::get('/users/export', [UserController::class, 'exportUsers']);

// User management routes (must be before specific /users/{id}/reviews route)
Route::middleware(['auth:api'])->group(function () {
    Route::put('/users/{id}', [UserController::class, 'update']);
});

Route::get('/users/{id}/reviews', [UserController::class, 'getUserReviews']);

// Test endpoint for debugging
Route::get('/test-comments', function() {
    return response()->json([
        'success' => true,
        'message' => 'Comments endpoint is working',
        'data' => []
    ]);
});

// Public endpoints for all reviews and comments (no auth required)
Route::get('/reviews/public', [ReviewController::class, 'getAllReviews']);
Route::get('/comments/public', function() {
    try {
 $comments = \App\Models\Comment::with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $formattedComments = [];
        foreach ($comments as $comment) {
            $formattedComments[] = [
                'id' => $comment->id,
                'movie_id' => $comment->movie_id,
                'user_id' => $comment->user_id,
                'parent_id' => $comment->parent_id,
                'content' => $comment->content,
                'is_hidden' => $comment->is_hidden,
                'hidden_reason' => $comment->hidden_reason,
                'hidden_at' => $comment->hidden_at,
                'name' => $comment->user ? $comment->user->name : 'Anonymous',
                'email' => $comment->user ? $comment->user->email : '',
                'avatar' => $comment->user ? $comment->user->avatar : '',
                'created_at' => $comment->created_at->toISOString(),
                'updated_at' => $comment->updated_at->toISOString(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $formattedComments
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in comments/public: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching comments',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Public endpoints for movie-specific reviews and comments (no auth required)
Route::get('/movies/{movieId}/reviews/public', [ReviewController::class, 'getMovieReviews']);
Route::get('/movies/{movieId}/comments/public', [CommentController::class, 'getMovieComments']);

// Public endpoints for single review and comment (no auth required)
Route::get('/reviews/{id}', [ReviewController::class, 'show']); // Get single review
Route::get('/comments/{id}', [CommentController::class, 'show']); // Get single comment

// Public endpoint for toggle visibility (no auth required for testing)
Route::post('/violations/{id}/toggle-visibility', [ViolationController::class, 'toggleVisibility']); // Toggle content visibility

// Public statistics endpoints (no auth required for testing)
Route::get('/admin/statistics/movies', [\App\Http\Controllers\Api\StatisticsController::class, 'getMoviesStats']);
Route::get('/admin/statistics/users', [\App\Http\Controllers\Api\StatisticsController::class, 'getUsersStats']);
Route::get('/admin/statistics/reviews', [\App\Http\Controllers\Api\StatisticsController::class, 'getReviewsStats']);
Route::get('/admin/statistics/bookings', [\App\Http\Controllers\Api\StatisticsController::class, 'getBookingsStats']);
Route::get('/admin/statistics/most-viewed-movies', [\App\Http\Controllers\Api\StatisticsController::class, 'getMostViewedMovies']);
Route::get('/admin/statistics/monthly-revenue', [\App\Http\Controllers\Api\StatisticsController::class, 'getMonthlyRevenue']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    

    
    
    // Reviews routes
    Route::get('/reviews', [ReviewController::class, 'getAllReviews']); // For admin dashboard
    Route::get('/movies/{movieId}/user-review', [ReviewController::class, 'getUserReview']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    
    // Comments routes
    Route::get('/comments', [CommentController::class, 'getAllComments']); // For admin dashboard
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
    Route::post('/admin/violations/{id}/toggle-visibility', [ViolationController::class, 'toggleVisibility']); // Toggle content visibility
    
    
    // Favorites routes
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{movieId}', [FavoriteController::class, 'destroy']);
    
    // Role & Permission Management routes
    Route::prefix('roles')->group(function () {
        Route::get('/', [RolePermissionController::class, 'getRoles']);
        Route::post('/', [RolePermissionController::class, 'createRole']);
        Route::put('/{id}', [RolePermissionController::class, 'updateRole']);
        Route::delete('/{id}', [RolePermissionController::class, 'deleteRole']);
    });
    
    Route::get('/permissions', [RolePermissionController::class, 'getPermissions']);
    
    Route::middleware(['admin'])->prefix('users')->group(function () {
        Route::get('/{id}/roles', [RolePermissionController::class, 'getUserRoles']);
        Route::post('/{id}/roles', [RolePermissionController::class, 'assignRoles']);
        Route::post('/{id}/assign-role', [UserController::class, 'assignRole']);
        Route::post('/{id}/revoke-admin', [UserController::class, 'revokeAdminRole']);
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    });
    
    // User Management routes
    Route::middleware(['auth:api'])->prefix('users')->group(function () {
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        // Route::put('/{id}', [UserController::class, 'update']); // Moved up to avoid conflict
        Route::get('/{id}/bookings', [UserController::class, 'getUserBookings']);
        Route::get('/{id}/favorites', [UserController::class, 'getUserFavorites']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
    });
    
    // Admin-only user management routes
    Route::middleware(['auth:api', 'admin'])->prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'getAllUsersForAdmin']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Violation Management routes
    Route::middleware(['auth:api'])->prefix('violations')->group(function () {
        Route::get('/', [ViolationController::class, 'index']);
        Route::post('/', [ViolationController::class, 'store']);
        Route::get('/statistics', [ViolationController::class, 'statistics']);
        Route::get('/{id}', [ViolationController::class, 'show']);
        Route::put('/{id}', [ViolationController::class, 'update']);
        Route::post('/users/{userId}/ban', [ViolationController::class, 'banUser']);
        Route::post('/users/{userId}/warn', [ViolationController::class, 'warnUser']);
    });

    // Theater Management routes (Admin & Movie Manager only)
    Route::middleware(['auth:api'])->prefix('admin/theaters')->group(function () {
        Route::get('/', [TheaterController::class, 'adminIndex']);
        Route::post('/', [TheaterController::class, 'store']);
        Route::get('/{id}', [TheaterController::class, 'show']);
        Route::put('/{id}', [TheaterController::class, 'update']);
        Route::delete('/{id}', [TheaterController::class, 'destroy']);
    });
});

    // File upload routes
    Route::get('/upload/test-auth', [FileUploadController::class, 'testGoogleDriveAuth']);
    Route::middleware(['auth:api'])->post('/upload/file', [FileUploadController::class, 'uploadFile']);
    
    // Debug route to check current user
    Route::middleware('auth:api')->get('/debug/current-user', function() {
        $user = auth()->user();
        if ($user) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'name' => $user->name
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No authenticated user'
            ]);
        }
    });
    Route::post('/upload/file-local', [FileUploadController::class, 'uploadFileLocal']);
    Route::post('/upload/files', [FileUploadController::class, 'uploadMultipleFiles']);
    Route::delete('/upload/file', [FileUploadController::class, 'deleteFile']);
    Route::get('/upload/file-info', [FileUploadController::class, 'getFileInfo']);
    
    // Booking routes
    Route::post('/bookings/lock-seats', [BookingController::class, 'lockSeats']);
    Route::post('/bookings/release-seats', [BookingController::class, 'releaseSeats']);
    Route::post('/bookings', [BookingController::class, 'createBooking']);
    Route::get('/bookings/{bookingId}', [BookingController::class, 'getBookingDetails']);
    Route::get('/users/{userId}/bookings', [BookingController::class, 'getUserBookings']);
    Route::post('/bookings/{bookingId}/cancel', [BookingController::class, 'cancelBooking']);
    Route::get('/snacks', [BookingController::class, 'getSnacks']);

// Test endpoint for debugging
Route::get('/test-auth', function() {
    return response()->json([
        'success' => true,
        'message' => 'API is working',
        'timestamp' => now(),
    ]);
});

// Test database endpoint
Route::get('/test-db', function() {
    $userCount = \App\Models\User::count();
    $movieCount = \App\Models\Movie::count();
    $reviewCount = \App\Models\Review::count();
    
    $users = \App\Models\User::select('id', 'name', 'email')->get();
    $movies = \App\Models\Movie::select('id', 'title')->get();
    
    return response()->json([
        'success' => true,
        'data' => [
            'user_count' => $userCount,
            'movie_count' => $movieCount,
            'review_count' => $reviewCount,
            'users' => $users,
            'movies' => $movies,
        ]
    ]);
});

// Test Google Drive endpoint
Route::get('/test-google-drive', function() {
    try {
        $service = new \App\Services\GoogleDriveService();
        $authResult = $service->testAuth();
        
        // Test thumbnail URL
        $testFileId = "1riZU9q6L6ndPIRFYC4Tta0erWhDAdJTo";
        $thumbnailUrl = $service->getThumbnailUrl($testFileId);
        
        return response()->json([
            'success' => true,
            'auth_result' => $authResult,
            'test_thumbnail_url' => $thumbnailUrl,
            'config' => [
                'client_id' => config('services.google.client_id') ? 'SET' : 'NOT_SET',
                'client_secret' => config('services.google.client_secret') ? 'SET' : 'NOT_SET',
                'refresh_token' => config('services.google.refresh_token') ? 'SET' : 'NOT_SET',
                'folder_id' => config('services.google.folder_id') ?: 'NOT_SET',
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Test upload endpoint (no auth required for testing)
Route::post('/test-upload', [FileUploadController::class, 'testUploadFile']);

// Image proxy endpoint to avoid CORS issues
Route::get('/image-proxy', function(\Illuminate\Http\Request $request) {
    $url = $request->query('url');
    
    if (!$url) {
        return response()->json(['error' => 'URL parameter required'], 400);
    }
    
    // Validate that it's a Google Drive URL
    if (!str_contains($url, 'drive.google.com')) {
        return response()->json(['error' => 'Only Google Drive URLs allowed'], 400);
    }
    
    try {
        // Extract file ID from Google Drive URL
        $fileId = null;
        if (preg_match('/id=([^&]+)/', $url, $matches)) {
            $fileId = $matches[1];
        }
        
        if (!$fileId) {
            return response()->json(['error' => 'Invalid Google Drive URL'], 400);
        }
        
        // Try different Google Drive access methods
        $urls = [
            "https://drive.google.com/thumbnail?id=" . $fileId . "&sz=w1000-h1000",
            "https://drive.google.com/uc?export=download&id=" . $fileId,
            "https://lh3.googleusercontent.com/d/" . $fileId,
        ];
        
        foreach ($urls as $testUrl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_COOKIEJAR, '');
            curl_setopt($ch, CURLOPT_COOKIEFILE, '');
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);
            
            if ($httpCode === 200 && $imageData && strlen($imageData) > 100) {
                // Return the image data directly
                return response($imageData)
                    ->header('Content-Type', $contentType ?: 'image/jpeg')
                    ->header('Cache-Control', 'public, max-age=3600')
                    ->header('Access-Control-Allow-Origin', '*');
            }
        }
        
        // If all fails, return a placeholder image
        $placeholderPath = public_path('placeholder.svg');
        if (file_exists($placeholderPath)) {
            return response()->file($placeholderPath);
        }
        
        return response()->json(['error' => 'Unable to access Google Drive file'], 404);
            
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

    // Admin routes
    Route::middleware(['auth:api', 'admin'])->group(function () {
        Route::post('/movies', [MovieController::class, 'store']);
        Route::post('/movies/with-files', [MovieController::class, 'storeWithFiles']);
        Route::put('/movies/{id}', [MovieController::class, 'update']);
        Route::delete('/movies/{id}', [MovieController::class, 'destroy']);
    });