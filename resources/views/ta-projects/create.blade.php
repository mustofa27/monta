<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Topik TA</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="app-page">
    <main class="app-shell app-shell-narrow">
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-kicker">Sprint 2</p>
                    <h1>Buat Pengajuan Topik</h1>
                    <p>Isi rancangan awal tugas akhir sebelum diajukan ke pembimbing atau koordinator.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('ta-projects.store') }}" class="ta-form">
                @include('ta-projects._form', [
                    'project' => $project,
                    'method' => 'POST',
                    'submitLabel' => 'Simpan Draft',
                ])
            </form>
        </section>
    </main>
</body>
</html>
