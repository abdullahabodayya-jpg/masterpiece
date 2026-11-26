<?php

namespace App\Http\Resources\Reviews;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toISOString(),
            $this->mergeWhen(! $request->routeIs('reviews.public.*'), fn () => $this->only([
                'work_item_id', 'reviewer_user_id', 'reviewed_user_id',
            ])),
        ];
    }
}
