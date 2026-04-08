<?php

namespace App\Http\Controllers;

use App\Models\TaMilestone;
use App\Models\TaDocument;
use App\Models\TaProject;
use App\Models\TaReview;
use App\Services\TaProgressService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
            'topic_attachments' => ['nullable', 'array'],
            'topic_attachments.*' => ['file', 'mimes:pdf,doc,docx,txt,zip', 'max:10240'],
        ]);

        $project = DB::transaction(function () use ($request, $user, $validated) {
            $project = TaProject::query()->create([
                'student_user_id' => $user->id,
                'title' => $validated['title'],
                'abstract' => $validated['abstract'] ?? null,
                'study_program' => $validated['study_program'] ?? null,
                'semester_code' => $this->resolveSemesterCode(),
                'status' => 'draft',
            ]);

            $this->seedMilestones($project);
            $this->storeTopicAttachments($request, $project, $user->id);

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
            'topic_attachments' => ['nullable', 'array'],
            'topic_attachments.*' => ['file', 'mimes:pdf,doc,docx,txt,zip', 'max:10240'],
        ]);

        $project->update($validated);
        $this->storeTopicAttachments($request, $project, (int) $request->user()->id);

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

        AuditLogger::logModelEvent(
            $project,
            'ta_project_reviewed',
            ['status' => $project->status],
            [
                'decision' => $validated['decision'],
                'note' => $validated['note'] ?? null,
            ]
        );

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

    public function downloadDocument(Request $request, TaDocument $document): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $project = $document->project;
        abort_unless($project, 404);
        abort_unless($this->canAccessProjectDocument($request, $project), 403);

        return Storage::disk('local')->download($document->stored_path, $document->original_name);
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

    private function canAccessProjectDocument(Request $request, TaProject $project): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['koordinator_ta', 'admin_prodi'])) {
            return true;
        }

        if ($user->hasRole('mahasiswa') && (int) $project->student_user_id === (int) $user->id) {
            return true;
        }

        return $user->hasRole('dosen_pembimbing')
            && (int) $project->supervisor_user_id === (int) $user->id;
    }

    private function storeTopicAttachments(Request $request, TaProject $project, int $uploadedByUserId): void
    {
        if (! $request->hasFile('topic_attachments')) {
            return;
        }

        foreach ((array) $request->file('topic_attachments') as $file) {
            if (! $file) {
                continue;
            }

            $storedPath = $file->storeAs(
                'ta-documents/topic-proposal/'.$project->id,
                Str::uuid()->toString().'-'.$file->getClientOriginalName(),
                'local'
            );

            TaDocument::query()->create([
                'ta_project_id' => $project->id,
                'uploaded_by_user_id' => $uploadedByUserId,
                'document_type' => 'topic_proposal',
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'size_bytes' => (int) $file->getSize(),
                'status' => 'uploaded',
            ]);
        }
    }

    private function resolveSemesterCode(): string
    {
        $now = now();
        $term = $now->month <= 6 ? 'GENAP' : 'GANJIL';

        return $now->year.'-'.$term;
    }
}
