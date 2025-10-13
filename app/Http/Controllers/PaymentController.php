<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\PaymentStoreRequest;
use App\Services\Contracts\PaymentServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    use ResponseTrait;

    protected PaymentServiceInterface $payments;

    public function __construct(PaymentServiceInterface $payments)
    {
        $this->payments = $payments;
    }

    public function index(Request $request, int $booking): JsonResponse
    {
        $filters = Arr::get($request->all(), 'filters', []);
        $orderInput = Arr::get($request->all(), 'order');
        $order = ['paid_at', 'desc'];

        if (is_string($orderInput)) {
            $order = [$orderInput, 'desc'];
        } elseif (is_array($orderInput)) {
            if (Arr::isAssoc($orderInput)) {
                $order = [
                    Arr::get($orderInput, 'column', 'paid_at'),
                    Arr::get($orderInput, 'direction', 'desc'),
                ];
            } else {
                $order = [
                    Arr::get($orderInput, 0, 'paid_at'),
                    Arr::get($orderInput, 1, 'desc'),
                ];
            }
        }

        $order[1] = strtolower((string) $order[1]) === 'asc' ? 'asc' : 'desc';
        $limit   = (int) Arr::get($request->all(), 'limit', 10);
        $page    = (int) Arr::get($request->all(), 'page', 1);

        try {
            $data = $this->payments->listByBooking($booking, $filters, $order, $limit, $page);
            return $this->successPagination('Payments retrieved successfully!', $data);
        } catch (\Exception $e) {
            return $this->error('Failed to load payments.', 500);
        }
    }

    public function store(PaymentStoreRequest $request, int $booking): JsonResponse
    {
        try {
            $payment = $this->payments->register($booking, $request->validated());
            return $this->success('Payment recorded successfully!', $payment);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to record payment.', 500);
        }
    }
}
