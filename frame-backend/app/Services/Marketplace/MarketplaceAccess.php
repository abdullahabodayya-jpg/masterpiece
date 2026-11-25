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

    public function recordedApproval(Builder $users): Builder
    {
        // A reviewed request is account-level evidence. It cannot authorize specialty-specific work.
        return $users->where(
            fn (Query $query) => $query->from('editor_verification_requests')->select('status')
                ->whereColumn('editor_id', 'users.id')->whereNotNull('decided_at')
                ->orderByDesc('decided_at')->orderByDesc('id')->limit(1),
            VerificationStatus::Approved->value,
        );
    }

    public function write(User $actor, UserRole $role, Closure $work): mixed
    {
        return $actor->getConnection()->transaction(function () use ($actor, $role, $work) {
            $current = $actor->newQuery()->lockForUpdate()->findOrFail($actor->id);

            return $work($this->actor($current, $role));
        }, 3);
    }
}
