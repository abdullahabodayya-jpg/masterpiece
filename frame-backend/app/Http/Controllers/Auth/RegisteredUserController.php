<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
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
        ]);

        $user = new User;

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password_hash = $validated['password'];
        $user->role = UserRole::from($validated['role']);
        $user->status = AccountStatus::PendingVerification;

        $user->save();

        event(new Registered($user));

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
            ],
        ], 201);
    }
}
