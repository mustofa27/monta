<?php

namespace App\Http\Controllers;

use App\Models\TaMilestone;
use App\Models\TaProject;
use App\Models\TaReview;
use App\Services\TaProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaProjectController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);

        return view('ta-projects.create', [
            'project' => new TaProject(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole('mahasiswa'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'study_program' => ['nullable', 'string', 'max:255'],
        ]);

        $project = DB::transaction(function () use ($user, $validated) {
            $project = TaProject::query()->create([
                'student_user_id' => $user->id,
                'title' => $validated['title'],
                'abstract' => $validated['abstract'] ?? null,
                'study_program' => $validated['study_program'] ?? null,
                'semester_code' => $this->resolveSemesterCode(),
                'status' => 'draft',
            ]);

            $this->seedMilestones($project);

            return $project;
        });

        return redirect()->route('dashboard')->with('status', 'Topik tugas akhir berhasil dibuat.');
    }

    public function edit(Request $request, TaProject $project): View
    {
        $this->authorizeStudentProject($request, $project);

        return view('ta-projects.edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, TaProject $project): RedirectResponse
    {
        $this->authorizeStudentProject($request, $project);

        abort_if($project->status !== 'draft' && $project->status !== 'revision_required', 422, 'Topik ini tidak dapat diubah pada status saat ini.');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'study_program' => ['nullable', 'string', 'max:255'],
        ]);

        $project->update($validated);

        return redirect()->route('dashboard')->with('status', 'Topik tugas akhir berhasil diperbarui.');
    }

    public function submit(Request $request, TaProject $project): RedirectResponse
    {
        $this->authorizeStudentProject($request, $project);

        abort_if($project->status !== 'draft' && $project->status !== 'revision_required', 422, 'Topik ini tidak dapat diajukan lagi.');

        $project->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $project->milestones()->where('code', 'TOPIC_SUBMISSION')->update([
            'status' => 'submitted',
        ]);

        return redirect()->route('dashboard')->with('status', 'Topik tugas akhir berhasil diajukan untuk review.');
    }

    public function review(Request $request, TaProject $project): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['dosen_pembimbing', 'koordinator_ta', 'admin_prodi']), 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected,revision_required'],
            'note' => ['nullable', 'string'],
        ]);

        TaReview::query()->create([
            'ta_project_id' => $project->id,
            'reviewer_user_id' => $user->id,
            'decision' => $validated['decision'],
            'note' => $validated['note'] ?? null,
        ]);

        $project->update([
            'status' => $validated['decision'],
            'supervisor_user_id' => $project->supervisor_user_id ?? $user->id,
        ]);

        $project->milestones()->where('code', 'TOPIC_SUBMISSION')->update([
            'status' => $validated['decision'] === 'approved' ? 'approved' : $validated['decision'],
            'completed_at' => $validated['decision'] === 'approved' ? now() : null,
        ]);

        return redirect()->route('dashboard')->with('status', 'Review topik berhasil disimpan.');
    }

    private function seedMilestones(TaProject $project): void
    {
        $templates = DB::table('ta_milestone_templates')
            ->where('semester_code', $project->semester_code)
            ->orderBy('order_no')
            ->get();

        foreach ($templates as $index => $template) {
            TaMilestone::query()->create([
                'ta_project_id' => $project->id,
                'code' => $template->code,
                'name' => $template->name,
                'weight' => $template->weight,
                'due_date' => Carbon::now()->addWeeks(($index + 1) * 2)->toDateString(),
                'status' => 'not_started',
            ]);
        }
    }

    private function authorizeStudentProject(Request $request, TaProject $project): void
    {
        $user = $request->user();

        abort_unless(
            $user && $user->hasRole('mahasiswa') && (int) $project->student_user_id === (int) $user->id,
            403
        );
    }

    private function resolveSemesterCode(): string
    {
        $now = now();
        $term = $now->month <= 6 ? 'GENAP' : 'GANJIL';

        return $now->year.'-'.$term;
    }
}
