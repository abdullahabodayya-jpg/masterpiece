<?php

namespace Tests\Feature\Admin;

use App\Models\Comment;
use App\Models\ContentType;
use App\Models\EditingTool;
use App\Models\EditorService;
use App\Models\EditorVerificationRequest;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Post;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkItem;
use Tests\Feature\Marketplace\MarketplaceTestCase;

final class AdminModulesTest extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        return 'frame_payments_test_002';
    }

    public function test_catalogs_validate_names_preserve_references_and_soft_archive_unused_entries(): void
    {
        $this->postJson('/api/v1/admin/content-types', ['name' => 'Guest'])->assertUnauthorized();
        $editor = $this->signIn(User::factory()->editor()->create());
        $this->postJson('/api/v1/admin/editing-tools', ['name' => 'Editor'])->assertForbidden();
        $admin = $this->signIn(User::factory()->admin()->create());

        foreach (['content-types' => ContentType::class, 'editing-tools' => EditingTool::class] as $kind => $class) {
            $url = "/api/v1/admin/{$kind}";
            $this->postJson($url, ['name' => str_repeat('x', 101)])->assertUnprocessable();
            $name = 'Demo '.$kind;
            $id = $this->postJson($url, ['name' => $name])->assertCreated()->json('data.id');
            $this->postJson($url, ['name' => $name])->assertUnprocessable();
            $this->getJson("{$url}?search=Demo&per_page=1")->assertOk()->assertJsonPath('data.0.id', $id);
            $this->patchJson("{$url}/{$id}", ['name' => 'Updated '.$kind])->assertOk();
            $this->getJson("{$url}/{$id}")->assertOk()->assertJsonPath('data.name', 'Updated '.$kind);
            $this->deleteJson("{$url}/{$id}")->assertNoContent();
            $this->assertSoftDeleted((new $class)->getTable(), ['id' => $id]);
            $this->getJson("{$url}/{$id}")->assertOk()->assertJsonPath('data.id', $id);
            $this->getJson("{$url}?archived=1")->assertOk()->assertJsonPath('data.0.id', $id);
            $this->signIn($editor);
            $this->getJson("/api/v1/{$kind}")->assertOk()->assertJsonMissing(['id' => $id]);
            $this->signIn($admin);
        }

        $work = WorkItem::factory()->create(['editing_tool_id' => EditingTool::factory()]);
        $this->deleteJson("/api/v1/admin/content-types/{$work->content_type_id}")->assertConflict();
        $this->deleteJson("/api/v1/admin/editing-tools/{$work->editing_tool_id}")->assertConflict();
    }

    public function test_monitoring_is_filtered_read_only_and_reuses_staff_routes(): void
    {
        $job = WorkItem::factory()->open()->create(['title' => 'Academic job', 'description' => 'Private project notes']);
        $payment = Payment::factory()->succeeded()->create(['provider_name' => 'stripe_sandbox', 'currency' => 'USD']);
        $verification = EditorVerificationRequest::factory()->create(['status' => 'pending']);
        $application = JobApplication::factory()->for($job, 'workItem')->create();
        $service = EditorService::factory()->create();
        $serviceRequest = WorkItem::factory()->forService($service)->create();
        $refund = Refund::factory()->for($payment)->create(['currency' => 'USD']);
        $payout = Payout::factory()->create([
            'payment_id' => $payment->id, 'work_item_id' => $payment->work_item_id,
            'editor_id' => $payment->workItem->editor_id, 'currency' => 'USD',
        ]);

        $this->signIn(User::factory()->support()->create());
        $this->getJson('/api/v1/admin/payments')->assertForbidden();
        $this->getJson('/api/v1/staff/verification-requests')->assertForbidden();
        $this->signIn(User::factory()->admin()->create());

        $this->getJson('/api/v1/admin/jobs?status=open&search=Academic&per_page=1')->assertOk()
            ->assertJsonPath('data.0.id', $job->id)->assertJsonPath('meta.per_page', 1)
            ->assertJsonMissingPath('data.0.description');
        foreach ([
            'applications' => [$application, $job->id],
            'services' => [$service, $service->editor_id],
            'service-requests' => [$serviceRequest, $serviceRequest->creator_id],
            'projects' => [$payment->workItem, $payment->creator_id],
            'refunds' => [$refund, $payment->id],
            'payouts' => [$payout, $payment->work_item_id],
        ] as $collection => [$record, $reference]) {
            $this->getJson("/api/v1/admin/{$collection}?reference_id={$reference}")->assertOk()
                ->assertJsonFragment(['id' => $record->id]);
            $this->getJson("/api/v1/admin/{$collection}/{$record->id}")->assertOk()
                ->assertJsonPath('data.id', $record->id);
        }
        $this->getJson('/api/v1/admin/jobs?status=bogus')->assertUnprocessable();
        $this->getJson("/api/v1/admin/service-requests/{$job->id}")->assertNotFound();
        $this->getJson("/api/v1/admin/work-items/{$job->id}")->assertOk();
        $this->getJson('/api/v1/admin/payments?currency=USD&status=succeeded')->assertOk()
            ->assertJsonPath('mode', 'academic_simulation')->assertJsonPath('data.0.id', $payment->id)
            ->assertJsonMissingPath('data.0.provider_payment_id');
        $this->getJson("/api/v1/admin/payments/{$payment->id}")->assertOk()->assertJsonMissingPath('data.provider_payment_id');
        $this->patchJson("/api/v1/admin/payments/{$payment->id}", ['amount' => 1000])->assertMethodNotAllowed();
        $this->patchJson("/api/v1/admin/jobs/{$job->id}", ['status' => 'completed'])->assertMethodNotAllowed();
        $this->assertEquals($payment->amount, $payment->fresh()->amount);
        $this->assertDatabaseHas('work_items', ['id' => $job->id, 'status' => 'open']);
        $this->getJson('/api/v1/staff/verification-requests')->assertOk()->assertJsonPath('data.0.id', $verification->id)
            ->assertJsonMissingPath('data.0.document_storage_key');
        $this->getJson('/api/v1/staff/financial-summary')->assertOk();
    }

    public function test_moderation_preserves_content_history_and_redacts_private_posts(): void
    {
        $post = Post::factory()->create();
        $private = Post::factory()->create(['visibility' => 'private', 'body' => 'Private post']);
        $comment = Comment::factory()->for($post)->create();
        $privateComment = Comment::factory()->for($private, 'post')->create(['body' => 'Private comment']);
        $review = Review::factory()->create(['rating' => 4]);
        $remove = "/api/v1/admin/posts/{$post->id}/removal";
        $hide = "/api/v1/admin/comments/{$comment->id}/hide";
        $reason = ['reason' => 'Synthetic policy violation'];

        $this->signIn(User::factory()->editor()->create());
        $this->postJson($remove, $reason)->assertForbidden();
        $this->signIn(User::factory()->admin()->create());
        $this->getJson("/api/v1/admin/posts/{$private->id}")->assertOk()
            ->assertJsonPath('data.body', null)->assertJsonMissingPath('data.media_storage_key');
        $this->getJson("/api/v1/admin/comments/{$privateComment->id}")->assertOk()->assertJsonPath('data.body', null);
        $this->postJson("/api/v1/admin/posts/{$private->id}/removal", $reason)->assertNotFound();
        $this->postJson($hide)->assertUnprocessable();
        $this->postJson($hide, $reason)->assertNoContent();
        $this->postJson($hide, $reason)->assertConflict();
        $this->postJson($remove, $reason)->assertNoContent();
        $this->postJson("/api/v1/admin/reviews/{$review->id}/hide", $reason)->assertNoContent();
        $this->postJson("/api/v1/admin/reviews/{$review->id}/hide", $reason)->assertConflict();
        $this->getJson("/api/v1/admin/reviews/{$review->id}")->assertOk()->assertJsonPath('data.rating', 4);
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'hidden', 'body' => $comment->body]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'status' => 'removed', 'body' => $post->body]);
        $this->assertSoftDeleted('reviews', ['id' => $review->id, 'rating' => 4]);
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/v1/posts/{$post->id}")->assertNotFound();
    }
}
