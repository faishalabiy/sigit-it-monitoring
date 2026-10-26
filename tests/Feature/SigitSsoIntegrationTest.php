<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SigitSsoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.sso', [
            'base_url' => 'https://nexid.example.test',
            'client_id' => 'sigit-test-client',
            'client_secret' => 'dummy-secret',
            'callback_url' => 'https://sigit.example.test/sso/callback',
            'after_login_url' => '/',
            'logout_url' => 'https://nexid.example.test/sso/logout',
            'after_logout_url' => 'https://sigit.example.test/login',
        ]);
        config()->set('iams.admin_user_ids', '');
    }

    public function test_login_page_has_sso_button_and_redirect_saves_state(): void
    {
        $this->get('/login')->assertOk()->assertSee('Login dengan SSO NEXID');
        $response = $this->get(route('sso.login'));
        $response->assertRedirectContains('https://nexid.example.test/sso/authorize?');
        $location = $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertEquals('sigit-test-client', $query['client_id']);
        $this->assertEquals('https://sigit.example.test/sso/callback', $query['redirect_uri']);
        $this->assertSame(session('sso_state'), $query['state']);
    }

    public function test_invalid_state_is_rejected_without_token_verification(): void
    {
        Http::fake();
        $this->withSession(['sso_state' => 'correct'])->get(route('sso.callback', [
            'state' => 'wrong', 'sso_token' => 'fake-token',
        ]))->assertRedirect(route('login'))->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_verified_sso_user_is_linked_to_existing_email_without_changing_role(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.test']);
        $role = Role::create(['name' => 'Viewer', 'slug' => 'viewer', 'permissions' => ['assets.view']]);
        $user->roles()->attach($role);
        Http::fake(['*/api/sso/verify-token' => Http::response([
            'user' => [
                'name' => 'Pegawai NEXID', 'email' => 'staff@example.test',
                'sso_id' => 'nexid-555', 'employee_id' => 'EMP001', 'roles' => ['super-admin'],
            ],
        ])]);
        $this->withSession(['sso_state' => 'valid'])->get(route('sso.callback', [
            'sso_token' => 'one-time-token', 'state' => 'valid',
        ]))->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $user->refresh();
        $this->assertSame('nexid-555', $user->sso_id);
        $this->assertSame('EMP001', $user->employee_id);
        $this->assertTrue($user->hasPermission('assets.view'));
        $this->assertFalse($user->hasPermission('roles.manage'));
        $this->assertTrue((bool) session('sso_authenticated'));
    }

    public function test_new_sso_user_is_created_without_privileged_role(): void
    {
        Http::fake(['*/api/sso/verify-token' => Http::response(['user' => [
            'name' => 'Pegawai Baru', 'email' => 'new@example.test',
            'sso_id' => 'new-sso-id', 'roles' => ['super-admin'],
        ]])]);
        $this->withSession(['sso_state' => 'good'])->get(route('sso.callback', [
            'sso_token' => 'valid', 'state' => 'good',
        ]))->assertRedirect('/');
        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertEquals(0, $user->roles()->count());
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_logout_from_sso_ends_local_session_and_redirects_to_nexid(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['sso_authenticated' => true])
            ->post(route('logout'))->assertRedirectContains('/sso/logout?');
        $this->assertGuest();
    }
}
