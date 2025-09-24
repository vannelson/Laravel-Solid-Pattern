<?php

namespace App\Services;

use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Contracts\BookingServiceInterface;
use App\Http\Resources\Booking\BookingResource;
use Illuminate\Validation\ValidationException;

class BookingService implements BookingServiceInterface
{
    protected BookingRepositoryInterface $bookingRepository;

    public function __construct(BookingRepositoryInterface $bookingRepository)
    {
        $this->bookingRepository = $bookingRepository;
    }

    /**
     * List bookings with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @param array $includes
     * @return array
     */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1, array $includes = []): array
    {
        return BookingResource::collection(
            $this->bookingRepository->listing($filters, $order, $limit, $page, $includes)
        )->response()->getData(true);
    }

    /**
     * Show booking detail.
     *
     * @param int $id
     * @param array $includes
     * @return array
     */
    public function detail(int $id): array
    {
        return (new BookingResource($this->bookingRepository->findById($id)))
            ->response()
            ->getData(true);
    }

    /**
     * Create a new booking.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        //  Check for overlapping bookings before creating
        $conflict = $this->bookingRepository->hasConflict(
            $data['car_id'],
            $data['start_date'],
            $data['end_date']
        );

        if ($conflict) {
            throw ValidationException::withMessages([
                'car_id' => ['This car is already booked for the selected dates.'],
            ]);
        }

        $booking = $this->bookingRepository->create($data);

        return (new BookingResource($booking))->response()->getData(true);
    }

    /**
     * Update an existing booking.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $conflict = $this->bookingRepository->hasConflict(
            $data['car_id'],
            $data['start_date'],
            $data['end_date'],
            $id 
        );

        if ($conflict) {
            throw ValidationException::withMessages([
                'car_id' => ['This car is already booked for the selected dates.'],
            ]);
        }

        return $this->bookingRepository->update($id, $data);
    }

    /**
     * Delete a booking by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->bookingRepository->delete($id);
    }
}
