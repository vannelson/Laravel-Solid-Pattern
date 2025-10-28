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
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude'=> $this->longitude !== null ? (float) $this->longitude : null,
            'industry' => $this->industry,
            'is_default' => (bool) $this->is_default,
            'logo'     => $this->logo,
            'user_id'  => $this->user_id,
            'user'     => new UserResource($this->whenLoaded('user')), // 👈 include user details
        ];
    }
}
