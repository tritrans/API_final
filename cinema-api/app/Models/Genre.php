<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Genre",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Action"),
        new OA\Property(property: "name_vi", type: "string", example: "Hành động"),
        new OA\Property(property: "slug", type: "string", example: "action"),
        new OA\Property(property: "description", type: "string", example: "Action movies"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_vi',
        'slug',
        'description',
    ];

    /**
     * Get the movies for the genre.
     */
    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_genre');
    }
}
