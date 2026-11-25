<?php

use App\Http\Controllers\Api\V1\Profile\PortfolioController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Middleware\MarketplaceApi;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::middleware(MarketplaceApi::class)->withoutMiddleware(SubstituteBindings::class)->group(function () {
    Route::get('editors', [ProfileController::class, 'editors']);
    Route::get('editors/{editorId}', [ProfileController::class, 'editor'])->whereUuid('editorId');
    Route::get('creators/{creatorId}', [ProfileController::class, 'creator'])->whereUuid('creatorId');
    Route::get('content-types', [ProfileController::class, 'contentTypes']);
    Route::get('editing-tools', [ProfileController::class, 'editingTools']);
    Route::get('editors/{editorId}/portfolio-items', [PortfolioController::class, 'index'])->whereUuid('editorId');
    Route::get('editors/{editorId}/portfolio-items/{portfolioId}', [PortfolioController::class, 'show'])->whereUuid(['editorId', 'portfolioId']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me/profile', [ProfileController::class, 'me']);
        Route::patch('me/profile', [ProfileController::class, 'update']);
        Route::get('me/profile/links', [ProfileController::class, 'links']);
        Route::post('me/profile/links', [ProfileController::class, 'createLink']);
        Route::patch('me/profile/links/{linkId}', [ProfileController::class, 'updateLink'])->whereUuid('linkId');
        Route::delete('me/profile/links/{linkId}', [ProfileController::class, 'removeLink'])->whereUuid('linkId');
        Route::get('me/portfolio-items', [PortfolioController::class, 'mine']);
        Route::post('me/portfolio-items', [PortfolioController::class, 'store']);
        Route::patch('me/portfolio-items/{portfolioId}', [PortfolioController::class, 'update'])->whereUuid('portfolioId');
        Route::delete('me/portfolio-items/{portfolioId}', [PortfolioController::class, 'destroy'])->whereUuid('portfolioId');
    });
});
