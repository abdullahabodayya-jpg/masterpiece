<?php

namespace Tests\Feature\Reviews;

use App\Models\Deliverable;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Marketplace\MarketplaceTestCase;

final class RevisionEvidenceTest extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        return 'frame_payments_test_002';
    }

    public function test_revision_holds_and_failed_replacement_attempts_preserve_the_original_submission(): void
    {
        $disk = Storage::fake('local');
        $work = WorkItem::factory()->assigned()->create(['currency' => 'USD']);
        $payment = Payment::factory()->succeeded()->for($work, 'workItem')->create([
            'currency' => 'USD', 'provider_name' => 'stripe_sandbox',
            'provider_payment_id' => 'cs_test_'.str_replace('-', '', (string) Str::uuid()),
            'paid_at' => $work->started_at,
        ]);
        $this->signIn($work->creator);
        $id = $this->postJson("/api/v1/work-items/{$work->id}/deliverables", ['title' => 'First cut'])
            ->assertCreated()->json('data.id');
        $delivery = "/api/v1/deliverables/{$id}";
        $contents = hex2bin('00000018667479706d703432000000006d70343269736f6d');

        $this->signIn($work->editor);
        $token = $this->post("{$delivery}/submission", ['file' => UploadedFile::fake()->createWithContent('first.mp4', $contents)])
            ->assertOk()->json('data.submission_token');
        $original = Deliverable::findOrFail($id);
        $this->assertSame($contents, $disk->get($original->storage_key));

        $this->signIn($work->creator);
        $this->postJson("{$delivery}/revisions", ['submission_token' => str_repeat('a', 64), 'reason' => 'Wrong evidence'])
            ->assertConflict();
        $revisionId = $this->postJson("{$delivery}/revisions", ['submission_token' => $token, 'reason' => 'Trim the intro'])
            ->assertCreated()->json('data.id');
        $revision = "/api/v1/revisions/{$revisionId}";
        $this->postJson("{$delivery}/acceptance", ['submission_token' => $token])->assertConflict();

        $this->signIn(User::factory()->editor()->create());
        $this->postJson("{$revision}/start")->assertNotFound();
        $this->get("{$delivery}/document")->assertNotFound();
        $this->signIn($work->editor);
        $refund = Refund::factory()->create(['payment_id' => $payment->id, 'status' => 'processing']);
        $this->postJson("{$revision}/start")->assertConflict();
        $refund->status = 'cancelled';
        $refund->save();
        $this->postJson("{$revision}/start")->assertOk()->assertJsonPath('data.status', 'in_progress');

        foreach (["{$revision}/submission" => 404, "{$delivery}/submission" => 409] as $url => $status) {
            $this->post($url, ['file' => UploadedFile::fake()->createWithContent('replacement.mp4', $contents.'new')])
                ->assertStatus($status);
        }
        $this->getJson($delivery)->assertOk()->assertJsonPath('data.submission_token', $token)
            ->assertJsonMissingPath('data.storage_key');
        $this->assertEquals($original->submitted_at, $original->fresh()->submitted_at);
        $this->assertSame($original->storage_key, $original->fresh()->storage_key);
        $this->assertSame($contents, $disk->get($original->storage_key));
        $this->assertSame([$original->storage_key], $disk->allFiles('work-deliverables'));
        $this->assertDatabaseHas('deliverable_revisions', ['id' => $revisionId, 'status' => 'in_progress']);
    }
}
