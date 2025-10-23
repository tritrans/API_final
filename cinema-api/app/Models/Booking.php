<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'showtime_id',
        'total_price',
        'status'
    ];

    protected $casts = [
        'total_price' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Schedule::class, 'showtime_id');
    }

    public function seats()
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function snacks()
    {
        return $this->hasMany(BookingSnack::class);
    }
}