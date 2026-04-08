<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Milestone — Admin SIPTAKHIR</title>
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="app-page">
<div class="app-shell">

    <header class="app-panel admin-topbar">
        <span class="app-kicker">SIPTAKHIR · Admin</span>
        <nav class="admin-nav">
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-link">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="admin-nav-link">Pengguna</a>
            <a href="{{ route('admin.templates') }}" class="admin-nav-link admin-nav-link--active">Template Milestone</a>
            <a href="{{ route('admin.audit-log') }}" class="admin-nav-link">Audit Log</a>
            <a href="{{ route('dashboard') }}" class="admin-nav-link">← Aplikasi</a>
        </nav>
    </header>

    <main class="admin-main">
        <h1 class="admin-page-title">Template Milestone</h1>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">{{ session('success') }}</div>
        @endif

        {{-- Filter by semester --}}
        <form method="GET" action="{{ route('admin.templates') }}" class="admin-search-form">
            <select name="semester" class="ta-field__input">
                <option value="">Semua Semester</option>
                @foreach($semesters as $sem)
                    <option value="{{ $sem }}" @selected($sem === $semester)>{{ $sem }}</option>
                @endforeach
            </select>
            <button type="submit" class="app-btn app-btn--primary">Filter</button>
            @if($semester)
                <a href="{{ route('admin.templates') }}" class="app-btn app-btn--ghost">Reset</a>
            @endif
        </form>

        {{-- Add Template Form --}}
        <div class="app-panel">
            <div class="app-panel-header">
                <h2 class="app-panel-title">Tambah Template</h2>
            </div>
            <form method="POST" action="{{ route('admin.templates.store') }}" class="ta-form-grid">
                @csrf
                <div class="ta-field">
                    <label class="ta-field__label">Kode Semester</label>
                    <input type="text" name="semester_code" value="{{ old('semester_code') }}"
                           placeholder="2026-GENAP" class="ta-field__input" required maxlength="20">
                    @error('semester_code')<span class="ta-field__error">{{ $message }}</span>@enderror
                </div>
                <div class="ta-field">
                    <label class="ta-field__label">Kode Milestone</label>
                    <input type="text" name="code" value="{{ old('code') }}"
                           placeholder="TOPIC_SUBMISSION" class="ta-field__input" required maxlength="60">
                    @error('code')<span class="ta-field__error">{{ $message }}</span>@enderror
                </div>
                <div class="ta-field">
                    <label class="ta-field__label">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           placeholder="Pengajuan Topik" class="ta-field__input" required maxlength="120">
                    @error('name')<span class="ta-field__error">{{ $message }}</span>@enderror
                </div>
                <div class="ta-field">
                    <label class="ta-field__label">Bobot (%)</label>
                    <input type="number" name="weight" value="{{ old('weight', 25) }}"
                           min="1" max="100" class="ta-field__input" required>
                    @error('weight')<span class="ta-field__error">{{ $message }}</span>@enderror
                </div>
                <div class="ta-field">
                    <label class="ta-field__label">Urutan</label>
                    <input type="number" name="order_no" value="{{ old('order_no', 1) }}"
                           min="1" class="ta-field__input" required>
                    @error('order_no')<span class="ta-field__error">{{ $message }}</span>@enderror
                </div>
                <div class="ta-field ta-field--action">
                    <button type="submit" class="app-btn app-btn--primary">Tambah</button>
                </div>
            </form>
        </div>

        {{-- Template Table --}}
        <div class="app-panel">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Bobot</th>
                        <th>Urutan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $tmpl)
                    <tr>
                        <td><code>{{ $tmpl->semester_code }}</code></td>
                        <td><code>{{ $tmpl->code }}</code></td>
                        <td>{{ $tmpl->name }}</td>
                        <td>{{ $tmpl->weight }}%</td>
                        <td>{{ $tmpl->order_no }}</td>
                        <td class="admin-action-cell">
                            {{-- Inline edit form --}}
                            <button
                                class="app-btn app-btn--ghost"
                                onclick="document.getElementById('edit-{{ $tmpl->id }}').hidden = !document.getElementById('edit-{{ $tmpl->id }}').hidden"
                            >Edit</button>

                            <form method="POST"
                                  action="{{ route('admin.templates.destroy', $tmpl->id) }}"
                                  class="admin-inline-delete"
                                  onsubmit="return confirm('Hapus template ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="app-btn app-btn--danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <tr id="edit-{{ $tmpl->id }}" hidden>
                        <td colspan="6">
                            <form method="POST"
                                  action="{{ route('admin.templates.update', $tmpl->id) }}"
                                  class="ta-form-grid">
                                @csrf
                                @method('PUT')
                                <div class="ta-field">
                                    <label class="ta-field__label">Nama</label>
                                    <input type="text" name="name" value="{{ $tmpl->name }}"
                                           class="ta-field__input" required>
                                </div>
                                <div class="ta-field">
                                    <label class="ta-field__label">Bobot</label>
                                    <input type="number" name="weight" value="{{ $tmpl->weight }}"
                                           min="1" max="100" class="ta-field__input" required>
                                </div>
                                <div class="ta-field">
                                    <label class="ta-field__label">Urutan</label>
                                    <input type="number" name="order_no" value="{{ $tmpl->order_no }}"
                                           min="1" class="ta-field__input" required>
                                </div>
                                <div class="ta-field ta-field--action">
                                    <button type="submit" class="app-btn app-btn--primary">Simpan</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="admin-empty">Belum ada template milestone.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $templates->links() }}
    </main>
</div>
</body>
</html>
