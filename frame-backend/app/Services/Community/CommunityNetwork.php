<?php

namespace App\Services\Community;

use App\Enums\ConnectionStatus;
use App\Models\Connection;
use App\Models\User;
use App\Support\ArchivedPivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

final class CommunityNetwork
{
    public function __construct(private readonly CommunityAccess $access) {}

    public function follow(User $actor, string $otherId, bool $active): void
    {
        $otherId = strtolower($otherId);
        $this->requireOther($actor, $otherId);

        $this->access->withLockedUsers($actor, [$otherId], function (User $viewer) use ($otherId, $active) {
            if ($active) {
                $this->access->available($viewer, $otherId);
                ArchivedPivot::attachOrRestore($viewer->following(), $otherId);
            } else {
                ArchivedPivot::archive($viewer->following(), $otherId);
            }
        });
    }

    public function block(User $actor, string $otherId, bool $active): void
    {
        $otherId = strtolower($otherId);
        $this->requireOther($actor, $otherId);

        $this->access->withLockedUsers($actor, [$otherId], function (User $viewer) use ($otherId, $active) {
            if ($active) {
                ArchivedPivot::attachOrRestore($viewer->blockedUsers(), $otherId);
            } else {
                ArchivedPivot::archive($viewer->blockedUsers(), $otherId);
            }
        });
    }

    public function visibleRelation(BelongsToMany $relation, User $viewer): BelongsToMany
    {
        $this->access->people($relation->getQuery(), $viewer);
        if (! $relation->getParent()->is($viewer)) {
            $this->access->people($relation->getQuery(), $relation->getParent());
        }

        return $relation;
    }

    public function relationship(User $viewer, string $otherId): array
    {
        $other = $this->access->available($viewer, $otherId);
        $connection = Connection::query()
            ->whereIn('requester_id', [$viewer->id, $other->id])
            ->whereIn('recipient_id', [$viewer->id, $other->id])
            ->first();

        return [
            'user_id' => $other->id,
            'following' => $viewer->following()->where('users.id', $other->id)->exists(),
            'followed_by' => $viewer->followers()->where('users.id', $other->id)->exists(),
            'followers_count' => $this->visibleRelation($other->followers(), $viewer)->count(),
            'following_count' => $this->visibleRelation($other->following(), $viewer)->count(),
            'connection' => $connection ? [
                'id' => $connection->id,
                'status' => $connection->status->value,
                'direction' => $connection->requester_id === $viewer->id ? 'outgoing' : 'incoming',
            ] : null,
        ];
    }

    public function requestConnection(User $actor, string $otherId): Connection
    {
        $otherId = strtolower($otherId);
        $this->requireOther($actor, $otherId);

        return $this->access->withLockedUsers($actor, [$otherId], function (User $viewer) use ($otherId) {
            $connection = Connection::between($viewer, $this->access->available($viewer, $otherId));
            abort_unless($connection->wasRecentlyCreated, 409);

            return $connection;
        });
    }

    public function changeConnection(User $actor, string $id, ConnectionStatus $next): Connection
    {
        $pair = $this->ownConnections($actor)->findOrFail($id);

        return $this->access->withLockedUsers(
            $actor,
            [$pair->requester_id, $pair->recipient_id],
            function (User $viewer) use ($id, $next, $pair) {
                $requiredActor = $next === ConnectionStatus::Cancelled ? $pair->requester_id : $pair->recipient_id;
                abort_unless($requiredActor === $viewer->id, 403);
                $other = $pair->requester_id === $viewer->id ? $pair->recipient_id : $pair->requester_id;
                $this->access->available($viewer, $other);

                $connection = $this->ownConnections($viewer)->lockForUpdate()->findOrFail($id);
                abort_unless($connection->status === ConnectionStatus::Pending, 409);
                $connection->status = $next;
                $connection->responded_at = now();
                $connection->save();

                return $connection;
            },
        );
    }

    public function connections(User $viewer, string $direction): Builder
    {
        $query = $this->ownConnections($viewer)
            ->whereHas('requester', fn (Builder $users) => $this->access->people($users, $viewer))
            ->whereHas('recipient', fn (Builder $users) => $this->access->people($users, $viewer))
            ->with(['requester:id,name,display_name,professional_title,role', 'recipient:id,name,display_name,professional_title,role']);

        return match ($direction) {
            'incoming' => $query->where('status', ConnectionStatus::Pending->value)->where('recipient_id', $viewer->id),
            'outgoing' => $query->where('status', ConnectionStatus::Pending->value)->where('requester_id', $viewer->id),
            'accepted' => $query->where('status', ConnectionStatus::Accepted->value),
        };
    }

    private function ownConnections(User $viewer): Builder
    {
        return Connection::query()
            ->where(fn (Builder $pair) => $pair->where('requester_id', $viewer->id)->orWhere('recipient_id', $viewer->id));
    }

    private function requireOther(User $actor, string $otherId): void
    {
        if ($actor->id === $otherId) {
            throw ValidationException::withMessages(['user_id' => 'Choose another user.']);
        }
    }
}
