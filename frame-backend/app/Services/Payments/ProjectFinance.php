<?php

namespace App\Services\Payments;

use App\Enums\DeliverableStatus;
use App\Enums\PayoutStatus;
use App\Enums\RevisionStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Models\DeliverableRevision;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Models\WorkItem;

final class ProjectFinance
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly Checkout $checkout,
    ) {}

    public function start(User $actor, string $id): WorkItem
    {
        $candidate = $this->access->participant($actor, $id);
        $this->access->verifyActor($candidate, $actor, UserRole::Editor);
        abort_unless($candidate->status === WorkStatus::PendingAgreement && $candidate->agreed_at !== null && $candidate->started_at === null, 409);
        $evidence = $this->verifiedFunding($candidate);

        return $this->access->locked($candidate, $actor, function (WorkItem $work, array $users, User $current) use ($evidence) {
            $this->access->verifyActor($work, $current, UserRole::Editor);
            $this->access->activePair($work, $users);
            abort_unless($work->status === WorkStatus::PendingAgreement && $work->agreed_at !== null
                && $work->agreed_at->lte(now()) && $work->started_at === null && $work->completed_at === null, 409);
            $matching = $this->access->funding($work)->lockForUpdate()->get();
            abort_unless($matching->count() === 1 && $this->sameEvidence($matching->sole(), $evidence), 409);
            $work->started_at = now();
            $work->status = WorkStatus::InProgress;
            $work->save();

            return $work;
        });
    }

    public function complete(User $actor, string $id): WorkItem
    {
        $candidate = $this->access->participant($actor, $id);
        $this->access->verifyActor($candidate, $actor, UserRole::Creator);
        abort_unless(in_array($candidate->status, [WorkStatus::InProgress, WorkStatus::InReview], true), 409);
        $evidence = $this->verifiedFunding($candidate);

        return $this->access->locked($candidate, $actor, function (WorkItem $work, array $users, User $current) use ($evidence) {
            $this->access->verifyActor($work, $current, UserRole::Creator);
            $this->access->activePair($work, $users);
            abort_unless(in_array($work->status, [WorkStatus::InProgress, WorkStatus::InReview], true)
                && $work->agreed_at !== null && $work->started_at !== null && $work->agreed_at->lte($work->started_at)
                && $work->started_at->lte(now()) && $work->completed_at === null, 409);
            $payments = $this->access->funding($work)->lockForUpdate()->get();
            abort_unless($payments->count() === 1 && $this->sameEvidence($payments->sole(), $evidence) && ! $work->payout()->exists(), 409);
            $payment = $payments->sole();
            abort_unless($work->deliverables()->exists()
                && ! $work->deliverables()->where('status', '<>', DeliverableStatus::Accepted->value)->exists()
                && ! DeliverableRevision::query()->whereIn('deliverable_id', $work->deliverables()->select('id')->getQuery())
                    ->whereIn('status', [RevisionStatus::Requested->value, RevisionStatus::InProgress->value])->exists(), 409);

            $payout = $work->payout()->make();
            $payout->forceFill([
                'payment_id' => $payment->id, 'editor_id' => $work->editor_id,
                'amount' => $payment->editor_net_amount, 'currency' => $payment->currency, 'status' => PayoutStatus::Pending,
            ])->save();
            $work->status = WorkStatus::Completed;
            $work->completed_at = now();
            $work->save();

            return $work;
        });
    }

    public function simulate(User $admin, string $id): Payout
    {
        $this->access->staff($admin);
        $candidate = Payout::query()->with('workItem')->findOrFail($id);

        abort_unless($candidate->status === PayoutStatus::Pending && $candidate->workItem->status === WorkStatus::Completed, 409);
        $evidence = $this->verifiedFunding($candidate->workItem);

        return $this->access->locked($candidate->workItem, $admin, function (WorkItem $work, array $users, User $current) use ($candidate, $evidence) {
            $this->access->staff($current);
            $payments = $this->access->funding($work)->lockForUpdate()->get();
            abort_unless($payments->count() === 1 && $this->sameEvidence($payments->sole(), $evidence)
                && $evidence->id === $candidate->payment_id, 409);
            $payment = $payments->sole();
            $payout = $work->payout()->lockForUpdate()->findOrFail($candidate->id);
            abort_unless($work->status === WorkStatus::Completed && $work->completed_at !== null
                && $payout->status === PayoutStatus::Pending && $payout->editor_id === $work->editor_id
                && $payout->amount === $payment->editor_net_amount && $payout->currency === $payment->currency
                && $payout->provider_payout_id === null, 409);
            $payout->status = PayoutStatus::Paid;
            $payout->paid_at = now();
            $payout->save();

            return $payout;
        });
    }

    private function verifiedFunding(WorkItem $work): Payment
    {
        $matches = $this->access->funding($work)->get();
        abort_unless($matches->count() === 1, 409);
        $evidence = $matches->sole();
        $this->checkout->revalidateCollection($evidence);

        return $evidence;
    }

    private function sameEvidence(Payment $payment, Payment $evidence): bool
    {
        return $payment->id === $evidence->id && $payment->provider_payment_id === $evidence->provider_payment_id
            && $payment->amount === $evidence->amount && $payment->currency === $evidence->currency;
    }
}
