<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Movie",
    title: "Movie",
    description: "Movie model",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", example: "Avengers: Endgame"),
        new OA\Property(property: "title_vi", type: "string", example: "Biệt Đội Siêu Anh Hùng: Hồi Kết"),
        new OA\Property(property: "description", type: "string", example: "The grave course of events..."),
        new OA\Property(property: "description_vi", type: "string", example: "Cuộc chiến cuối cùng..."),
        new OA\Property(property: "poster", type: "string", example: "https://example.com/poster.jpg"),
        new OA\Property(property: "backdrop", type: "string", example: "https://example.com/backdrop.jpg"),
        new OA\Property(property: "trailer", type: "string", example: "https://youtube.com/watch?v=123"),
        new OA\Property(property: "release_date", type: "string", format: "date", example: "2019-04-26"),
        new OA\Property(property: "duration", type: "integer", example: 181),
        new OA\Property(property: "genre", type: "array", items: new OA\Items(type: "string"), example: ["Action", "Adventure"]),
        new OA\Property(property: "rating", type: "number", format: "float", example: 8.4),
        new OA\Property(property: "country", type: "string", example: "USA"),
        new OA\Property(property: "language", type: "string", example: "English"),
        new OA\Property(property: "director", type: "string", example: "Anthony Russo, Joe Russo"),
        new OA\Property(property: "cast", type: "array", items: new OA\Items(type: "string"), example: ["Robert Downey Jr.", "Chris Evans"]),
        new OA\Property(property: "slug", type: "string", example: "avengers-endgame"),
        new OA\Property(property: "featured", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time")
    ]
)]
class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_vi',
        'description',
        'description_vi',
        'poster',
        'backdrop',
        'trailer',
        'release_date',
        'duration',
        'rating',
        'country',
        'language',
        'director',
        'slug',
        'featured',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'featured' => 'boolean',
        'release_date' => 'date',
    ];

    /**
     * Get the genres for the movie.
     */
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genre');
    }

    /**
     * Get the casts for the movie.
     */
    public function movieCasts()
    {
        return $this->belongsToMany(Person::class, 'movie_people')
                    ->withPivot('character_name', 'billing_order', 'role')
                    ->withTimestamps();
    }

    /**
     * Get the tickets for the movie.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'movie_id', 'id');
    }

    /**
     * Get the reviews for the movie.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'movie_id', 'id');
    }

    /**
     * Get the comments for the movie.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'movie_id', 'id');
    }

    /**
     * Get the favorites for the movie.
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'movie_id', 'id');
    }

    /**
     * Get the schedules for the movie.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the average rating for the movie.
     */
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Get the total reviews count for the movie.
     */
    public function getReviewsCountAttribute()
    {
        return $this->reviews()->count();
    }

    /**
     * Get the genre names as an array.
     */
    public function getGenreAttribute()
    {
        return $this->genres()->pluck('name')->toArray();
    }

    /**
     * Scope a query to only include featured movies.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope a query to search movies by title.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('title_vi', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('description_vi', 'like', "%{$search}%");
        });
    }
}
