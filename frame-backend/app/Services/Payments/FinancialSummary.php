<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\RefundStatus;
use App\Enums\WorkStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class FinancialSummary
{
    public function creator(User $user): array
    {
        return [
            'gross_collected' => $this->total($this->collected()->where('creator_id', $user->id), 'amount'),
            'confirmed_refunds' => $this->total($this->returned()->whereHas('payment', fn (Builder $payment) => $payment->where('creator_id', $user->id)), 'amount'),
        ];
    }

    public function editor(User $user): array
    {
        $payouts = $this->entitlements()->where('editor_id', $user->id);

        return [
            'eligible_earnings' => $this->total(clone $payouts, 'amount'),
            'pending_simulated_payouts' => $this->total((clone $payouts)->where('status', PayoutStatus::Pending->value), 'amount'),
            'paid_simulated_payouts' => $this->total($payouts->where('status', PayoutStatus::Paid->value), 'amount'),
        ];
    }

    public function staff(): array
    {
        $entitlements = $this->entitlements();
        $net = $this->collected()->whereDoesntHave('refunds', fn (Builder $refund) => $refund->where('status', RefundStatus::Succeeded->value));

        return [
            'gross_collected' => $this->total($this->collected(), 'amount'),
            'confirmed_refunds' => $this->total($this->returned(), 'amount'),
            'refund_adjusted_collected' => $this->total(clone $net, 'amount'),
            'eligible_commission' => $this->total($net->whereHas('payouts', fn (Builder $payout) => $payout->whereIn('status', ['pending', 'paid']))
                ->whereHas('workItem', fn (Builder $work) => $work->where('status', WorkStatus::Completed->value))
                ->whereDoesntHave('refunds', PaymentAccess::blockingRefunds(...)), 'platform_fee'),
            'eligible_editor_earnings' => $this->total(clone $entitlements, 'amount'),
            'pending_simulated_payouts' => $this->total((clone $entitlements)->where('status', PayoutStatus::Pending->value), 'amount'),
            'paid_simulated_payouts' => $this->total($entitlements->where('status', PayoutStatus::Paid->value), 'amount'),
        ];
    }

    private function collected(): Builder
    {
        return Payment::query()->where('provider_name', 'stripe_sandbox')->where('status', PaymentStatus::Succeeded->value);
    }

    private function returned(): Builder
    {
        return Refund::query()->where('status', RefundStatus::Succeeded->value)
            ->whereHas('payment', fn (Builder $payment) => $payment->where('provider_name', 'stripe_sandbox'));
    }

    private function entitlements(): Builder
    {
        return Payout::query()->whereIn('status', ['pending', 'paid'])
            ->whereHas('payment', fn (Builder $payment) => $payment->where('provider_name', 'stripe_sandbox')
                ->where('status', PaymentStatus::Succeeded->value)->whereDoesntHave('refunds', PaymentAccess::blockingRefunds(...)))
            ->whereHas('workItem', fn (Builder $work) => $work->where('status', WorkStatus::Completed->value));
    }

    private function total(Builder $query, string $column): array
    {
        return $query->selectRaw("currency, SUM({$column}) AS aggregate")->groupBy('currency')
            ->pluck('aggregate', 'currency')->map(fn ($amount) => (string) $amount)->all();
    }
}
