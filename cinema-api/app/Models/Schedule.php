<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'theater_id',
        'room_name',
        'start_time',
        'end_time',
        'price',
        'available_seats',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'price' => 'decimal:2',
        'available_seats' => 'array',
    ];

    /**
     * Get the movie that owns the schedule.
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Get the theater that owns the schedule.
     */
    public function theater()
    {
        return $this->belongsTo(Theater::class);
    }
}
