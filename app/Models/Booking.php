<?php

namespace App\Models;

use App\Models\Concerns\HasPayment;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    use HasPayment;

    protected $fillable = [
        'car_id',
        'company_id',
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
        'is_lock',
    ];

    protected $casts = [
        'identification_images' => 'array',
        'is_lock' => 'boolean',
    ];

    /**
     * The car that was booked.
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * The company that owns the booked car.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
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
