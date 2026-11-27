<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\Feature\Marketplace\MarketplaceTestCase;

final class AdminDashboardWebTest extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        return 'frame_payments_test_002';
    }

    public function test_guests_are_redirected_to_the_admin_login(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('admin'));
        $this->get('/')->assertOk();
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Admin sign in')
            ->assertSee('id="login-form"', false)
            ->assertSee(asset('admin-assets/css/admin.css'))
            ->assertSee(asset('admin-assets/js/auth.js'));
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
    }

    public function test_non_admin_and_inactive_accounts_cannot_open_protected_pages(): void
    {
        $this->signIn(User::factory()->creator()->create());
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/login')->assertOk()->assertSee('Sign out to switch accounts');

        $this->signIn(User::factory()->admin()->create(['status' => 'suspended']));
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_admin_sign_in_renders_each_protected_section_and_sign_out_revokes_access(): void
    {
        $admin = User::factory()->admin()->create(['name' => '<script>fixture</script>']);
        $this->withHeader('Origin', 'http://localhost');
        $this->get('/api/v1/csrf-cookie')->assertNoContent()->assertCookie('XSRF-TOKEN');
        $this->postJson('/api/v1/sessions', ['email' => $admin->email, 'password' => 'password'])->assertNoContent();
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.role', 'admin');
        $this->get('/admin/login')->assertRedirect(route('admin.dashboard'));
        $this->get('/admin')->assertOk()
            ->assertSee(asset('admin-assets/vendor/chart.umd.js'))
            ->assertSee(asset('admin-assets/js/dashboard.js'));

        $paths = [
            '' => 'Dashboard', 'users' => 'Users', 'verification' => 'Editor Verification',
            'jobs' => 'Jobs', 'applications' => 'Applications', 'services' => 'Services',
            'service-requests' => 'Service Requests', 'projects' => 'Projects',
            'payments' => 'Payments', 'refunds' => 'Refunds', 'payouts' => 'Payouts',
            'posts' => 'Posts', 'comments' => 'Comments', 'reviews' => 'Reviews',
            'content-types' => 'Content Types', 'editing-tools' => 'Editing Tools',
        ];
        foreach ($paths as $path => $title) {
            $this->get(rtrim('/admin/'.$path, '/'))
                ->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertSee('<h1>'.$title.'</h1>', false)
                ->assertSee('aria-current="page"', false)
                ->assertSee($admin->name)
                ->assertDontSee($admin->name, false)
                ->assertDontSee($admin->password_hash);
        }
        $this->get('/admin/unsupported')->assertNotFound();

        $this->deleteJson('/api/v1/sessions/current')->assertNoContent();
        $this->assertGuest('web');
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->get('/admin/payments')->assertRedirect(route('login'));
    }
}
