<?php

namespace Tests\Feature\Verification;

use App\Models\ContentType;
use App\Models\EditorService;
use App\Models\EditorVerificationRequest;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\ArchivedPivot;
use Illuminate\Support\Facades\Event;

final class ProfessionalMarketplaceTest extends VerificationTestCase
{
    public function test_service_publication_and_resumption_follow_current_eligibility(): void
    {
        $editor = $this->signIn(User::factory()->editor()->create());
        $type = ContentType::factory()->create();
        $listing = EditorService::factory()->for($editor, 'editor')->for($type)->create();
        $url = "/api/v1/services/{$listing->id}";
        $this->postJson("{$url}/publication")->assertForbidden();
        $this->approve($editor);
        $this->postJson("{$url}/publication")->assertForbidden();
        ArchivedPivot::attachOrRestore($editor->contentTypes(), $type->id);
        $this->postJson("{$url}/publication")->assertOk()->assertJsonPath('data.status', 'active');
        $this->postJson("{$url}/pause")->assertOk();
        $this->postJson("{$url}/publication")->assertOk();
        ArchivedPivot::archive($editor->contentTypes(), $type->id);
        $this->getJson('/api/v1/services')->assertOk()->assertJsonCount(0, 'data');
        ArchivedPivot::attachOrRestore($editor->contentTypes(), $type->id);
        EditorVerificationRequest::factory()->for($editor, 'editor')->create(['status' => 'rejected', 'decided_at' => now()->addSecond()]);
        $this->getJson('/api/v1/services')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_applications_and_hiring_preserve_pending_agreement_and_competing_proposals(): void
    {
        $creator = User::factory()->creator()->create();
        $type = ContentType::factory()->create();
        $job = WorkItem::factory()->open()->for($creator, 'creator')->for($type)->create(['deadline' => now()->addDay()]);
        $editor = $this->signIn(User::factory()->editor()->create());
        $url = "/api/v1/jobs/{$job->id}/applications";
        $proposal = ['cover_message' => 'I can edit this', 'proposed_price' => '31.25', 'estimated_delivery_days' => 4];
        $this->postJson($url, $proposal)->assertForbidden();
        $this->approve($editor, $type);
        $this->postJson($url, $proposal + ['status' => 'accepted'])->assertUnprocessable();
        $id = $this->postJson($url, $proposal)->assertCreated()->json('data.id');
        $this->postJson($url, $proposal)->assertConflict();
        $this->patchJson("/api/v1/applications/{$id}", ['proposed_price' => '32.50'])->assertOk()
            ->assertJsonPath('data.proposed_price', '32.50');
        $this->postJson("/api/v1/applications/{$id}/selection")->assertForbidden();

        $other = $this->signIn(User::factory()->editor()->create());
        $this->approve($other, $type);
        $this->patchJson("/api/v1/applications/{$id}", ['proposed_price' => '60.00'])->assertNotFound();
        $otherId = $this->postJson($url, $proposal)->assertCreated()->json('data.id');

        $this->signIn($creator);
        $this->postJson("/api/v1/applications/{$id}/shortlisting")->assertOk()->assertJsonPath('data.status', 'shortlisted');
        ArchivedPivot::attachOrRestore($editor->blockedUsers(), $creator->id);
        $this->postJson("/api/v1/applications/{$id}/selection")->assertNotFound();
        ArchivedPivot::archive($editor->blockedUsers(), $creator->id);

        Event::listen('eloquent.saving: '.WorkItem::class, function (WorkItem $work) {
            if ($work->editor_id !== null) {
                throw new \RuntimeException('Synthetic write failure');
            }
        });
        $this->postJson("/api/v1/applications/{$id}/selection")->assertServerError();
        Event::forget('eloquent.saving: '.WorkItem::class);
        $this->assertDatabaseHas('job_applications', ['id' => $id, 'status' => 'shortlisted']);
        $this->assertDatabaseHas('work_items', ['id' => $job->id, 'editor_id' => null, 'status' => 'open']);
        $this->postJson("/api/v1/applications/{$id}/selection")->assertOk()
            ->assertJsonPath('data.status', 'pending_agreement')->assertJsonPath('data.agreed_price', '32.50');
        $this->postJson("/api/v1/applications/{$otherId}/selection")->assertConflict();
        $this->assertDatabaseHas('job_applications', ['id' => $otherId, 'status' => 'pending']);
        $this->assertDatabaseHas('job_applications', ['id' => $id, 'status' => 'accepted']);
        $this->assertDatabaseHas('work_items', ['id' => $job->id, 'editor_id' => $editor->id, 'agreed_at' => null, 'started_at' => null]);
        $this->assertDatabaseCount('payments', 0);
        $this->signIn($editor);
        $this->postJson($url, $proposal)->assertConflict();
    }

    public function test_expired_jobs_and_suspended_editors_cannot_apply(): void
    {
        $type = ContentType::factory()->create();
        $job = WorkItem::factory()->open()->for($type)->create(['deadline' => now()->subMinute()]);
        $editor = $this->signIn(User::factory()->editor()->create());
        $this->approve($editor, $type);
        $url = "/api/v1/jobs/{$job->id}/applications";
        $proposal = ['cover_message' => 'A proposal', 'proposed_price' => '20.00'];
        $this->postJson($url, $proposal)->assertConflict();
        $editor->status = 'suspended';
        $editor->save();
        $this->signIn($editor);
        $this->postJson($url, $proposal)->assertForbidden();
        $this->assertDatabaseCount('job_applications', 0);
    }

    private function approve(User $editor, ?ContentType $type = null): void
    {
        EditorVerificationRequest::factory()->for($editor, 'editor')->create(['status' => 'approved', 'decided_at' => now()]);
        if ($type) {
            ArchivedPivot::attachOrRestore($editor->contentTypes(), $type->id);
        }
    }
}
