<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlbumRequest;
use App\Services\Contracts\AlbumServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Class AlbumController
 */
class AlbumController extends Controller
{
    use ResponseTrait;

    protected AlbumServiceInterface $albumService;

    /**
     * AlbumController constructor.
     *
     * @param AlbumServiceInterface $albumService
     */
    public function __construct(AlbumServiceInterface $albumService)
    {
        $this->albumService = $albumService;
    }

    /**
     * List albums with pagination, filtering, and sorting.
     *
     * @param Request $request HTTP request object.
     * @return JsonResponse JSON response containing album list.
     */
    public function list(Request $request): JsonResponse
    {
        $filters = Arr::get($request->all(), 'filters', []);
        $order = Arr::get($request->all(), 'order', ['songs_count', 'desc']);
        $limit = (int) Arr::get($request->all(), 'limit', 10);
        $page = (int) Arr::get($request->all(), 'page', 1);

        // try {
            $data = $this->albumService->getPaginatedAlbums($filters, $order, $limit, $page);
            return $this->successPagination('Albums retrieved successfully!', $data);
        // } catch (\Exception $e) {
        //     return $this->error('Failed to retrieve albums.');
        // }
    }

    /**
     * Create a new album by user.
     *
     * @param AlbumRequest $request
     * @return JsonResponse
     */
    public function create(AlbumRequest $request): JsonResponse
    {
        try {
            $album = $this->albumService->createAlbumByUser($request->validated());
            return $this->success('Album created successfully!', $album);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to create album.', 500);
        }
    }

    /**
     * Update an album by user.
     *
     * @param AlbumRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(AlbumRequest $request, int $id): JsonResponse
    {
        try {
            $album = $this->albumService->updateAlbumByUser($id, $request->validated());
            return $this->success('Album updated successfully!', $album);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to update album.', 500);
        }
    }

    /**
     * Delete an album by user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $this->albumService->deleteAlbumByUser($id);
            return $this->success('Album deleted successfully!');
        } catch (\Exception $e) {
            return $this->error('Failed to delete album.', 500);
        }
    }
}
