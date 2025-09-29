<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'name',       // e.g., "Standard Rate", "Christmas Promo"
        'rate',
        'rate_type',  // daily, weekly, hourly
        'start_date',
        'status',     // active, inactive, scheduled
    ];

    /**
     * Car that owns this rate.
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Scope: only active rates.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: only inactive rates.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope: only scheduled rates (future use).
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    protected static function booted()
    {
        static::saved(function (CarRate $rate) {
            if ($rate->status === 'active') {
                // Ensure only one active rate per car by deactivating others
                static::where('car_id', $rate->car_id)
                    ->where('id', '!=', $rate->id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);
            }
        });
    }
}
