<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class ModuleWriteRequest extends FormRequest
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach (array_diff(array_keys($this->all()), array_keys($validator->getRules())) as $field) {
                $validator->errors()->add($field, 'This field is not supported.');
            }
        });
    }
}
