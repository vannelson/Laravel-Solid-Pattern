<?php

namespace App\Services;

use App\Http\Resources\Payment\PaymentResource;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Services\Contracts\PaymentServiceInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService implements PaymentServiceInterface
{
    protected PaymentRepositoryInterface $payments;
    protected BookingRepositoryInterface $bookings;

    public function __construct(
        PaymentRepositoryInterface $payments,
        BookingRepositoryInterface $bookings
    ) {
        $this->payments = $payments;
        $this->bookings = $bookings;
    }

    public function listByBooking(
        int $bookingId,
        array $filters = [],
        array $order = ['paid_at', 'desc'],
        int $limit = 10,
        int $page = 1
    ): array {
        $this->bookings->findById($bookingId);

        $paginator = $this->payments->listByBooking($bookingId, $filters, $order, $limit, $page);

        return PaymentResource::collection($paginator)->response()->getData(true);
    }

    public function register(int $bookingId, array $data): array
    {
        $booking = $this->bookings->findById($bookingId);

        if ((bool) $booking->is_lock) {
            throw ValidationException::withMessages([
                'booking' => ['This booking is locked and cannot receive new payments.'],
            ]);
        }

        return DB::transaction(function () use ($booking, $data) {
            $payload = Arr::only($data, [
                'amount',
                'status',
                'method',
                'reference',
                'meta',
                'paid_at',
            ]);

            $payload['booking_id'] = $booking->id;

            if (!isset($payload['status'])) {
                $payload['status'] = $booking->payment_status ?? 'Pending';
            }

            $payment = $this->payments->create($payload);

            $booking->syncPaymentStatus($payment);

            return (new PaymentResource($payment))->response()->getData(true);
        });
    }
}
