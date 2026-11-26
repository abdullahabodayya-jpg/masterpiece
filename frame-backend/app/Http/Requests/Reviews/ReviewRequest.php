<?php

namespace App\Http\Requests\Reviews;

use App\Http\Requests\Marketplace\ModuleWriteRequest;

final class ReviewRequest extends ModuleWriteRequest
{
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer:strict', 'between:1,5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
