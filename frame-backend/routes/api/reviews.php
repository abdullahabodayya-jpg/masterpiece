<?php

use App\Http\Controllers\Api\V1\Reviews\ReviewController;
use App\Http\Middleware\MarketplaceApi;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::middleware(MarketplaceApi::class)->withoutMiddleware(SubstituteBindings::class)->group(function () {
    Route::get('editors/{editorId}/reviews', [ReviewController::class, 'editor'])
        ->whereUuid('editorId')->name('reviews.public.editor');
    Route::get('creators/{creatorId}/reviews', [ReviewController::class, 'creator'])
        ->whereUuid('creatorId')->name('reviews.public.creator');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('work-items/{workItemId}/reviews', [ReviewController::class, 'project'])->whereUuid('workItemId');
        Route::post('work-items/{workItemId}/reviews', [ReviewController::class, 'store'])->whereUuid('workItemId');
        Route::get('work-items/{workItemId}/review-status', [ReviewController::class, 'status'])->whereUuid('workItemId');
        Route::get('me/reviews', [ReviewController::class, 'mine']);
    });
});
