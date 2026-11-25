<?php

namespace App\Http\Requests\Community;

final class CommentRequest extends CommunityWriteRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'author_id' => ['missing'],
            'status' => ['missing'],
            'post_id' => ['missing'],
            'parent_id' => ['missing'],
            'parent_comment_id' => ['missing'],
        ];
    }
}
