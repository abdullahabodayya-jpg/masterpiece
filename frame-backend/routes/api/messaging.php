<?php

use App\Http\Controllers\Api\V1\Messaging\ConversationController;
use App\Http\Middleware\MarketplaceApi;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::middleware([MarketplaceApi::class, 'auth:sanctum'])->withoutMiddleware(SubstituteBindings::class)->group(function () {
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::post('conversations', [ConversationController::class, 'direct']);
    Route::get('conversations/{conversationId}', [ConversationController::class, 'show'])->whereUuid('conversationId');
    Route::post('work-items/{workItemId}/conversations', [ConversationController::class, 'project'])->whereUuid('workItemId');
    Route::get('conversations/{conversationId}/messages', [ConversationController::class, 'messages'])->whereUuid('conversationId');
    Route::post('conversations/{conversationId}/messages', [ConversationController::class, 'send'])->whereUuid('conversationId');
    Route::post('conversations/{conversationId}/read-marker', [ConversationController::class, 'read'])->whereUuid('conversationId');
});
