<?php

namespace App\Repositories;

use App\Models\Song;
use App\Repositories\Contracts\SongRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

/**
 * Class SongRepository
 */
class SongRepository extends BaseRepository implements SongRepositoryInterface
{
    /**
     * SongRepository constructor.
     */
    public function __construct(Song $song)
    {
        parent::__construct($song);
    }

    /**
     * Retrieve a paginated list of songs 
     *
     * @param array $filters 
     * @param array $order 
     * @param int $limi
     * @param int $page 
     * @return LengthAwarePaginator 
     */
    public function listSongs(array $filters = [], array $order = [], int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['album']) 
            ->withCount('reactions'); 

        // Apply optional filters
        if ($title = Arr::get($filters, 'title')) {
            $query->where('title', 'LIKE', "%$title%");
        }
        if ($albumId = Arr::get($filters, 'album_id')) {
            $query->where('album_id', $albumId);
        }

        // Apply ordering (default: reactions_count desc)
        [$orderBy, $dir] = !empty($order) ? $order : ['reactions_count', 'desc'];
        $query->orderBy($orderBy, $dir);

        // Return paginator instance
        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
