<?php

namespace App\Http\Resources\Messaging;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(['id', 'conversation_id', 'sender_id', 'message_body']),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
