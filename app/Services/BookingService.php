<?php

namespace App\Services;

use App\Http\Resources\Booking\BookingResource;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Contracts\BookingServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $booking = $this->bookingRepository->findById($id);

        $booking->loadMissing([
            'car',
            'borrower',
            'tenant',
            'latestPayment',
            'payments' => static fn ($query) => $query
                ->orderByDesc('paid_at')
                ->orderByDesc('id'),
        ]);

        return (new BookingResource($booking))->response()->getData(true);
    }

    /**
     * Create a new booking.
     */
    public function register(array $data): array
    {
        if (empty($data['borrower_id'])) {
            $borrowerId = Auth::id();
            if ($borrowerId === null) {
                throw ValidationException::withMessages([
                    'borrower_id' => ['Borrower is required.'],
                ]);
            }
            $data['borrower_id'] = $borrowerId;
        }

        if (array_key_exists('is_lock', $data)) {
            $data['is_lock'] = (bool) $data['is_lock'];
        }

        $this->ensureNoConflict($data['car_id'], $data['start_date'], $data['end_date']);

        [$files, $retainedImages, $dataUris] = $this->extractIdentificationUploads($data);

        if ($retainedImages !== null) {
            $data['identification_images'] = $retainedImages;
        }

        $booking = $this->bookingRepository->create($data);

        $updates = $this->storeIdentificationUploads((int) $booking->id, $files, $retainedImages ?? [], $dataUris);
        if (!empty($updates)) {
            $this->bookingRepository->update((int) $booking->id, $updates);
            foreach ($updates as $key => $value) {
                $booking->{$key} = $value;
            }
        }

        $booking->loadMissing([
            'car',
            'borrower',
            'tenant',
            'latestPayment',
        ]);

        return (new BookingResource($booking))->response()->getData(true);
    }

    /**
     * Update an existing booking.
     */
    public function update(int $id, array $data): bool
    {
        $booking = $this->bookingRepository->findById($id);
        if ((bool) $booking->is_lock) {
            throw ValidationException::withMessages(['booking' => ['This booking is locked and cannot be updated.']]);
        }
        if (array_key_exists('is_lock', $data)) {
            $data['is_lock'] = (bool) $data['is_lock'];
        }

        $carId = Arr::get($data, 'car_id', $booking->car_id);
        $start = Arr::get($data, 'start_date', $booking->start_date);
        $end   = Arr::get($data, 'end_date', $booking->end_date);

        if ($carId && $start && $end) {
            $this->ensureNoConflict($carId, $start, $end, $id);
        }

        [$files, $retainedImages, $dataUris, $hasImagesKey] = $this->extractIdentificationUploads($data, true);

        $baseline = $hasImagesKey
            ? ($retainedImages ?? [])
            : (is_array($booking->identification_images) ? $booking->identification_images : []);

        $uploadUpdates = $this->storeIdentificationUploads($id, $files, $baseline, $dataUris);
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
     */
    protected function extractIdentificationUploads(array &$data, bool $includeFlag = false): array
    {
        $files = [];
        $retained = null;
        $dataUris = [];

        $directFiles = Arr::pull($data, 'identificationImagesFiles');
        $files = $this->mergeUploadedFiles($files, $directFiles);

        $hasImagesKey = array_key_exists('identification_images', $data);

        if ($hasImagesKey) {
            $retained = [];
            $raw = $data['identification_images'];

            if ($raw instanceof UploadedFile) {
                $files[] = $raw;
            } elseif (is_string($raw)) {
                if ($this->isDataUri($raw)) {
                    $dataUris[] = $raw;
                } elseif ($raw !== '') {
                    $retained[] = $raw;
                }
            } elseif (is_array($raw)) {
                foreach ($raw as $value) {
                    if ($value instanceof UploadedFile) {
                        $files[] = $value;
                    } elseif (is_string($value)) {
                        if ($this->isDataUri($value)) {
                            $dataUris[] = $value;
                        } elseif ($value !== '') {
                            $retained[] = $value;
                        }
                    }
                }
            }

            $data['identification_images'] = $retained;
        }

        if ($includeFlag) {
            return [$files, $retained, $dataUris, $hasImagesKey];
        }

        return [$files, $retained, $dataUris];
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

    protected function isDataUri(string $value): bool
    {
        return preg_match('/^data:image\/[^;]+;base64,/', $value) === 1;
    }

    protected function storeDataUri(int $bookingId, string $dataUri): ?string
    {
        if (!preg_match('/^data:image\/(?P<mime>[^;]+);base64,(?P<data>.+)$/', $dataUri, $matches)) {
            return null;
        }

        $binary = base64_decode($matches['data'], true);
        if ($binary === false) {
            return null;
        }

        $extension = strtolower($matches['mime']);
        $extension = $extension === 'jpeg' ? 'jpg' : ($extension === 'svg+xml' ? 'svg' : $extension);
        $allowed = ['jpg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        if (!in_array($extension, $allowed, true)) {
            return null;
        }

        $filename = Str::uuid()->toString() . '.' . $extension;
        $path = "bookings/{$bookingId}/identifications/{$filename}";

        if (!Storage::disk('public')->put($path, $binary)) {
            return null;
        }

        return asset('storage/' . $path);
    }

    /**
     * Store uploaded identification images under bookings/{id}/identifications and return URL updates.
     */
    protected function storeIdentificationUploads(int $bookingId, array $files, array $existing = [], array $dataUris = []): array
    {
        $stored = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store("bookings/{$bookingId}/identifications", 'public');
            $stored[] = asset('storage/' . $path);
        }

        foreach ($dataUris as $dataUri) {
            $url = $this->storeDataUri($bookingId, $dataUri);
            if ($url !== null) {
                $stored[] = $url;
            }
        }

        if (empty($stored)) {
            return [];
        }

        $merged = array_values(array_unique(array_merge($existing, $stored)));

        return ['identification_images' => $merged];
    }
}
