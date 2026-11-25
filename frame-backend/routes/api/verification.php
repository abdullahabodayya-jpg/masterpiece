<?php

use App\Http\Controllers\Api\V1\Verification\VerificationController;
use App\Http\Middleware\MarketplaceApi;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::middleware([MarketplaceApi::class.':verification', 'auth:sanctum'])
    ->withoutMiddleware(SubstituteBindings::class)->group(function () {
        Route::get('verification-requests', [VerificationController::class, 'index']);
        Route::post('verification-requests', [VerificationController::class, 'store']);
        Route::get('verification-requests/status', [VerificationController::class, 'status']);
        Route::get('verification-requests/{verificationId}', [VerificationController::class, 'show'])->whereUuid('verificationId');
        Route::get('verification-requests/{verificationId}/document', [VerificationController::class, 'document'])->whereUuid('verificationId');
        Route::get('staff/verification-requests', [VerificationController::class, 'queue']);
        Route::get('staff/verification-requests/{verificationId}', [VerificationController::class, 'review'])->whereUuid('verificationId');
        Route::get('staff/verification-requests/{verificationId}/document', [VerificationController::class, 'reviewDocument'])->whereUuid('verificationId');
        Route::post('staff/verification-requests/{verificationId}/decisions', [VerificationController::class, 'decide'])->whereUuid('verificationId');
    });
