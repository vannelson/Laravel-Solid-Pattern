<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transcript\TranscriptUploadRequest;
use App\Services\Contracts\TranscriptServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;

class TranscriptController extends Controller
{
    use ResponseTrait;

    protected TranscriptServiceInterface $transcriptService;

    public function __construct(TranscriptServiceInterface $transcriptService)
    {
        $this->transcriptService = $transcriptService;
    }

    /**
     * Upload a transcript JSON file and return the parsed payload.
     *
     * @param TranscriptUploadRequest $request
     * @return JsonResponse
     */
    public function upload(TranscriptUploadRequest $request): JsonResponse
    {
        try {
            if (!$request->hasFile('file')) {
                return $this->error('File is required.', 422);
            }

            $file = $request->file('file');
            if (!$file || !$file->isValid()) {
                return $this->error('Uploaded file is invalid.', 422);
            }

            $payload = $this->transcriptService->parseUpload($file);

            return $this->success('Transcript uploaded successfully!', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'transcript' => $payload,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 500);
        } catch (\Exception $e) {
            return $this->error('Failed to upload transcript.', 500);
        }
    }
}
