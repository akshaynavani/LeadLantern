<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteractionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_id' => $this->contact_id,
            'channel' => $this->channel,
            'direction' => $this->direction,
            'subject' => $this->subject,
            'body' => $this->body,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => $this->user?->only(['id', 'name'])),
            'task_id' => $this->whenLoaded('task', fn () => $this->task?->id),
        ];
    }
}
