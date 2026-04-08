<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config()->set('services.sso.base_url', 'https://sso.poltera.test');
        config()->set('services.sso.client_id', 'client-123');
        config()->set('services.sso.client_secret', 'secret-123');
        config()->set('services.sso.redirect_uri', 'http://localhost/auth/sso/callback');
        config()->set('services.sso.scope', 'openid profile email');
    }

    public function test_redirect_starts_authorization_code_flow_with_state(): void
    {
        $response = $this->get(route('sso.redirect'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertNotNull($location);
        $this->assertStringContainsString('https://sso.poltera.test/oauth/authorize', $location);
        $this->assertStringContainsString('response_type=code', $location);
        $this->assertStringContainsString('client_id=client-123', $location);
        $this->assertStringContainsString('redirect_uri=http%3A%2F%2Flocalhost%2Fauth%2Fsso%2Fcallback', $location);
        $this->assertStringContainsString('scope=openid+profile+email', $location);

        $this->assertNotEmpty(session('sso.oauth_state'));
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $this->withSession(['sso.oauth_state' => 'expected-state']);

        $response = $this->get(route('sso.callback', [
            'code' => 'code-abc',
            'state' => 'wrong-state',
        ]));

        $response->assertStatus(403);
        $response->assertSeeText('Invalid OAuth state.');
    }

    public function test_callback_creates_or_updates_local_user_from_sso_profile(): void
    {
        Http::fake([
            'https://sso.poltera.test/oauth/token' => Http::response([
                'access_token' => 'access-1',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'refresh-1',
                'scope' => 'openid profile email',
            ], 200),
            'https://sso.poltera.test/oauth/userinfo' => Http::response([
                'sub' => '42',
                'name' => 'SSO User',
                'email' => 'sso@example.com',
                'user_type' => 'employee',
                'employee_type' => 'lecturer',
                'organization' => [
                    'department' => 'Engineering',
                    'program_study' => 'Informatics',
                    'support_unit' => null,
                ],
            ], 200),
        ]);

        $this->withSession(['sso.oauth_state' => 'valid-state']);

        $response = $this->get(route('sso.callback', [
            'code' => 'code-abc',
            'state' => 'valid-state',
        ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'sso_sub' => '42',
            'email' => 'sso@example.com',
            'sso_user_type' => 'employee',
            'sso_employee_type' => 'lecturer',
        ]);

        $user = User::query()->where('sso_sub', '42')->first();
        $this->assertNotNull($user);
        $this->assertSame('SSO User', $user->name);
        $this->assertIsArray($user->sso_profile);
        $this->assertTrue($user->hasRole('dosen_pembimbing'));
    }

    public function test_backchannel_notification_rejects_invalid_signature(): void
    {
        $response = $this->postJson(route('sso.logout-notification'), [
            'event' => 'sso.single_logout',
            'user_id' => 42,
        ], [
            'X-SSO-Signature' => 'invalid',
        ]);

        $response->assertStatus(401);
        $response->assertExactJson(['message' => 'Invalid signature.']);
    }

    public function test_callback_updates_existing_user_when_email_already_exists(): void
    {
        $existing = User::query()->create([
            'name' => 'Local Account',
            'email' => 'existing@example.com',
            'password' => 'secret-password',
        ]);

        Http::fake([
            'https://sso.poltera.test/oauth/token' => Http::response([
                'access_token' => 'access-existing',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'refresh-existing',
            ], 200),
            'https://sso.poltera.test/oauth/userinfo' => Http::response([
                'sub' => 'sso-existing-123',
                'name' => 'Updated From SSO',
                'email' => 'EXISTING@example.com',
                'user_type' => 'employee',
                'employee_type' => 'staff',
            ], 200),
        ]);

        $this->withSession(['sso.oauth_state' => 'state-existing']);

        $response = $this->get(route('sso.callback', [
            'code' => 'code-existing',
            'state' => 'state-existing',
        ]));

        $response->assertRedirect(route('dashboard'));

        $existing->refresh();

        $this->assertSame('Updated From SSO', $existing->name);
        $this->assertSame('existing@example.com', $existing->email);
        $this->assertSame('sso-existing-123', $existing->sso_sub);
        $this->assertSame('employee', $existing->sso_user_type);
        $this->assertSame('staff', $existing->sso_employee_type);
        $this->assertTrue($existing->hasRole('koordinator_ta'));
        $this->assertAuthenticatedAs($existing);
    }

    public function test_callback_maps_super_admin_sso_role_to_admin_prodi(): void
    {
        Http::fake([
            'https://sso.poltera.test/oauth/token' => Http::response([
                'access_token' => 'access-admin',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'refresh-admin',
            ], 200),
            'https://sso.poltera.test/oauth/userinfo' => Http::response([
                'sub' => 'admin-001',
                'name' => 'Super Admin User',
                'email' => 'superadmin@example.com',
                'user_type' => 'employee',
                'employee_type' => 'staff',
                'roles' => ['Super Admin'],
            ], 200),
        ]);

        $this->withSession(['sso.oauth_state' => 'state-admin']);

        $response = $this->get(route('sso.callback', [
            'code' => 'code-admin',
            'state' => 'state-admin',
        ]));

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->where('sso_sub', 'admin-001')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('admin_prodi'));
    }
}
