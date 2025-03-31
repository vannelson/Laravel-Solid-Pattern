<?php

namespace App\Services;

use App\Repositories\Contracts\SongRepositoryInterface;
use App\Services\Contracts\SongServiceInterface;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\SongResource;

/**
 * Class SongService
 */
class SongService implements SongServiceInterface
{
    protected SongRepositoryInterface $songRepository;

    /**
     * SongService constructor.
     *
     * @param SongRepositoryInterface $songRepository
     */
    public function __construct(SongRepositoryInterface $songRepository)
    {
        $this->songRepository = $songRepository;
    }

    /**
     * Retrieve a paginated list of songs.
     *
     * @param array $filters 
     * @param array $order 
     * @param int $limit 
     * @param int $page
     * @return array 
     */
    public function getPaginatedSongs(array $filters, array $order, int $limit, int $page): array
    {
        $songs = $this->songRepository->listSongs($filters, $order, $limit, $page);

        return SongResource::collection($songs)->response()->getData(true);
    }

    /**
     * Create a new song.
     *
     * @param array $data
     * @return array
     */
    public function createSong(array $data): array
    {
        $data['user_id'] = Auth::id();
        $song = $this->songRepository->create($data);
     
        return (new SongResource($song))->response()->getData(true);
    }

    /**
     * Update a song.
     *
     * @param int $id
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function updateSong(int $id, array $data): array
    {
        $song = $this->songRepository->findById($id);
        if ($song->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized to update this song.');
        }

        $updatedSong = $this->songRepository->updateAndGet($id, $data);

        return (new SongResource($updatedSong))->response()->getData(true);
    }

    /**
     * Delete a song.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteSong(int $id): bool
    {
        $song = $this->songRepository->findById($id);
        if ($song->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized to delete this song.');
        }

        return $this->songRepository->delete($id);
    }
}
