<?php

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;

interface TranscriptServiceInterface
{
    /**
     * Parse an uploaded transcript JSON file into an array payload.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function parseUpload(UploadedFile $file): array;
}
