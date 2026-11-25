<?php

namespace App\Http\Resources\Profile;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProfessionalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $editor = $this->role === UserRole::Editor;

        return [
            'id' => $this->id,
            'display_name' => $this->display_name ?: $this->name,
            'bio' => $this->bio,
            'role' => $this->role->value,
            'professional_title' => $this->when($editor, $this->professional_title),
            'experience_level' => $this->when($editor, $this->experience_level?->value),
            'years_of_experience' => $this->when($editor, $this->years_of_experience),
            'is_open_to_work' => $this->when($editor, $this->is_open_to_work),
            'creator_niche' => $this->when(! $editor, $this->creator_niche),
            'audience_size' => $this->when(! $editor, $this->audience_size),
            'audience_size_updated_at' => $this->when(! $editor, $this->audience_size_updated_at?->toISOString()),
            'content_types' => $this->whenLoaded('contentTypes', fn () => $this->contentTypes->map->only(['id', 'name'])),
            'editing_tools' => $this->whenLoaded('editingTools', fn () => $this->editingTools->map->only(['id', 'name'])),
        ];
    }
}
