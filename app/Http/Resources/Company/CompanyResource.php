<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\User\UserResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'address'  => $this->address,
            'industry' => $this->industry,
            'user_id'  => $this->user_id,
            'user'     => new UserResource($this->whenLoaded('user')), // 👈 include user details
        ];
    }
}
