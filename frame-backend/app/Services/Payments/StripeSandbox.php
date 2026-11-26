<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Refund;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeSandbox
{
    public function checkout(Payment $payment): array
    {
        $base = rtrim((string) config('frame.frontend_url'), '/');
        abort_unless(filter_var($base, FILTER_VALIDATE_URL) && in_array(parse_url($base, PHP_URL_SCHEME), ['http', 'https'], true)
            && parse_url($base, PHP_URL_QUERY) === null && parse_url($base, PHP_URL_FRAGMENT) === null, 503);
        $return = $base.'/payments/return?work_item_id='.$payment->work_item_id;

        return $this->call(fn (StripeClient $stripe) => $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'adaptive_pricing' => ['enabled' => false],
            'client_reference_id' => $payment->id,
            'metadata' => ['frame_payment_id' => $payment->id, 'frame_work_item_id' => $payment->work_item_id],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => CommissionCalculator::minor($payment->amount),
                    'product_data' => ['name' => 'FRAME academic project funding'],
                ],
            ]],
            'expires_at' => $payment->created_at->copy()->addHours(2)->timestamp,
            'success_url' => $return.'&checkout=returned',
            'cancel_url' => $return.'&checkout=cancelled',
        ], ['idempotency_key' => 'frame-checkout-'.$payment->id]));
    }

    public function session(string $id): array
    {
        abort_unless(str_starts_with($id, 'cs_test_'), 409);

        return $this->call(fn (StripeClient $stripe) => $stripe->checkout->sessions->retrieve($id, [
            'expand' => ['payment_intent.latest_charge'],
        ]));
    }

    public function createRefund(Refund $refund, string $intent): array
    {
        return $this->call(fn (StripeClient $stripe) => $stripe->refunds->create([
            'payment_intent' => $intent,
            'amount' => CommissionCalculator::minor($refund->amount),
            'metadata' => ['frame_refund_id' => $refund->id, 'frame_payment_id' => $refund->payment_id],
        ], ['idempotency_key' => 'frame-refund-'.$refund->id]));
    }

    public function refund(string $id): array
    {
        abort_unless(str_starts_with($id, 're_'), 409);

        return $this->call(fn (StripeClient $stripe) => $stripe->refunds->retrieve($id, []));
    }

    public function event(string $body, string $signature): array
    {
        $this->requireSandbox();
        $secret = (string) config('frame.stripe.webhook_secret');
        abort_unless(str_starts_with($secret, 'whsec_'), 503);
        try {
            $event = Webhook::constructEvent($body, $signature, $secret)->toArray();
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            abort(400);
        }
        abort_unless(($event['livemode'] ?? null) === false, 400);

        return $event;
    }

    private function call(callable $operation): array
    {
        $this->requireSandbox();
        try {
            return $operation(new StripeClient((string) config('frame.stripe.secret')))->toArray();
        } catch (ApiErrorException) {
            // No provider detail or credentials are exposed to callers. Reservations stay unresolved.
            abort(503);
        }
    }

    public function requireSandbox(): void
    {
        abort_unless(config('frame.payment_mode') === 'stripe_test'
            && str_starts_with((string) config('frame.stripe.secret'), 'sk_test_')
            && str_starts_with((string) config('frame.stripe.key'), 'pk_test_'), 503);
    }
}
