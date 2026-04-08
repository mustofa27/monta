<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_dashboard_requires_authorized_role(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(403);
    }

    public function test_dashboard_allows_student_role(): void
    {
        $user = User::factory()->create();
        $user->syncRolesBySlug(['mahasiswa']);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Signed in with SSO');
    }

    public function test_role_sync_helper_replaces_existing_roles(): void
    {
        $user = User::factory()->create();

        $user->syncRolesBySlug(['mahasiswa']);
        $this->assertTrue($user->hasRole('mahasiswa'));

        $user->syncRolesBySlug(['koordinator_ta']);

        $this->assertTrue($user->hasRole('koordinator_ta'));
        $this->assertFalse($user->hasRole('mahasiswa'));

        $this->assertSame(1, $user->roles()->count());
        $this->assertTrue(Role::query()->where('slug', 'koordinator_ta')->exists());
    }
}
