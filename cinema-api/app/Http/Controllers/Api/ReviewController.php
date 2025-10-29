<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Display all reviews (for admin dashboard)
     */
    public function getAllReviews()
    {
        $reviews = Review::with('user', 'movie')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'movie_id' => $review->movie_id,
                    'user_id' => $review->user_id,
                    'user_name' => $review->user->name ?? 'Người dùng ẩn danh',
                    'user_email' => $review->user->email,
                    'user_avatar_url' => $review->user->avatar,
                    'rating' => (float) $review->rating,
                    'comment' => $review->comment,
                    'is_hidden' => $review->is_hidden ?? false,
                    'created_at' => $review->created_at->toISOString(),
                    'updated_at' => $review->updated_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Get reviews for a specific movie (public endpoint)
     */
    public function getMovieReviews($movieId)
    {
        $reviews = Review::where('movie_id', $movieId)
            ->whereNull('parent_review_id') // Only get main reviews, not replies
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'movie_id' => $review->movie_id,
                    'user_id' => $review->user_id,
                    'user_name' => $review->user->name ?? 'Người dùng ẩn danh',
                    'user_email' => $review->user->email,
                    'user_avatar_url' => $review->user->avatar,
                    'rating' => (float) $review->rating,
                    'comment' => $review->comment,
                    'is_hidden' => $review->is_hidden ?? false,
                    'created_at' => $review->created_at->toISOString(),
                    'updated_at' => $review->updated_at->toISOString(),
                    'replies' => $review->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'user_id' => $reply->user_id,
                            'movie_id' => $reply->movie_id,
                            'user_name' => $reply->user->name ?? 'Người dùng ẩn danh',
                            'user_email' => $reply->user->email,
                            'user_avatar_url' => $reply->user->avatar,
                            'rating' => (float) $reply->rating,
                            'comment' => $reply->comment,
                            'is_hidden' => $reply->is_hidden ?? false,
                            'parent_review_id' => $reply->parent_review_id,
                            'created_at' => $reply->created_at->toISOString(),
                            'updated_at' => $reply->updated_at->toISOString(),
                        ];
                    })
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Display a listing of reviews for a specific movie
     */
    public function index($movieId)
    {
        $reviews = Review::where('movie_id', $movieId)
            ->whereNull('parent_review_id') // Only get main reviews, not replies
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'movie_id' => $review->movie_id,
                    'user_id' => $review->user_id,
                    'user_name' => $review->user->name ?? 'Người dùng ẩn danh',
                    'user_email' => $review->user->email,
                    'user_avatar_url' => $review->user->avatar,
                    'rating' => (float) $review->rating,
                    'comment' => $review->comment,
                    'is_hidden' => $review->is_hidden ?? false,
                    'created_at' => $review->created_at->toISOString(),
                    'replies' => $review->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'movie_id' => $reply->movie_id,
                            'user_id' => $reply->user_id,
                            'user_name' => $reply->user->name ?? 'Người dùng ẩn danh',
                            'user_email' => $reply->user->email,
                            'user_avatar_url' => $reply->user->avatar,
                            'rating' => (float) $reply->rating,
                            'comment' => $reply->comment,
                            'is_hidden' => $reply->is_hidden ?? false,
                            'created_at' => $reply->created_at->toISOString(),
                        ];
                    })
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Store a newly created review
     * Tạo đánh giá mới cho phim
     * 
     * @param Request $request - Dữ liệu request chứa rating và comment
     * @param int $movieId - ID của phim
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $movieId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // For public API, use authenticated user or get from request
        $userId = Auth::id() ?? $request->input('user_id'); // Use authenticated user ID or get from request
        
        // If no user_id provided and no auth, return error
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required'
            ], 400);
        }
        
        // Check if user already reviewed this movie
        $existingReview = Review::where('user_id', $userId)
            ->where('movie_id', $movieId)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this movie'
            ], 400);
        }

        $review = Review::create([
            'user_id' => $userId,
            'movie_id' => $movieId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->load('user');

        $reviewData = [
            'id' => $review->id,
            'movie_id' => $review->movie_id,
            'user_id' => $review->user_id,
            'user_name' => $review->user->name ?? 'Người dùng ẩn danh',
            'user_email' => $review->user->email,
            'user_avatar_url' => $review->user->avatar,
            'rating' => (float) $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => $reviewData
        ], 201);
    }

    /**
     * Update the specified review
     */
    public function update(Request $request, $id)
    {
        $review = Review::where('user_id', Auth::id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $review->update($request->only(['rating', 'comment']));
        $review->load('user');

        $reviewData = [
            'id' => $review->id,
            'movie_id' => $review->movie_id,
            'user_id' => $review->user_id,
            'user_email' => $review->user->email,
            'user_avatar_url' => $review->user->avatar,
            'rating' => (float) $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => $reviewData
        ]);
    }

    /**
     * Get user's review for a specific movie
     */
    public function getUserReview($movieId)
    {
        $review = Review::where('user_id', Auth::id())
            ->where('movie_id', $movieId)
            ->with('user')
            ->first();

        if (!$review) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        $reviewData = [
            'id' => $review->id,
            'movie_id' => $review->movie_id,
            'user_id' => $review->user_id,
            'user_email' => $review->user->email,
            'user_avatar_url' => $review->user->avatar,
            'rating' => (float) $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'data' => $reviewData
        ]);
    }

    /**
     * Remove the specified review
     */
    public function destroy($id)
    {
        $review = Review::where('user_id', Auth::id())->findOrFail($id);
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Display a single review
     */
    public function show($id)
    {
        try {
            $review = Review::with('user', 'movie')->find($id);
            
            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $review->id,
                    'movie_id' => $review->movie_id,
                    'user_id' => $review->user_id,
                    'user_name' => $review->user->name ?? 'Người dùng ẩn danh',
                    'user_email' => $review->user->email ?? 'N/A',
                    'user_avatar_url' => $review->user->avatar ?? null,
                    'rating' => (float) $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->toISOString(),
                    'updated_at' => $review->updated_at->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching review'
            ], 500);
        }
    }

    /**
     * Create a reply to a review
     * Tạo reply cho đánh giá (thực chất là tạo comment)
     * 
     * @param Request $request - Dữ liệu request chứa content
     * @param int $id - ID của đánh giá gốc
     * @return \Illuminate\Http\JsonResponse
     */
    public function createReply(Request $request, $id)
    {
        try {
            $review = Review::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create a comment as a reply to the review
            $userId = Auth::id() ?? $request->input('user_id'); // Use authenticated user ID or get from request
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }
            
            // Create a review as a reply to the review
            $replyReview = Review::create([
                'user_id' => $userId,
                'movie_id' => $review->movie_id,
                'rating' => 0.0, // Replies don't have ratings
                'comment' => $request->content,
                'is_hidden' => false,
                'parent_review_id' => $review->id // Link to parent review
            ]);
            
            // Load user relationship
            $replyReview->load('user');
            
            return response()->json([
                'success' => true,
                'message' => 'Reply created successfully',
                'data' => [
                    'id' => $replyReview->id,
                    'content' => $replyReview->comment,
                    'user_id' => $replyReview->user_id,
                    'user_name' => $replyReview->user->name ?? 'Người dùng ẩn danh',
                    'user_email' => $replyReview->user->email ?? null,
                    'user_avatar_url' => $replyReview->user->avatar ?? null,
                    'movie_id' => $replyReview->movie_id,
                    'rating' => $replyReview->rating,
                    'parent_review_id' => $replyReview->parent_review_id,
                    'created_at' => $replyReview->created_at->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating reply: ' . $e->getMessage()
            ], 500);
        }
    }
}
