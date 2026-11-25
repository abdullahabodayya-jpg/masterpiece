<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Validation\Rule;

final class JobRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        $presence = $this->isMethod('PATCH') ? 'sometimes' : 'required';
        $money = ['sometimes', 'nullable', 'string', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D'];

        return [
            'title' => [$presence, 'required', 'string', 'max:255'],
            'description' => [$presence, 'required', 'string', 'max:10000'],
            'content_type_id' => [$presence, 'required', 'uuid', Rule::exists('content_types', 'id')->whereNull('deleted_at')],
            'editing_tool_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('editing_tools', 'id')->whereNull('deleted_at')],
            'budget_min' => $money,
            'budget_max' => $money,
            'currency' => ['sometimes', Rule::in(['JOD'])],
            'deadline' => ['sometimes', 'nullable', 'date', 'after:now', 'before:2038-01-19 03:14:07 UTC'],
            'quantity' => ['sometimes', 'nullable', 'integer', 'between:1,2147483647'],
        ];
    }
}
