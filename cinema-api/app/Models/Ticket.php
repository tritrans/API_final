<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_email',
        'movie_id',
        'movie_title',
        'poster_url',
        'schedule_id',
        'seats',
        'total_amount',
        'date_time',
        'theater_id',
        'status',
    ];

    protected $casts = [
        'seats' => 'array',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the ticket.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the movie for the ticket.
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Get the theater for the ticket.
     */
    public function theater()
    {
        return $this->belongsTo(Theater::class);
    }

    /**
     * Get the schedule for the ticket.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Scope a query to only include active tickets.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include tickets for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
