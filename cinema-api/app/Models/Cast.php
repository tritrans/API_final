<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cast extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'bio',
        'birth_date',
        'nationality',
        'image_url'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * The movies that belong to the cast.
     */
    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_casts')
                    ->withPivot('character_name')
                    ->withTimestamps();
    }
}
