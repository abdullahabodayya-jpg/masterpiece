<?php

use App\Http\Controllers\Api\V1\Marketplace\ApplicationController;
use App\Http\Controllers\Api\V1\Marketplace\JobController;
use App\Http\Controllers\Api\V1\Marketplace\ServiceController;
use App\Http\Controllers\Api\V1\Marketplace\ServiceIntakeController;
use App\Http\Controllers\Api\V1\Marketplace\WorkController;
use App\Http\Middleware\MarketplaceApi;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::middleware(MarketplaceApi::class)->withoutMiddleware(SubstituteBindings::class)->group(function () {
    Route::get('jobs', [JobController::class, 'index']);
    Route::get('jobs/{workItemId}', [JobController::class, 'show'])->whereUuid('workItemId');

    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{serviceId}', [ServiceController::class, 'show'])->whereUuid('serviceId');
    Route::get('editors/{editorId}/services', [ServiceController::class, 'editor'])->whereUuid('editorId');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me/jobs', [JobController::class, 'mine']);
        Route::post('jobs', [JobController::class, 'store']);
        Route::patch('jobs/{workItemId}', [JobController::class, 'update'])->whereUuid('workItemId');
        Route::post('jobs/{workItemId}/publication', [JobController::class, 'publish'])->whereUuid('workItemId');
        Route::post('jobs/{workItemId}/closure', [JobController::class, 'close'])->whereUuid('workItemId');
        Route::post('jobs/{workItemId}/cancellation', [JobController::class, 'cancel'])->whereUuid('workItemId');
        Route::get('me/services', [ServiceController::class, 'mine']);
        Route::post('services', [ServiceController::class, 'store']);
        Route::patch('services/{serviceId}', [ServiceController::class, 'update'])->whereUuid('serviceId');
        Route::post('services/{serviceId}/publication', [ServiceController::class, 'publish'])->whereUuid('serviceId');
        Route::post('services/{serviceId}/pause', [ServiceController::class, 'pause'])->whereUuid('serviceId');
        Route::delete('services/{serviceId}', [ServiceController::class, 'destroy'])->whereUuid('serviceId');

        Route::post('jobs/{workItemId}/applications', [ApplicationController::class, 'store'])->whereUuid('workItemId');
        Route::patch('applications/{applicationId}', [ApplicationController::class, 'update'])->whereUuid('applicationId');
        Route::post('applications/{applicationId}/shortlisting', [ApplicationController::class, 'shortlist'])->whereUuid('applicationId');
        Route::post('applications/{applicationId}/selection', [ApplicationController::class, 'select'])->whereUuid('applicationId');
        Route::get('jobs/{workItemId}/applications', [ApplicationController::class, 'job'])->whereUuid('workItemId');
        Route::get('me/applications', [ApplicationController::class, 'mine']);
        Route::get('applications/{applicationId}', [ApplicationController::class, 'show'])->whereUuid('applicationId');
        Route::post('applications/{applicationId}/withdrawal', [ApplicationController::class, 'withdraw'])->whereUuid('applicationId');
        Route::post('applications/{applicationId}/rejection', [ApplicationController::class, 'reject'])->whereUuid('applicationId');

        Route::get('me/service-requests', [ServiceIntakeController::class, 'index']);
        Route::post('services/{serviceId}/requests', [ServiceIntakeController::class, 'store'])->whereUuid('serviceId');
        Route::post('work-items/{workItemId}/service-selection', [ServiceIntakeController::class, 'accept'])->whereUuid('workItemId');
        Route::post('work-items/{workItemId}/service-rejection', [ServiceIntakeController::class, 'reject'])->whereUuid('workItemId');
        Route::post('work-items/{workItemId}/service-cancellation', [ServiceIntakeController::class, 'cancel'])->whereUuid('workItemId');

        Route::get('work-items', [WorkController::class, 'index']);
        Route::get('work-items/{workItemId}', [WorkController::class, 'show'])->whereUuid('workItemId');
    });
});
