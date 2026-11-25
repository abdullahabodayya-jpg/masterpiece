<?php

namespace App\Services\Marketplace;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Query;

final class MarketplaceAccess
{
    public function actor(User $user, ?UserRole $role = null): User
    {
        abort_unless(
            ! $user->trashed() && $user->status === AccountStatus::Active
                && in_array($user->role, [UserRole::Creator, UserRole::Editor], true)
                && ($role === null || $user->role === $role),
            403,
        );

        return $user;
    }

    public function people(Builder $users, ?User $viewer, UserRole $role): Builder
    {
        $users->where('users.status', AccountStatus::Active->value)->where('users.role', $role->value);

        return $this->unblocked($users, $viewer);
    }

    public function unblocked(Builder $users, ?User $viewer): Builder
    {
        if ($viewer) {
            $users->whereNotExists(function (Query $blocks) use ($viewer) {
                $blocks->selectRaw('1')->from('user_blocks')->whereNull('deleted_at')
                    ->where(function (Query $pair) use ($viewer) {
                        $pair->where(fn (Query $side) => $side->where('blocker_id', $viewer->id)->whereColumn('blockee_id', 'users.id'))
                            ->orWhere(fn (Query $side) => $side->where('blockee_id', $viewer->id)->whereColumn('blocker_id', 'users.id'));
                    });
            });
        }

        return $users;
    }

    public function latestDecision(Query $requests): Query
    {
        return $requests->from('editor_verification_requests')
            ->whereNotNull('decided_at')
            ->whereIn('status', [VerificationStatus::Approved->value, VerificationStatus::Rejected->value, VerificationStatus::NeedsChanges->value])
            ->orderByDesc('decided_at')->orderByDesc('id');
    }

    public function recordedApproval(Builder $users): Builder
    {
        return $users->where(
            fn (Query $query) => $this->latestDecision($query)->select('status')->whereColumn('editor_id', 'users.id')->limit(1),
            VerificationStatus::Approved->value,
        );
    }

    public function eligibleEditors(Builder $users, ?User $viewer, ?string $specialty = null): Builder
    {
        // In a service catalog the specialty comes from the outer listing row.
        return $this->recordedApproval($this->people($users, $viewer, UserRole::Editor))
            ->whereExists(function (Query $pivot) use ($specialty) {
                $pivot->selectRaw('1')->from('user_content_types')
                    ->join('content_types', 'content_types.id', '=', 'user_content_types.content_type_id')
                    ->whereColumn('user_content_types.user_id', 'users.id')
                    ->whereNull('user_content_types.deleted_at')->whereNull('content_types.deleted_at');
                if ($specialty === null) {
                    $pivot->whereColumn('content_types.id', 'editor_services.content_type_id');
                } else {
                    $pivot->where('content_types.id', $specialty);
                }
            });
    }

    public function requireEditor(User $editor, string $specialty, ?User $counterpart = null): void
    {
        if ($counterpart) {
            abort_unless($this->unblocked($editor->newQuery(), $counterpart)->whereKey($editor->id)->exists(), 404);
        }
        abort_unless($this->eligibleEditors($editor->newQuery(), null, $specialty)->whereKey($editor->id)->exists(), 403);
    }

    public function lockAccounts(User $actor, array $ids, Closure $work): mixed
    {
        return $actor->getConnection()->transaction(function () use ($actor, $ids, $work) {
            $ids = array_unique([$actor->id, ...$ids]);
            sort($ids, SORT_STRING);
            $locked = [];
            foreach ($ids as $id) {
                $locked[$id] = $actor->newQuery()->lockForUpdate()->findOrFail($id);
            }

            return $work($locked[$actor->id], $locked);
        }, 3);
    }

    public function write(User $actor, UserRole $role, Closure $work): mixed
    {
        return $this->lockAccounts($actor, [], fn (User $current) => $work($this->actor($current, $role)));
    }
}
