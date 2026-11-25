<?php

namespace App\Http\Requests\Community;

use App\Enums\PostVisibility;
use Illuminate\Validation\Rule;

final class PostRequest extends CommunityWriteRequest
{
    public function rules(): array
    {
        $presence = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'body' => [$presence, 'required', 'string', 'max:5000'],
            'visibility' => [$presence, 'required', Rule::enum(PostVisibility::class)],
            'author_id' => ['missing'],
            'status' => ['missing'],
            'media_storage_key' => ['missing'],
            'media_file_name' => ['missing'],
            'media_type' => ['missing'],
        ];
    }
}
