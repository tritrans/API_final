<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Snack extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_vi',
        'description',
        'description_vi',
        'price',
        'image',
        'category',
        'available',
        'stock'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'available' => 'boolean',
        'stock' => 'integer'
    ];

    public function bookingSnacks()
    {
        return $this->hasMany(BookingSnack::class);
    }
}