<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',

        // Basic Info (info_)
        'info_make',
        'info_model',
        'info_year',
        'info_age',
        'info_carType',
        'info_plateNumber',
        'info_vin',
        'info_availabilityStatus',
        'info_location',
        'info_mileage',

        // Specifications (spcs_)
        'spcs_seats',
        'spcs_largeBags',
        'spcs_smallBags',
        'spcs_engineSize',
        'spcs_transmission',
        'spcs_fuelType',
        'spcs_fuelEfficiency',

        // Features & Images
        'features',
        'profileImage',
        'displayImages',
    ];

    protected $casts = [
        'features'       => 'array',
        'displayImages'  => 'array',
    ];

    /**
     * Car belongs to a Company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Car has many rates.
     */
    public function rates()
    {
        return $this->hasMany(CarRate::class);
    }

    /**
     * Car’s currently active rate.
     */
    public function activeRate()
    {
        return $this->hasOne(CarRate::class)
            ->where('status', 'active')
            ->latest();
    }

    /**
     * Car bookings.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
