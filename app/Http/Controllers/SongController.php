<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongCreateRequest;
use App\Http\Requests\SongUpdateRequest;
use App\Services\Contracts\SongServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Http\Resources\SongResource;

/**
 * Class SongController
 */
class SongController extends Controller
{
    use ResponseTrait;

    protected SongServiceInterface $songService;

    /**
     * SongController constructor.
     */
    public function __construct(SongServiceInterface $songService)
    {
        $this->songService = $songService;
    }
    
    /**
     * List songs with pagination, filtering, and sorting.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $filters = Arr::get($request->all(), 'filters', []);
        $order   = Arr::get($request->all(), 'order', ['reactions_count', 'desc']);
        $limit   = (int) Arr::get($request->all(), 'limit', 10);
        $page    = (int) Arr::get($request->all(), 'page', 1);
    
        try {
            $data = $this->songService->getPaginatedSongs($filters, $order, $limit, $page);
            return $this->successPagination('Songs retrieved successfully!', $data);
        } catch (\Exception $e) {
            return $this->error('Failed to load song.', 500);
        }
    }


    /**
     * Create a new song.
     */
    public function create(SongCreateRequest $request): JsonResponse
    {
        try {
            $song = $this->songService->createSong($request->validated());
            return $this->success('Song created successfully!', $song);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to create song.', 500);
        }
    }

    /**
     * Update an existing song.
     */
    public function update(SongUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $this->songService->updateSong($id, $request->validated());
            return $this->success('Song updated successfully!');
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to update song.', 500);
        }
    }

    /**
     * Delete a song.
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $this->songService->deleteSong($id);
            return $this->success('Song deleted successfully!');
        } catch (\Exception $e) {
            return $this->error('Failed to delete song.', 500);
        }
    }
}
