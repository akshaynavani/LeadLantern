<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'channel' => $this->channel,
            'status' => $this->status?->value,
            'category' => $this->category,
            'priority' => $this->priority?->value,
            'team' => $this->whenLoaded('team', fn () => $this->team?->only(['id', 'name', 'slug'])),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee?->only(['id', 'name'])),
            'contact' => $this->whenLoaded('contact', fn () => $this->contact ? [
                'id' => $this->contact->id,
                'full_name' => $this->contact->full_name,
                'company' => $this->contact->company,
            ] : null),
            'interaction_id' => $this->interaction_id,
            'latest_decision' => new RoutingDecisionResource($this->whenLoaded('latestDecision')),
            'routing_decisions' => RoutingDecisionResource::collection($this->whenLoaded('routingDecisions')),
            'due_at' => $this->due_at?->toIso8601String(),
            'routed_at' => $this->routed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
