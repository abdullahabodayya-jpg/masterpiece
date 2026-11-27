<?php

use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Middleware\MarketplaceApi;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware([MarketplaceApi::class.':admin', 'auth:sanctum', 'admin'])
    ->group(function () {

        Route::get('/dashboard', [
            DashboardController::class,
            'index',
        ]);

        Route::patch('/users/{user}/status', [
            UserController::class,
            'updateStatus',
        ]);

        $catalogs = ['content-types', 'editing-tools'];
        Route::get('{catalog}', [CatalogController::class, 'index'])->whereIn('catalog', $catalogs);
        Route::post('{catalog}', [CatalogController::class, 'store'])->whereIn('catalog', $catalogs);
        Route::get('{catalog}/{id}', [CatalogController::class, 'show'])->whereIn('catalog', $catalogs)->whereUuid('id');
        Route::patch('{catalog}/{id}', [CatalogController::class, 'update'])->whereIn('catalog', $catalogs)->whereUuid('id');
        Route::delete('{catalog}/{id}', [CatalogController::class, 'destroy'])->whereIn('catalog', $catalogs)->whereUuid('id');

        $collections = ['jobs', 'applications', 'services', 'service-requests', 'projects', 'work-items',
            'payments', 'refunds', 'payouts', 'posts', 'comments', 'reviews'];
        Route::get('{collection}', [MonitoringController::class, 'index'])->whereIn('collection', $collections);
        Route::get('{collection}/{id}', [MonitoringController::class, 'show'])->whereIn('collection', $collections)->whereUuid('id');
        Route::post('posts/{id}/removal', [ModerationController::class, 'removePost'])->whereUuid('id');
        Route::post('comments/{id}/hide', [ModerationController::class, 'hideComment'])->whereUuid('id');
        Route::post('reviews/{id}/hide', [ModerationController::class, 'hideReview'])->whereUuid('id');

        Route::apiResource('users', UserController::class)
            ->only([
                'index',
                'store',
                'show',
                'update',
            ]);
    });
