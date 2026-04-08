<?php

namespace Tests\Feature;

use App\Models\TaProject;
use App\Models\TaSupervision;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TaMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            TaMasterDataSeeder::class,
        ]);
    }

    public function test_student_can_create_topic_draft_with_seeded_milestones(): void
    {
        Storage::fake('local');

        $student = User::factory()->create();
        $student->syncRolesBySlug(['mahasiswa']);

        $response = $this->actingAs($student)->post(route('ta-projects.store'), [
            'title' => 'Sistem Monitoring Progres Tugas Akhir',
            'abstract' => 'Platform monitoring untuk mahasiswa dan pembimbing.',
            'study_program' => 'Teknik Informatika',
            'topic_attachments' => [
                UploadedFile::fake()->create('proposal-topik.pdf', 300),
            ],
        ]);

        $response->assertRedirect(route('dashboard'));

        $project = TaProject::query()->where('student_user_id', $student->id)->first();
        $this->assertNotNull($project);
        $this->assertSame('draft', $project->status);
        $this->assertSame(4, $project->milestones()->count());
        $this->assertDatabaseHas('ta_documents', [
            'ta_project_id' => $project->id,
            'uploaded_by_user_id' => $student->id,
            'document_type' => 'topic_proposal',
            'original_name' => 'proposal-topik.pdf',
        ]);
    }

    public function test_student_can_submit_topic_for_review(): void
    {
        $student = User::factory()->create();
        $student->syncRolesBySlug(['mahasiswa']);

        $project = TaProject::query()->create([
            'student_user_id' => $student->id,
            'title' => 'Topik Draft',
            'semester_code' => '2026-GENAP',
            'status' => 'draft',
        ]);

        $project->milestones()->create([
            'code' => 'TOPIC_SUBMISSION',
            'name' => 'Pengajuan Topik',
            'weight' => 15,
            'status' => 'not_started',
        ]);

        $response = $this->actingAs($student)->post(route('ta-projects.submit', $project));

        $response->assertRedirect(route('dashboard'));

        $project->refresh();
        $this->assertSame('submitted', $project->status);
        $this->assertSame('submitted', $project->milestones()->where('code', 'TOPIC_SUBMISSION')->value('status'));
    }

    public function test_supervisor_can_review_topic_and_assign_self_if_missing(): void
    {
        $student = User::factory()->create();
        $student->syncRolesBySlug(['mahasiswa']);

        $supervisor = User::factory()->create();
        $supervisor->syncRolesBySlug(['dosen_pembimbing']);

        $project = TaProject::query()->create([
            'student_user_id' => $student->id,
            'title' => 'Topik Submitted',
            'semester_code' => '2026-GENAP',
            'status' => 'submitted',
        ]);

        $project->milestones()->create([
            'code' => 'TOPIC_SUBMISSION',
            'name' => 'Pengajuan Topik',
            'weight' => 15,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($supervisor)->post(route('ta-projects.review', $project), [
            'decision' => 'approved',
            'note' => 'Lanjut ke tahap berikutnya.',
        ]);

        $response->assertRedirect(route('dashboard'));

        $project->refresh();
        $this->assertSame('approved', $project->status);
        $this->assertSame($supervisor->id, $project->supervisor_user_id);
        $this->assertSame('approved', $project->milestones()->where('code', 'TOPIC_SUBMISSION')->value('status'));
        $this->assertDatabaseHas('ta_reviews', [
            'ta_project_id' => $project->id,
            'reviewer_user_id' => $supervisor->id,
            'decision' => 'approved',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'ta_project_reviewed',
            'auditable_id' => $project->id,
        ]);
    }

    public function test_student_can_submit_supervision_log_and_supervisor_can_accept_it(): void
    {
        Storage::fake('local');

        $student = User::factory()->create();
        $student->syncRolesBySlug(['mahasiswa']);

        $supervisor = User::factory()->create();
        $supervisor->syncRolesBySlug(['dosen_pembimbing']);

        $project = TaProject::query()->create([
            'student_user_id' => $student->id,
            'supervisor_user_id' => $supervisor->id,
            'title' => 'Topik Aktif',
            'semester_code' => '2026-GENAP',
            'status' => 'approved',
        ]);

        $project->milestones()->create([
            'code' => 'SUPERVISION_LOG',
            'name' => 'Bimbingan Rutin',
            'weight' => 30,
            'status' => 'not_started',
        ]);

        $submitResponse = $this->actingAs($student)->post(route('ta-supervisions.store', $project), [
            'meeting_date' => '2026-04-08',
            'summary' => 'Diskusi metodologi penelitian.',
            'evidence_file' => UploadedFile::fake()->create('bukti-bimbingan.pdf', 220),
        ]);

        $submitResponse->assertRedirect(route('dashboard'));

        $supervision = TaSupervision::query()->where('ta_project_id', $project->id)->first();
        $this->assertNotNull($supervision);
        $this->assertSame('submitted', $supervision->status);
        $this->assertNotNull($supervision->ta_document_id);
        $this->assertSame('in_progress', $project->milestones()->where('code', 'SUPERVISION_LOG')->value('status'));
        $this->assertDatabaseHas('ta_documents', [
            'id' => $supervision->ta_document_id,
            'ta_project_id' => $project->id,
            'document_type' => 'supervision_evidence',
            'original_name' => 'bukti-bimbingan.pdf',
        ]);

        $reviewResponse = $this->actingAs($supervisor)->post(route('ta-supervisions.review', $supervision), [
            'status' => 'accepted',
            'supervisor_note' => 'Lanjutkan penyusunan BAB 2.',
        ]);

        $reviewResponse->assertRedirect(route('dashboard'));

        $supervision->refresh();
        $this->assertSame('accepted', $supervision->status);
        $this->assertSame('Lanjutkan penyusunan BAB 2.', $supervision->supervisor_note);
    }
}
