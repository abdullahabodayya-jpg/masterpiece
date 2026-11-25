<?php

namespace App\Http\Resources\Messaging;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latest = $this->latestMessage;
        $marker = $this->participants->firstWhere('id', $request->user('sanctum')->id)?->pivot->last_read_at;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'work_item_id' => $this->work_item_id,
            'participants' => $this->participants->map(fn ($person) => [
                'id' => $person->id,
                'display_name' => $person->display_name ?: $person->name,
            ]),
            'latest_message' => $latest ? [
                'id' => $latest->id,
                'sender_id' => $latest->sender_id,
                'preview' => $latest->message_body === null ? null : Str::limit($latest->message_body, 160),
                'created_at' => $latest->created_at?->toISOString(),
            ] : null,
            'last_read_at' => $marker === null ? null : Carbon::parse($marker)->toISOString(),
            'activity_at' => $this->activity_at?->toISOString(),
        ];
    }
}
