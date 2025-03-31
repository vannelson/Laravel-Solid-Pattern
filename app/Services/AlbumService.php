<?php

namespace App\Services;

use App\Repositories\Contracts\AlbumRepositoryInterface;
use App\Services\Contracts\AlbumServiceInterface;
use App\Http\Resources\AlbumResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class AlbumService
 *
 * @package App\Services
 */
class AlbumService implements AlbumServiceInterface
{
    protected AlbumRepositoryInterface $albumRepository;

    /**
     * AlbumService constructor.
     *
     * @param AlbumRepositoryInterface $albumRepository
     */
    public function __construct(AlbumRepositoryInterface $albumRepository)
    {
        $this->albumRepository = $albumRepository;
    }

    /**
     * Retrieve a paginated list of albums with sorting and filtering.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getPaginatedAlbums(array $filters, array $order, int $limit, int $page): array
    {
        $paginator = $this->albumRepository->listAlbums($filters, $order, $limit, $page);
        
        // Transform using AlbumResource collection
        return AlbumResource::collection($paginator)->response()->getData(true);
    }

    /**
     * Create an album by the user.
     *
     * @param array $data
     * @return array
     */
    public function createAlbumByUser(array $data): array
    {
        $data['user_id'] = Auth::id();
        $album = $this->albumRepository->create($data);

        return (new AlbumResource($album))->response()->getData(true);
    }

    /**
     * Update an album by the user.
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateAlbumByUser(int $id, array $data): array
    {
        $album = $this->albumRepository->findById($id);
        if ($album->user_id !== Auth::id()) {
            throw new \Exception('You are not authorized to update this album.');
        }

        $updatedAlbum = $this->albumRepository->updateAndGet($id, $data);
        
        return (new AlbumResource($updatedAlbum))->response()->getData(true);
    }

    /**
     * Delete an album by the user.
     *
     * @param int $id
     * @return bool
     */
    public function deleteAlbumByUser(int $id): bool
    {
        $album = $this->albumRepository->findById($id);
        if ($album->user_id !== Auth::id()) {
            throw new \Exception('You are not authorized to delete this album.');
        }

        return $this->albumRepository->delete($id); 
    }
}
