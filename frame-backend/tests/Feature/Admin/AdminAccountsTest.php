<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Marketplace\MarketplaceTestCase;

final class AdminAccountsTest extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        return 'frame_payments_test_002';
    }

    public function test_dashboard_requires_an_active_admin(): void
    {
        $this->get('/api/v1/admin/dashboard')->assertUnauthorized();
        foreach ([User::factory()->creator(), User::factory()->editor(), User::factory()->support(),
            User::factory()->admin()->state(['status' => 'suspended'])] as $factory) {
            $this->signIn($factory->create());
            $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
        }
        $target = User::factory()->creator()->create();
        $this->signIn(User::factory()->editor()->create());
        $this->getJson("/api/v1/admin/users/{$target->id}")->assertForbidden();
        $this->getJson('/api/v1/admin/users/'.Str::uuid())->assertForbidden();

        $this->signIn(User::factory()->admin()->create());
        $this->getJson('/api/v1/admin/dashboard')->assertOk()
            ->assertJsonStructure(['total_users', 'monthly_registrations', 'financial_summary']);
    }

    public function test_admin_creates_and_updates_public_accounts_without_replacing_session(): void
    {
        $admin = User::factory()->admin()->create();
        $this->withHeader('Origin', 'http://localhost');
        $this->postJson('/api/v1/sessions', ['email' => $admin->email, 'password' => 'password'])->assertNoContent();
        $url = '/api/v1/admin/users';
        $input = ['name' => 'Admin fixture', 'email' => 'admin-created@example.test', 'role' => 'editor',
            'password' => 'synthetic-password-123', 'password_confirmation' => 'synthetic-password-123'];
        $this->postJson($url, [...$input, 'role' => 'admin'])->assertUnprocessable();
        $this->postJson($url, $input + ['status' => 'active'])->assertUnprocessable();
        $id = $this->postJson($url, $input)->assertCreated()->assertJsonPath('data.status', 'pending_verification')
            ->assertJsonMissingPath('data.password_hash')->json('data.id');
        $this->assertAuthenticatedAs($admin, 'web');
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $admin->id);
        $this->assertTrue(Hash::check($input['password'], User::findOrFail($id)->password_hash));
        $this->getJson($url.'?search=fixture&role=editor&status=pending_verification&per_page=1')
            ->assertOk()->assertJsonPath('data.0.id', $id)->assertJsonPath('per_page', 1);
        $this->getJson($url.'?per_page=101')->assertUnprocessable();
        $this->patchJson("{$url}/{$id}", ['role' => 'admin', 'password' => 'new-secret'])->assertUnprocessable();
        $this->patchJson("{$url}/{$admin->id}", ['name' => 'Protected'])->assertForbidden();

        User::findOrFail($id)->forceFill(['email_verified_at' => now()])->save();
        $this->patchJson("{$url}/{$id}", ['email' => 'renamed@example.test'])->assertOk()->assertJsonPath('data.email', 'renamed@example.test');
        $this->assertDatabaseHas('users', ['id' => $id, 'email_verified_at' => null, 'role' => 'editor']);
        $this->getJson("{$url}/{$id}")->assertOk()->assertJsonMissingPath('data.remember_token')->assertJsonMissingPath('data.profile_image_key');
        $this->deleteJson("{$url}/{$id}")->assertMethodNotAllowed();
    }

    public function test_suspension_validates_transitions_and_blocks_existing_authentication(): void
    {
        $admin = $this->signIn(User::factory()->admin()->create());
        $creator = User::factory()->creator()->create(['remember_token' => 'synthetic-remember-token']);
        $pending = User::factory()->creator()->create(['status' => 'pending_verification']);
        $url = "/api/v1/admin/users/{$creator->id}/status";
        $this->patchJson("/api/v1/admin/users/{$admin->id}/status", ['status' => 'suspended', 'reason' => 'Fixture'])->assertForbidden();
        $this->patchJson("/api/v1/admin/users/{$pending->id}/status", ['status' => 'active', 'reason' => 'Fixture'])->assertConflict();
        $this->patchJson($url, ['status' => 'closed', 'reason' => 'Fixture'])->assertUnprocessable();

        DB::table('sessions')->insert(['id' => 'admin-test-existing-session', 'user_id' => $creator->id, 'payload' => '', 'last_activity' => time()]);
        config(['session.driver' => 'database', 'session.connection' => DB::connection()->getName()]);
        $this->patchJson($url, ['status' => 'suspended', 'reason' => 'Fixture'])->assertOk()->assertJsonPath('data.status', 'suspended');
        $this->assertDatabaseMissing('sessions', ['id' => 'admin-test-existing-session']);
        $this->assertDatabaseHas('users', ['id' => $creator->id, 'remember_token' => null]);

        $this->signIn($creator->fresh());
        $this->getJson('/api/v1/me')->assertForbidden();
        $this->getJson('/api/v1/work-items')->assertForbidden();
        $this->signIn($admin);
        $this->patchJson($url, ['status' => 'suspended', 'reason' => 'Already suspended'])->assertConflict();
        $this->patchJson($url, ['status' => 'active', 'reason' => 'Fixture'])->assertOk();
        $this->signIn($creator->fresh());
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.status', 'active');
    }

    public function test_public_registration_still_uses_the_shared_pending_account_flow(): void
    {
        $this->withHeader('Origin', 'http://localhost')->postJson('/api/v1/registrations', [
            'name' => 'Public fixture', 'email' => 'public-registration@example.test', 'role' => 'creator',
            'password' => 'synthetic-password-123', 'password_confirmation' => 'synthetic-password-123',
        ])->assertCreated()->assertJsonPath('data.status', 'pending_verification')->assertJsonMissingPath('data.password_hash');
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.role', 'creator');
        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }
}
