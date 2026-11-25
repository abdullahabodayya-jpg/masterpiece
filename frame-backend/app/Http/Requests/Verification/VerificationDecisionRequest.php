<?php

namespace App\Http\Requests\Verification;

use App\Http\Requests\Marketplace\ModuleWriteRequest;
use Illuminate\Validation\Rule;

final class VerificationDecisionRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:5000'],
        ];
    }
}
