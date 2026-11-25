<?php

namespace App\Http\Resources\Community;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConnectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_id' => $this->requester_id,
            'recipient_id' => $this->recipient_id,
            'status' => $this->status->value,
            'requester' => new PersonResource($this->whenLoaded('requester')),
            'recipient' => new PersonResource($this->whenLoaded('recipient')),
            'created_at' => $this->created_at?->toISOString(),
            'responded_at' => $this->responded_at?->toISOString(),
        ];
    }
}
