<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\ReactionRequest;
use App\Http\Resources\Community\ReactionResource;
use App\Services\Community\CommunityContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ReactionController
{
    public function __construct(private readonly CommunityContent $content) {}

    public function upsert(ReactionRequest $request, string $postId, ?string $commentId = null): JsonResponse
    {
        return ReactionResource::make($this->content->react(
            $request->user('sanctum'),
            $postId,
            $commentId,
            $request->validated('reaction_type'),
        ))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $postId, ?string $commentId = null): Response
    {
        $this->content->react($request->user('sanctum'), $postId, $commentId, null);

        return response()->noContent();
    }
}
