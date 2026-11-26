<?php

namespace App\Http\Resources\Marketplace;

use App\Services\Payments\Agreement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(['id', 'editor_id', 'work_item_id', 'cover_message', 'proposed_price', 'estimated_delivery_days']),
            'terms_fingerprint' => $this->whenLoaded('workItem', fn () => Agreement::selection($this->workItem, $this->resource)),
            'work_terms' => $this->whenLoaded('workItem', fn () => Agreement::terms($this->workItem)),
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
