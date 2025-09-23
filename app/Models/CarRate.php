<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'rate',
        'rate_type',
        'start_date',
        'end_date',
        'status', // active, inactive, expired, scheduled
    ];

    /**
     * Car that owns this rate.
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Scope to only active rates.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
