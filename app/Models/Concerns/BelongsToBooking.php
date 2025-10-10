<?php

namespace App\Models\Concerns;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBooking
{
    /**
     * Every payment entry belongs to a booking record.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
