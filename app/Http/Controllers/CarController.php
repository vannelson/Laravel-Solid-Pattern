<?php

namespace App\Http\Controllers;

use App\Http\Requests\Car\CarStoreRequest;
use App\Http\Requests\Car\CarUpdateRequest;
use App\Http\Requests\Car\CarImageUploadRequest;
use App\Services\Contracts\CarServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CarController extends Controller
{
    use ResponseTrait;

    protected CarServiceInterface $carService;

    /**
     * CarController constructor.
     *
     * @param CarServiceInterface $carService
     */
    public function __construct(CarServiceInterface $carService)
    {
        $this->carService = $carService;
    }

    /**
     * Display a listing of cars.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters  = Arr::get($request->all(), 'filters', []);
        $order    = Arr::get($request->all(), 'order', ['id', 'desc']);
        $limit    = (int) Arr::get($request->all(), 'limit', 10);
        $page     = (int) Arr::get($request->all(), 'page', 1);
        $includes = Arr::get($request->all(), 'include', []); // 👈 array of relationships

        try {
            $data = $this->carService->getList($filters, $order, $limit, $page, $includes);
            return $this->successPagination('Cars retrieved successfully!', $data);
        } catch (\Exception $e) {
            return $this->error('Failed to load cars.', 500);
        }
    }


    /**
     * Store a new car.
     *
     * @param CarStoreRequest $request
     * @return JsonResponse
     */
    public function store(CarStoreRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Collect file uploads; service will store them under cars/{id}/...
            $data['profileImageFile'] = $request->file('profileImageFile') ?: $request->file('profileImage');
            $displayFiles = $request->file('displayImagesFiles') ?: $request->file('displayImages');
            if ($displayFiles) {
                $data['displayImagesFiles'] = is_array($displayFiles) ? $displayFiles : [$displayFiles];
            }

            // Let the service layer handle storing files and setting URLs

            $car = $this->carService->register($data);
            return $this->success('Car registered successfully!', $car);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to register car.', 500);
        }
    }

    /**
     * Display a specific car.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $includes = Arr::get($request->all(), 'include', []);
            $car = $this->carService->detail($id, $includes);
            return $this->success('Car retrieved successfully!', $car);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve car.', 500);
        }
    }

    /**
     * Update an existing car.
     *
     * @param CarUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(CarUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();

            // Collect file uploads; service will store them under cars/{id}/...
            $data['profileImageFile'] = $request->file('profileImageFile') ?: $request->file('profileImage');
            
            $displayFiles = $request->file('displayImagesFiles') ?: $request->file('displayImages');
            if ($displayFiles) {
                $data['displayImagesFiles'] = is_array($displayFiles) ? $displayFiles : [$displayFiles];
            }

            // Files will be handled by service

            $this->carService->update($id, $data);
            return $this->success('Car updated successfully!');
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to update car.', 500);
        }
    }

    /**
     * Remove a car.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->carService->delete($id);
            return $this->success('Car deleted successfully!');
        } catch (\Exception $e) {
            return $this->error('Failed to delete car.', 500);
        }
    }

    /**
     * Upload a car image and return its public URL.
     *
     * @param CarImageUploadRequest $request
     * @return JsonResponse
     */
    public function upload(CarImageUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('image');

            // Store on the public disk under cars/
            $path = $file->store('cars', 'public');

            // Build the accessible URL via storage symlink
            $url = asset('storage/' . $path);

            return $this->success('Image uploaded successfully!', [
                'url' => $url,
                'path' => $path,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to upload image.', 500);
        }
    }

    /**
     * Download a remote image URL and store it locally on the public disk.
     * Returns the public URL on success, null on failure.
     */
    protected function downloadAndStoreImage(string $url): ?string
    {
        // Disabled fetching remote URLs to avoid long HTTP waits/timeouts.
        // Keep existing URL as-is; uploads should be sent as files.
        return null;
    }
}
