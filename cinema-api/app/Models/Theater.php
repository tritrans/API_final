<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theater extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the schedules for the theater.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the tickets for the theater.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get schedule info for the theater.
     */
    public function getScheduleInfoAttribute()
    {
        return [
            'total_schedules' => $this->schedules()->count(),
            'today_schedules' => $this->schedules()->whereDate('start_time', today())->count(),
            'upcoming_schedules' => $this->schedules()->where('start_time', '>', now())->count(),
        ];
    }
}
