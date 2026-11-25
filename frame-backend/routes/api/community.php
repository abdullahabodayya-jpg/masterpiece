<?php

use App\Http\Controllers\Api\V1\Community\CommentController;
use App\Http\Controllers\Api\V1\Community\NetworkController;
use App\Http\Controllers\Api\V1\Community\PostController;
use App\Http\Controllers\Api\V1\Community\ReactionController;
use App\Http\Middleware\CommunityApi;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}';

Route::middleware(CommunityApi::class)->withoutMiddleware(SubstituteBindings::class)->group(function () use ($uuid) {
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{postId}', [PostController::class, 'show'])->whereUuid('postId');
    Route::get('users/{userId}/posts', [PostController::class, 'byAuthor'])->whereUuid('userId');
    Route::get('posts/{postId}/comments', [CommentController::class, 'index'])->whereUuid('postId');

    Route::middleware('auth:sanctum')->group(function () use ($uuid) {
        Route::get('me/following/posts', [PostController::class, 'following']);
        Route::post('posts', [PostController::class, 'store']);

        Route::prefix('posts/{postId}')->where(['postId' => $uuid])->group(function () use ($uuid) {
            Route::patch('/', [PostController::class, 'update']);
            Route::delete('/', [PostController::class, 'destroy']);
            Route::post('comments', [CommentController::class, 'store']);
            Route::put('reaction', [ReactionController::class, 'upsert']);
            Route::delete('reaction', [ReactionController::class, 'destroy']);

            Route::prefix('comments/{commentId}')->where(['commentId' => $uuid])->group(function () {
                Route::patch('/', [CommentController::class, 'update']);
                Route::delete('/', [CommentController::class, 'destroy']);
                Route::put('reaction', [ReactionController::class, 'upsert']);
                Route::delete('reaction', [ReactionController::class, 'destroy']);
            });
        });

        Route::put('me/follows/{userId}', [NetworkController::class, 'follow'])->whereUuid('userId');
        Route::delete('me/follows/{userId}', [NetworkController::class, 'unfollow'])->whereUuid('userId');
        Route::get('users/{userId}/followers', [NetworkController::class, 'followers'])->whereUuid('userId');
        Route::get('users/{userId}/following', [NetworkController::class, 'following'])->whereUuid('userId');
        Route::get('users/{userId}/relationship', [NetworkController::class, 'relationship'])->whereUuid('userId');

        Route::post('connections', [NetworkController::class, 'requestConnection']);
        Route::get('connections/{direction}', [NetworkController::class, 'connections'])
            ->whereIn('direction', ['incoming', 'outgoing', 'accepted']);
        Route::post('connections/{connectionId}/decisions', [NetworkController::class, 'decideConnection'])->whereUuid('connectionId');
        Route::delete('connections/{connectionId}', [NetworkController::class, 'cancelConnection'])->whereUuid('connectionId');

        Route::get('me/blocks', [NetworkController::class, 'blocked']);
        Route::put('me/blocks/{userId}', [NetworkController::class, 'block'])->whereUuid('userId');
        Route::delete('me/blocks/{userId}', [NetworkController::class, 'unblock'])->whereUuid('userId');
    });
});
