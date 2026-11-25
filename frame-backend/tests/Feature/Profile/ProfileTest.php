<?php

namespace Tests\Feature\Profile;

use App\Models\ContentType;
use App\Models\EditingTool;
use App\Models\EditorVerificationRequest;
use App\Models\User;
use App\Support\ArchivedPivot;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Marketplace\MarketplaceTestCase;

final class ProfileTest extends MarketplaceTestCase
{
    public function test_profiles_enforce_ownership_and_role_and_restore_specialties(): void
    {
        $url = '/api/v1/me/profile';
        $this->patchJson($url, ['bio' => 'Private write'])->assertUnauthorized();
        $editor = $this->signIn(User::factory()->editor()->create());
        $other = User::factory()->editor()->create();
        $specialty = ContentType::factory()->create();
        $tool = EditingTool::factory()->create();

        $this->patchJson($url, ['role' => 'creator', 'status' => 'active', 'audience_size' => 10, 'user_id' => $other->id])
            ->assertUnprocessable()->assertJsonStructure(['error' => ['fields' => ['role', 'status', 'audience_size', 'user_id']]]);
        $payload = ['display_name' => 'New editor', 'professional_title' => 'Film editor', 'experience_level' => 'expert',
            'is_open_to_work' => false, 'content_type_ids' => [$specialty->id], 'editing_tool_ids' => [$tool->id]];
        $this->patchJson($url, $payload)->assertOk()->assertJsonPath('data.content_types.0.id', $specialty->id);
        $creation = DB::table('editor_tools')->sole()->created_at;
        $this->patchJson($url, ['content_type_ids' => [], 'editing_tool_ids' => []])->assertOk()
            ->assertJsonCount(0, 'data.content_types');
        $this->assertNotNull(DB::table('editor_tools')->sole()->deleted_at);
        $this->patchJson($url, $payload)->assertOk()->assertJsonPath('data.editing_tools.0.id', $tool->id);
        $this->assertNull(DB::table('user_content_types')->sole()->deleted_at);
        $this->assertSame($creation, DB::table('editor_tools')->sole()->created_at);
        $this->assertDatabaseHas('users', ['id' => $editor->id, 'display_name' => 'New editor']);
        $this->getJson($url)->assertOk()->assertJsonMissingPath('data.email')->assertJsonMissingPath('data.password_hash');

        $creator = $this->signIn(User::factory()->creator()->create());
        $this->patchJson($url, ['experience_level' => 'senior'])->assertUnprocessable();
        $this->patchJson($url, ['creator_niche' => 'Film', 'audience_size' => 9000])->assertOk();
        $this->assertNotNull($creator->fresh()->audience_size_updated_at);
        $this->getJson("/api/v1/creators/{$creator->id}")->assertOk()->assertJsonPath('data.creator_niche', 'Film');
    }

    public function test_professional_links_are_owner_scoped_and_archived(): void
    {
        $owner = $this->signIn(User::factory()->creator()->create());
        $url = '/api/v1/me/profile/links';
        $this->postJson($url, ['url' => 'javascript:alert(1)'])->assertUnprocessable();
        $id = $this->postJson($url, ['url' => 'https://example.test/profile'])->assertCreated()->json('data.id');
        $this->getJson($url)->assertOk()->assertJsonPath('data.0.id', $id);
        $this->signIn(User::factory()->editor()->create());
        $this->patchJson("{$url}/{$id}", ['title' => 'Bad edit'])->assertNotFound();
        $this->signIn($owner);
        $this->patchJson("{$url}/{$id}", ['title' => 'Portfolio'])->assertOk()->assertJsonPath('data.title', 'Portfolio');
        $this->deleteJson("{$url}/{$id}")->assertNoContent();
        $this->assertSoftDeleted('user_links', ['id' => $id]);
    }

