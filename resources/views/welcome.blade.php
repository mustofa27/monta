<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPTAKHIR Poltera</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="landing-page">
    <main class="shell">
        <header>
            <div class="brand">
                <div class="badge">TA</div>
                <div>
                    <h1>SIPTAKHIR POLTERA</h1>
                    <small>Sistem Monitoring Progres Tugas Akhir Mahasiswa</small>
                </div>
            </div>
            <a href="{{ route('login') }}" class="btn btn-primary">Masuk via SSO</a>
        </header>

        <section class="hero">
            <h2>Pantau kemajuan Tugas Akhir mahasiswa secara real-time dan terstruktur.</h2>
            <p>
                Platform ini membantu program studi, dosen pembimbing, dan mahasiswa Politeknik Negeri Madura
                untuk memonitor status TA dari pengajuan topik, bimbingan, seminar proposal, hingga sidang akhir
                dalam satu dashboard yang jelas dan mudah ditindaklanjuti.
            </p>
        </section>

        <section class="stats">
            <article class="card">
                <div class="label">Mahasiswa TA Aktif</div>
                <div class="value">184</div>
            </article>
            <article class="card">
                <div class="label">Siap Seminar Proposal</div>
                <div class="value ok">52</div>
            </article>
            <article class="card">
                <div class="label">Melewati Deadline Bimbingan</div>
                <div class="value danger">17</div>
            </article>
            <article class="card">
                <div class="label">Dokumen Validasi Menunggu</div>
                <div class="value warn">29</div>
            </article>
        </section>

        <section class="grid">
            <article class="card">
                <h3 class="section-title">Tahapan Monitoring</h3>
                <div class="timeline">
                    <div class="step">
                        <div>
                            <strong>1. Pengajuan Topik</strong>
                            <span>Validasi topik oleh koordinator TA dan kaprodi</span>
                        </div>
                        <span class="chip ok">Selesai 91%</span>
                    </div>
                    <div class="step">
                        <div>
                            <strong>2. Bimbingan Rutin</strong>
                            <span>Tracking log bimbingan mingguan mahasiswa</span>
                        </div>
                        <span class="chip process">Berjalan</span>
                    </div>
                    <div class="step">
                        <div>
                            <strong>3. Seminar Proposal</strong>
                            <span>Penjadwalan, reviewer, dan notulensi terintegrasi</span>
                        </div>
                        <span class="chip warn">Perlu Tindakan</span>
                    </div>
                    <div class="step">
                        <div>
                            <strong>4. Sidang Akhir</strong>
                            <span>Rekap revisi, status ACC, dan administrasi kelulusan</span>
                        </div>
                        <span class="chip process">Antrian</span>
                    </div>
                </div>
            </article>

            <article class="card">
                <h3 class="section-title">Fitur Utama Sistem</h3>
                <div class="timeline">
                    <div class="step">
                        <div>
                            <strong>Dashboard Progres per Mahasiswa</strong>
                            <span>Persentase capaian berbasis milestone TA</span>
                        </div>
                    </div>
                    <div class="step">
                        <div>
                            <strong>Notifikasi Deadline Otomatis</strong>
                            <span>Peringatan untuk mahasiswa dan dosen pembimbing</span>
                        </div>
                    </div>
                    <div class="step">
                        <div>
                            <strong>Audit Trail Bimbingan</strong>
                            <span>Riwayat aktivitas tersimpan untuk evaluasi prodi</span>
                        </div>
                    </div>
                    <div class="step">
                        <div>
                            <strong>Akses Terintegrasi SSO Poltera</strong>
                            <span>Login aman sesuai role: mahasiswa, dosen, admin</span>
                        </div>
                    </div>
                </div>
                <div class="notice">
                    Fokus semester ini: percepatan penyelesaian TA tepat waktu dengan pemantauan berbasis data.
                </div>
                <div class="cta">
                    <a href="{{ route('login') }}" class="btn btn-primary">Mulai Monitoring</a>
                    <a href="{{ route('login') }}" class="btn btn-secondary">Masuk sebagai Pembimbing</a>
                </div>
            </article>
        </section>

        <div class="footer-note">
            © {{ date('Y') }} Politeknik Negeri Madura • SIPTAKHIR • Sistem monitoring tugas akhir lintas program studi.
        </div>
    </main>
</body>
</html>
