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
        'destination',
        'rate',
        'rate_type',
        'base_amount',
        'extra_payment',
        'discount',
        'total_amount',
        'payment_status',
        'status',
        'identification_type',
        'identification',
        'identification_number',
        'identification_images',
        'renter_first_name',
        'renter_middle_name',
        'renter_last_name',
        'renter_address',
        'renter_phone_number',
        'renter_email',
    ];

    protected $casts = [
        'identification_images' => 'array',
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
