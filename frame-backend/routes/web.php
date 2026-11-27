<?php

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/v1/csrf-cookie', [
    CsrfCookieController::class,
    'show',
]);

require __DIR__.'/web/admin.php';
