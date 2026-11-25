<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->only(['id', 'platform_name', 'title', 'url', 'handle', 'is_primary']);
    }
}
