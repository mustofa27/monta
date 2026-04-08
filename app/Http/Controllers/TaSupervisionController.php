<?php

namespace App\Http\Controllers;

use App\Models\TaProject;
use App\Models\TaSupervision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaSupervisionController extends Controller
{
    public function store(Request $request, TaProject $project): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('mahasiswa') && (int) $project->student_user_id === (int) $user->id, 403);

        abort_if(! $project->supervisor_user_id, 422, 'Pembimbing belum ditetapkan untuk topik ini.');

        $validated = $request->validate([
            'meeting_date' => ['required', 'date'],
            'summary' => ['required', 'string'],
        ]);

        TaSupervision::query()->create([
            'ta_project_id' => $project->id,
            'student_user_id' => $user->id,
            'supervisor_user_id' => $project->supervisor_user_id,
            'meeting_date' => $validated['meeting_date'],
            'summary' => $validated['summary'],
            'status' => 'submitted',
        ]);

        $project->milestones()->where('code', 'SUPERVISION_LOG')->update([
            'status' => 'in_progress',
        ]);

        return redirect()->route('dashboard')->with('status', 'Log bimbingan berhasil dikirim.');
    }

    public function review(Request $request, TaSupervision $supervision): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['dosen_pembimbing', 'koordinator_ta', 'admin_prodi']), 403);
        abort_unless((int) $supervision->supervisor_user_id === (int) $user->id || $user->hasAnyRole(['koordinator_ta', 'admin_prodi']), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:accepted,revision_required'],
            'supervisor_note' => ['nullable', 'string'],
        ]);

        $supervision->update($validated);

        $project = $supervision->project;
        $acceptedCount = $project->supervisions()->where('status', 'accepted')->count();

        $project->milestones()->where('code', 'SUPERVISION_LOG')->update([
            'status' => $acceptedCount >= 3 ? 'approved' : 'in_progress',
            'completed_at' => $acceptedCount >= 3 ? now() : null,
        ]);

        return redirect()->route('dashboard')->with('status', 'Review bimbingan berhasil disimpan.');
    }
}
