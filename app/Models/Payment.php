<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBooking;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    use BelongsToBooking;

    protected $fillable = [
        'booking_id',
        'amount',
        'status',
        'method',
        'reference',
        'meta',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'meta'    => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Normalise status casing so we can accept lower-case input safely.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value,
            set: fn (?string $value) => $value !== null
                ? ucfirst(strtolower($value))
                : null
        );
    }
}
