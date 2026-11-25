<?php

use App\Http\Controllers\Api\V1\Work\DeliveryController;
use App\Http\Middleware\MarketplaceApi;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::middleware([MarketplaceApi::class, 'auth:sanctum'])->withoutMiddleware(SubstituteBindings::class)->group(function () {
    Route::get('work-items/{workItemId}/progress', [DeliveryController::class, 'progress'])->whereUuid('workItemId');
    Route::get('work-items/{workItemId}/deliverables', [DeliveryController::class, 'index'])->whereUuid('workItemId');
    Route::post('work-items/{workItemId}/deliverables', [DeliveryController::class, 'store'])->whereUuid('workItemId');

    Route::get('deliverables/{deliverableId}', [DeliveryController::class, 'show'])->whereUuid('deliverableId');
    Route::patch('deliverables/{deliverableId}', [DeliveryController::class, 'update'])->whereUuid('deliverableId');
    Route::post('deliverables/{deliverableId}/submission', [DeliveryController::class, 'submit'])->whereUuid('deliverableId');
    Route::get('deliverables/{deliverableId}/document', [DeliveryController::class, 'document'])->whereUuid('deliverableId');
    Route::post('deliverables/{deliverableId}/review', [DeliveryController::class, 'review'])->whereUuid('deliverableId');
    Route::post('deliverables/{deliverableId}/acceptance', [DeliveryController::class, 'accept'])->whereUuid('deliverableId');
    Route::get('deliverables/{deliverableId}/revisions', [DeliveryController::class, 'revisions'])->whereUuid('deliverableId');
    Route::post('deliverables/{deliverableId}/revisions', [DeliveryController::class, 'requestRevision'])->whereUuid('deliverableId');

    Route::get('revisions/{revisionId}', [DeliveryController::class, 'revision'])->whereUuid('revisionId');
    Route::post('revisions/{revisionId}/start', [DeliveryController::class, 'begin'])->whereUuid('revisionId');
    Route::post('revisions/{revisionId}/cancellation', [DeliveryController::class, 'cancel'])->whereUuid('revisionId');
});
