<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Display all comments (for admin dashboard)
     */
    public function getAllComments()
    {
        try {
            $comments = Comment::with('user', 'movie')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'movie_id' => $comment->movie_id,
                        'user_id' => $comment->user_id,
                        'user_name' => $comment->user->name ?? 'Người dùng ẩn danh',
                        'user_email' => $comment->user->email ?? 'N/A',
                        'user_avatar_url' => $comment->user->avatar ?? null,
                        'content' => $comment->content,
                        'parent_id' => $comment->parent_id,
                        'is_hidden' => $comment->is_hidden ?? false,
                        'created_at' => $comment->created_at->toISOString(),
                        'updated_at' => $comment->updated_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $comments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comments for a specific movie (public endpoint)
     */
    public function getMovieComments($movieId)
    {
        // Get only parent comments (no parent_id) with their replies, include hidden comments
        $comments = Comment::where('movie_id', $movieId)
            ->whereNull('parent_id')
            ->with(['user', 'replies' => function($query) {
                $query->with('user'); // Load user for replies
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'movie_id' => $comment->movie_id,
                    'user_id' => $comment->user_id,
                    'user_name' => $comment->user->name ?? 'Người dùng ẩn danh',
                    'user_email' => $comment->user->email ?? null,
                    'user_avatar_url' => $comment->user->avatar ?? null,
                    'parent_id' => $comment->parent_id,
                    'content' => $comment->content,
                    'is_hidden' => $comment->is_hidden,
                    'hidden_reason' => $comment->hidden_reason,
                    'hidden_at' => $comment->hidden_at,
                    'replies' => $comment->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'movie_id' => $reply->movie_id,
                            'user_id' => $reply->user_id,
                            'user_name' => $reply->user->name ?? 'Người dùng ẩn danh',
                            'user_email' => $reply->user->email ?? null,
                            'user_avatar_url' => $reply->user->avatar ?? null,
                            'parent_id' => $reply->parent_id,
                            'content' => $reply->content,
                            'is_hidden' => $reply->is_hidden,
                            'hidden_reason' => $reply->hidden_reason,
                            'hidden_at' => $reply->hidden_at,
                            'created_at' => $reply->created_at->toISOString(),
                            'updated_at' => $reply->updated_at->toISOString(),
                        ];
                    })->sortBy('created_at')->values(), // Sort replies by creation time
                    'created_at' => $comment->created_at->toISOString(),
                    'updated_at' => $comment->updated_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $comments->values()->toArray()
        ]);
    }

    /**
     * Display a listing of comments for a specific movie
     */
    public function index($movieId)
    {
        $comments = Comment::where('movie_id', $movieId)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    /**
     * Store a newly created comment
     * Tạo bình luận mới cho phim
     * 
     * @param Request $request - Dữ liệu request chứa content và parent_id
     * @param int $movieId - ID của phim
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $movieId)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
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
        
        $comment = Comment::create([
            'user_id' => $userId,
            'movie_id' => $movieId,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        $comment->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => $comment
        ], 201);
    }

    /**
     * Create a reply to a comment (for admin)
     * Tạo reply cho bình luận (dành cho admin)
     * 
     * @param Request $request - Dữ liệu request chứa content
     * @param int $commentId - ID của bình luận gốc
     * @return \Illuminate\Http\JsonResponse
     */
    public function createReply(Request $request, $commentId)
    {
        try {
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

            // Get the parent comment
            $parentComment = Comment::find($commentId);
            if (!$parentComment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent comment not found'
                ], 404);
            }
            
            // Create reply as authenticated user
            $userId = Auth::id() ?? $request->input('user_id'); // Use authenticated user ID or get from request
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }
            
            $reply = Comment::create([
                'user_id' => $userId,
                'movie_id' => $parentComment->movie_id,
                'content' => $request->content,
                'parent_id' => $commentId,
            ]);

            // Load user relationship
            $reply->load('user');

            // Format response similar to getMovieComments
            $formattedReply = [
                'id' => $reply->id,
                'movie_id' => $reply->movie_id,
                'user_id' => $reply->user_id,
                'user_name' => $reply->user->name ?? 'Người dùng ẩn danh',
                'user_email' => $reply->user->email ?? null,
                'user_avatar_url' => $reply->user->avatar ?? null,
                'content' => $reply->content,
                'parent_id' => $reply->parent_id,
                'is_hidden' => $reply->is_hidden ?? false,
                'created_at' => $reply->created_at->toISOString(),
                'updated_at' => $reply->updated_at->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Reply created successfully',
                'data' => $formattedReply
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating reply',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified comment
     */
    public function update(Request $request, $id)
    {
        $comment = Comment::where('user_id', Auth::id())->findOrFail($id);

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

        $comment->update($request->only(['content']));
        $comment->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => $comment
        ]);
    }

    /**
     * Remove the specified comment
     */
    public function destroy($id)
    {
        $comment = Comment::where('user_id', Auth::id())->findOrFail($id);
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    /**
     * Hide comment for violation (violation_manager only)
     */

    /**
     * Display a single comment
     */
    public function show($id)
    {
        try {
            $comment = Comment::with('user', 'movie')->find($id);
            
            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $comment->id,
                    'movie_id' => $comment->movie_id,
                    'user_id' => $comment->user_id,
                    'user_name' => $comment->user->name ?? 'Người dùng ẩn danh',
                    'user_email' => $comment->user->email ?? 'N/A',
                    'user_avatar_url' => $comment->user->avatar ?? null,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at->toISOString(),
                    'updated_at' => $comment->updated_at->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching comment'
            ], 500);
        }
    }
}
