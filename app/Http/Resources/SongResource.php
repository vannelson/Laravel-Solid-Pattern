<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class SongResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $userId = Auth::id(); // Get authenticated user ID
        $isLiked = $this->reactions->contains('user_id', $userId); // Check if user reacted

        return [
            'id'              => $this->id,
            'album_id'        => $this->album_id,
            'user_id'         => $this->user_id,
            'title'           => $this->title,
            'artist'          => $this->artist,
            'duration'        => $this->duration,
            'url'             => $this->url,
            'created_at'      => $this->created_at->toDateTimeString(),
            'updated_at'      => $this->updated_at->toDateTimeString(),
            'album'           => new AlbumResource($this->whenLoaded('album')),
            'reactions_count' => $this->reactions_count ?? 0,
            'is_liked'        => $isLiked, // Check if the user liked the song
        ];
    }
}

