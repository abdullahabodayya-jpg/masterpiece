<?php

namespace App\Http\Resources\Verification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(['id', 'editor_id', 'document_type', 'reviewed_by_user_id', 'decision_notes']),
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'decided_at' => $this->decided_at?->toISOString(),
        ];
    }
}
