<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SIPTAKHIR</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="app-page">
    <main class="app-shell">
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-kicker">SIPTAKHIR POLTERA</p>
                    <h1>Dashboard Tugas Akhir</h1>
                    <p>Ringkasan progres, review topik, dan log bimbingan dalam satu ruang kerja.</p>
                </div>
                <div class="app-top-actions">
                    <form method="POST" action="{{ route('sso.refresh-token') }}">
                        @csrf
                        <button class="app-btn app-btn-secondary" type="submit">Refresh Token</button>
                    </form>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="app-btn app-btn-danger" type="submit">Logout</button>
                    </form>
                </div>
            </div>

            @if (session('status'))
                <div class="app-alert">{{ session('status') }}</div>
            @endif

            <div class="app-summary-grid">
                <article class="app-stat-card">
                    <span class="label">Nama</span>
                    <strong>{{ $user->name }}</strong>
                </article>
                <article class="app-stat-card">
                    <span class="label">Email</span>
                    <strong>{{ $user->email }}</strong>
                </article>
                <article class="app-stat-card">
                    <span class="label">Tipe Pengguna</span>
                    <strong>{{ $user->sso_user_type ?? '-' }}</strong>
                </article>
                <article class="app-stat-card">
                    <span class="label">Role Sistem</span>
                    <strong>{{ $user->roles->pluck('name')->implode(', ') ?: 'Belum tersinkron' }}</strong>
                </article>
            </div>
        </section>

        <section class="app-dashboard-grid">
            <article class="app-panel">
                <div class="section-heading">
                    <div>
                        <p class="app-kicker">Mahasiswa</p>
                        <h2>Progres Pengajuan Topik</h2>
                    </div>
                    @if ($user->hasRole('mahasiswa') && ! $studentProject)
                        <a href="{{ route('ta-projects.create') }}" class="app-btn app-btn-primary">Buat Topik</a>
                    @endif
                </div>

                @if ($studentProject)
                    <div class="project-card">
                        <div class="project-card-row">
                            <div>
                                <h3>{{ $studentProject->title }}</h3>
                                <p>{{ $studentProject->study_program ?: 'Program studi belum diisi' }}</p>
                            </div>
                            <span class="status-pill status-{{ $studentProject->status }}">{{ str_replace('_', ' ', $studentProject->status) }}</span>
                        </div>

                        <progress class="progress-bar" value="{{ $studentProgress }}" max="100">{{ $studentProgress }}</progress>
                        <p class="project-progress-text">Progress keseluruhan: {{ $studentProgress }}%</p>

                        <div class="project-actions">
                            @if (in_array($studentProject->status, ['draft', 'revision_required'], true))
                                <a href="{{ route('ta-projects.edit', $studentProject) }}" class="app-btn app-btn-secondary">Edit Topik</a>
                                <form method="POST" action="{{ route('ta-projects.submit', $studentProject) }}">
                                    @csrf
                                    <button class="app-btn app-btn-primary" type="submit">Ajukan Topik</button>
                                </form>
                            @endif
                        </div>

                        <div class="milestone-list">
                            @foreach ($studentProject->milestones as $milestone)
                                <div class="milestone-item">
                                    <div>
                                        <strong>{{ $milestone->name }}</strong>
                                        <span>Bobot {{ $milestone->weight }}% • Deadline {{ optional($milestone->due_date)->format('d M Y') ?: '-' }}</span>
                                    </div>
                                    <span class="status-pill status-{{ $milestone->status }}">{{ str_replace('_', ' ', $milestone->status) }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if ($user->hasRole('mahasiswa'))
                            <div class="subsection">
                                <h3>Kirim Log Bimbingan</h3>
                                <form method="POST" action="{{ route('ta-supervisions.store', $studentProject) }}" class="ta-inline-form">
                                    @csrf
                                    <label class="ta-field">
                                        <span>Tanggal Bimbingan</span>
                                        <input type="date" name="meeting_date" required>
                                    </label>
                                    <label class="ta-field ta-field-full">
                                        <span>Ringkasan Bimbingan</span>
                                        <textarea name="summary" rows="4" required></textarea>
                                    </label>
                                    <button class="app-btn app-btn-primary" type="submit">Kirim Log</button>
                                </form>
                            </div>
                        @endif

                        <div class="subsection">
                            <h3>Riwayat Bimbingan</h3>
                            <div class="review-list">
                                @forelse ($studentProject->supervisions as $supervision)
                                    <div class="review-item">
                                        <div>
                                            <strong>{{ $supervision->meeting_date->format('d M Y') }}</strong>
                                            <p>{{ $supervision->summary }}</p>
                                            @if ($supervision->supervisor_note)
                                                <small>Catatan pembimbing: {{ $supervision->supervisor_note }}</small>
                                            @endif
                                        </div>
                                        <span class="status-pill status-{{ $supervision->status }}">{{ str_replace('_', ' ', $supervision->status) }}</span>
                                    </div>
                                @empty
                                    <p class="empty-state">Belum ada log bimbingan.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="subsection">
                            <h3>Riwayat Review Topik</h3>
                            <div class="review-list">
                                @forelse ($studentProject->reviews as $review)
                                    <div class="review-item">
                                        <div>
                                            <strong>{{ $review->reviewer->name ?? 'Reviewer' }}</strong>
                                            <p>{{ $review->note ?: 'Tidak ada catatan tambahan.' }}</p>
                                        </div>
                                        <span class="status-pill status-{{ $review->decision }}">{{ str_replace('_', ' ', $review->decision) }}</span>
                                    </div>
                                @empty
                                    <p class="empty-state">Belum ada review topik.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @else
                    <p class="empty-state">Belum ada proyek TA untuk akun ini.</p>
                @endif
            </article>

            <article class="app-panel">
                <div class="section-heading">
                    <div>
                        <p class="app-kicker">Pembimbing / Admin</p>
                        <h2>Antrian Review</h2>
                    </div>
                </div>

                <div class="review-list">
                    @forelse ($supervisorProjects as $project)
                        <div class="review-item review-item-column">
                            <div class="project-card-row">
                                <div>
                                    <strong>{{ $project->title }}</strong>
                                    <p>{{ $project->student->name ?? 'Mahasiswa' }}</p>
                                </div>
                                <span class="status-pill status-{{ $project->status }}">{{ str_replace('_', ' ', $project->status) }}</span>
                            </div>

                            <p class="compact-text">Progress {{ $progressService->calculate($project) }}% • Bimbingan tercatat {{ $project->supervisions->count() }}</p>

                            @if (in_array($project->status, ['submitted', 'under_review'], true))
                                <form method="POST" action="{{ route('ta-projects.review', $project) }}" class="ta-inline-form">
                                    @csrf
                                    <label class="ta-field">
                                        <span>Keputusan</span>
                                        <select name="decision" required>
                                            <option value="approved">Setujui</option>
                                            <option value="revision_required">Minta Revisi</option>
                                            <option value="rejected">Tolak</option>
                                        </select>
                                    </label>
                                    <label class="ta-field ta-field-full">
                                        <span>Catatan Review</span>
                                        <textarea name="note" rows="3"></textarea>
                                    </label>
                                    <button class="app-btn app-btn-primary" type="submit">Simpan Review</button>
                                </form>
                            @endif

                            @foreach ($project->supervisions->where('status', 'submitted') as $supervision)
                                <form method="POST" action="{{ route('ta-supervisions.review', $supervision) }}" class="ta-inline-form ta-inline-form-bordered">
                                    @csrf
                                    <div class="compact-text">Log {{ $supervision->meeting_date->format('d M Y') }}: {{ $supervision->summary }}</div>
                                    <label class="ta-field">
                                        <span>Status</span>
                                        <select name="status" required>
                                            <option value="accepted">Terima</option>
                                            <option value="revision_required">Perlu Revisi</option>
                                        </select>
                                    </label>
                                    <label class="ta-field ta-field-full">
                                        <span>Catatan Pembimbing</span>
                                        <textarea name="supervisor_note" rows="3"></textarea>
                                    </label>
                                    <button class="app-btn app-btn-secondary" type="submit">Review Bimbingan</button>
                                </form>
                            @endforeach
                        </div>
                    @empty
                        <p class="empty-state">Belum ada proyek yang perlu Anda review.</p>
                    @endforelse
                </div>

                <div class="subsection">
                    <h3>SSO Debug</h3>
                    <pre class="app-pre">{{ json_encode($user->sso_profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </article>
        </section>
    </main>
</body>
</html>
