<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Validation\Rule;

final class ServiceRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        $presence = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'title' => [$presence, 'required', 'string', 'max:255'],
            'description' => [$presence, 'required', 'string', 'max:10000'],
            'content_type_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('content_types', 'id')->whereNull('deleted_at')],
            'price' => [$presence, 'required', 'string', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D', 'gt:0'],
            'currency' => ['sometimes', Rule::in(['JOD', 'USD'])],
            'delivery_days' => ['sometimes', 'nullable', 'integer', 'between:1,2147483647'],
        ];
    }
}
