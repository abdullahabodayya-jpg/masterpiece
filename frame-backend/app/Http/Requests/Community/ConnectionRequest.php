<?php

namespace App\Http\Requests\Community;

use Illuminate\Validation\Rule;

final class ConnectionRequest extends CommunityWriteRequest
{
    public function rules(): array
    {
        return $this->route('connectionId') ? [
            'decision' => ['required', Rule::in(['accept', 'reject'])],
            'status' => ['missing'],
            'requester_id' => ['missing'],
            'recipient_id' => ['missing'],
        ] : [
            'recipient_id' => ['required', 'uuid'],
            'requester_id' => ['missing'],
            'status' => ['missing'],
        ];
    }
}
