<?php

namespace App\Http\Requests\Accounts;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['missing'],
            'password_hash' => ['missing'],
            'email_verified_at' => ['missing'],
            'remember_token' => ['missing'],
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],

            'role' => [
                'required',
                Rule::in([
                    UserRole::Creator->value,
                    UserRole::Editor->value,
                ]),
            ],
        ];
    }
}
