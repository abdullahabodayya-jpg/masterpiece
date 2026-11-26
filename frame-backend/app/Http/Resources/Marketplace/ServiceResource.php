<?php

namespace App\Http\Resources\Marketplace;

use App\Services\Payments\Agreement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(['id', 'editor_id', 'content_type_id', 'title', 'description', 'price', 'currency', 'delivery_days']),
            'offer_fingerprint' => Agreement::offer($this->resource),
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
