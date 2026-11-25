<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\Marketplace\ModuleWriteRequest;

final class LinkRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        return [
            'url' => [$this->isMethod('PATCH') ? 'sometimes' : 'required', 'required', 'string', 'max:512', 'url:http,https'],
            'platform_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'title' => ['sometimes', 'nullable', 'string', 'max:100'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
