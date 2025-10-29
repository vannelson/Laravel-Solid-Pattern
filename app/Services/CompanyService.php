<?php

namespace App\Services;

use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Services\Contracts\CompanyServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Company\CompanyResource;

/**
 * Class UserService
 *
 * Handles business logic related to user management.
 */
class CompanyService implements CompanyServiceInterface
{
    protected CompanyRepositoryInterface $companyRepository;

    /**
     * UserService constructor.
     *
     * @param CompanyRepositoryInterface $userRepository
     */
    public function __construct(CompanyRepositoryInterface $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    /**
     * List companies  with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @return array
    */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1): array 
    {
        return CompanyResource::collection($this->companyRepository
                    ->listing($filters, $order, $limit, $page))
            ->response()
            ->getData(true);
    }

    /**
     * 
     * @param int $id
     * @return array
     */
    public function detail($id): array 
    {
        return (new CompanyResource(
                    $this->companyRepository->findById($id)))
            ->response()->getData(true);
    }

    /**
     * Register a new company.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $data = array_merge(['is_default' => false], $data);
        $data['is_default'] = (bool) $data['is_default'];

        $logoFile = Arr::get($data, 'logo');
        if ($logoFile instanceof UploadedFile) {
            unset($data['logo']);
        }

        if ($data['is_default'] === true) {
            $this->companyRepository->clearDefaultForUser((int) $data['user_id']);
        }

        $company = $this->companyRepository->create($data);

        if ($logoFile instanceof UploadedFile) {
            $logoUrl = $this->storeLogoFile((int) $company->id, $logoFile);
            $this->companyRepository->update((int) $company->id, ['logo' => $logoUrl]);
            $company->logo = $logoUrl;
        }

        return (new CompanyResource($company))
                        ->response()->getData(true);
    }

    /**
     * Update an existing user.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        if (Arr::exists($data, 'is_default')) {
            $data['is_default'] = (bool) $data['is_default'];

            if ($data['is_default'] === true) {
                $company = $this->companyRepository->findById($id);

                if ($company) {
                    $this->companyRepository->clearDefaultForUser((int) $company->user_id, $id);
                }
            }
        }

        $logoFile = Arr::get($data, 'logo');
        if ($logoFile instanceof UploadedFile) {
            $data['logo'] = $this->storeLogoFile($id, $logoFile);
        }

        return $this->companyRepository->update($id, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function findNearby(array $params): array
    {
        $latitude = (float) $params['lat'];
        $longitude = (float) $params['lng'];
        $radius = (int) ($params['radius'] ?? 10000);
        $radius = max(1, min($radius, 50000));

        $limit = (int) ($params['limit'] ?? 20);
        $limit = max(1, min($limit, 100));

        $withCarsFlag = array_key_exists('with_cars', $params) ? (bool) $params['with_cars'] : null;
        $withCars = $withCarsFlag === true;
        $requireAvailableCars = $withCarsFlag !== false;
        $includeDistance = array_key_exists('include_distance', $params)
            ? (bool) $params['include_distance']
            : true;
        $minDistance = Arr::get($params, 'min_distance');
        $minDistance = $minDistance !== null ? (int) $minDistance : null;
        $filters = Arr::get($params, 'filters', []);

        $companies = $this->companyRepository->findNearby(
            $latitude,
            $longitude,
            $radius,
            [
                'limit' => $limit,
                'with_cars' => $withCars,
                'filters' => $filters,
                'min_distance' => $minDistance,
                'require_available_cars' => $requireAvailableCars,
            ]
        );

        $data = $companies->map(function ($company) use ($withCars, $includeDistance) {
            $payload = [
                'id'        => $company->id,
                'name'      => $company->name,
                'address'   => $company->address,
                'latitude'  => $company->latitude !== null ? (float) $company->latitude : null,
                'longitude' => $company->longitude !== null ? (float) $company->longitude : null,
                'industry'  => $company->industry,
                'logo_url'  => $company->logo,
            ];

            if ($includeDistance && isset($company->distance_m)) {
                $distance = (float) $company->distance_m;
                $payload['distance_m'] = (int) round($distance);
                $payload['distance_km'] = round($distance / 1000, 2);
            }

            if ($withCars) {
                $cars = collect($company->cars ?? [])
                    ->filter(static function ($car) {
                        return strtolower((string) $car->info_availabilityStatus) === 'available';
                    })
                    ->values()
                    ->map(static function ($car) {
                        $name = trim(implode(' ', array_filter([$car->info_make, $car->info_model])));

                        return [
                            'id'                   => $car->id,
                            'name'                 => $name !== '' ? $name : null,
                            'car_type'             => $car->info_carType,
                            'availability_status'  => $car->info_availabilityStatus,
                            'location'             => $car->info_location,
                            'rate'                 => optional($car->activeRate)->rate,
                            'rate_type'            => optional($car->activeRate)->rate_type,
                        ];
                    })
                    ->take(5)
                    ->values()
                    ->all();

                $payload['cars'] = $cars;
            }

            return $payload;
        })->values()->all();

        return [
            'data' => $data,
            'meta' => [
                'radius' => $radius,
                'count'  => count($data),
            ],
            'links' => null,
        ];
    }

    /**
     * Delete a user by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->companyRepository->delete($id);
    }

    /**
     * Store an uploaded logo file and return the public URL.
     */
    protected function storeLogoFile(int $companyId, UploadedFile $file): string
    {
        $folder = "companies/{$companyId}/logo";
        Storage::disk('public')->deleteDirectory($folder);

        $path = $file->store($folder, 'public');

        return asset('storage/' . $path);
    }
}
