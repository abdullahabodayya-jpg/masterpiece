<?php

namespace Tests\Feature\Reviews;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Payments\StripeSandbox;
use App\Support\ArchivedPivot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Feature\Marketplace\MarketplaceTestCase;
use Tests\Feature\Payments\FakeStripeSandbox;

final class ReviewWorkflowTest extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        return 'frame_payments_test_002';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(StripeSandbox::class, new FakeStripeSandbox);
        $this->assertInstanceOf(FakeStripeSandbox::class, $this->app->make(StripeSandbox::class));
    }

    public function test_actual_participants_can_review_each_other_only_once_per_completed_project(): void
    {
        [$work, $payment] = $this->completedWork();
        $url = "/api/v1/work-items/{$work->id}/reviews";
        $status = "/api/v1/work-items/{$work->id}/review-status";
        $this->postJson($url, ['rating' => 5])->assertUnauthorized();
        $this->signIn(User::factory()->creator()->create());
        $this->postJson($url, ['rating' => 5])->assertNotFound();
        $this->getJson($status)->assertNotFound();
        $this->getJson($url)->assertNotFound();

        $this->signIn($work->creator);
        $this->getJson($status)->assertOk()->assertJsonPath('data.can_review', true)->assertJsonPath('data.has_reviewed', false);
        $text = '<b>Delivered on time</b>';
        $id = $this->postJson($url, ['rating' => 5, 'comment' => $text])->assertCreated()
            ->assertJsonPath('data.reviewer_user_id', $work->creator_id)
            ->assertJsonPath('data.reviewed_user_id', $work->editor_id)
            ->assertJsonPath('data.comment', $text)->json('data.id');
        $this->postJson($url, ['rating' => 1])->assertConflict();
        $this->getJson($status)->assertOk()->assertJsonPath('data.has_reviewed', true)->assertJsonPath('data.can_review', false);

        $this->signIn($work->editor);
        $this->postJson($url, ['rating' => 4])->assertCreated()
            ->assertJsonPath('data.reviewer_user_id', $work->editor_id)
            ->assertJsonPath('data.reviewed_user_id', $work->creator_id);
        $this->getJson($url)->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/me/reviews')->assertOk()->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('reviews', ['id' => $id, 'work_item_id' => $work->id, 'comment' => $text]);
        $this->assertDatabaseCount('reviews', 2);
        $this->assertEquals($work->getAttributes(), $work->fresh()->getAttributes());
        $this->assertEquals($payment->getAttributes(), $payment->fresh()->getAttributes());
        $this->assertDatabaseCount('refunds', 0);
        $this->assertDatabaseCount('payouts', 0);
        $this->assertSame([], $this->app->make(StripeSandbox::class)->sessions);
    }

    public function test_ineligible_work_self_reviews_and_unsettled_funding_are_rejected(): void
    {
        foreach (['in_progress', 'cancelled'] as $status) {
            [$work] = $this->completedWork();
            $work->forceFill(['status' => $status])->save();
            $this->signIn($work->creator);
            $this->postJson("/api/v1/work-items/{$work->id}/reviews", ['rating' => 5])->assertConflict();
            $this->getJson("/api/v1/work-items/{$work->id}/review-status")->assertOk()->assertJsonPath('data.eligible', false);
        }

        [$work, $payment] = $this->completedWork();
        $this->signIn($work->creator);
        $url = "/api/v1/work-items/{$work->id}/reviews";
        $payment->status = 'processing';
        $payment->save();
        $this->postJson($url, ['rating' => 5])->assertConflict();
        $payment->status = 'succeeded';
        $payment->save();
        $refund = Refund::factory()->create(['payment_id' => $payment->id, 'status' => 'processing']);
        $this->postJson($url, ['rating' => 5])->assertConflict();
        $refund->status = 'succeeded';
        $refund->save();
        $this->postJson($url, ['rating' => 5])->assertConflict();
        $this->getJson($url)->assertConflict();

        $work->editor_id = $work->creator_id;
        $work->save();
        $this->postJson($url, ['rating' => 5])->assertConflict();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_input_validation_and_soft_deleted_tuple_preserve_the_unique_review(): void
    {
        [$work] = $this->completedWork();
        $url = "/api/v1/work-items/{$work->id}/reviews";
        $this->signIn($work->creator);
        foreach ([0, 6, 1.5, '5', true] as $rating) {
            $this->postJson($url, ['rating' => $rating])->assertUnprocessable()
                ->assertJsonStructure(['error' => ['fields' => ['rating']]]);
        }
        $this->postJson($url, ['rating' => 5, 'reviewed_user_id' => $work->creator_id])->assertUnprocessable();
        $this->postJson($url, ['rating' => 5, 'reviewer_user_id' => $work->editor_id])->assertUnprocessable();
        $this->postJson($url, ['rating' => 5, 'comment' => ['malformed']])->assertUnprocessable();
        $this->postJson($url, ['rating' => 5, 'comment' => str_repeat('x', 5001)])->assertUnprocessable();
        $this->assertDatabaseCount('reviews', 0);
        $id = $this->postJson($url, ['rating' => 3])->assertCreated()->json('data.id');
        Review::findOrFail($id)->delete();
        $this->postJson($url, ['rating' => 4])->assertConflict();
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data');
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_database_unique_conflict_returns_conflict_and_rolls_back_the_write(): void
    {
        [$work] = $this->completedWork();
        $this->signIn($work->creator);
        Event::listen('eloquent.creating: '.Review::class, function (Review $review) {
            DB::table('reviews')->insert([
                'id' => (string) Str::uuid(), 'work_item_id' => $review->work_item_id,
                'reviewer_user_id' => $review->reviewer_user_id, 'reviewed_user_id' => $review->reviewed_user_id,
                'rating' => $review->rating,
            ]);
        });

        $this->postJson("/api/v1/work-items/{$work->id}/reviews", ['rating' => 5])->assertConflict()
            ->assertJsonPath('error.code', 'state_conflict');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_public_reviews_use_viewer_visibility_and_aggregate_over_all_eligible_pages(): void
    {
        $editor = User::factory()->editor()->create();
        $zero = User::factory()->editor()->create();
        [$first, $payment] = $this->completedWork($editor);
        [$second] = $this->completedWork($editor);
        $this->signIn($first->creator);
        $firstId = $this->postJson("/api/v1/work-items/{$first->id}/reviews", ['rating' => 5])->assertCreated()->json('data.id');
        $this->signIn($second->creator);
        $this->postJson("/api/v1/work-items/{$second->id}/reviews", ['rating' => 4])->assertCreated();
        $this->signIn($editor);
        $this->postJson("/api/v1/work-items/{$first->id}/reviews", ['rating' => 3])->assertCreated();
        $this->app['auth']->forgetGuards();

        $url = "/api/v1/editors/{$editor->id}/reviews";
        $this->assertSame(3, $this->queryCount(fn () => $this->getJson("{$url}?per_page=1")->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.has_more', true)->assertJsonPath('summary.review_count', 2)->assertJsonPath('summary.average_rating', '4.50')
            ->assertJsonMissingPath('data.0.work_item_id')->assertJsonMissingPath('data.0.reviewer_user_id')));
        $this->getJson("/api/v1/creators/{$first->creator_id}/reviews")->assertOk()
            ->assertJsonPath('summary.average_rating', '3.00');
        $this->getJson("/api/v1/editors/{$zero->id}/reviews")->assertOk()
            ->assertJsonPath('summary.review_count', 0)->assertJsonPath('summary.average_rating', null);

        $viewer = $this->signIn(User::factory()->creator()->create());
        ArchivedPivot::attachOrRestore($first->creator->blockedUsers(), $viewer->id);
        $this->getJson($url)->assertOk()->assertJsonMissing(['id' => $firstId])
            ->assertJsonPath('summary.review_count', 1)->assertJsonPath('summary.average_rating', '4.00');
        ArchivedPivot::attachOrRestore($editor->blockedUsers(), $viewer->id);
        $this->getJson($url)->assertNotFound();

        Refund::factory()->create(['payment_id' => $payment->id, 'status' => 'succeeded']);
        $this->app['auth']->forgetGuards();
        $this->getJson($url)->assertOk()->assertJsonPath('summary.review_count', 1)
            ->assertJsonPath('summary.average_rating', '4.00');
        $this->assertDatabaseCount('reviews', 3);
    }

    private function completedWork(?User $editor = null): array
    {
        $work = WorkItem::factory()->completed()->create([
            'editor_id' => $editor?->id ?? User::factory()->editor(), 'currency' => 'USD',
            'agreed_at' => now()->subHours(2), 'started_at' => now()->subHour(),
        ]);
        $payment = Payment::factory()->succeeded()->for($work, 'workItem')->create([
            'currency' => 'USD', 'provider_name' => 'stripe_sandbox',
            'provider_payment_id' => 'cs_test_'.str_replace('-', '', (string) Str::uuid()),
            'paid_at' => $work->started_at,
        ]);

        return [$work->refresh(), $payment->refresh()];
    }
}
