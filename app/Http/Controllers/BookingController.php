<?php

namespace App\Http\Controllers;

use App\Http\Requests\Booking\BookingStoreRequest;
use App\Http\Requests\Booking\BookingUpdateRequest;
use App\Services\Contracts\BookingServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    use ResponseTrait;

    protected BookingServiceInterface $bookingService;

    public function __construct(BookingServiceInterface $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Display a listing of bookings with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $filters  = Arr::get($request->all(), 'filters', []);
        $order    = Arr::get($request->all(), 'order', ['id', 'desc']);
        $limit    = (int) Arr::get($request->all(), 'limit', 10);
        $page     = (int) Arr::get($request->all(), 'page', 1);
        $includes = Arr::get($request->all(), 'include', []);

        try {
            $data = $this->bookingService->getList($filters, $order, $limit, $page, $includes);
            return $this->successPagination('Bookings retrieved successfully!', $data);
        } catch (\Exception $e) {
            return $this->error('Failed to load bookings.', 500);
        }
    }

    /**
     * Store a newly created booking.
     */
    public function store(BookingStoreRequest $request): JsonResponse
    {
        try {
            $data = $this->appendIdentificationUploads($request->validated(), $request);

            $booking = $this->bookingService->register($data);
            return $this->success('Booking created successfully!', $booking);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to create booking.', 500);
        }
    }

    /**
     * Display the specified booking.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->detail($id);
            return $this->success('Booking retrieved successfully!', $booking);
        } catch (\Exception $e) {
            return $this->error('Failed to load booking.', 500);
        }
    }

    /**
     * Update the specified booking.
     */
    public function update(BookingUpdateRequest $request, int $id): JsonResponse
    {
        try {
             sleep(5);
            $data = $this->appendIdentificationUploads($request->validated(), $request);

            $this->bookingService->update($id, $data);
            return $this->success('Booking updated successfully!');
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to update booking.', 500);
        }
    }

    /**
     * Remove the specified booking.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->bookingService->delete($id);
            return $this->success('Booking deleted successfully!');
        } catch (\Exception $e) {
            return $this->error('Failed to delete booking.', 500);
        }
    }

    /**
     * Extract identification upload files from the request while keeping the payload clean.
     */
    protected function appendIdentificationUploads(array $data, Request $request): array
    {
        $files = $request->file('identificationImagesFiles') ?: $request->file('identification_images');

        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        if (is_array($files)) {
            $uploads = array_values(array_filter($files, static fn ($file) => $file instanceof UploadedFile));
            if (!empty($uploads)) {
                $data['identificationImagesFiles'] = $uploads;
            }
        }

        return $data;
    }
}
