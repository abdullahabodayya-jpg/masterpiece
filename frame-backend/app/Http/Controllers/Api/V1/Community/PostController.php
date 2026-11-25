<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\CommunityListRequest;
use App\Http\Requests\Community\PostRequest;
use App\Http\Resources\Community\PostResource;
use App\Models\User;
use App\Services\Community\CommunityAccess;
use App\Services\Community\CommunityContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PostController
{
    public function __construct(
        private readonly CommunityContent $content,
        private readonly CommunityAccess $access,
    ) {}

    public function index(CommunityListRequest $request): JsonResponse
    {
        $query = $this->content->posts($request->user('sanctum'));

        return $request->page($query, PostResource::class, 'posts.created_at', 'posts.id');
    }

    public function byAuthor(CommunityListRequest $request, string $userId): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $this->access->people(User::query(), $viewer)->findOrFail($userId);
        $query = $this->content->posts($viewer)->where('posts.author_id', $userId);

        return $request->page($query, PostResource::class, 'posts.created_at', 'posts.id');
    }

    public function following(CommunityListRequest $request): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $authors = $viewer->following()->select('users.id')->getQuery();
        $query = $this->content->posts($viewer)->whereIn('posts.author_id', $authors);

        return $request->page($query, PostResource::class, 'posts.created_at', 'posts.id');
    }

    public function show(Request $request, string $postId): PostResource
    {
        return new PostResource($this->content->posts($request->user('sanctum'))->findOrFail($postId));
    }

    public function store(PostRequest $request): JsonResponse
    {
        $actor = $request->user('sanctum');
        $post = $this->content->createPost($actor, $request->validated());

        return PostResource::make($this->content->posts($actor)->findOrFail($post->id))->response()->setStatusCode(201);
    }

    public function update(PostRequest $request, string $postId): PostResource
    {
        $this->content->changePost($request->user('sanctum'), $postId, $request->validated());

        return $this->show($request, $postId);
    }

    public function destroy(Request $request, string $postId): Response
    {
        $this->content->changePost($request->user('sanctum'), $postId, null);

        return response()->noContent();
    }
}
