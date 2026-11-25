<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PortfolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $url = $this->external_url;
        $safeUrl = $url && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);

        return [
            'id' => $this->id,
            'editor_id' => $this->editor_id,
            'content_type_id' => $this->content_type_id,
            'title' => $this->title,
            'description' => $this->description,
            'external_url' => $safeUrl ? $url : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
