<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\Marketplace\ModuleWriteRequest;
use Illuminate\Validation\Rule;

final class PortfolioRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        $presence = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'title' => [$presence, 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'content_type_id' => [$presence, 'required', 'uuid', Rule::exists('content_types', 'id')->whereNull('deleted_at')],
            'external_url' => [$presence, 'required', 'string', 'max:512', 'url:http,https'],
        ];
    }
}
