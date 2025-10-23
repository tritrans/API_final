<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    /**
     * Display a listing of favorites for the authenticated user
     */
    public function index()
    {
        $favorites = Favorite::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    /**
     * Store a newly created favorite
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'movie_id' => 'required|string',
            'title' => 'required|string',
            'poster_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if already favorited
        $existingFavorite = Favorite::where('user_id', Auth::id())
            ->where('movie_id', $request->movie_id)
            ->first();

        if ($existingFavorite) {
            return response()->json([
                'success' => false,
                'message' => 'Movie is already in your favorites'
            ], 400);
        }

        $favorite = Favorite::create([
            'user_id' => Auth::id(),
            'user_email' => Auth::user()->email,
            'movie_id' => $request->movie_id,
            'title' => $request->title,
            'poster_url' => $request->poster_url,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to favorites successfully',
            'data' => $favorite
        ], 201);
    }

    /**
     * Remove the specified favorite
     */
    public function destroy($movieId)
    {
        $favorite = Favorite::where('user_id', Auth::id())
            ->where('movie_id', $movieId)
            ->firstOrFail();

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed from favorites successfully'
        ]);
    }
}
