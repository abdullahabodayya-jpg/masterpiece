<?php

namespace App\Http\Resources\Community;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_filter([
            'post_id' => $this->post_id,
            'comment_id' => $this->comment_id,
            'reaction_type' => $this->reaction_type,
        ], fn ($value) => $value !== null);
    }
}
