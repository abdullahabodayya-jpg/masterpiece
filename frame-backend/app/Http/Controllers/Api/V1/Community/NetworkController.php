<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Enums\ConnectionStatus;
use App\Http\Requests\Community\CommunityListRequest;
use App\Http\Requests\Community\ConnectionRequest;
use App\Http\Resources\Community\ConnectionResource;
use App\Http\Resources\Community\PersonResource;
use App\Services\Community\CommunityAccess;
use App\Services\Community\CommunityNetwork;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class NetworkController
{
    public function __construct(
        private readonly CommunityNetwork $network,
        private readonly CommunityAccess $access,
    ) {}

    public function follow(Request $request, string $userId): Response
    {
        $this->network->follow($request->user('sanctum'), $userId, true);

        return response()->noContent();
    }

    public function unfollow(Request $request, string $userId): Response
    {
        $this->network->follow($request->user('sanctum'), $userId, false);

        return response()->noContent();
    }

    public function block(Request $request, string $userId): Response
    {
        $this->network->block($request->user('sanctum'), $userId, true);

        return response()->noContent();
    }

    public function unblock(Request $request, string $userId): Response
    {
        $this->network->block($request->user('sanctum'), $userId, false);

        return response()->noContent();
    }

    public function blocked(CommunityListRequest $request): JsonResponse
    {
        $users = $request->user('sanctum')->blockedUsers()->select($this->personColumns());

        return $request->page($users, PersonResource::class, 'user_blocks.created_at', 'users.id');
    }

    public function followers(CommunityListRequest $request, string $userId): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $user = $this->access->available($viewer, $userId);
        $users = $this->network->visibleRelation($user->followers(), $viewer)->select($this->personColumns());

        return $request->page($users, PersonResource::class, 'user_follows.created_at', 'users.id');
    }

    public function following(CommunityListRequest $request, string $userId): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $user = $this->access->available($viewer, $userId);
        $users = $this->network->visibleRelation($user->following(), $viewer)->select($this->personColumns());

        return $request->page($users, PersonResource::class, 'user_follows.created_at', 'users.id');
    }

    public function relationship(Request $request, string $userId): JsonResponse
    {
        return response()->json(['data' => $this->network->relationship($request->user('sanctum'), $userId)]);
    }

    public function connections(CommunityListRequest $request, string $direction): JsonResponse
    {
        $query = $this->network->connections($request->user('sanctum'), $direction);

        return $request->page($query, ConnectionResource::class, 'connections.created_at', 'connections.id');
    }

    public function requestConnection(ConnectionRequest $request): JsonResponse
    {
        $connection = $this->network->requestConnection($request->user('sanctum'), $request->validated('recipient_id'));

        return ConnectionResource::make($connection)->response()->setStatusCode(201);
    }

    public function decideConnection(ConnectionRequest $request, string $connectionId): ConnectionResource
    {
        $next = $request->validated('decision') === 'accept' ? ConnectionStatus::Accepted : ConnectionStatus::Rejected;

        return new ConnectionResource($this->network->changeConnection($request->user('sanctum'), $connectionId, $next));
    }

    public function cancelConnection(Request $request, string $connectionId): Response
    {
        $this->network->changeConnection($request->user('sanctum'), $connectionId, ConnectionStatus::Cancelled);

        return response()->noContent();
    }

    private function personColumns(): array
    {
        return ['users.id', 'users.name', 'users.display_name', 'users.professional_title', 'users.role'];
    }
}
