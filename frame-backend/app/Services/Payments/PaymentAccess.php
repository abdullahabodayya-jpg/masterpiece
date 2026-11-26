<?php

namespace App\Services\Payments;

use App\Enums\AccountStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Marketplace\MarketplaceAccess;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class PaymentAccess
{
    public function __construct(private readonly MarketplaceAccess $accounts) {}

    public function participant(User $actor, string $id): WorkItem
    {
        $this->accounts->actor($actor);
        $field = $actor->role === UserRole::Creator ? 'creator_id' : 'editor_id';

        return WorkItem::query()->where($field, $actor->id)->whereNotNull('editor_id')->findOrFail($id);
    }

    public function staff(User $actor): void
    {
        abort_unless(! $actor->trashed() && $actor->role === UserRole::Admin && $actor->status === AccountStatus::Active, 403);
    }

    public function locked(WorkItem $candidate, ?User $actor, Closure $action): mixed
    {
        return $candidate->getConnection()->transaction(function () use ($candidate, $actor, $action) {
            $ids = array_values(array_unique(array_filter([$candidate->creator_id, $candidate->editor_id, $actor?->id])));
            sort($ids, SORT_STRING);
            $users = [];
            foreach ($ids as $id) {
                $users[$id] = User::withTrashed()->lockForUpdate()->findOrFail($id);
            }
            $work = WorkItem::query()->lockForUpdate()->findOrFail($candidate->id);
            abort_unless($work->creator_id === $candidate->creator_id && $work->editor_id === $candidate->editor_id
                && $work->editor_id !== null && $work->creator_id !== $work->editor_id, 409);

            return $action($work, $users, $actor ? $users[$actor->id] : null);
        }, 3);
    }

    public function verifyActor(WorkItem $work, User $actor, UserRole $role): void
    {
        $this->accounts->actor($actor, $role);
        abort_unless($actor->id === ($role === UserRole::Creator ? $work->creator_id : $work->editor_id), 404);
    }

    public function activePair(WorkItem $work, array $users): void
    {
        $this->verifyActor($work, $users[$work->creator_id], UserRole::Creator);
        $this->verifyActor($work, $users[$work->editor_id], UserRole::Editor);
    }

    public static function refundCutoff(): Carbon
    {
        // Use the existing conservative 20-hour creation window, anchored to the reservation.
        return now()->subHours(20);
    }

    public static function blockingRefunds(Builder $refunds): Builder
    {
        return $refunds->where(function (Builder $blocking) {
            $blocking->whereIn('status', [RefundStatus::Processing->value, RefundStatus::Succeeded->value])
                ->orWhere(function (Builder $pending) {
                    $pending->where('status', RefundStatus::Pending->value)
                        ->where(fn (Builder $decision) => $decision->whereNotNull('provider_refund_id')
                            ->orWhere('created_at', '>', self::refundCutoff()));
                });
        });
    }

    public function funding(WorkItem $work): Builder
    {
        $now = now();
        $confirmedBy = $work->started_at !== null && $work->started_at->lte($now) ? $work->started_at : $now;

        return Payment::query()->where('work_item_id', $work->id)->where('creator_id', $work->creator_id)
            ->where('provider_name', 'stripe_sandbox')->whereRaw('CAST(provider_payment_id AS BINARY) REGEXP ?', ['^cs_test_[A-Za-z0-9]+$'])
            ->where('status', PaymentStatus::Succeeded->value)->where('currency', 'USD')->where('currency', $work->currency)
            ->where('amount', $work->agreed_price)->whereNotNull('paid_at')->where('paid_at', '<=', $confirmedBy)
            ->whereDoesntHave('workItem.payments.refunds', self::blockingRefunds(...));
    }
}
