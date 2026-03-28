<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoutingRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'team_id' => $this->team_id,
            'team' => $this->whenLoaded('team', fn () => $this->team?->only(['id', 'name', 'slug'])),
            'default_priority' => $this->default_priority?->value,
            'is_active' => $this->is_active,
        ];
    }
}
