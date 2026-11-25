<?php

namespace Tests\Feature\Marketplace;

use App\Models\ContentType;
use App\Models\EditorService;
use App\Models\EditorVerificationRequest;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\ArchivedPivot;

final class LifecycleTest extends MarketplaceTestCase
{
    public function test_service_drafts_are_private_and_active_listings_require_recorded_approval(): void
    {
        $editor = $this->signIn(User::factory()->editor()->create());
        $type = ContentType::factory()->create();
        $url = '/api/v1/services';
        $payload = ['title' => 'Short video', 'description' => 'Editing package', 'price' => '35.00', 'content_type_id' => $type->id];
        $this->postJson($url, [...$payload, 'price' => '0'])->assertUnprocessable();
        $this->postJson($url, $payload + ['status' => 'active'])->assertUnprocessable();
        $id = $this->postJson($url, $payload)->assertCreated()->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.price', '35.00')->json('data.id');
        $this->patchJson("{$url}/{$id}", ['title' => 'Updated'])->assertOk()->assertJsonPath('data.title', 'Updated');
        $this->getJson('/api/v1/me/services')->assertOk()->assertJsonPath('data.0.id', $id);

        $creator = $this->signIn(User::factory()->creator()->create());
        $this->postJson($url, $payload)->assertForbidden();
        $this->getJson("{$url}/{$id}")->assertNotFound();
        $active = EditorService::factory()->for($editor, 'editor')->for($type)->create(['status' => 'active']);
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data');
        EditorVerificationRequest::factory()->for($editor, 'editor')->create(['status' => 'approved', 'decided_at' => now()]);
        $this->assertSame(1, $this->queryCount(fn () => $this->getJson("{$url}?content_type_id={$type->id}")
            ->assertOk()->assertJsonPath('data.0.id', $active->id)));
        $this->getJson("/api/v1/editors/{$editor->id}/services")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("{$url}/{$active->id}")->assertOk();
        ArchivedPivot::attachOrRestore($editor->blockedUsers(), $creator->id);
        $this->getJson("{$url}/{$active->id}")->assertNotFound();

        $this->signIn($editor);
        $this->patchJson("{$url}/{$active->id}", ['title' => 'Unversioned'])->assertConflict();
        $this->postJson("{$url}/{$active->id}/pause")->assertOk()->assertJsonPath('data.status', 'paused');
        $this->postJson("{$url}/{$active->id}/pause")->assertConflict();
        $this->deleteJson("{$url}/{$active->id}")->assertNoContent();
        $this->assertSoftDeleted('editor_services', ['id' => $active->id]);
        $this->assertDatabaseHas('editor_services', ['id' => $id, 'editor_id' => $editor->id, 'status' => 'draft']);
    }

    public function test_existing_applications_are_private_and_only_pending_states_can_be_closed(): void
    {
        $application = JobApplication::factory()->create();
        $job = $application->workItem;
        $other = JobApplication::factory()->for($job)->create(['status' => 'shortlisted']);
        $url = "/api/v1/applications/{$application->id}";
        $jobUrl = "/api/v1/jobs/{$job->id}/applications";

        $this->signIn(User::factory()->creator()->create());
        $this->getJson($url)->assertNotFound();
        $this->getJson($jobUrl)->assertNotFound();
        $this->postJson("{$url}/rejection")->assertNotFound();

        $this->signIn($application->editor);
        $this->getJson($url)->assertOk()->assertJsonPath('data.id', $application->id);
        $this->assertSame(1, $this->queryCount(fn () => $this->getJson('/api/v1/me/applications')
            ->assertOk()->assertJsonCount(1, 'data')));
        $this->postJson("{$url}/rejection")->assertForbidden();
        $this->postJson("{$url}/withdrawal")->assertOk()->assertJsonPath('data.status', 'withdrawn');
        $this->postJson("{$url}/withdrawal")->assertConflict();

        $this->signIn($job->creator);
        $this->assertSame(2, $this->queryCount(fn () => $this->getJson($jobUrl)->assertOk()->assertJsonCount(2, 'data')));
        ArchivedPivot::attachOrRestore($other->editor->blockedUsers(), $job->creator_id);
        $this->getJson("/api/v1/applications/{$other->id}")->assertNotFound();
        ArchivedPivot::archive($other->editor->blockedUsers(), $job->creator_id);
        $this->postJson("/api/v1/applications/{$other->id}/rejection")->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertDatabaseHas('job_applications', ['id' => $application->id, 'status' => 'withdrawn']);
        $this->assertDatabaseHas('job_applications', ['id' => $other->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('work_items', ['id' => $job->id, 'editor_id' => null]);
    }

    public function test_work_reads_are_participant_only(): void
    {
        $work = WorkItem::factory()->assigned()->create();
        $url = "/api/v1/work-items/{$work->id}";
        $this->getJson($url)->assertUnauthorized();
        $this->signIn(User::factory()->editor()->create());
        $this->getJson($url)->assertNotFound();
        $this->getJson('/api/v1/work-items')->assertOk()->assertJsonCount(0, 'data');
        $this->signIn($work->editor);
        $this->getJson($url)->assertOk()->assertJsonPath('data.editor_id', $work->editor_id);
        $this->signIn($work->creator);
        $this->getJson('/api/v1/work-items')->assertOk()->assertJsonPath('data.0.id', $work->id);
    }
}
