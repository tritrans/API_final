<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'user_id',
        'rating',
        'comment',
        'is_hidden',
        'hidden_reason',
        'hidden_by',
        'hidden_at',
        'parent_review_id',
    ];

    protected $casts = [
        'rating' => 'float',
        'is_hidden' => 'boolean',
        'hidden_at' => 'datetime',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Review::class, 'parent_review_id');
    }

    public function parentReview(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'parent_review_id');
    }

    // Auto-update movie rating when review changes
    protected static function booted()
    {
        static::created(function ($review) {
            $review->updateMovieRating();
        });

        static::updated(function ($review) {
            $review->updateMovieRating();
        });

        static::deleted(function ($review) {
            $review->updateMovieRating();
        });
    }

    public function updateMovieRating()
    {
        $movie = $this->movie;
        if ($movie) {
            // Calculate average rating from all reviews
            $averageRating = static::where('movie_id', $movie->id)
                ->avg('rating');
            
            // Update movie rating
            $movie->update(['rating' => round($averageRating, 1)]);
        }
    }
}
