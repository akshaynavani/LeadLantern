<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'role' => $this->role?->name,
            'team' => $this->whenLoaded('team', fn () => $this->team ? new TeamResource($this->team) : null),
            'team_id' => $this->team_id,
        ];
    }
}
