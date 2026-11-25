<?php

namespace App\Http\Requests\Marketplace;

final class ApplicationRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        $presence = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'cover_message' => [$presence, 'required', 'string', 'max:5000'],
            'proposed_price' => [$presence, 'required', 'string', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D', 'gt:0'],
            'estimated_delivery_days' => ['sometimes', 'nullable', 'integer', 'between:1,2147483647'],
        ];
    }
}
