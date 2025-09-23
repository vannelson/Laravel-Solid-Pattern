<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'borrower_id',
        'tenant_id',
        'start_date',
        'end_date',
        'expected_return_date',
        'actual_return_date',
        'rate',
        'rate_type',
        'base_amount',
        'extra_payment',
        'discount',
        'total_amount',
        'payment_status',
        'status',
    ];

    /**
     * The car that was booked.
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * The user who borrowed the car.
     */
    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    /**
     * The staff/admin who handled the booking.
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
