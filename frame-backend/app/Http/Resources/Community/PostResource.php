<?php

namespace App\Http\Resources\Community;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'visibility' => $this->visibility->value,
            'status' => $this->status->value,
            'author' => new PersonResource($this->whenLoaded('author')),
            'comments_count' => $this->whenCounted('comments'),
            'reactions_count' => $this->whenCounted('reactions'),
            'my_reaction' => $this->whenHas('my_reaction'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
