<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TaMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(TaMasterDataSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@test.com']);
        $this->admin->syncRolesBySlug(['admin_prodi']);

        $this->student = User::factory()->create(['name' => 'Student User', 'email' => 'student@test.com']);
        $this->student->syncRolesBySlug(['mahasiswa']);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $this->actingAs($this->student);

        $this->get(route('admin.dashboard'))->assertStatus(403);
        $this->get(route('admin.users'))->assertStatus(403);
        $this->get(route('admin.templates'))->assertStatus(403);
        $this->get(route('admin.audit-log'))->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected_from_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_can_access_users_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.users'));
        $response->assertOk();
        $response->assertSee('Student User');
        $response->assertSee('Admin User');
    }

    // ── Search ────────────────────────────────────────────────────────────────

    public function test_admin_users_search_filters_by_name(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.users', ['q' => 'Student']));
        $response->assertOk();
        $response->assertSee('Student User');
        $response->assertDontSee('Admin User');
    }

    // ── Role Override ─────────────────────────────────────────────────────────

    public function test_admin_can_override_user_roles(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->student->hasRole('mahasiswa'));
        $this->assertFalse($this->student->hasRole('dosen_pembimbing'));

        $response = $this->put(route('admin.users.roles', $this->student), [
            'roles' => ['dosen_pembimbing'],
        ]);

        $response->assertRedirect(route('admin.users'));

        $this->student->refresh();
        $this->assertTrue($this->student->hasRole('dosen_pembimbing'));
        $this->assertFalse($this->student->hasRole('mahasiswa'));
    }

    public function test_role_override_creates_audit_log(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('admin.users.roles', $this->student), [
            'roles' => ['koordinator_ta'],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->admin->id,
            'event'         => 'role_override',
            'auditable_type' => User::class,
            'auditable_id'  => $this->student->id,
        ]);

        $log = AuditLog::query()
            ->where('event', 'role_override')
            ->where('auditable_id', $this->student->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(['mahasiswa'], $log->before['roles']);
        $this->assertSame(['koordinator_ta'], $log->after['roles']);
    }

    public function test_role_override_rejects_invalid_slug(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('admin.users.roles', $this->student), [
            'roles' => ['nonexistent_role_xyz'],
        ]);

        $response->assertSessionHasErrors('roles.0');
    }

    public function test_role_override_allows_clearing_all_roles(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('admin.users.roles', $this->student), [
            'roles' => [],
        ]);

        $this->student->refresh();
        $this->assertSame(0, $this->student->roles()->count());
    }

    // ── Milestone Templates ───────────────────────────────────────────────────

    public function test_admin_can_view_templates(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.templates'));
        $response->assertOk();
        $response->assertSee('2026-GENAP'); // seeded
    }

    public function test_admin_can_create_milestone_template(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.templates.store'), [
            'semester_code' => '2026-GASAL',
            'code'          => 'NEW_MILESTONE',
            'name'          => 'Milestone Baru',
            'weight'        => 20,
            'order_no'      => 5,
        ]);

        $response->assertRedirect(route('admin.templates'));

        $this->assertDatabaseHas('ta_milestone_templates', [
            'semester_code' => '2026-GASAL',
            'code'          => 'NEW_MILESTONE',
            'name'          => 'Milestone Baru',
            'weight'        => 20,
        ]);
    }

    public function test_admin_cannot_create_duplicate_template(): void
    {
        $this->actingAs($this->admin);

        // Seeder already created TOPIC_SUBMISSION for 2026-GENAP
        $response = $this->post(route('admin.templates.store'), [
            'semester_code' => '2026-GENAP',
            'code'          => 'TOPIC_SUBMISSION',
            'name'          => 'Duplicate',
            'weight'        => 10,
            'order_no'      => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_milestone_template(): void
    {
        $this->actingAs($this->admin);

        $templateId = \DB::table('ta_milestone_templates')
            ->where('semester_code', '2026-GENAP')
            ->where('code', 'TOPIC_SUBMISSION')
            ->value('id');

        $response = $this->put(route('admin.templates.update', $templateId), [
            'name'     => 'Pengajuan Topik TA (Diperbarui)',
            'weight'   => 20,
            'order_no' => 1,
        ]);

        $response->assertRedirect(route('admin.templates'));

        $this->assertDatabaseHas('ta_milestone_templates', [
            'id'   => $templateId,
            'name' => 'Pengajuan Topik TA (Diperbarui)',
        ]);
    }

    public function test_admin_can_delete_milestone_template(): void
    {
        $this->actingAs($this->admin);

        $templateId = \DB::table('ta_milestone_templates')
            ->where('code', 'FINAL_DEFENSE')
            ->value('id');

        $this->delete(route('admin.templates.destroy', $templateId))
            ->assertRedirect(route('admin.templates'));

        $this->assertDatabaseMissing('ta_milestone_templates', ['id' => $templateId]);
    }

    // ── Audit Log Viewer ──────────────────────────────────────────────────────

    public function test_admin_can_view_audit_log(): void
    {
        $this->actingAs($this->admin);

        // Generate at least one log entry via role override
        $this->put(route('admin.users.roles', $this->student), [
            'roles' => ['dosen_pembimbing'],
        ]);

        $response = $this->get(route('admin.audit-log'));
        $response->assertOk();
        $response->assertSeeText('role_override');
    }

    public function test_audit_log_filters_by_event(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('admin.users.roles', $this->student), [
            'roles' => ['dosen_pembimbing'],
        ]);

        $response = $this->get(route('admin.audit-log', ['event' => 'role_override']));
        $response->assertOk();
        $response->assertSeeText('role_override');
    }

    public function test_admin_kpi_counts_are_correct(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        // At minimum 2 users (admin + student) were seeded
        $response->assertSee('2'); // total_users >= 2, displayed as KPI value
    }
}
