<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'album_id' => 'required|exists:albums,id',
            'title' => 'required|string|max:255',
            'artist' => 'required|string|max:255',
            'url' => 'required'
        ];
    }
}
