<?php

namespace App\Http\Requests\Transcript;

use Illuminate\Foundation\Http\FormRequest;

class TranscriptUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for uploading a transcript JSON file.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'file' => 'file|mimes:json|mimetypes:application/json,text/json|max:102400',
        ];
    }
}
