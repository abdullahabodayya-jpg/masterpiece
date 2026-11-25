<?php

namespace App\Services\Work;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Marketplace\MarketplaceAccess;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Database\Eloquent\Builder;

final class WorkAccess
{
    public function __construct(private readonly MarketplaceAccess $accounts) {}

    public function participant(User $actor): Builder
    {
        $this->accounts->actor($actor);
        $column = $actor->role === UserRole::Creator ? 'creator_id' : 'editor_id';

        return WorkItem::query()->where($column, $actor->id);
    }

    public function project(User $actor): Builder
    {
        return $this->participant($actor)->whereNotNull('editor_id');
    }

    public function write(User $actor, string $workId, UserRole $role, Closure $change): mixed
    {
        $this->accounts->actor($actor, $role);
        $candidate = $this->project($actor)->findOrFail($workId);

        return $this->accounts->lockAccounts(
            $actor,
            [$candidate->creator_id, $candidate->editor_id],
            function (User $current, array $accounts) use ($workId, $role, $change) {
                $this->accounts->actor($current, $role);
                $work = $this->project($current)->lockForUpdate()->findOrFail($workId);
                abort_unless(isset($accounts[$work->creator_id], $accounts[$work->editor_id]), 409);
                abort_if($work->creator_id === $work->editor_id, 409);
                $this->accounts->actor($accounts[$work->creator_id], UserRole::Creator);
                $this->accounts->actor($accounts[$work->editor_id], UserRole::Editor);
                $this->requireDeliveryState($work);

                return $change($work, $current);
            },
        );
    }

    public function requireDeliveryState(WorkItem $work): void
    {
        // Only consume existing active work and payment evidence. This module cannot activate a project.
        abort_unless(
            in_array($work->status, [WorkStatus::InProgress, WorkStatus::InReview], true)
            && $work->agreed_at !== null && $work->started_at !== null && $work->agreed_at->lte($work->started_at)
            && $work->started_at->lte(now()) && $work->completed_at === null
            && $work->agreed_price !== null && BigDecimal::of($work->agreed_price)->isPositive(),
            409,
        );

        abort_unless($work->payments()
            ->where('status', PaymentStatus::Succeeded->value)
            ->where('creator_id', $work->creator_id)
            ->where('currency', $work->currency)
            ->where('amount', $work->agreed_price)
            ->whereNotNull('paid_at')->where('paid_at', '<=', $work->started_at)
            ->where('provider_name', '<>', '')->where('provider_payment_id', '<>', '')
            ->exists(), 409);
    }
}
