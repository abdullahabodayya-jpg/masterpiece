<?php

namespace App\Http\Requests\Marketplace;

final class ServiceIntakeRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        return [
            'offer_fingerprint' => ['sometimes', 'required', 'string', 'size:64'],
            'description' => ['required', 'string', 'max:10000'],
            'quantity' => ['sometimes', 'integer', 'in:1'],
            'deadline' => ['sometimes', 'nullable', 'date', 'after:now', 'before:2038-01-19 03:14:07 UTC'],
        ];
    }
}
