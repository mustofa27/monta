<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna — Admin SIPTAKHIR</title>
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
            <a href="{{ route('admin.users') }}" class="admin-nav-link admin-nav-link--active">Pengguna</a>
            <a href="{{ route('admin.templates') }}" class="admin-nav-link">Template Milestone</a>
            <a href="{{ route('admin.audit-log') }}" class="admin-nav-link">Audit Log</a>
            <a href="{{ route('dashboard') }}" class="admin-nav-link">← Aplikasi</a>
        </nav>
    </header>

    <main class="admin-main">
        <h1 class="admin-page-title">Kelola Pengguna</h1>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">{{ session('success') }}</div>
        @endif

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.users') }}" class="admin-search-form">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari nama, email, SSO sub, user type…"
                class="ta-field__input admin-search-input"
            >
            <button type="submit" class="app-btn app-btn--primary">Cari</button>
            @if(request('q'))
                <a href="{{ route('admin.users') }}" class="app-btn app-btn--ghost">Reset</a>
            @endif
        </form>

        {{-- User Table --}}
        <div class="app-panel">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Tipe</th>
                        <th>Role Aktif</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            @if($user->sso_sub)
                                <div class="admin-sub-text">{{ $user->sso_sub }}</div>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->sso_user_type)
                                <span class="admin-badge">{{ $user->sso_user_type }}</span>
                            @endif
                            @if($user->sso_employee_type)
                                <span class="admin-badge admin-badge--secondary">{{ $user->sso_employee_type }}</span>
                            @endif
                        </td>
                        <td>
                            @forelse($user->roles as $role)
                                <span class="status-pill">{{ $role->slug }}</span>
                            @empty
                                <span class="admin-empty-inline">—</span>
                            @endforelse
                        </td>
                        <td>
                            {{-- Role Override Modal Trigger --}}
                            <button
                                class="app-btn app-btn--ghost admin-role-btn"
                                data-user-id="{{ $user->id }}"
                                data-user-name="{{ $user->name }}"
                                data-user-roles="{{ $user->roles->pluck('slug')->implode(',') }}"
                            >Edit Role</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="admin-empty">Tidak ada pengguna ditemukan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}

        {{-- Role Override Inline Form (hidden, shown via JS) --}}
        <div id="admin-role-modal" class="admin-modal" hidden>
            <div class="admin-modal-backdrop"></div>
            <div class="admin-modal-box app-panel">
                <h2 class="admin-modal-title">Override Role: <span id="modal-user-name"></span></h2>
                <form id="admin-role-form" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="admin-role-checklist">
                        @foreach($allRoles as $role)
                        <label class="admin-role-check">
                            <input type="checkbox" name="roles[]" value="{{ $role->slug }}" class="admin-role-checkbox">
                            <span class="admin-role-check-label">{{ $role->name }} <code>{{ $role->slug }}</code></span>
                        </label>
                        @endforeach
                    </div>
                    <div class="admin-modal-actions">
                        <button type="submit" class="app-btn app-btn--primary">Simpan</button>
                        <button type="button" id="admin-role-cancel" class="app-btn app-btn--ghost">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
(function () {
    const modal    = document.getElementById('admin-role-modal');
    const nameSpan = document.getElementById('modal-user-name');
    const form     = document.getElementById('admin-role-form');
    const cancel   = document.getElementById('admin-role-cancel');
    const checkboxes = modal.querySelectorAll('.admin-role-checkbox');

    document.querySelectorAll('.admin-role-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const userId    = btn.dataset.userId;
            const userName  = btn.dataset.userName;
            const userRoles = btn.dataset.userRoles ? btn.dataset.userRoles.split(',') : [];

            nameSpan.textContent = userName;
            form.action = '/admin/users/' + userId + '/roles';

            checkboxes.forEach(function (cb) {
                cb.checked = userRoles.indexOf(cb.value) !== -1;
            });

            modal.hidden = false;
        });
    });

    cancel.addEventListener('click', function () {
        modal.hidden = true;
    });

    modal.querySelector('.admin-modal-backdrop').addEventListener('click', function () {
        modal.hidden = true;
    });
}());
</script>
</body>
</html>
