<?php

namespace App\Models\Concerns;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Small reusable helpers for models that expose payment relations.
 */
trait HasPayment
{
    /**
     * A model may expose the most recent payment via a one-to-one relation.
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * A model may have many payment records (partial payments, refunds, etc.).
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Helper to propagate a payment status back into the model if supported.
     */
    public function syncPaymentStatus(?Payment $payment = null): void
    {
        if (!method_exists($this, 'forceFill')) {
            return;
        }

        if ($payment === null) {
            $payment = $this->relationLoaded('latestPayment')
                ? $this->getRelation('latestPayment')
                : $this->latestPayment()->first();
        }

        if ($payment === null) {
            return;
        }

        if ($this->getAttribute('payment_status') !== $payment->status) {
            $this->forceFill(['payment_status' => $payment->status])->save();
        }
    }
}
