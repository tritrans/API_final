<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'avatar',
        'birthday',
        'country',
        'bio',
    ];

    /**
     * Get the movies for the person.
     */
    public function movieCasts()
    {
        return $this->belongsToMany(Movie::class, 'movie_people')
                    ->withPivot('character_name', 'billing_order', 'role')
                    ->withTimestamps();
    }
}


