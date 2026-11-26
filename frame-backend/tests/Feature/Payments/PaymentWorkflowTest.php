<?php

namespace Tests\Feature\Payments;

use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Refund;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Payments\CommissionCalculator;
use App\Services\Payments\StripeSandbox;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Marketplace\MarketplaceTestCase;

final class PaymentWorkflowTest extends MarketplaceTestCase
{
    private FakeStripeSandbox $stripe;

    protected function expectedDatabase(): string
    {
        return 'frame_payments_test_002';
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['frame.payment_mode' => 'stripe_test', 'frame.stripe.webhook_secret' => 'whsec_synthetic_fixture',
            'frame.stripe.key' => 'pk_test_synthetic_fixture', 'frame.stripe.secret' => 'sk_test_synthetic_fixture']);
        $this->app->instance(StripeSandbox::class, $this->stripe = new FakeStripeSandbox);
    }

    public function test_agreement_requires_both_roles_and_exact_terms(): void
    {
        $work = $this->work();
        $base = $this->url($work);
        $this->signIn(User::factory()->creator()->create());
        $this->getJson("{$base}/agreement")->assertNotFound();
        $this->signIn($work->creator);
        $this->postJson("{$base}/payment-attempts")->assertConflict();
        $fingerprint = $this->getJson("{$base}/agreement")->assertOk()
            ->assertJsonPath('data.terms.price', '100.00')->json('data.terms_fingerprint');
        $this->postJson("{$base}/agreement-confirmation", ['terms_fingerprint' => str_repeat('a', 64)])->assertConflict();
        $receipt = $this->postJson("{$base}/agreement-confirmation", ['terms_fingerprint' => $fingerprint])->assertOk()
            ->assertJsonPath('data.agreed_at', null)->json('data.creator_receipt');
        $this->assertNull($work->fresh()->agreed_at);
        $this->signIn($work->editor);
        $this->postJson("{$base}/agreement-confirmation", ['terms_fingerprint' => $fingerprint])->assertConflict();
        $this->postJson("{$base}/agreement-confirmation", ['terms_fingerprint' => $fingerprint, 'creator_receipt' => 'tampered'])->assertConflict();
        $this->postJson("{$base}/agreement-confirmation", ['terms_fingerprint' => $fingerprint, 'creator_receipt' => $receipt])->assertOk();
        $this->postJson("{$base}/agreement-confirmation", ['terms_fingerprint' => $fingerprint, 'creator_receipt' => $receipt])->assertConflict();
        $this->assertNotNull($work->fresh()->agreed_at);
        $this->postJson("{$base}/start")->assertConflict();
        $this->assertNull($work->fresh()->started_at);
    }

    public function test_checkout_and_signed_webhooks_preserve_one_attempt_and_verify_collection(): void
    {
        $work = $this->work();
        $this->agree($work);
        $base = $this->url($work);
        $this->signIn(User::factory()->creator()->create());
        $this->postJson("{$base}/payment-attempts")->assertNotFound();
        $this->signIn($work->creator);
        $this->postJson("{$base}/payment-attempts", ['amount' => '1.00'])->assertUnprocessable();
        $this->stripe->timeout = true;
        $this->postJson("{$base}/payment-attempts")->assertServiceUnavailable();
        $payment = Payment::query()->sole();
        $this->assertNull($payment->provider_payment_id);
        $this->stripe->timeout = false;
        config(['frame.commission_percent' => '25']);
        $this->postJson("{$base}/payment-attempts")->assertOk()->assertJsonPath('data.payment_id', $payment->id);
        $this->postJson("{$base}/payment-attempts")->assertOk()->assertJsonPath('data.payment_id', $payment->id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'platform_fee' => '15.00', 'editor_net_amount' => '85.00']);
        $session = $payment->fresh()->provider_payment_id;
        $this->getJson("{$base}/payment-summary?checkout=returned")->assertOk()->assertJsonPath('data.funded', false);
        $event = $this->stripe->collect($session);
        $this->webhook('checkout.session.completed', $event, valid: false)->assertBadRequest();
        $this->webhook('checkout.session.completed', $event, live: true)->assertBadRequest();
        config(['frame.stripe.secret' => 'sk_live_deliberately_invalid_fixture']);
        $this->webhook('checkout.session.completed', $event)->assertServiceUnavailable();
        config(['frame.stripe.secret' => 'sk_test_synthetic_fixture']);
        $this->stripe->sessions[$session]['amount_total']--;
        $this->webhook('checkout.session.completed', $event)->assertConflict();
        $this->stripe->sessions[$session] = $event;
        $this->stripe->sessions[$session]['currency'] = 'jod';
        $this->webhook('checkout.session.completed', $event)->assertConflict();
        $this->assertNull($payment->fresh()->paid_at);
        $this->stripe->sessions[$session] = $event;
        $this->webhook('checkout.session.completed', $event)->assertOk();
        $timestamp = $payment->fresh()->paid_at;
        $this->travel(2)->seconds();
        $this->webhook('checkout.session.completed', $event)->assertOk();
        $this->webhook('checkout.session.expired', $event)->assertOk();
        $this->assertEquals($timestamp, $payment->fresh()->paid_at);
        $this->getJson("{$base}/payment-summary")->assertOk()->assertJsonPath('data.ready_to_start', true);
        $this->getJson('/api/v1/me/payments')->assertOk()->assertJsonPath('summary.gross_collected.USD', '100.00');
        $this->getJson("/api/v1/payments/{$payment->id}")->assertOk()->assertJsonMissingPath('data.provider_payment_id');
        $this->assertDatabaseHas('work_items', ['id' => $work->id, 'status' => 'pending_agreement', 'started_at' => null]);
    }

    public function test_unsupported_currency_and_exact_commission(): void
    {
        $calculator = app(CommissionCalculator::class);
        $this->assertSame(['amount' => '50.10', 'platform_fee' => '7.52', 'editor_net_amount' => '42.58'], $calculator->quote('50.10'));
        foreach (['0.00', '-1.00', '1.005', '1000000.00'] as $invalid) {
            try {
                $calculator->quote($invalid);
                $this->fail('Invalid amount was accepted.');
            } catch (HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
            }
        }
        $work = $this->work('JOD');
        $this->agree($work);
        $this->signIn($work->creator);
        $this->postJson($this->url($work).'/payment-attempts')->assertConflict();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_completion_and_internal_payout_follow_actual_delivery_acceptance(): void
    {
        Storage::fake('local');
        $work = $this->work();
        $payment = $this->fund($work);
        $base = $this->url($work);
        $this->postJson("{$base}/start")->assertForbidden();
        $this->signIn($work->editor);
        $sessionId = $payment->provider_payment_id;
        $this->stripe->sessions[$sessionId]['payment_intent']['latest_charge']['amount_refunded'] = 10000;
        $this->postJson("{$base}/start")->assertConflict();
        $this->assertNull($work->fresh()->started_at);
        $this->stripe->sessions[$sessionId]['payment_intent']['latest_charge']['amount_refunded'] = 0;
        $this->postJson("{$base}/start")->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->postJson("{$base}/start")->assertConflict();
        $this->signIn($work->creator);
        $this->postJson("{$base}/completion")->assertConflict();
        $delivery = $this->postJson("{$base}/deliverables", ['title' => 'Edited video'])->assertCreated()->json('data.id');
        $this->postJson("{$base}/completion")->assertConflict();
        $this->signIn($work->editor);
        $file = UploadedFile::fake()->createWithContent('test.mp4', hex2bin('00000018667479706d703432000000006d70343269736f6d'));
        $token = $this->post("/api/v1/deliverables/{$delivery}/submission", ['file' => $file])->assertOk()->json('data.submission_token');
        $this->signIn($work->creator);
        $revision = $this->postJson("/api/v1/deliverables/{$delivery}/revisions", [
            'reason' => 'Review intro', 'submission_token' => $token,
        ])->assertCreated()->json('data.id');
        $this->postJson("{$base}/completion")->assertConflict();
        $this->postJson("/api/v1/revisions/{$revision}/cancellation")->assertOk();
        $this->postJson("/api/v1/deliverables/{$delivery}/acceptance", ['submission_token' => $token])->assertOk();
        $this->assertDatabaseCount('payouts', 0);
        $this->postJson("{$base}/completion")->assertOk()->assertJsonPath('data.status', 'completed');
        $this->postJson("{$base}/completion")->assertConflict();
        $this->postJson("/api/v1/payments/{$payment->id}/refunds", ['reason' => 'Completed'])->assertConflict();
        $payout = Payout::query()->sole();
        $url = "/api/v1/staff/payouts/{$payout->id}/simulation";
        $this->signIn($work->editor);
        $this->getJson('/api/v1/me/earnings')->assertOk()->assertJsonPath('data.eligible_earnings.USD', '85.00');
        $this->getJson('/api/v1/me/payouts')->assertOk()->assertJsonPath('data.0.mode', 'academic_simulation');
        $this->postJson($url)->assertForbidden();
        $this->signIn(User::factory()->admin()->create());
        $this->postJson($url)->assertOk()->assertJsonPath('data.status', 'paid')->assertJsonPath('data.amount', '85.00');
        $this->postJson($url)->assertConflict();
        $this->assertNull($payout->fresh()->provider_payout_id);
        $this->getJson('/api/v1/staff/financial-summary')->assertOk()->assertJsonPath('data.eligible_commission.USD', '15.00')
            ->assertJsonPath('data.paid_simulated_payouts.USD', '85.00');
    }

    public function test_full_refunds_gate_activation_and_delivery_and_require_admin_after_start(): void
    {
        $first = $this->work();
        $payment = $this->fund($first);
        $url = "/api/v1/payments/{$payment->id}/refunds";
        $this->stripe->invalidRefund = true;
        $this->postJson($url, ['reason' => 'Before start'])->assertConflict();
        $this->assertDatabaseHas('refunds', ['payment_id' => $payment->id, 'status' => 'processing', 'completed_at' => null]);
        $this->signIn($first->editor);
        $this->postJson($this->url($first).'/start')->assertConflict();
        $this->stripe->invalidRefund = false;
        $this->signIn($first->creator);
        $this->postJson($url, ['reason' => 'Before start'])->assertAccepted()->assertJsonPath('data.status', 'succeeded');
        $this->postJson($url, ['reason' => 'Duplicate'])->assertConflict();
        $this->assertDatabaseHas('work_items', ['id' => $first->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'succeeded', 'platform_fee' => '15.00']);
        $this->webhook('refund.updated', $this->stripe->refunds['re_'.Refund::query()->sole()->id])->assertOk();

        $second = $this->work();
        $payment = $this->fund($second);
        $base = $this->url($second);
        $this->signIn($second->editor);
        $this->postJson("{$base}/start")->assertOk();
        $this->signIn($second->creator);
        $calls = $this->stripe->refundCalls;
        $url = "/api/v1/payments/{$payment->id}/refunds";
        $refundId = $this->postJson($url, ['reason' => 'After start'])->assertAccepted()->assertJsonPath('data.status', 'pending')->json('data.id');
        $this->postJson($url, ['reason' => 'Same work'])->assertAccepted()->assertJsonPath('data.id', $refundId);
        $this->assertSame($calls, $this->stripe->refundCalls);
        $this->postJson("{$base}/deliverables", ['title' => 'During refund'])->assertConflict();
        $approval = "/api/v1/staff/refunds/{$refundId}/approval";
        $this->signIn(User::factory()->support()->create());
        $this->postJson($approval)->assertForbidden();
        $this->signIn(User::factory()->admin()->create());
        $this->getJson('/api/v1/staff/refunds')->assertOk()->assertJsonPath('data.0.id', $refundId);
        $this->postJson($approval)->assertOk()->assertJsonPath('data.status', 'succeeded');
        $this->postJson($approval)->assertConflict();
        $this->assertDatabaseHas('work_items', ['id' => $second->id, 'status' => 'cancelled']);
        $this->assertDatabaseCount('payouts', 0);
    }

    public function test_only_active_admins_can_reject_pending_undispatched_refunds_and_release_the_hold(): void
    {
        $work = $this->work();
        $payment = $this->fund($work);
        $base = $this->url($work);
        $this->signIn($work->editor);
        $this->postJson("{$base}/start")->assertOk();
        $this->signIn($work->creator);
        $id = $this->postJson("/api/v1/payments/{$payment->id}/refunds", ['reason' => 'Change of plans'])->assertAccepted()->json('data.id');
        $rejection = "/api/v1/staff/refunds/{$id}/rejection";
        $this->postJson("{$base}/deliverables", ['title' => 'Held'])->assertConflict();
        $this->postJson($rejection)->assertForbidden();
        foreach ([User::factory()->support(), User::factory()->admin()->state(['status' => 'suspended'])] as $factory) {
            $this->signIn($factory->create());
            $this->postJson($rejection)->assertForbidden();
        }

        $this->signIn(User::factory()->admin()->create());
        $this->postJson($rejection)->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->postJson($rejection)->assertConflict();
        $this->assertDatabaseHas('refunds', ['id' => $id, 'status' => 'cancelled', 'provider_refund_id' => null, 'completed_at' => null]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'succeeded', 'platform_fee' => '15.00', 'editor_net_amount' => '85.00']);
        $this->assertSame(0, $this->stripe->refundCalls);
        $this->signIn($work->creator);
        $this->postJson("{$base}/deliverables", ['title' => 'Audition cut'])->assertCreated();
        $this->assertDatabaseHas('work_items', ['id' => $work->id, 'status' => 'in_progress']);
    }

    public function test_expired_admin_decisions_release_holds_but_ambiguous_dispatches_stay_blocked(): void
    {
        $work = $this->work();
        $payment = $this->fund($work);
        $base = $this->url($work);
        $this->signIn($work->editor);
        $this->postJson("{$base}/start")->assertOk();
        $this->signIn($work->creator);
        $url = "/api/v1/payments/{$payment->id}/refunds";
        $id = $this->postJson($url, ['reason' => 'Wait for decision'])->assertAccepted()->json('data.id');
        $this->travel(21)->hours();
        $this->getJson("/api/v1/payments/{$payment->id}")->assertOk()->assertJsonPath('data.refunds.0.decision_expired', true);
        $this->postJson("{$base}/deliverables", ['title' => 'Expired hold released'])->assertCreated();
        $admin = $this->signIn(User::factory()->admin()->create());
        $this->getJson('/api/v1/staff/refunds')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson("/api/v1/staff/refunds/{$id}/approval")->assertConflict();
        $this->assertSame(0, $this->stripe->refundCalls);

        $this->signIn($work->creator);
        $next = $this->postJson($url, ['reason' => 'New request'])->assertAccepted()->json('data.id');
        $this->assertNotSame($id, $next);
        $this->signIn($admin);
        $this->stripe->invalidRefund = true;
        $this->postJson("/api/v1/staff/refunds/{$next}/approval")->assertConflict();
        $this->assertDatabaseHas('refunds', ['id' => $next, 'status' => 'processing', 'provider_refund_id' => null]);
        $this->postJson("/api/v1/staff/refunds/{$next}/rejection")->assertConflict();

        $this->travel(21)->hours();
        $this->stripe->invalidRefund = false;
        $this->postJson("/api/v1/staff/refunds/{$next}/approval")->assertConflict();
        $this->postJson("/api/v1/staff/refunds/{$next}/rejection")->assertConflict();
        $this->assertSame(1, $this->stripe->refundCalls);
        $this->signIn($work->creator);
        $this->postJson("{$base}/deliverables", ['title' => 'Still held'])->assertConflict();
        $this->postJson($url, ['reason' => 'Repeat'])->assertAccepted()->assertJsonPath('data.id', $next);
        $this->assertDatabaseCount('refunds', 2);
    }

    public function test_old_linked_refunds_can_be_reconciled_and_terminal_events_replayed(): void
    {
        foreach (['failed' => 'failed', 'canceled' => 'cancelled'] as $providerStatus => $localStatus) {
            $work = $this->work();
            $payment = $this->fund($work);
            $this->stripe->refundStatus = 'pending';
            $id = $this->postJson("/api/v1/payments/{$payment->id}/refunds", ['reason' => 'Before start'])
                ->assertAccepted()->assertJsonPath('data.status', 'processing')->json('data.id');
            $providerId = Refund::findOrFail($id)->provider_refund_id;
            $this->assertNotNull($providerId);
            $this->travel(21)->hours();
            $this->signIn(User::factory()->admin()->create());
            $this->postJson("/api/v1/staff/refunds/{$id}/rejection")->assertConflict();
            $this->stripe->refunds[$providerId]['status'] = $providerStatus;
            $calls = $this->stripe->refundCalls;
            $this->postJson("/api/v1/staff/refunds/{$id}/approval")->assertOk()->assertJsonPath('data.status', $localStatus);
            $this->assertSame($calls, $this->stripe->refundCalls);
            $evidence = $this->stripe->refunds[$providerId];
            $this->webhook('refund.updated', $evidence)->assertOk();
            $this->webhook('refund.updated', $evidence)->assertOk();
            $this->stripe->refunds[$providerId]['status'] = 'succeeded';
            $this->webhook('refund.updated', $evidence)->assertConflict();
            $this->assertDatabaseHas('refunds', ['id' => $id, 'status' => $localStatus, 'completed_at' => null]);
            $this->assertDatabaseHas('work_items', ['id' => $work->id, 'status' => 'pending_agreement']);
        }
    }

    private function work(string $currency = 'USD'): WorkItem
    {
        $work = WorkItem::factory()->assigned()->create(['status' => 'pending_agreement', 'agreed_price' => '100.00',
            'currency' => $currency, 'agreed_at' => null, 'started_at' => null]);
        JobApplication::factory()->for($work, 'workItem')->for($work->editor, 'editor')
            ->create(['status' => 'accepted', 'proposed_price' => '100.00']);

        return $work;
    }

    private function agree(WorkItem $work): void
    {
        $this->signIn($work->creator);
        $base = $this->url($work);
        $terms = ['terms_fingerprint' => $this->getJson("{$base}/agreement")->assertOk()->json('data.terms_fingerprint')];
        $receipt = $this->postJson("{$base}/agreement-confirmation", $terms)->assertOk()->json('data.creator_receipt');
        $this->signIn($work->editor);
        $this->postJson("{$base}/agreement-confirmation", $terms + ['creator_receipt' => $receipt])->assertOk();
    }

    private function fund(WorkItem $work): Payment
    {
        $this->agree($work);
        $this->signIn($work->creator);
        $id = $this->postJson($this->url($work).'/payment-attempts')->assertOk()->json('data.payment_id');
        $payment = Payment::findOrFail($id);
        $this->webhook('checkout.session.completed', $this->stripe->collect($payment->provider_payment_id))->assertOk();

        return $payment->fresh();
    }

    private function webhook(string $type, array $object, bool $valid = true, bool $live = false): TestResponse
    {
        $body = json_encode(['id' => 'evt_fixture', 'type' => $type, 'livemode' => $live, 'data' => ['object' => $object]], JSON_THROW_ON_ERROR);
        $time = time();
        $signature = hash_hmac('sha256', $time.'.'.$body, $valid ? 'whsec_synthetic_fixture' : 'different_fixture');

        return $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t={$time},v1={$signature}",
        ], $body);
    }

    private function url(WorkItem $work): string
    {
        return "/api/v1/work-items/{$work->id}";
    }
}
