<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\CreateUserRequest;
use App\Services\Accounts\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(
        CreateUserRequest $request,
        UserService $userService
    ): JsonResponse
    {
        $user = $userService->create($request->validated());

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