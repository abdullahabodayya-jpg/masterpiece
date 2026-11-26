<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Str;

final class Checkout
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly CommissionCalculator $commission,
        private readonly StripeSandbox $stripe,
    ) {}

    public function open(User $actor, string $workId): array
    {
        $this->stripe->requireSandbox();
        $payment = $this->access->locked($this->access->participant($actor, $workId), $actor,
            function (WorkItem $work, array $users, User $current) {
                $this->access->verifyActor($work, $current, UserRole::Creator);
                $this->access->activePair($work, $users);
                abort_unless($work->status === WorkStatus::PendingAgreement && $work->agreed_at !== null
                    && $work->agreed_at->lte(now()) && $work->started_at === null && $work->currency === 'USD'
                    && $work->agreed_price !== null, 409);

                $attempts = $work->payments()->whereIn('status', ['pending', 'processing', 'succeeded'])->lockForUpdate()->get();
                abort_if($attempts->count() > 1 || $attempts->first()?->status === PaymentStatus::Succeeded, 409);
                if ($existing = $attempts->first()) {
                    abort_unless($existing->provider_name === 'stripe_sandbox' && $existing->creator_id === $current->id
                        && $existing->currency === $work->currency && $existing->amount === $work->agreed_price, 409);

                    return $existing;
                }

                $payment = $work->payments()->make();
                $payment->forceFill($this->commission->quote($work->agreed_price) + [
                    'creator_id' => $current->id, 'currency' => 'USD', 'provider_name' => 'stripe_sandbox', 'status' => PaymentStatus::Processing,
                ]);
                $payment->save();

                return $payment;
            },
        );

        if ($payment->provider_payment_id === null) {
            // Retry only well inside Stripe's idempotency retention window and the fixed session expiry.
            abort_if($payment->created_at->lt(now()->subHour()), 409);
            $session = $this->stripe->checkout($payment);
        } else {
            $session = $this->stripe->session($payment->provider_payment_id);
        }

        $current = $this->observe($payment, $session);
        $url = $session['url'] ?? null;
        abort_unless($current->status === PaymentStatus::Processing && ($session['status'] ?? null) === 'open'
            && is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https'
            && parse_url($url, PHP_URL_HOST) === 'checkout.stripe.com', 409);

        return ['payment_id' => $current->id, 'checkout_url' => $url, 'status' => $current->status->value];
    }

    public function event(array $object): void
    {
        $reference = $object['metadata']['frame_payment_id'] ?? null;
        if (! is_string($reference) || ! Str::isUuid($reference)) {
            return;
        }
        $payment = Payment::query()->findOrFail($reference);
        $sessionId = $object['id'] ?? null;
        abort_unless(is_string($sessionId) && str_starts_with($sessionId, 'cs_test_')
            && ($payment->provider_payment_id === null || $payment->provider_payment_id === $sessionId), 409);
        $session = $this->stripe->session($sessionId);
        abort_unless(($session['id'] ?? null) === $sessionId, 409);
        $this->observe($payment, $session);
    }

    public function verifySession(Payment $payment, array $session): void
    {
        abort_unless($payment->provider_name === 'stripe_sandbox' && $payment->currency === 'USD'
            && is_string($session['id'] ?? null) && str_starts_with($session['id'], 'cs_test_')
            && ($session['livemode'] ?? null) === false && ($session['mode'] ?? null) === 'payment'
            && ($session['currency'] ?? null) === 'usd' && ($session['amount_total'] ?? null) === CommissionCalculator::minor($payment->amount)
            && ($session['client_reference_id'] ?? null) === $payment->id
            && ($session['metadata']['frame_payment_id'] ?? null) === $payment->id
            && ($session['metadata']['frame_work_item_id'] ?? null) === $payment->work_item_id
            && ($payment->provider_payment_id === null || $payment->provider_payment_id === $session['id']), 409);
    }

    public function revalidateCollection(Payment $payment): void
    {
        abort_unless($payment->provider_payment_id !== null, 409);
        $session = $this->stripe->session($payment->provider_payment_id);
        $this->verifySession($payment, $session);
        $this->verifyCollection($payment, $session);
    }

    private function verifyCollection(Payment $payment, array $session): void
    {
        $intent = $session['payment_intent'] ?? null;
        $amount = CommissionCalculator::minor($payment->amount);
        $charge = $intent['latest_charge'] ?? null;
        abort_unless(($session['status'] ?? null) === 'complete'
            && ($intent['livemode'] ?? null) === false && ($intent['status'] ?? null) === 'succeeded'
            && ($intent['currency'] ?? null) === 'usd' && ($intent['amount_received'] ?? null) === $amount
            && ($charge['livemode'] ?? null) === false && ($charge['currency'] ?? null) === 'usd'
            && ($charge['captured'] ?? null) === true && ($charge['paid'] ?? null) === true
            && ($charge['amount_captured'] ?? null) === $amount && ($charge['amount_refunded'] ?? null) === 0
            && ($charge['disputed'] ?? false) === false && ($session['payment_status'] ?? null) === 'paid', 409);
    }

    private function observe(Payment $candidate, array $session): Payment
    {
        return $this->access->locked($candidate->workItem, null, function (WorkItem $work) use ($candidate, $session) {
            $payment = $work->payments()->lockForUpdate()->findOrFail($candidate->id);
            $this->verifySession($payment, $session);
            abort_unless($work->creator_id === $payment->creator_id && $work->currency === $payment->currency
                && $work->agreed_price === $payment->amount && $work->agreed_at !== null, 409);

            if ($payment->status === PaymentStatus::Succeeded) {
                return $payment;
            }
            if ($payment->status === PaymentStatus::Cancelled) {
                abort_unless(($session['status'] ?? null) === 'expired' && ($session['payment_status'] ?? null) === 'unpaid', 409);

                return $payment;
            }
            abort_unless(in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true), 409);
            $payment->provider_payment_id = $session['id'];
            $intent = $session['payment_intent'] ?? null;
            if (($session['payment_status'] ?? null) === 'paid') {
                $this->verifyCollection($payment, $session);
                $payment->status = PaymentStatus::Succeeded;
                $payment->paid_at = now();
            } elseif (($session['status'] ?? null) === 'expired' && ($session['payment_status'] ?? null) === 'unpaid'
                && ($intent === null || ($intent['status'] ?? null) === 'canceled')) {
                $payment->status = PaymentStatus::Cancelled;
            }
            $payment->save();

            return $payment;
        });
    }
}
