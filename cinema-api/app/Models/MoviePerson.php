<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoviePerson extends Model
{
    use HasFactory;

    protected $table = 'movie_people';

    protected $fillable = [
        'movie_id',
        'person_id',
        'role',
        'character_name',
        'billing_order',
    ];

    protected $casts = [
        'movie_id' => 'integer',
        'person_id' => 'integer',
        'billing_order' => 'integer',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
