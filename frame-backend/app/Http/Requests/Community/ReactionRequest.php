<?php

namespace App\Http\Requests\Community;

final class ReactionRequest extends CommunityWriteRequest
{
    public function rules(): array
    {
        return [
            'reaction_type' => ['required', 'string', 'max:50'],
            'user_id' => ['missing'],
            'post_id' => ['missing'],
            'comment_id' => ['missing'],
        ];
    }
}
