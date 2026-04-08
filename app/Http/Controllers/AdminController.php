<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\TaProject;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    // ── Admin Dashboard ──────────────────────────────────────────────────────────

    public function dashboard(): View
    {
        $kpi = [
            'total_users'         => User::query()->count(),
            'total_students'      => User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'mahasiswa'))->count(),
            'total_supervisors'   => User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'dosen_pembimbing'))->count(),
            'total_projects'      => TaProject::query()->count(),
            'projects_draft'      => TaProject::query()->where('status', 'draft')->count(),
            'projects_submitted'  => TaProject::query()->where('status', 'submitted')->count(),
            'projects_approved'   => TaProject::query()->where('status', 'approved')->count(),
            'projects_completed'  => TaProject::query()->where('status', 'completed')->count(),
        ];

        $recentProjects = TaProject::query()
            ->with(['student', 'supervisor'])
            ->latest()
            ->limit(5)
            ->get();

        $recentLogs = AuditLog::query()
            ->with('actor')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('kpi', 'recentProjects', 'recentLogs'));
    }

    // ── User Management ──────────────────────────────────────────────────────────

    public function users(Request $request): View
    {
        $query = User::query()->with('roles')->latest();

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('sso_sub', 'like', "%{$search}%")
                    ->orWhere('sso_user_type', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(25)->withQueryString();
        $allRoles = Role::query()->orderBy('name')->get();

        return view('admin.users', compact('users', 'allRoles'));
    }

    public function updateUserRoles(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles'   => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,slug'],
        ]);

        $before   = $user->roles()->pluck('slug')->all();
        $newSlugs = $validated['roles'] ?? [];

        $roleIds = Role::query()->whereIn('slug', $newSlugs)->pluck('id');
        $user->roles()->sync($roleIds);

        AuditLogger::logModelEvent(
            $user,
            'role_override',
            ['roles' => $before],
            ['roles' => $newSlugs]
        );

        return redirect()->route('admin.users')->with('success', "Roles updated for {$user->name}.");
    }

    // ── Milestone Template Admin ──────────────────────────────────────────────────

    public function templates(Request $request): View
    {
        $semester = $request->input('semester', '');

        $query = DB::table('ta_milestone_templates')
            ->orderBy('semester_code')
            ->orderBy('order_no');

        if ($semester !== '') {
            $query->where('semester_code', $semester);
        }

        $templates = $query->paginate(30)->withQueryString();

        $semesters = DB::table('ta_milestone_templates')
            ->distinct()
            ->orderByDesc('semester_code')
            ->pluck('semester_code');

        return view('admin.templates', compact('templates', 'semesters', 'semester'));
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'semester_code' => ['required', 'string', 'max:20'],
            'code'          => ['required', 'string', 'max:60', 'regex:/^[A-Z0-9_]+$/'],
            'name'          => ['required', 'string', 'max:120'],
            'weight'        => ['required', 'integer', 'min:1', 'max:100'],
            'order_no'      => ['required', 'integer', 'min:1'],
        ]);

        $exists = DB::table('ta_milestone_templates')
            ->where('semester_code', $validated['semester_code'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'A template with this semester + code already exists.'])->withInput();
        }

        DB::table('ta_milestone_templates')->insert([
            ...$validated,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.templates')->with('success', 'Milestone template created.');
    }

    public function updateTemplate(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'weight'   => ['required', 'integer', 'min:1', 'max:100'],
            'order_no' => ['required', 'integer', 'min:1'],
        ]);

        DB::table('ta_milestone_templates')
            ->where('id', $id)
            ->update([...$validated, 'updated_at' => now()]);

        return redirect()->route('admin.templates')->with('success', 'Template updated.');
    }

    public function destroyTemplate(int $id): RedirectResponse
    {
        DB::table('ta_milestone_templates')->where('id', $id)->delete();

        return redirect()->route('admin.templates')->with('success', 'Template deleted.');
    }

    // ── Audit Log Viewer ─────────────────────────────────────────────────────────

    public function auditLog(Request $request): View
    {
        $query = AuditLog::query()->with('actor')->latest();

        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }

        if ($actorId = $request->input('actor')) {
            $query->where('actor_user_id', $actorId);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(25)->withQueryString();

        $events = AuditLog::query()
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $actors = User::query()
            ->whereIn('id', AuditLog::query()->distinct()->whereNotNull('actor_user_id')->pluck('actor_user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.audit-log', compact('logs', 'events', 'actors'));
    }
}
