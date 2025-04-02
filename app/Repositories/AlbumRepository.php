<?php

namespace App\Repositories;

use App\Repositories\Contracts\AlbumRepositoryInterface;
use App\Models\Album;
use Illuminate\Pagination\LengthAwarePaginator;

class AlbumRepository extends BaseRepository implements AlbumRepositoryInterface
{

    public function __construct(Album $album)
    {
        parent::__construct($album);
    }

    /**
     * Retrieve a paginated list of albums ordered by song count.
     *
     * @param array 
     * @param array 
     * @param int 
     * @param int 
     * @return LengthAwarePaginator
     */
    public function listAlbums(array $filters = [], array $order = [], int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->withCount('songs'); 
    
        // Apply filters
        if (!empty($filters['title'])) {
            $query->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
    
        // Order by song count or custom order
        if (!empty($order)) {
            [$orderBy, $dir] = $order;
            $query->orderBy($orderBy, $dir);
        } else {
            $query->orderBy('songs_count', 'desc'); 
        }
    
        // Paginate without eager loading relationships
        $albums = $query->paginate($limit, ['*'], 'page', $page);
    
        // Lazy load relationships
        $albums->each(function ($album) {
            $album->load(['user', 'songs']);
        });
    
        return $albums;
    }
}
