<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:web')->group(function () {

    Route::post('/registrations', [
        RegisteredUserController::class,
        'store',
    ])->middleware('throttle:5,1');

    Route::post('/sessions', [
        AuthenticatedSessionController::class,
        'store',
    ]);

});

Route::middleware('auth:sanctum')->group(function () {

    Route::delete('/sessions/current', [
        AuthenticatedSessionController::class,
        'destroy',
    ]);

    Route::get('/me', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
            ],
        ]);
    });

});

require __DIR__.'/api/profile.php';
require __DIR__.'/api/marketplace.php';
require __DIR__.'/api/verification.php';
require __DIR__.'/api/work.php';
require __DIR__.'/api/messaging.php';
require __DIR__.'/api/payments.php';
require __DIR__.'/api/reviews.php';
require __DIR__.'/api/community.php';
