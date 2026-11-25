<?php

namespace App\Http\Resources\Work;

use App\Services\Work\DeliveryFiles;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DeliverableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(['id', 'work_item_id', 'title', 'description', 'sequence_number', 'review_notes']),
            'status' => $this->status->value,
            'submission_token' => DeliveryFiles::token($this->resource),
            'due_at' => $this->due_at?->toISOString(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
        ];
    }
}
