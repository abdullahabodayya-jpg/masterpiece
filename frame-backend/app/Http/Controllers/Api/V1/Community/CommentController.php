<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\CommentRequest;
use App\Http\Requests\Community\CommunityListRequest;
use App\Http\Resources\Community\CommentResource;
use App\Services\Community\CommunityContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CommentController
{
    public function __construct(private readonly CommunityContent $content) {}

    public function index(CommunityListRequest $request, string $postId): JsonResponse
    {
        $comments = $this->content->comments($postId, $request->user('sanctum'));

        return $request->page($comments, CommentResource::class, 'comments.created_at', 'comments.id');
    }

    public function store(CommentRequest $request, string $postId): JsonResponse
    {
        $actor = $request->user('sanctum');
        $comment = $this->content->createComment($actor, $postId, $request->validated());

        return CommentResource::make($this->content->comments($postId, $actor)->findOrFail($comment->id))
            ->response()->setStatusCode(201);
    }

    public function update(CommentRequest $request, string $postId, string $commentId): CommentResource
    {
        $actor = $request->user('sanctum');
        $this->content->changeComment($actor, $postId, $commentId, $request->validated());

        return new CommentResource($this->content->comments($postId, $actor)->findOrFail($commentId));
    }

    public function destroy(Request $request, string $postId, string $commentId): Response
    {
        $this->content->changeComment($request->user('sanctum'), $postId, $commentId, null);

        return response()->noContent();
    }
}
