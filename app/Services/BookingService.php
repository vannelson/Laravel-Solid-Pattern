<?php

namespace App\Services;

use App\Http\Resources\Booking\BookingResource;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Contracts\BookingServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
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
     */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1, array $includes = []): array
    {
        return BookingResource::collection(
            $this->bookingRepository->listing($filters, $order, $limit, $page, $includes)
        )->response()->getData(true);
    }

    /**
     * Show booking detail.
     */
    public function detail(int $id): array
    {
        return (new BookingResource($this->bookingRepository->findById($id)))
            ->response()
            ->getData(true);
    }

    /**
     * Create a new booking.
     */
    public function register(array $data): array
    {
        $this->ensureNoConflict($data['car_id'], $data['start_date'], $data['end_date']);

        [$files, $retainedImages] = $this->extractIdentificationUploads($data);

        if ($retainedImages !== null) {
            $data['identification_images'] = $retainedImages;
        }

        $booking = $this->bookingRepository->create($data);

        $updates = $this->storeIdentificationUploads((int) $booking->id, $files, $retainedImages ?? []);
        if (!empty($updates)) {
            $this->bookingRepository->update((int) $booking->id, $updates);
            foreach ($updates as $key => $value) {
                $booking->{$key} = $value;
            }
        }

        return (new BookingResource($booking))->response()->getData(true);
    }

    /**
     * Update an existing booking.
     */
    public function update(int $id, array $data): bool
    {
        $booking = $this->bookingRepository->findById($id);

        $carId = Arr::get($data, 'car_id', $booking->car_id);
        $start = Arr::get($data, 'start_date', $booking->start_date);
        $end   = Arr::get($data, 'end_date', $booking->end_date);

        if ($carId && $start && $end) {
            $this->ensureNoConflict($carId, $start, $end, $id);
        }

        [$files, $retainedImages, $hasImagesKey] = $this->extractIdentificationUploads($data, true);

        $baseline = $hasImagesKey ? ($retainedImages ?? []) : (is_array($booking->identification_images) ? $booking->identification_images : []);

        $uploadUpdates = $this->storeIdentificationUploads($id, $files, $baseline);
        if (!empty($uploadUpdates)) {
            $data['identification_images'] = $uploadUpdates['identification_images'];
        } elseif ($hasImagesKey) {
            $data['identification_images'] = $retainedImages ?? [];
        }

        return (bool) $this->bookingRepository->update($id, $data);
    }

    /**
     * Delete a booking by ID.
     */
    public function delete(int $id): bool
    {
        return $this->bookingRepository->delete($id);
    }

    /**
     * Ensure there is no schedule conflict for the given car.
     */
    protected function ensureNoConflict(int $carId, string $startDate, string $endDate, ?int $ignoreId = null): void
    {
        $conflict = $this->bookingRepository->hasConflict($carId, $startDate, $endDate, $ignoreId);

        if ($conflict) {
            throw ValidationException::withMessages([
                'car_id' => ['This car is already booked for the selected dates.'],
            ]);
        }
    }

    /**
     * Extract identification uploads from the payload and normalise retained URLs.
     *
     * @return array
     */
    protected function extractIdentificationUploads(array &$data, bool $includeFlag = false): array
    {
        $files = [];

        $directFiles = Arr::pull($data, 'identificationImagesFiles');
        $files = $this->mergeUploadedFiles($files, $directFiles);

        $hasImagesKey = array_key_exists('identification_images', $data);
        $retained = null;

        if ($hasImagesKey) {
            $retained = [];
            $raw = $data['identification_images'];

            if ($raw instanceof UploadedFile) {
                $files[] = $raw;
            } elseif (is_string($raw) && $raw !== '') {
                $retained[] = $raw;
            } elseif (is_array($raw)) {
                foreach ($raw as $value) {
                    if ($value instanceof UploadedFile) {
                        $files[] = $value;
                    } elseif (is_string($value) && $value !== '') {
                        $retained[] = $value;
                    }
                }
            }

            $data['identification_images'] = $retained;
        }

        if ($includeFlag) {
            return [$files, $retained, $hasImagesKey];
        }

        return [$files, $retained];
    }

    /**
     * Merge a mixed value into the uploads collection.
     */
    protected function mergeUploadedFiles(array $files, $value): array
    {
        if ($value instanceof UploadedFile) {
            $files[] = $value;
        } elseif (is_array($value)) {
            foreach ($value as $item) {
                if ($item instanceof UploadedFile) {
                    $files[] = $item;
                }
            }
        }

        return $files;
    }

    /**
     * Store uploaded identification images under bookings/{id}/identifications and return URL updates.
     */
    protected function storeIdentificationUploads(int $bookingId, array $files, array $existing = []): array
    {
        if (empty($files)) {
            return [];
        }

        $stored = [];
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store("bookings/{$bookingId}/identifications", 'public');
            $stored[] = asset('storage/' . $path);
        }

        if (empty($stored)) {
            return [];
        }

        $merged = array_values(array_unique(array_merge($existing, $stored)));

        return ['identification_images' => $merged];
    }
}
