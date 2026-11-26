<?php

namespace Tests\Feature\Work;

use App\Models\Deliverable;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\ArchivedPivot;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Marketplace\MarketplaceTestCase;

final class DeliveryWorkflowTest extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        if (getenv('FRAME_PAYMENTS_REGRESSION') === '1') {
            return 'frame_payments_test_002';
        }

        return getenv('FRAME_MESSAGING_REGRESSION') === '1' ? 'frame_messaging_test_001' : 'frame_work_test_001';
    }

    public function test_project_material_and_mutations_are_gated_separately(): void
    {
        $work = WorkItem::factory()->assigned()->create(['status' => 'pending_agreement', 'agreed_at' => null, 'started_at' => null]);
        $url = "/api/v1/work-items/{$work->id}";
        $this->getJson("{$url}/deliverables")->assertUnauthorized();
        $this->signIn(User::factory()->editor()->create());
        $this->getJson($url)->assertNotFound();
        $this->getJson("{$url}/deliverables")->assertNotFound();

        $this->signIn($work->creator);
        $this->getJson($url)->assertOk()->assertJsonPath('data.status', 'pending_agreement');
        $this->assertSame(3, $this->queryCount(fn () => $this->getJson("{$url}/progress")
            ->assertOk()->assertJsonPath('data.delivery_ready', false)));
        $this->postJson("{$url}/deliverables", ['title' => 'Cut'])->assertConflict();
        $unassigned = WorkItem::factory()->for($work->creator, 'creator')->open()->create();
        $this->getJson("/api/v1/work-items/{$unassigned->id}")->assertOk();
        $this->getJson("/api/v1/work-items/{$unassigned->id}/deliverables")->assertNotFound();

        // Synthetic activation fields alone cannot unlock delivery.
        $work->status = 'in_progress';
        $work->agreed_at = now();
        $work->started_at = now();
        $work->currency = 'USD';
        $work->save();
        $this->postJson("{$url}/deliverables", ['title' => 'Cut'])->assertConflict();
        $this->fund($work, ['amount' => '1.00']);
        $this->postJson("{$url}/deliverables", ['title' => 'Cut'])->assertConflict();
        $this->fund($work);

        foreach (['cancelled', 'closed', 'completed', 'disputed'] as $state) {
            $work->status = $state;
            $work->save();
            $this->getJson("{$url}/deliverables")->assertOk();
            $this->postJson("{$url}/deliverables", ['title' => 'Cut'])->assertConflict();
        }
        $editor = $work->editor;
        $editor->status = 'suspended';
        $editor->save();
        $this->signIn($editor);
        $this->getJson("{$url}/deliverables")->assertForbidden();
        $this->assertDatabaseCount('deliverables', 0);

        Storage::fake('local');
        $work->status = 'pending_agreement';
        $work->save();
        $pendingDelivery = Deliverable::factory()->for($work, 'workItem')->create();
        $editor->status = 'active';
        $editor->save();
        $this->signIn($editor);
        $this->post("/api/v1/deliverables/{$pendingDelivery->id}/submission", ['file' => $this->video()])->assertConflict();
        $this->assertSame([], Storage::disk('local')->allFiles('work-deliverables'));
        $this->assertDatabaseHas('deliverables', ['id' => $pendingDelivery->id, 'status' => 'pending', 'storage_key' => null]);
    }

    public function test_submission_download_review_and_acceptance_keep_funds_and_work_unchanged(): void
    {
        Storage::fake('local');
        $work = $this->activeWork();
        $url = "/api/v1/work-items/{$work->id}";
        $this->signIn($work->creator);
        $this->postJson("{$url}/deliverables", ['title' => 'Cut', 'sequence_number' => 9, 'status' => 'accepted'])->assertUnprocessable();
        $id = $this->postJson("{$url}/deliverables", ['title' => 'First cut'])->assertCreated()
            ->assertJsonPath('data.sequence_number', 1)->json('data.id');
        $second = $this->postJson("{$url}/deliverables", ['title' => 'Trailer'])->assertCreated()
            ->assertJsonPath('data.sequence_number', 2)->json('data.id');
        $delivery = "/api/v1/deliverables/{$id}";
        $this->patchJson($delivery, ['title' => 'Rough cut'])->assertOk()->assertJsonPath('data.title', 'Rough cut');
        $this->postJson("{$delivery}/acceptance", ['submission_token' => str_repeat('0', 64)])->assertConflict();

        $this->signIn($work->editor);
        $this->postJson("{$url}/deliverables", ['title' => 'Editor create'])->assertForbidden();
        $this->getJson("{$url}/deliverables?per_page=1&sort=oldest")->assertOk()
            ->assertJsonPath('data.0.id', $id)->assertJsonPath('meta.has_more', true);
        $this->post("{$delivery}/submission", ['file' => UploadedFile::fake()->createWithContent('fake.mp4', 'text')])
            ->assertUnprocessable();
        $token = $this->post("{$delivery}/submission", ['file' => $this->video()])->assertOk()
            ->assertJsonPath('data.status', 'submitted')->assertJsonMissingPath('data.storage_key')
            ->assertJsonMissingPath('data.file_name')->json('data.submission_token');
        $this->assertNotNull($token);
        $this->post("{$delivery}/submission", ['file' => $this->video()])->assertConflict();
        $this->assertCount(1, Storage::disk('local')->allFiles('work-deliverables'));
        $this->postJson("{$delivery}/acceptance", ['submission_token' => $token])->assertForbidden();

        $this->signIn(User::factory()->creator()->create());
        $this->get("{$delivery}/document")->assertNotFound();
        $this->patchJson($delivery, ['title' => 'Intrusion'])->assertNotFound();
        $this->signIn($work->creator);
        ArchivedPivot::attachOrRestore($work->creator->blockedUsers(), $work->editor_id);
        $this->get($delivery.'/document')->assertOk()->assertDownload('deliverable.mp4')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->getJson($delivery)->assertOk()->assertJsonPath('data.submission_token', $token);
        $this->patchJson($delivery, ['title' => 'Changed after submission'])->assertConflict();
        $this->postJson("{$delivery}/acceptance", ['submission_token' => str_repeat('1', 64)])->assertConflict();
        $this->postJson("{$delivery}/review", ['submission_token' => $token, 'review_notes' => 'Checked'])->assertOk()
            ->assertJsonPath('data.status', 'in_review');
        $this->postJson("{$delivery}/acceptance", ['submission_token' => $token])->assertOk()
            ->assertJsonPath('data.status', 'accepted')->assertJsonPath('data.review_notes', 'Checked');
        $this->postJson("{$delivery}/acceptance", ['submission_token' => $token])->assertConflict();
        $this->getJson("{$url}/progress")->assertOk()->assertJsonPath('data.outstanding_deliverables', 1)
            ->assertJsonPath('data.delivery_ready', false);

        $this->signIn($work->editor);
        $secondUrl = "/api/v1/deliverables/{$second}";
        $secondToken = $this->post("{$secondUrl}/submission", ['file' => $this->video()])->assertOk()->json('data.submission_token');
        $this->signIn($work->creator);
        $this->postJson("{$secondUrl}/acceptance", ['submission_token' => $token])->assertConflict();
        $this->postJson("{$secondUrl}/acceptance", ['submission_token' => $secondToken])->assertOk();
        $this->getJson("{$url}/progress")->assertOk()->assertJsonPath('data.delivery_ready', true);
        $this->assertDatabaseHas('work_items', ['id' => $work->id, 'status' => 'in_progress', 'completed_at' => null]);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payouts', 0);
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_revision_lifecycle_preserves_evidence_and_rolls_back_failed_writes(): void
    {
        Storage::fake('local');
        $work = $this->activeWork();
        $this->signIn($work->creator);
        $id = $this->postJson("/api/v1/work-items/{$work->id}/deliverables", ['title' => 'Cut'])->assertCreated()->json('data.id');
        $url = "/api/v1/deliverables/{$id}";
        $event = 'eloquent.saving: '.Deliverable::class;
        $this->signIn($work->editor);
        Event::listen($event, fn () => throw new \RuntimeException('Synthetic persistence failure'));
        $this->post("{$url}/submission", ['file' => $this->video()])->assertServerError();
        Event::forget($event);
        $this->assertSame([], Storage::disk('local')->allFiles('work-deliverables'));
        $this->assertDatabaseHas('deliverables', ['id' => $id, 'status' => 'pending', 'storage_key' => null]);
        $token = $this->post("{$url}/submission", ['file' => $this->video()])->assertOk()->json('data.submission_token');
        $input = ['submission_token' => $token, 'reason' => 'Trim the intro'];
        $this->postJson("{$url}/revisions", $input)->assertForbidden();

        $this->signIn($work->creator);
        Event::listen($event, fn () => throw new \RuntimeException('Synthetic persistence failure'));
        $this->postJson("{$url}/revisions", $input)->assertServerError();
        Event::forget($event);
        $this->assertDatabaseCount('deliverable_revisions', 0);
        $this->assertDatabaseHas('deliverables', ['id' => $id, 'status' => 'submitted']);

        $revision = $this->postJson("{$url}/revisions", $input)->assertCreated()->assertJsonPath('data.status', 'requested')->json('data.id');
        $revisionUrl = "/api/v1/revisions/{$revision}";
        $this->postJson("{$url}/revisions", $input)->assertConflict();
        $this->postJson("{$url}/acceptance", ['submission_token' => $token])->assertConflict();
        $this->getJson("/api/v1/work-items/{$work->id}/progress")->assertOk()
            ->assertJsonPath('data.unresolved_revisions', 1)->assertJsonPath('data.delivery_ready', false);

        $this->signIn(User::factory()->editor()->create());
        $this->getJson($revisionUrl)->assertNotFound();
        $this->signIn($work->editor);
        $this->getJson("{$url}/revisions")->assertOk()->assertJsonPath('data.0.id', $revision);
        $this->getJson($revisionUrl)->assertOk();
        $this->postJson("{$revisionUrl}/start")->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->postJson("{$revisionUrl}/start")->assertConflict();
        $this->post("{$url}/submission", ['file' => $this->video()])->assertConflict();

        $this->signIn($work->creator);
        $this->postJson("{$revisionUrl}/cancellation")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertNotNull(Deliverable::findOrFail($id)->deliverableRevisions()->sole()->resolved_at);
        $this->postJson("{$url}/acceptance", ['submission_token' => $token])->assertOk();
        $this->assertCount(1, Storage::disk('local')->allFiles('work-deliverables'));
    }

    public function test_delivery_requires_a_unique_matching_sandbox_payment_confirmed_by_activation(): void
    {
        foreach ([
            ['JOD', ['currency' => 'JOD']],
            ['USD', ['provider_name' => 'synthetic']],
            ['USD', ['provider_payment_id' => 'cs_test_']],
            ['USD', ['provider_payment_id' => 'CS_TEST_bad']],
            ['USD', ['paid_at' => now()->subMinute()]],
            ['USD', ['creator_id' => User::factory()->creator()->create()->id]],
        ] as [$currency, $overrides]) {
            $work = WorkItem::factory()->assigned()->create([
                'currency' => $currency, 'agreed_at' => now()->subHours(2), 'started_at' => now()->subHour(),
            ]);
            $this->fund($work, $overrides);
            $this->signIn($work->creator);
            $summary = $this->getJson("/api/v1/work-items/{$work->id}/payment-summary")->assertOk();
            $this->assertFalse($summary->json('data.funded'), json_encode($overrides, JSON_THROW_ON_ERROR));
            $this->postJson("/api/v1/work-items/{$work->id}/deliverables", ['title' => 'Invalid funding'])->assertConflict();
        }
        $work = $this->activeWork();
        $this->fund($work);
        $this->signIn($work->creator);
        $this->postJson("/api/v1/work-items/{$work->id}/deliverables", ['title' => 'Duplicate funding'])->assertConflict();
        $this->assertDatabaseCount('deliverables', 0);
    }

    private function activeWork(): WorkItem
    {
        $work = WorkItem::factory()->assigned()->create(['currency' => 'USD']);
        $this->fund($work);

        return $work;
    }

    private function fund(WorkItem $work, array $overrides = []): void
    {
        Payment::factory()->succeeded()->for($work, 'workItem')->create($overrides + [
            'paid_at' => $work->started_at,
            'currency' => 'USD',
            'provider_name' => 'stripe_sandbox',
            'provider_payment_id' => 'cs_test_'.str_replace('-', '', (string) Str::uuid()),
        ]);
    }

    private function video(): UploadedFile
    {
        // Synthetic MP4 signature for MIME validation. This is not a playable video.
        return UploadedFile::fake()->createWithContent('clip.mp4', hex2bin('00000018667479706d703432000000006d70343269736f6d'));
    }
}