    public function test_discovery_filters_and_does_not_equate_account_status_with_approval(): void
    {
        $viewer = $this->signIn(User::factory()->creator()->create());
        $editor = User::factory()->editor()->create(['display_name' => 'Editor Zeta', 'experience_level' => 'expert', 'is_open_to_work' => true]);
        $other = User::factory()->editor()->create();
        $specialty = ContentType::factory()->create();
        $tool = EditingTool::factory()->create();
        ArchivedPivot::attachOrRestore($editor->contentTypes(), $specialty->id);
        ArchivedPivot::attachOrRestore($editor->editingTools(), $tool->id);
        User::factory()->editor()->create(['status' => 'suspended']);
        $url = '/api/v1/editors';
        $this->getJson("{$url}?recorded_approval=1")->assertOk()->assertJsonCount(0, 'data');
        EditorVerificationRequest::factory()->for($editor, 'editor')->create(['status' => 'approved', 'decided_at' => now()->subDay()]);
        $filter = "?q=Zeta&content_type_id={$specialty->id}&editing_tool_id={$tool->id}&experience_level=expert&open_to_work=1&recorded_approval=1";
        $this->assertSame(3, $this->queryCount(fn () => $this->getJson($url.$filter)->assertOk()
            ->assertJsonPath('data.0.id', $editor->id)->assertJsonMissingPath('data.0.email')
            ->assertJsonMissingPath('data.0.editor_verification_requests')));
        $this->getJson("{$url}?per_page=1")->assertOk()->assertJsonPath('meta.has_more', true);
        $this->getJson('/api/v1/content-types')->assertOk()->assertJsonPath('data.0.id', $specialty->id);
        $this->getJson('/api/v1/editing-tools')->assertOk()->assertJsonPath('data.0.id', $tool->id);

        ArchivedPivot::attachOrRestore($editor->blockedUsers(), $viewer->id);
        $this->getJson("{$url}/{$editor->id}")->assertNotFound();
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $other->id);
        ArchivedPivot::archive($editor->blockedUsers(), $viewer->id);
        EditorVerificationRequest::factory()->for($editor, 'editor')->create(['status' => 'rejected', 'decided_at' => now()]);
        $this->getJson("{$url}?recorded_approval=1")->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_portfolio_projects_are_external_owner_scoped_and_soft_deleted(): void
    {
        $editor = $this->signIn(User::factory()->editor()->create());
        $type = ContentType::factory()->create();
        $mine = '/api/v1/me/portfolio-items';
        $payload = ['title' => 'Edited film', 'content_type_id' => $type->id, 'external_url' => 'https://example.test/work'];
        $this->postJson($mine, $payload + ['storage_key' => 'private'])->assertUnprocessable();
        $id = $this->postJson($mine, $payload)->assertCreated()->json('data.id');
        $public = "/api/v1/editors/{$editor->id}/portfolio-items";
        $this->getJson($mine)->assertOk()->assertJsonPath('data.0.id', $id);
        $this->assertSame(2, $this->queryCount(fn () => $this->getJson($public)->assertOk()->assertJsonCount(1, 'data')));
        $this->getJson("{$public}/{$id}")->assertOk()->assertJsonMissingPath('data.storage_key');

        $otherEditor = $this->signIn(User::factory()->editor()->create());
        ArchivedPivot::attachOrRestore($editor->blockedUsers(), $otherEditor->id);
        $this->getJson("{$public}/{$id}")->assertNotFound();
        $this->patchJson("{$mine}/{$id}", ['title' => 'Wrong owner'])->assertNotFound();
        $this->signIn(User::factory()->creator()->create());
        $this->postJson($mine, $payload)->assertForbidden();
        $this->signIn($editor);
        $this->patchJson("{$mine}/{$id}", ['title' => 'Edited again'])->assertOk()->assertJsonPath('data.title', 'Edited again');
        $this->deleteJson("{$mine}/{$id}")->assertNoContent();
        $this->assertSoftDeleted('portfolio_projects', ['id' => $id]);
        $this->getJson("{$public}/{$id}")->assertNotFound();
    }
}
