<?php

namespace Tests\Feature\Verification;

use App\Models\ContentType;
use App\Models\EditorService;
use App\Models\EditorVerificationRequest;
use App\Models\User;
use App\Support\ArchivedPivot;

final class ServiceIntakeTest extends VerificationTestCase
{
    public function test_service_intake_snapshots_the_offer_and_never_confirms_agreement_or_funding(): void
    {
        $creator = $this->signIn(User::factory()->creator()->create());
        $editor = User::factory()->editor()->create();
        $type = ContentType::factory()->create();
        $offer = EditorService::factory()->for($editor, 'editor')->for($type)->create(['status' => 'active', 'price' => '29.50']);
        $url = "/api/v1/services/{$offer->id}/requests";
        $input = ['description' => 'Edit a short clip', 'quantity' => 1];

        $this->postJson($url, $input)->assertForbidden();
        EditorVerificationRequest::factory()->for($editor, 'editor')->create(['status' => 'approved', 'decided_at' => now()]);
        ArchivedPivot::attachOrRestore($editor->contentTypes(), $type->id);
        ArchivedPivot::attachOrRestore($creator->blockedUsers(), $editor->id);
        $this->postJson($url, $input)->assertNotFound();
        ArchivedPivot::archive($creator->blockedUsers(), $editor->id);

        $this->postJson($url, [...$input, 'quantity' => 2])->assertUnprocessable();
        $this->postJson($url, $input + ['editor_id' => $creator->id])->assertUnprocessable();
        $id = $this->postJson($url, $input)->assertCreated()->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.editor_id', $editor->id)->assertJsonPath('data.budget_min', '29.50')->json('data.id');
        $this->getJson('/api/v1/me/service-requests')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->postJson($url, $input)->assertConflict();
        $this->postJson("/api/v1/work-items/{$id}/service-selection")->assertForbidden();

        $this->signIn(User::factory()->editor()->create());
        $this->postJson("/api/v1/work-items/{$id}/service-selection")->assertNotFound();
        $this->signIn($editor);
        $this->getJson('/api/v1/me/service-requests')->assertOk()->assertJsonPath('data.0.id', $id);
        $offer->price = '35.00';
        $offer->save();
        $this->postJson("/api/v1/work-items/{$id}/service-selection")->assertConflict();

        $offer->price = '29.50';
        $offer->save();
        $this->postJson("/api/v1/work-items/{$id}/service-selection")->assertOk()
            ->assertJsonPath('data.status', 'pending_agreement')->assertJsonPath('data.agreed_price', '29.50');
        $this->postJson("/api/v1/work-items/{$id}/service-rejection")->assertConflict();
        $this->assertDatabaseHas('work_items', ['id' => $id, 'agreed_at' => null, 'started_at' => null]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_open_requests_can_be_cancelled_by_the_creator_or_rejected_by_the_editor(): void
    {
        $creator = $this->signIn(User::factory()->creator()->create());
        $offer = EditorService::factory()->create(['status' => 'active']);
        $editor = $offer->editor;
        EditorVerificationRequest::factory()->for($editor, 'editor')->create(['status' => 'approved', 'decided_at' => now()]);
        ArchivedPivot::attachOrRestore($editor->contentTypes(), $offer->content_type_id);
        $url = "/api/v1/services/{$offer->id}/requests";
        $id = $this->postJson($url, ['description' => 'A short clip'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/work-items/{$id}/service-cancellation")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $second = $this->postJson($url, ['description' => 'A different clip'])->assertCreated()->json('data.id');
        $this->signIn($editor);
        $this->postJson("/api/v1/work-items/{$second}/service-rejection")->assertOk()->assertJsonPath('data.status', 'closed');
        $this->assertDatabaseHas('work_items', ['id' => $second, 'status' => 'closed', 'agreed_price' => null]);
        $this->assertDatabaseHas('work_items', ['id' => $id, 'creator_id' => $creator->id, 'status' => 'cancelled']);
    }
}
