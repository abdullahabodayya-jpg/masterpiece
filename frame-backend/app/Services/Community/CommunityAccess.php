<?php

namespace App\Services\Community;

use App\Enums\AccountStatus;
use App\Enums\ConnectionStatus;
use App\Enums\PostStatus;
use App\Enums\PostVisibility;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Query;

final class CommunityAccess
{
    public function actor(User $user): User
    {
        abort_unless(
            ! $user->trashed() && $user->status === AccountStatus::Active
                && in_array($user->role, [UserRole::Creator, UserRole::Editor], true),
            403,
        );

        return $user;
    }

    public function people(Builder $query, ?User $viewer): Builder
    {
        $query->where('users.status', AccountStatus::Active->value)
            ->whereIn('users.role', [UserRole::Creator->value, UserRole::Editor->value]);

        if ($viewer) {
            $query->whereNotExists(function (Query $blocks) use ($viewer) {
                $blocks->selectRaw('1')->from('user_blocks')->whereNull('deleted_at')
                    ->where(fn (Query $pair) => $this->pair($pair, 'blocker_id', 'blockee_id', $viewer->id, 'users.id'));
            });
        }

        return $query;
    }

    public function posts(?User $viewer): Builder
    {
        return Post::query()
            ->where('posts.status', PostStatus::Published->value)
            ->whereHas('author', fn (Builder $users) => $this->people($users, $viewer))
            ->where(function (Builder $posts) use ($viewer) {
                $posts->where('posts.visibility', PostVisibility::Public->value);
                if ($viewer) {
                    $posts->orWhere('posts.author_id', $viewer->id)
                        ->orWhere(function (Builder $connected) use ($viewer) {
                            $connected->where('posts.visibility', PostVisibility::ConnectionsOnly->value)
                                ->whereExists(function (Query $connections) use ($viewer) {
                                    $connections->selectRaw('1')->from('connections')
                                        ->where('status', ConnectionStatus::Accepted->value)
                                        ->where(fn (Query $pair) => $this->pair($pair, 'requester_id', 'recipient_id', $viewer->id, 'posts.author_id'));
                                });
                        });
                }
            });
    }

    public function available(User $viewer, string $otherId): User
    {
        return $this->people(User::query(), $viewer)->findOrFail($otherId);
    }

    public function withLockedUsers(User $actor, array $ids, Closure $work): mixed
    {
        return $actor->getConnection()->transaction(function () use ($actor, $ids, $work) {
            $ids = array_values(array_unique([$actor->id, ...$ids]));
            sort($ids, SORT_STRING);
            $lockedActor = $actor;
            foreach ($ids as $id) {
                $user = $actor->newQuery()->lockForUpdate()->findOrFail($id);
                if ($user->is($actor)) {
                    $lockedActor = $user;
                }
            }

            return $work($this->actor($lockedActor));
        }, 3);
    }

    private function pair(Query $query, string $first, string $second, string $viewerId, string $otherColumn): void
    {
        $query->where(fn (Query $side) => $side->where($first, $viewerId)->whereColumn($second, $otherColumn))
            ->orWhere(fn (Query $side) => $side->where($second, $viewerId)->whereColumn($first, $otherColumn));
    }
}
