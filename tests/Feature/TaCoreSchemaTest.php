<?php

namespace Tests\Feature;

use App\Models\TaProject;
use App\Models\User;
use Database\Seeders\TaMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaCoreSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ta_core_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('ta_projects'));
        $this->assertTrue(Schema::hasTable('ta_milestones'));
        $this->assertTrue(Schema::hasTable('ta_supervisions'));
        $this->assertTrue(Schema::hasTable('ta_documents'));
        $this->assertTrue(Schema::hasTable('ta_reviews'));
        $this->assertTrue(Schema::hasTable('ta_schedules'));
        $this->assertTrue(Schema::hasTable('ta_milestone_templates'));
        $this->assertTrue(Schema::hasTable('ta_status_catalogs'));
        $this->assertTrue(Schema::hasTable('audit_logs'));

        $this->assertTrue(Schema::hasColumns('ta_projects', [
            'student_user_id',
            'supervisor_user_id',
            'title',
            'status',
        ]));
    }

    public function test_ta_master_data_seeder_is_idempotent(): void
    {
        $this->seed(TaMasterDataSeeder::class);
        $this->seed(TaMasterDataSeeder::class);

        $templateCount = DB::table('ta_milestone_templates')
            ->where('semester_code', '2026-GENAP')
            ->count();

        $projectStatusCount = DB::table('ta_status_catalogs')
            ->where('domain', 'ta_project.status')
            ->count();

        $this->assertSame(4, $templateCount);
        $this->assertSame(7, $projectStatusCount);
    }

    public function test_status_change_creates_audit_log(): void
    {
        $student = User::factory()->create();
        $supervisor = User::factory()->create();

        $project = TaProject::query()->create([
            'student_user_id' => $student->id,
            'supervisor_user_id' => $supervisor->id,
            'title' => 'Sistem Monitoring TA',
            'status' => 'draft',
        ]);

        $project->update(['status' => 'submitted']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'ta_project.status_changed',
            'auditable_type' => TaProject::class,
            'auditable_id' => $project->id,
        ]);
    }
}
