<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\Refund;
use App\Services\Payments\CommissionCalculator;
use App\Services\Payments\StripeSandbox;

final class FakeStripeSandbox extends StripeSandbox
{
    public array $sessions = [];

    public array $refunds = [];

    public bool $timeout = false;

    public bool $invalidRefund = false;

    public int $refundCalls = 0;

    public string $refundStatus = 'succeeded';

    public function checkout(Payment $payment): array
    {
        $id = 'cs_test_'.str_replace('-', '', $payment->id);
        $this->sessions[$id] ??= [
            'id' => $id, 'livemode' => false, 'mode' => 'payment', 'status' => 'open', 'payment_status' => 'unpaid',
            'currency' => 'usd', 'amount_total' => CommissionCalculator::minor($payment->amount),
            'client_reference_id' => $payment->id, 'url' => 'https://checkout.stripe.com/c/pay/'.$id,
            'metadata' => ['frame_payment_id' => $payment->id, 'frame_work_item_id' => $payment->work_item_id],
        ];
        abort_if($this->timeout, 503);

        return $this->sessions[$id];
    }

    public function session(string $id): array
    {
        return $this->sessions[$id];
    }

    public function collect(string $id): array
    {
        $session = $this->sessions[$id];
        $session['status'] = 'complete';
        $session['payment_status'] = 'paid';
        $session['payment_intent'] = [
            'id' => 'pi_'.substr($id, 8), 'status' => 'succeeded', 'livemode' => false,
            'currency' => 'usd', 'amount_received' => $session['amount_total'],
            'latest_charge' => ['livemode' => false, 'currency' => 'usd', 'paid' => true, 'captured' => true,
                'amount_captured' => $session['amount_total'], 'amount_refunded' => 0],
        ];

        return $this->sessions[$id] = $session;
    }

    public function createRefund(Refund $refund, string $intent): array
    {
        $this->refundCalls++;
        $id = 're_'.$refund->id;

        return $this->refunds[$id] = [
            'id' => $id, 'status' => $this->refundStatus, 'payment_intent' => $intent, 'currency' => 'usd',
            'amount' => CommissionCalculator::minor($refund->amount) + ($this->invalidRefund ? 1 : 0),
            'metadata' => ['frame_refund_id' => $refund->id, 'frame_payment_id' => $refund->payment_id],
        ];
    }

    public function refund(string $id): array
    {
        return $this->refunds[$id];
    }
}
