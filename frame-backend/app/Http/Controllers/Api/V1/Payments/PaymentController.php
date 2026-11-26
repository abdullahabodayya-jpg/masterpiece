<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Resources\Payments\FinanceResource;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Refund;
use App\Services\Marketplace\MarketplaceAccess;
use App\Services\Payments\Agreement;
use App\Services\Payments\Checkout;
use App\Services\Payments\FinancialSummary;
use App\Services\Payments\PaymentAccess;
use App\Services\Payments\ProjectFinance;
use App\Services\Payments\Refunds;
use App\Services\Payments\StripeSandbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentController
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly MarketplaceAccess $accounts,
    ) {}

    public function agreement(Request $request, string $workItemId, Agreement $agreement): JsonResponse
    {
        $work = $this->access->participant($request->user('sanctum'), $workItemId);
        $terms = $agreement->forWork($work);

        return $this->data(['terms' => $terms, 'terms_fingerprint' => Agreement::fingerprint($terms),
            'agreed_at' => $work->agreed_at?->toISOString()]);
    }

    public function confirm(Request $request, string $workItemId, Agreement $agreement): JsonResponse
    {
        $input = $request->validate(['terms_fingerprint' => ['required', 'string', 'size:64'],
            'creator_receipt' => ['sometimes', 'required', 'string', 'max:4096']]);

        return $this->data($agreement->confirm($request->user('sanctum'), $workItemId, $input));
    }

    public function checkout(Request $request, string $workItemId, Checkout $checkout): JsonResponse
    {
        abort_unless($request->all() === [], 422);

        return $this->data($checkout->open($request->user('sanctum'), $workItemId));
    }

    public function readiness(Request $request, string $workItemId): JsonResponse
    {
        $work = $this->access->participant($request->user('sanctum'), $workItemId);
        $funded = $this->access->funding($work)->count() === 1;

        return $this->data(['work_item_id' => $work->id, 'status' => $work->status->value,
            'agreed_at' => $work->agreed_at?->toISOString(), 'funded' => $funded,
            'ready_to_start' => $funded && $work->status === WorkStatus::PendingAgreement && $work->agreed_at !== null
                && $work->agreed_at->lte(now()) && $work->started_at === null && $work->completed_at === null]);
    }

    public function start(Request $request, string $workItemId, ProjectFinance $finance): JsonResponse
    {
        $work = $finance->start($request->user('sanctum'), $workItemId);

        return $this->data(['work_item_id' => $work->id, 'status' => $work->status->value, 'started_at' => $work->started_at->toISOString()]);
    }

    public function complete(Request $request, string $workItemId, ProjectFinance $finance): JsonResponse
    {
        $work = $finance->complete($request->user('sanctum'), $workItemId);

        return $this->data(['work_item_id' => $work->id, 'status' => $work->status->value, 'completed_at' => $work->completed_at->toISOString()]);
    }

    public function payment(Request $request, string $paymentId): FinanceResource
    {
        $actor = $this->accounts->actor($request->user('sanctum'), UserRole::Creator);

        return new FinanceResource(Payment::query()->where('provider_name', 'stripe_sandbox')->where('creator_id', $actor->id)->with('refunds')->findOrFail($paymentId));
    }

    public function payments(CatalogRequest $request, FinancialSummary $summary): JsonResponse
    {
        $actor = $this->accounts->actor($request->user('sanctum'), UserRole::Creator);
        $page = $request->page(Payment::query()->where('provider_name', 'stripe_sandbox')->where('creator_id', $actor->id)->with('refunds'), FinanceResource::class);

        return response()->json($page->getData(true) + ['summary' => $summary->creator($actor), 'mode' => 'academic_simulation']);
    }

    public function requestRefund(Request $request, string $paymentId, Refunds $refunds): JsonResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:2000']])['reason'];
        $refund = $refunds->request($request->user('sanctum'), $paymentId, $reason);

        return FinanceResource::make($refund)->response()->setStatusCode(202);
    }

    public function approve(Request $request, string $refundId, Refunds $refunds): FinanceResource
    {
        return new FinanceResource($refunds->approve($request->user('sanctum'), $refundId));
    }

    public function rejectRefund(Request $request, string $refundId, Refunds $refunds): FinanceResource
    {
        return new FinanceResource($refunds->reject($request->user('sanctum'), $refundId));
    }

    public function refundQueue(CatalogRequest $request): JsonResponse
    {
        $this->access->staff($request->user('sanctum'));

        $queue = Refund::query()->whereHas('payment', fn ($payment) => $payment->where('provider_name', 'stripe_sandbox'))
            ->whereIn('status', ['pending', 'processing']);

        return $request->page(PaymentAccess::blockingRefunds($queue), FinanceResource::class);
    }

    public function earnings(Request $request, FinancialSummary $summary): JsonResponse
    {
        return $this->data($summary->editor($this->accounts->actor($request->user('sanctum'), UserRole::Editor)));
    }

    public function payouts(CatalogRequest $request): JsonResponse
    {
        $actor = $this->accounts->actor($request->user('sanctum'), UserRole::Editor);

        return $request->page(Payout::query()->whereHas('payment', fn ($payment) => $payment->where('provider_name', 'stripe_sandbox'))->where('editor_id', $actor->id), FinanceResource::class);
    }

    public function simulate(Request $request, string $payoutId, ProjectFinance $finance): FinanceResource
    {
        return new FinanceResource($finance->simulate($request->user('sanctum'), $payoutId));
    }

    public function staffSummary(Request $request, FinancialSummary $summary): JsonResponse
    {
        $this->access->staff($request->user('sanctum'));

        return $this->data($summary->staff());
    }

    public function webhook(Request $request, StripeSandbox $stripe, Checkout $checkout, Refunds $refunds): JsonResponse
    {
        $event = $stripe->event($request->getContent(), (string) $request->header('Stripe-Signature'));
        $object = $event['data']['object'] ?? [];
        match ($event['type'] ?? null) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed',
            'checkout.session.expired' => $checkout->event($object),
            'refund.created', 'refund.updated', 'refund.failed' => $refunds->event($object),
            default => null,
        };

        return $this->data(['received' => true]);
    }

    private function data(array $data): JsonResponse
    {
        return response()->json(['data' => ['mode' => 'academic_simulation'] + $data]);
    }
}
