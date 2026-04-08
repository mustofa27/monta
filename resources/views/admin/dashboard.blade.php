<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — SIPTAKHIR</title>
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="app-page">
<div class="app-shell">

    <header class="app-panel admin-topbar">
        <span class="app-kicker">SIPTAKHIR · Admin</span>
        <nav class="admin-nav">
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-link admin-nav-link--active">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="admin-nav-link">Pengguna</a>
            <a href="{{ route('admin.templates') }}" class="admin-nav-link">Template Milestone</a>
            <a href="{{ route('admin.audit-log') }}" class="admin-nav-link">Audit Log</a>
            <a href="{{ route('dashboard') }}" class="admin-nav-link">← Aplikasi</a>
        </nav>
    </header>

    <main class="admin-main">
        <h1 class="admin-page-title">Dashboard Admin</h1>

        {{-- KPI Cards --}}
        <div class="admin-kpi-grid">
            <div class="admin-kpi-card">
                <div class="admin-kpi-value">{{ $kpi['total_users'] }}</div>
                <div class="admin-kpi-label">Total Pengguna</div>
            </div>
            <div class="admin-kpi-card">
                <div class="admin-kpi-value">{{ $kpi['total_students'] }}</div>
                <div class="admin-kpi-label">Mahasiswa</div>
            </div>
            <div class="admin-kpi-card">
                <div class="admin-kpi-value">{{ $kpi['total_supervisors'] }}</div>
                <div class="admin-kpi-label">Dosen Pembimbing</div>
            </div>
            <div class="admin-kpi-card">
                <div class="admin-kpi-value">{{ $kpi['total_projects'] }}</div>
                <div class="admin-kpi-label">Total Proyek TA</div>
            </div>
            <div class="admin-kpi-card admin-kpi-card--draft">
                <div class="admin-kpi-value">{{ $kpi['projects_draft'] }}</div>
                <div class="admin-kpi-label">Draft</div>
            </div>
            <div class="admin-kpi-card admin-kpi-card--warning">
                <div class="admin-kpi-value">{{ $kpi['projects_submitted'] }}</div>
                <div class="admin-kpi-label">Menunggu Review</div>
            </div>
            <div class="admin-kpi-card admin-kpi-card--success">
                <div class="admin-kpi-value">{{ $kpi['projects_approved'] }}</div>
                <div class="admin-kpi-label">Disetujui</div>
            </div>
            <div class="admin-kpi-card admin-kpi-card--success">
                <div class="admin-kpi-value">{{ $kpi['projects_completed'] }}</div>
                <div class="admin-kpi-label">Selesai</div>
            </div>
        </div>

        <div class="admin-two-col">
            {{-- Recent Projects --}}
            <div class="app-panel">
                <div class="app-panel-header">
                    <h2 class="app-panel-title">Proyek TA Terbaru</h2>
                    <a href="{{ route('dashboard') }}" class="app-btn app-btn--ghost">Lihat Semua</a>
                </div>
                @if($recentProjects->isEmpty())
                    <p class="admin-empty">Belum ada proyek.</p>
                @else
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Mahasiswa</th>
                                <th>Judul</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentProjects as $project)
                            <tr>
                                <td>{{ $project->student->name ?? '—' }}</td>
                                <td>{{ Str::limit($project->title, 40) }}</td>
                                <td><span class="status-pill status-{{ $project->status }}">{{ $project->status }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Recent Audit Logs --}}
            <div class="app-panel">
                <div class="app-panel-header">
                    <h2 class="app-panel-title">Audit Log Terbaru</h2>
                    <a href="{{ route('admin.audit-log') }}" class="app-btn app-btn--ghost">Lihat Semua</a>
                </div>
                @if($recentLogs->isEmpty())
                    <p class="admin-empty">Belum ada log.</p>
                @else
                    <ul class="admin-log-list">
                        @foreach($recentLogs as $log)
                        <li class="admin-log-item">
                            <span class="admin-log-event">{{ $log->event }}</span>
                            <span class="admin-log-actor">{{ $log->actor->name ?? 'System' }}</span>
                            <span class="admin-log-time">{{ $log->created_at->diffForHumans() }}</span>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </main>
</div>
</body>
</html>
