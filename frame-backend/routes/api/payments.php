<?php

use App\Http\Controllers\Api\V1\Payments\PaymentController;
use App\Http\Middleware\MarketplaceApi;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::middleware(MarketplaceApi::class.':verification')->withoutMiddleware(SubstituteBindings::class)->group(function () {
    Route::post('webhooks/stripe', [PaymentController::class, 'webhook'])
        ->withoutMiddleware(EnsureFrontendRequestsAreStateful::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('work-items/{workItemId}/agreement', [PaymentController::class, 'agreement'])->whereUuid('workItemId');
        Route::post('work-items/{workItemId}/agreement-confirmation', [PaymentController::class, 'confirm'])->whereUuid('workItemId');
        Route::post('work-items/{workItemId}/payment-attempts', [PaymentController::class, 'checkout'])->whereUuid('workItemId');
        Route::get('work-items/{workItemId}/payment-summary', [PaymentController::class, 'readiness'])->whereUuid('workItemId');
        Route::post('work-items/{workItemId}/start', [PaymentController::class, 'start'])->whereUuid('workItemId');
        Route::post('work-items/{workItemId}/completion', [PaymentController::class, 'complete'])->whereUuid('workItemId');
        Route::get('payments/{paymentId}', [PaymentController::class, 'payment'])->whereUuid('paymentId');
        Route::post('payments/{paymentId}/refunds', [PaymentController::class, 'requestRefund'])->whereUuid('paymentId');
        Route::get('me/payments', [PaymentController::class, 'payments']);
        Route::get('me/earnings', [PaymentController::class, 'earnings']);
        Route::get('me/payouts', [PaymentController::class, 'payouts']);
        Route::get('staff/refunds', [PaymentController::class, 'refundQueue']);
        Route::post('staff/refunds/{refundId}/approval', [PaymentController::class, 'approve'])->whereUuid('refundId');
        Route::post('staff/refunds/{refundId}/rejection', [PaymentController::class, 'rejectRefund'])->whereUuid('refundId');
        Route::post('staff/payouts/{payoutId}/simulation', [PaymentController::class, 'simulate'])->whereUuid('payoutId');
        Route::get('staff/financial-summary', [PaymentController::class, 'staffSummary']);
    });
});
