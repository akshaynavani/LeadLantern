<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoutingDecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'source' => $this->source?->value,
            'category' => $this->category,
            'priority' => $this->priority,
            'sentiment' => $this->sentiment,
            'confidence' => $this->confidence,
            'rationale' => $this->rationale,
            'suggested_actions' => $this->suggested_actions ?? [],
            'team' => $this->whenLoaded('team', fn () => $this->team?->only(['id', 'name', 'slug'])),
            'model' => $this->model,
            'latency_ms' => $this->latency_ms,
            'decided_by' => $this->whenLoaded('decider', fn () => $this->decider?->only(['id', 'name'])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
