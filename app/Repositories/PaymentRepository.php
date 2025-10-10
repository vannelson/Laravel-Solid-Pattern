<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $payment)
    {
        parent::__construct($payment);
    }

    public function listByBooking(
        int $bookingId,
        array $filters = [],
        array $order = ['paid_at', 'desc'],
        int $limit = 10,
        int $page = 1
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->where('booking_id', $bookingId);

        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', $status);
        }

        if ($method = Arr::get($filters, 'method')) {
            $query->where('method', $method);
        }

        if ($reference = Arr::get($filters, 'reference')) {
            $query->where('reference', $reference);
        }

        if (Arr::has($filters, 'from') && Arr::has($filters, 'to')) {
            $query->whereBetween('paid_at', [$filters['from'], $filters['to']]);
        }

        [$orderBy, $direction] = $order;

        $query->orderBy($orderBy, $direction);

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function latestForBooking(int $bookingId): ?Payment
    {
        return $this->model->newQuery()
            ->where('booking_id', $bookingId)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();
    }
}
