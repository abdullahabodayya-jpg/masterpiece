<?php

namespace App\Http\Resources\Marketplace;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WorkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user('sanctum');
        $participant = $viewer && in_array($viewer->id, [$this->creator_id, $this->editor_id], true);

        return [
            ...$this->only(['id', 'creator_id', 'editor_id', 'editor_service_id', 'content_type_id', 'editing_tool_id', 'title',
                'description', 'budget_min', 'budget_max', 'currency', 'quantity']),
            'agreed_price' => $this->when($participant, $this->agreed_price),
            'agreed_at' => $this->when($participant, $this->agreed_at?->toISOString()),
            'started_at' => $this->when($participant, $this->started_at?->toISOString()),
            'type' => $this->type->value,
            'status' => $this->status->value,
            'deadline' => $this->deadline?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
