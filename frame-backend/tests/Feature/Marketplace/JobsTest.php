<?php

namespace Tests\Feature\Marketplace;

use App\Models\ContentType;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\ArchivedPivot;

final class JobsTest extends MarketplaceTestCase
{
    public function test_jobs_enforce_draft_ownership_and_lifecycle(): void
    {
        $creator = $this->signIn(User::factory()->creator()->create());
        $type = ContentType::factory()->create();
        $input = ['title' => 'Video editing', 'description' => 'Cut a short video', 'content_type_id' => $type->id,
            'budget_min' => '20.00', 'budget_max' => '40.00', 'deadline' => now()->addWeek()->toISOString()];
        $url = '/api/v1/jobs';
        $this->postJson($url, $input + ['status' => 'open', 'editor_id' => $creator->id])->assertUnprocessable();
        $this->postJson($url, [...$input, 'budget_min' => '20.001'])->assertUnprocessable();
        $this->postJson($url, [...$input, 'budget_max' => '1.00'])->assertUnprocessable();
        $this->assertDatabaseCount('work_items', 0);

        $id = $this->postJson($url, $input)->assertCreated()->assertJsonPath('data.creator_id', $creator->id)
            ->assertJsonPath('data.status', 'draft')->json('data.id');
        $this->patchJson("{$url}/{$id}", ['budget_max' => '10.00'])->assertUnprocessable();
        $this->patchJson("{$url}/{$id}", ['title' => 'Updated'])->assertOk()->assertJsonPath('data.title', 'Updated');
        $this->getJson('/api/v1/me/jobs')->assertOk()->assertJsonPath('data.0.id', $id);

        $this->signIn(User::factory()->creator()->create());
        $this->getJson("{$url}/{$id}")->assertNotFound();
        $this->patchJson("{$url}/{$id}", ['title' => 'Wrong owner'])->assertNotFound();
        $this->postJson("{$url}/{$id}/publication")->assertNotFound();
        $this->signIn(User::factory()->editor()->create());
        $this->postJson($url, $input)->assertForbidden();
        $this->signIn($creator);
        $this->postJson("{$url}/{$id}/publication")->assertOk()->assertJsonPath('data.status', 'open');
        $this->patchJson("{$url}/{$id}", ['title' => 'Cannot edit'])->assertConflict();
        $this->postJson("{$url}/{$id}/closure")->assertOk()->assertJsonPath('data.status', 'closed');
        $this->postJson("{$url}/{$id}/publication")->assertConflict();
        $this->assertDatabaseHas('work_items', ['id' => $id, 'status' => 'closed', 'editor_id' => null]);

        $draft = $this->postJson($url, $input)->assertCreated()->json('data.id');
        $this->postJson("{$url}/{$draft}/cancellation")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('work_items', ['id' => $draft, 'status' => 'cancelled']);
    }

    public function test_open_job_filters_blocks_deadlines_and_pagination(): void
    {
        $viewer = $this->signIn(User::factory()->editor()->create());
        $creator = User::factory()->creator()->create();
        $type = ContentType::factory()->create();
        $visible = WorkItem::factory()->open()->for($creator, 'creator')->for($type)->create(['deadline' => now()->addWeek()]);
        WorkItem::factory()->open()->for($type)->create(['deadline' => now()->subDay()]);
        WorkItem::factory()->open()->for($type)->create(['deadline' => now()->addWeek(), 'budget_max' => '5.00']);
        WorkItem::factory()->for($type)->create(['deadline' => now()->addWeek()]);
        $url = '/api/v1/jobs';
        $filters = "?content_type_id={$type->id}&budget_at_least=20.00&per_page=1";

        $this->assertSame(1, $this->queryCount(fn () => $this->getJson($url.$filters)->assertOk()
            ->assertJsonPath('data.0.id', $visible->id)->assertJsonPath('meta.has_more', false)));
        $this->getJson("{$url}/{$visible->id}")->assertOk();
        $this->getJson("{$url}?per_page=101")->assertBadRequest();
        ArchivedPivot::attachOrRestore($viewer->blockedUsers(), $creator->id);
        $this->getJson("{$url}/{$visible->id}")->assertNotFound();
        $this->getJson($url.$filters)->assertOk()->assertJsonCount(0, 'data');
    }
}
