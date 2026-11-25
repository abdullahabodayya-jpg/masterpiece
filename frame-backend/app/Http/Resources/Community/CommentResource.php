<?php

namespace App\Http\Resources\Community;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'body' => $this->body,
            'status' => $this->status->value,
            'author' => new PersonResource($this->whenLoaded('author')),
            'reactions_count' => $this->whenCounted('reactions'),
            'my_reaction' => $this->whenHas('my_reaction'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
