<?php

namespace App\Services;

use App\Repositories\Contracts\CarRepositoryInterface;
use App\Services\Contracts\CarServiceInterface;
use App\Http\Resources\Car\CarResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class CarService implements CarServiceInterface
{
    protected CarRepositoryInterface $carRepository;

    /**
     * CarService constructor.
     *
     * @param CarRepositoryInterface $carRepository
     */
    public function __construct(CarRepositoryInterface $carRepository)
    {
        $this->carRepository = $carRepository;
    }

    /**
     * List cars with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @param array $includes
     * 
     * @return array
    */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1, array $includes = []): array
    {
        return CarResource::collection(
            $this->carRepository->listing($filters, $order, $limit, $page, $includes)
        )->response()->getData(true);
    }

    /**
     * Get details of a car.
     *
     * @param int $id
     * @return array
     */
    public function detail(int $id): array
    {
        return (new CarResource(
            $this->carRepository->findById($id)
        ))->response()->getData(true);
    }

    /**
     * Register a new car.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        // Extract files from payload and remove them from $data before create
        [$profileFile, $displayFiles] = $this->extractUploadFiles($data);

        // Create first to get the car ID for folder naming
        $car = $this->carRepository->create($data);

        // Store files under cars/{id}/...
        $updates = $this->storeUploadsForCar((int) $car->id, $profileFile, $displayFiles, Arr::get($data, 'displayImages', []));
        if (!empty($updates)) {
            // Persist file URL updates on the created car
            $this->carRepository->update((int) $car->id, $updates);
            // Refresh representation
            foreach ($updates as $k => $v) {
                $car->{$k} = $v;
            }
        }

        return (new CarResource($car))->response()->getData(true);
    }

    /**
     * Update an existing car.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        // Extract files from payload and remove them from $data before update
        [$profileFile, $displayFiles] = $this->extractUploadFiles($data);

        // Store uploads (if any) and merge back to $data
        $updates = $this->storeUploadsForCar($id, $profileFile, $displayFiles, Arr::get($data, 'displayImages', []));
        if (!empty($updates)) {
            $data = array_merge($data, $updates);
        }

        return $this->carRepository->update($id, $data);
    }

    /**
     * Delete a car by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->carRepository->delete($id);
    }

    /**
     * Pull UploadedFile instances out of the input data.
     * 
     * @param array $data
     * @return array
     */
    protected function extractUploadFiles(array &$data): array
    {
        $profileFile = Arr::get($data, 'profileImageFile');
        if (!($profileFile instanceof UploadedFile)) {
            $tmp = Arr::get($data, 'profileImage');
            $profileFile = $tmp instanceof UploadedFile ? $tmp : null;
        }

        $displayFiles = [];
        $fromExplicit = Arr::get($data, 'displayImagesFiles');
        if (is_array($fromExplicit)) {
            foreach ($fromExplicit as $f) {
                if ($f instanceof UploadedFile) {
                    $displayFiles[] = $f;
                }
            }
        }
        $fromNamed = Arr::get($data, 'displayImages');
        if (is_array($fromNamed)) {
            foreach ($fromNamed as $v) {
                if ($v instanceof UploadedFile) {
                    $displayFiles[] = $v;
                }
            }
        }

        // Remove temp file keys
        unset($data['profileImageFile'], $data['displayImagesFiles']);

        return [$profileFile, $displayFiles];
    }

    /**
     * Store uploaded files into per-car folders and return URL updates.
     * - profile image: cars/{id}/profileImage/
     * - display images: cars/{id}/displayImages/
     * Optionally merges with existing displayImages array (strings) passed in $existingDisplay.
     */
    protected function storeUploadsForCar(int $carId, ?UploadedFile $profileFile, array $displayFiles, array $existingDisplay = []): array
    {
        $updates = [];

        if ($profileFile instanceof UploadedFile) {
            $path = $profileFile->store("cars/{$carId}/profileImage", 'public');
            $updates['profileImage'] = asset('storage/' . $path);
        }

        $displayUrls = [];
        foreach ($displayFiles as $file) {
            if ($file instanceof UploadedFile) {
                $p = $file->store("cars/{$carId}/displayImages", 'public');
                $displayUrls[] = asset('storage/' . $p);
            }
        }
        if (!empty($displayUrls)) {
            // Merge with any existing display image URLs provided
            $merged = array_values(array_filter(array_merge((array) $existingDisplay, $displayUrls)));
            $updates['displayImages'] = $merged;
        }

        return $updates;
    }
}
