<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Str;

final class Refunds
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly Checkout $checkout,
        private readonly StripeSandbox $stripe,
    ) {}

    public function request(User $actor, string $paymentId, string $reason): Refund
    {
        $candidate = Payment::query()->where('creator_id', $actor->id)->findOrFail($paymentId);
        [$refund, $dispatch] = $this->access->locked($candidate->workItem, $actor, function (WorkItem $work, array $users, User $current) use ($candidate, $reason) {
            $this->access->verifyActor($work, $current, UserRole::Creator);
            $payment = $work->payments()->lockForUpdate()->findOrFail($candidate->id);
            $this->eligible($work, $payment);
            $existing = PaymentAccess::blockingRefunds($payment->refunds()->getQuery())->lockForUpdate()->first();
            abort_if($existing?->status === RefundStatus::Succeeded, 409);
            $dispatch = $work->started_at === null;
            if ($dispatch) {
                $this->stripe->requireSandbox();
            }
            if ($existing) {
                return [$existing, $dispatch && $existing->status === RefundStatus::Processing];
            }

            $refund = $payment->refunds()->make();
            $refund->forceFill([
                'requested_by_user_id' => $current->id, 'amount' => $payment->amount, 'currency' => $payment->currency,
                'reason' => $reason, 'status' => $dispatch ? RefundStatus::Processing : RefundStatus::Pending,
            ])->save();

            return [$refund, $dispatch];
        });

        return $dispatch ? $this->dispatch($refund) : $refund;
    }

    public function approve(User $admin, string $id): Refund
    {
        $this->access->staff($admin);
        $this->stripe->requireSandbox();
        $candidate = Refund::query()->with('payment.workItem')->findOrFail($id);
        $refund = $this->access->locked($candidate->payment->workItem, $admin, function (WorkItem $work, array $users, User $current) use ($candidate) {
            $this->access->staff($current);
            $payment = $work->payments()->lockForUpdate()->findOrFail($candidate->payment_id);
            $this->eligible($work, $payment);
            $refund = $payment->refunds()->lockForUpdate()->findOrFail($candidate->id);
            if ($refund->status === RefundStatus::Pending) {
                abort_unless($refund->provider_refund_id === null && $refund->created_at->gt(PaymentAccess::refundCutoff()), 409);
                $refund->status = RefundStatus::Processing;
                $refund->save();
            } else {
                // A provider reference allows retrieval even after the safe creation retry window.
                abort_unless($refund->status === RefundStatus::Processing
                    && ($refund->provider_refund_id !== null || $refund->created_at->gt(PaymentAccess::refundCutoff())), 409);
            }

            return $refund;
        });

        return $this->dispatch($refund);
    }

    public function reject(User $admin, string $id): Refund
    {
        $this->access->staff($admin);
        $candidate = Refund::query()->with('payment.workItem')->findOrFail($id);

        return $this->access->locked($candidate->payment->workItem, $admin, function (WorkItem $work, array $users, User $current) use ($candidate) {
            $this->access->staff($current);
            $payment = $work->payments()->lockForUpdate()->findOrFail($candidate->payment_id);
            abort_unless($payment->provider_name === 'stripe_sandbox', 409);
            $refund = $payment->refunds()->lockForUpdate()->findOrFail($candidate->id);
            abort_unless($refund->status === RefundStatus::Pending && $refund->provider_refund_id === null, 409);
            $refund->status = RefundStatus::Cancelled;
            $refund->save();

            return $refund;
        });
    }

    public function event(array $object): void
    {
        $reference = $object['metadata']['frame_refund_id'] ?? null;
        if (! is_string($reference) || ! Str::isUuid($reference)) {
            return;
        }
        $candidate = Refund::query()->with('payment')->findOrFail($reference);
        $id = $object['id'] ?? null;
        abort_unless(is_string($id) && str_starts_with($id, 're_')
            && ($candidate->provider_refund_id === null || $candidate->provider_refund_id === $id), 409);
        $refund = $this->stripe->refund($id);
        abort_unless(($refund['id'] ?? null) === $id, 409);
        $this->observe($candidate, $refund, $this->intent($candidate->payment));
    }

    private function dispatch(Refund $refund): Refund
    {
        abort_unless($refund->provider_refund_id !== null || $refund->created_at->gt(PaymentAccess::refundCutoff()), 409);
        $intent = $this->intent($refund->payment);
        $known = $refund->provider_refund_id !== null
            ? $this->stripe->refund($refund->provider_refund_id)
            : $this->stripe->createRefund($refund, $intent);

        return $this->observe($refund, $known, $intent);
    }

    private function intent(Payment $payment): string
    {
        abort_unless($payment->provider_payment_id !== null, 409);
        $session = $this->stripe->session($payment->provider_payment_id);
        $this->checkout->verifySession($payment, $session);
        $intent = $session['payment_intent'] ?? null;
        abort_unless(($session['payment_status'] ?? null) === 'paid' && is_string($intent['id'] ?? null)
            && str_starts_with($intent['id'], 'pi_') && ($intent['livemode'] ?? null) === false
            && ($intent['status'] ?? null) === 'succeeded', 409);

        return $intent['id'];
    }

    private function observe(Refund $candidate, array $evidence, string $intent): Refund
    {
        return $this->access->locked($candidate->payment->workItem, null, function (WorkItem $work) use ($candidate, $evidence, $intent) {
            $payment = $work->payments()->lockForUpdate()->findOrFail($candidate->payment_id);
            $refund = $payment->refunds()->lockForUpdate()->findOrFail($candidate->id);
            abort_unless(is_string($evidence['id'] ?? null) && str_starts_with($evidence['id'], 're_')
                && ($evidence['livemode'] ?? false) === false
                && ($refund->provider_refund_id === null || $refund->provider_refund_id === $evidence['id'])
                && ($evidence['payment_intent'] ?? null) === $intent && ($evidence['currency'] ?? null) === 'usd'
                && ($evidence['amount'] ?? null) === CommissionCalculator::minor($refund->amount)
                && ($evidence['metadata']['frame_refund_id'] ?? null) === $refund->id
                && ($evidence['metadata']['frame_payment_id'] ?? null) === $payment->id
                && $refund->amount === $payment->amount && $refund->currency === $payment->currency, 409);

            $observed = match ($evidence['status'] ?? null) {
                'succeeded' => RefundStatus::Succeeded,
                'failed' => RefundStatus::Failed,
                'canceled' => RefundStatus::Cancelled,
                'pending', 'requires_action' => RefundStatus::Processing,
                default => abort(409),
            };
            if (in_array($refund->status, [RefundStatus::Succeeded, RefundStatus::Failed, RefundStatus::Cancelled], true)) {
                abort_unless($refund->status === $observed && $refund->provider_refund_id === $evidence['id'], 409);

                return $refund;
            }
            $this->eligible($work, $payment);
            abort_unless($refund->status === RefundStatus::Processing, 409);
            $refund->provider_refund_id = $evidence['id'];
            $refund->status = $observed;
            if ($observed === RefundStatus::Succeeded) {
                $refund->completed_at = now();
                $work->status = WorkStatus::Cancelled;
                $work->save();
            }
            $refund->save();

            return $refund;
        });
    }

    private function eligible(WorkItem $work, Payment $payment): void
    {
        abort_unless(in_array($work->status, [WorkStatus::PendingAgreement, WorkStatus::InProgress, WorkStatus::InReview], true)
            && $work->completed_at === null && ! $work->payout()->exists()
            && $payment->status === PaymentStatus::Succeeded && $payment->provider_name === 'stripe_sandbox'
            && $payment->paid_at !== null && $payment->work_item_id === $work->id && $payment->creator_id === $work->creator_id
            && $payment->currency === 'USD' && $payment->currency === $work->currency && $payment->amount === $work->agreed_price, 409);
    }
}
