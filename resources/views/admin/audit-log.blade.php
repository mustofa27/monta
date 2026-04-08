<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log — Admin SIPTAKHIR</title>
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
            <a href="{{ route('admin.templates') }}" class="admin-nav-link">Template Milestone</a>
            <a href="{{ route('admin.audit-log') }}" class="admin-nav-link admin-nav-link--active">Audit Log</a>
            <a href="{{ route('dashboard') }}" class="admin-nav-link">← Aplikasi</a>
        </nav>
    </header>

    <main class="admin-main">
        <h1 class="admin-page-title">Audit Log</h1>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.audit-log') }}" class="admin-filter-form">
            <div class="ta-field">
                <label class="ta-field__label">Event</label>
                <select name="event" class="ta-field__input">
                    <option value="">Semua Event</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ $ev }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ta-field">
                <label class="ta-field__label">Aktor</label>
                <select name="actor" class="ta-field__input">
                    <option value="">Semua Aktor</option>
                    @foreach($actors as $actor)
                        <option value="{{ $actor->id }}" @selected(request('actor') == $actor->id)>
                            {{ $actor->name }} ({{ $actor->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="ta-field">
                <label class="ta-field__label">Dari</label>
                <input type="date" name="from" value="{{ request('from') }}" class="ta-field__input">
            </div>
            <div class="ta-field">
                <label class="ta-field__label">Sampai</label>
                <input type="date" name="to" value="{{ request('to') }}" class="ta-field__input">
            </div>
            <div class="ta-field ta-field--action">
                <button type="submit" class="app-btn app-btn--primary">Filter</button>
                <a href="{{ route('admin.audit-log') }}" class="app-btn app-btn--ghost">Reset</a>
            </div>
        </form>

        {{-- Log Table --}}
        <div class="app-panel">
            <table class="admin-table admin-table--wide">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Event</th>
                        <th>Aktor</th>
                        <th>Entitas</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="admin-nowrap">{{ $log->created_at->format('d/m/y H:i') }}</td>
                        <td><code>{{ $log->event }}</code></td>
                        <td>{{ $log->actor->name ?? '—' }}</td>
                        <td>
                            <span class="admin-sub-text">{{ class_basename($log->auditable_type) }}</span>
                            <code>#{{ $log->auditable_id }}</code>
                        </td>
                        <td>
                            @if($log->before)
                                <details class="admin-json-details">
                                    <summary>Before</summary>
                                    <pre class="admin-json-pre">{{ json_encode($log->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @else
                                <span class="admin-empty-inline">—</span>
                            @endif
                        </td>
                        <td>
                            @if($log->after)
                                <details class="admin-json-details">
                                    <summary>After</summary>
                                    <pre class="admin-json-pre">{{ json_encode($log->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @else
                                <span class="admin-empty-inline">—</span>
                            @endif
                        </td>
                        <td class="admin-nowrap">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="admin-empty">Tidak ada log yang cocok dengan filter.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </main>
</div>
</body>
</html>
