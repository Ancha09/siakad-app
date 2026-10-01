@extends('layouts.admin')
@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Akademik')
@section('page-subtitle', 'Ringkasan data kampus dan informasi terbaru')
@section('content')
@php
    $number = fn ($value, $decimals = 0) => $value === null ? '—' : number_format($value, $decimals, ',', '.');
    $mainRatePct = min(100, (float)($attendanceData['stats']['today_rate'] ?? ($stats['attendance'] ?? 0)));
    $dosenRatePct = $stats['totalLecturers'] > 0 ? round(($stats['activeLecturers'] / $stats['totalLecturers']) * 100) : 0;
@endphp
<div class="inner-page academic-dashboard">
    <!-- Header Welcome & Filter Tahun -->
    <section class="academic-welcome">
        <div>
            <span class="academic-eyebrow">PANEL ADMINISTRATOR</span>
            <h2>Gambaran Umum Kampus</h2>
            <p>Pantau kegiatan perkuliahan, capaian mahasiswa, dan statistik presensi STTMI.</p>
        </div>
        <form method="GET" class="academic-year">
            <label for="tahun">Tahun akademik</label>
            <div>
                <select id="tahun" name="tahun">
                    @foreach($years as $year)
                        <option @selected($year === $stats['year'])>{{ $year }}</option>
                    @endforeach
                </select>
                <button type="submit">Tampilkan</button>
            </div>
        </form>
    </section>

    <!-- =========================================================
         RINGKASAN BAGIAN ATAS (SIMPLE STAT CARDS DENGAN GARIS WARNA)
    ========================================================== -->
    <div class="simple-stats-grid">
        <!-- 1. Persentase Kehadiran (% Kehadiran) -->
        <div class="simple-stat-card card-accent-green">
            <div class="card-head">
                <span class="card-label">% Kehadiran</span>
                <span class="card-badge badge-green">Presensi</span>
            </div>
            <div class="card-body">
                <strong class="stat-number text-green" id="stat-main-rate">{{ $attendanceData['stats']['today_rate'] ?? ($stats['attendance'] ?? 0) }}%</strong>
                <div class="stat-meter-bar">
                    <div class="meter-fill fill-green" id="stat-main-progress" style="width: {{ $mainRatePct }}%;"></div>
                </div>
                <small class="stat-subtext" id="stat-main-desc">
                    <span id="stat-main-hadir">{{ $attendanceData['stats']['today_hadir'] }}</span> hadir dari <span id="stat-main-total">{{ $attendanceData['stats']['today_total'] }}</span> sesi
                </small>
            </div>
        </div>

        <!-- 2. Jumlah / Total Mata Kuliah (Matkul) -->
        <a class="simple-stat-card card-accent-blue" href="{{ route('admin.matakuliah') }}">
            <div class="card-head">
                <span class="card-label">Total Mata Kuliah</span>
                <span class="card-badge badge-blue">Kurikulum</span>
            </div>
            <div class="card-body">
                <strong class="stat-number text-blue">{{ $number($stats['totalMatkul'] ?? 0) }}</strong>
                <div class="stat-meter-bar">
                    <div class="meter-fill fill-blue" style="width: 100%;"></div>
                </div>
                <small class="stat-subtext">Mata kuliah aktif terdaftar</small>
            </div>
        </a>

        <!-- 3. Total Mahasiswa -->
        <a class="simple-stat-card card-accent-purple" href="{{ route('admin.mahasiswa') }}">
            <div class="card-head">
                <span class="card-label">Total Mahasiswa</span>
                <span class="card-badge badge-purple">Sivitas</span>
            </div>
            <div class="card-body">
                <strong class="stat-number text-purple">{{ $number($stats['totalStudents']) }}</strong>
                <div class="stat-meter-bar">
                    <div class="meter-fill fill-purple" style="width: 100%;"></div>
                </div>
                <small class="stat-subtext">Seluruh mahasiswa aktif</small>
            </div>
        </a>

        <!-- 4. Dosen Aktif Mengajar -->
        <a class="simple-stat-card card-accent-amber" href="{{ route('admin.dosen') }}">
            <div class="card-head">
                <span class="card-label">Dosen Aktif</span>
                <span class="card-badge badge-amber">Pengajar</span>
            </div>
            <div class="card-body">
                <strong class="stat-number text-amber">{{ $number($stats['activeLecturers']) }}</strong>
                <div class="stat-meter-bar">
                    <div class="meter-fill fill-amber" style="width: {{ $dosenRatePct }}%;"></div>
                </div>
                <small class="stat-subtext">Dari {{ $number($stats['totalLecturers']) }} total dosen</small>
            </div>
        </a>
    </div>

    <!-- =========================================================
         BAGIAN GRAFIK (HANYA 1 GRAFIK BULAT: PIE / DONUT CHART)
    ========================================================== -->
    <section class="simple-chart-panel" id="attendance-analytics-section" data-attendance="{{ json_encode($attendanceData) }}">
        <div class="chart-panel-header">
            <div>
                <div class="chart-tagline">
                    <span class="tag-dot"></span>
                    <span>GRAFIK UTAMA</span>
                </div>
                <h3 class="chart-title">Distribusi Status Presensi Mahasiswa</h3>
                <p class="chart-subtitle" id="attendance-range-label">Menampilkan data: {{ $attendanceData['range_label'] }}</p>
            </div>

            <!-- Filter Rentang Waktu Sederhana -->
            <div class="chart-filter-box">
                <div class="pill-group" role="tablist">
                    <button type="button" class="chart-pill {{ $attendanceData['range'] === 'today' ? 'active' : '' }}" data-range="today">Hari Ini</button>
                    <button type="button" class="chart-pill {{ $attendanceData['range'] === '7_days' ? 'active' : '' }}" data-range="7_days">7 Hari</button>
                    <button type="button" class="chart-pill {{ $attendanceData['range'] === '30_days' ? 'active' : '' }}" data-range="30_days">30 Hari</button>
                    <button type="button" class="chart-pill {{ $attendanceData['range'] === 'this_month' ? 'active' : '' }}" data-range="this_month">Bulan Ini</button>
                </div>
                <div id="attendance-loading" class="chart-loading" style="display: none;">
                    <span class="spin-indicator"></span>
                    <small>Memuat data...</small>
                </div>
            </div>
        </div>

        <div class="single-round-chart-layout">
            <!-- Canvas Grafik Bulat (Donut) -->
            <div class="chart-donut-container">
                <div class="donut-wrapper">
                    <canvas id="chartAttendanceSingle"></canvas>
                    <div class="donut-center-badge">
                        <span class="center-percentage" id="donut-center-rate">{{ $attendanceData['distribution']['rate_hadir'] }}%</span>
                        <span class="center-label">Tingkat Hadir</span>
                    </div>
                </div>
            </div>

            <!-- Rincian Status Presensi -->
            <div class="chart-status-details">
                <h4 class="details-title">Ringkasan Kehadiran</h4>
                <div class="status-items-list">
                    <div class="status-item line-green">
                        <div class="item-name"><span class="dot dot-green"></span> Hadir</div>
                        <div class="item-value">
                            <strong id="donut-val-hadir">{{ $attendanceData['distribution']['counts'][0] }}</strong>
                            <small id="donut-pct-hadir">({{ $attendanceData['distribution']['rates'][0] }}%)</small>
                        </div>
                    </div>
                    <div class="status-item line-blue">
                        <div class="item-name"><span class="dot dot-blue"></span> Izin</div>
                        <div class="item-value">
                            <strong id="donut-val-izin">{{ $attendanceData['distribution']['counts'][1] }}</strong>
                            <small id="donut-pct-izin">({{ $attendanceData['distribution']['rates'][1] }}%)</small>
                        </div>
                    </div>
                    <div class="status-item line-amber">
                        <div class="item-name"><span class="dot dot-amber"></span> Sakit</div>
                        <div class="item-value">
                            <strong id="donut-val-sakit">{{ $attendanceData['distribution']['counts'][2] }}</strong>
                            <small id="donut-pct-sakit">({{ $attendanceData['distribution']['rates'][2] }}%)</small>
                        </div>
                    </div>
                    <div class="status-item line-red">
                        <div class="item-name"><span class="dot dot-red"></span> Alpa</div>
                        <div class="item-value">
                            <strong id="donut-val-alpa">{{ $attendanceData['distribution']['counts'][3] }}</strong>
                            <small id="donut-pct-alpa">({{ $attendanceData['distribution']['rates'][3] }}%)</small>
                        </div>
                    </div>
                </div>

                <div class="details-action">
                    <a href="{{ route('admin.presensi') }}" class="btn-monitoring-link">
                        Lihat Data Rekap Absensi Lengkap &rarr;
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================
         PANEL AKADEMIK (PERUBAHAN IPK, PRODI & PENGUMUMAN)
    ========================================================== -->
    <div class="academic-grid">
        <section class="academic-panel" style="grid-column: 1 / -1;">
            <div class="announcement-heading">
                <div>
                    <h3>Perubahan IPK</h3>
                    <p class="announcement-muted">Tahun akademik sebelumnya dan tahun terpilih</p>
                </div>
                @if($stats['delta'] !== null)
                    <span class="badge {{ $stats['delta'] < 0 ? 'badge-red' : 'badge-green' }}">
                        {{ $stats['delta'] > 0 ? 'Naik' : ($stats['delta'] < 0 ? 'Turun' : 'Tetap') }} {{ $number(abs($stats['delta']), 2) }}
                    </span>
                @endif
            </div>
            @foreach([[$stats['previousYear'], $stats['previousIpk'], $stats['previousGradedStudents']], [$stats['year'], $stats['ipk'], $stats['gradedStudents']]] as [$year, $ipk, $count])
                <div class="academic-comparison">
                    <div><span>{{ $year }}</span><strong>{{ $number($ipk, 2) }}</strong></div>
                    <meter class="academic-track" min="0" max="4" value="{{ $ipk ?? 0 }}" aria-label="Rata-rata IPK tahun {{ $year }}">
                        {{ $number($ipk, 2) }}
                    </meter>
                    <small>{{ $count }} mahasiswa bernilai</small>
                </div>
            @endforeach
            @if($stats['delta'] === null)
                <p class="academic-note">Belum cukup data nilai pada kedua tahun untuk menyimpulkan kenaikan atau penurunan IPK.</p>
            @else
                <p class="academic-note">Rata-rata IPK {{ $stats['delta'] < 0 ? 'menurun' : ($stats['delta'] > 0 ? 'meningkat' : 'tetap') }} {{ $number(abs($stats['delta']), 2) }} poin dibanding {{ $stats['previousYear'] }}.</p>
            @endif
            <details class="academic-method">
                <summary>Cara menghitung IPK</summary>
                <p>IPK setiap mahasiswa = jumlah (bobot nilai × SKS) ÷ jumlah SKS dari seluruh KHS bernilai pada KRS yang disetujui, sampai tahun terpilih.</p>
            </details>
        </section>
    </div>

    <!-- Ringkasan Program Studi & Pengumuman -->
    <div class="academic-grid">
        <section class="academic-panel">
            <div class="announcement-heading">
                <div>
                    <h3>Ringkasan Program Studi</h3>
                    <p class="announcement-muted">Jumlah dosen dan mahasiswa saat ini sampai {{ $stats['year'] }}</p>
                </div>
            </div>
            <div class="prodi-line-list">
                @forelse($stats['prodis'] as $prodi)
                    <div class="prodi-line-item">
                        <div class="prodi-info">
                            <strong class="prodi-name">{{ $prodi->nama_prodi }}</strong>
                            <div class="prodi-stats-inline">
                                <span><strong class="stat-val">{{ $prodi->dosens_count }}</strong> Dosen</span>
                                <span class="dot-sep">&middot;</span>
                                <span><strong class="stat-val">{{ $prodi->mahasiswas_count }}</strong> Mahasiswa</span>
                                <span class="dot-sep">&middot;</span>
                                <span class="muted-val">{{ $prodi->jumlah_bernilai }} bernilai</span>
                            </div>
                        </div>
                        <div class="prodi-ipk-badge">
                            <small class="ipk-label">Rata-rata IPK</small>
                            <span class="badge badge-blue">{{ $number($prodi->ipk, 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="announcement-empty">Belum ada data program studi.</p>
                @endforelse
            </div>
        </section>

        <section class="academic-panel">
            <div class="announcement-heading">
                <div>
                    <h3 class="icon-heading"><x-layout-icon name="megaphone" /> Pengumuman Kampus</h3>
                    <p class="announcement-muted">Kelola informasi pengumuman aktif.</p>
                </div>
                <div class="action-buttons">
                    <a class="announcement-button icon-button" href="{{ route('admin.pengumuman.create', ['penerima' => 'dosen']) }}"><x-layout-icon name="plus" /> Dosen</a>
                    <a class="announcement-button secondary icon-button" href="{{ route('admin.pengumuman.create', ['penerima' => 'mahasiswa']) }}"><x-layout-icon name="plus" /> Mahasiswa</a>
                </div>
            </div>
            @forelse($announcements as $item)
                <a class="academic-announcement" href="{{ route('admin.pengumuman.edit', $item) }}">
                    <div>
                        <strong>{{ $item->judul }}</strong>
                        <p class="announcement-muted">{{ ucfirst($item->penerima) }} &middot; {{ $item->terbit_pada?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? 'Belum terbit' }}</p>
                    </div>
                    <span class="badge badge-blue">{{ $item->label_status }}</span>
                </a>
            @empty
                <p class="announcement-empty">Belum ada pengumuman.</p>
            @endforelse
            <a class="announcement-all" href="{{ route('admin.pengumuman.index') }}">Kelola semua pengumuman &rarr;</a>
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* ── SIMPLE STAT CARDS WITH TOP COLORED BORDER ─────────────── */
    .simple-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin: 20px 0;
    }
    .simple-stat-card {
        background: #ffffff;
        border-radius: 10px;
        padding: 16px 18px;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .simple-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.06);
    }
    .card-accent-green { border-top: 3px solid #10b981; }
    .card-accent-blue  { border-top: 3px solid #3b82f6; }
    .card-accent-purple{ border-top: 3px solid #8b5cf6; }
    .card-accent-amber { border-top: 3px solid #f59e0b; }

    .card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .card-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
    }
    .card-badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .card-badge.badge-green { background: #d1fae5; color: #065f46; }
    .card-badge.badge-blue  { background: #dbeafe; color: #1e40af; }
    .card-badge.badge-purple{ background: #ede9fe; color: #5b21b6; }
    .card-badge.badge-amber { background: #fef3c7; color: #92400e; }

    .stat-number {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.1;
        display: block;
        margin-bottom: 10px;
    }
    .text-green { color: #10b981; }
    .text-blue  { color: #2563eb; }
    .text-purple{ color: #7c3aed; }
    .text-amber { color: #d97706; }

    .stat-meter-bar {
        width: 100%;
        height: 4px;
        background: #f1f5f9;
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    .meter-fill {
        height: 100%;
        border-radius: 2px;
    }
    .fill-green { background: #10b981; }
    .fill-blue  { background: #3b82f6; }
    .fill-purple{ background: #8b5cf6; }
    .fill-amber { background: #f59e0b; }

    .stat-subtext {
        font-size: 0.775rem;
        color: #64748b;
        display: block;
    }

    /* ── SINGLE ROUND CHART PANEL ──────────────────────────────── */
    .simple-chart-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px 24px;
        margin: 20px 0 28px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .chart-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 20px;
    }
    .chart-tagline {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.725rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 4px;
    }
    .tag-dot {
        width: 6px;
        height: 6px;
        background: #10b981;
        border-radius: 50%;
    }
    .chart-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px;
    }
    .chart-subtitle {
        font-size: 0.825rem;
        color: #64748b;
        margin: 0;
    }

    .chart-filter-box {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .pill-group {
        display: flex;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 3px;
        gap: 2px;
    }
    .chart-pill {
        border: none;
        background: transparent;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .chart-pill:hover {
        color: #0f172a;
    }
    .chart-pill.active {
        background: #ffffff;
        color: #2563eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    .chart-loading {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 0.775rem;
    }
    .spin-indicator {
        width: 12px;
        height: 12px;
        border: 2px solid #cbd5e1;
        border-top-color: #2563eb;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── SINGLE ROUND CHART LAYOUT ─────────────────────────────── */
    .single-round-chart-layout {
        display: grid;
        grid-template-columns: minmax(260px, 340px) 1fr;
        gap: 32px;
        align-items: center;
        padding: 10px 0;
    }
    @media (max-width: 768px) {
        .single-round-chart-layout {
            grid-template-columns: 1fr;
        }
    }
    .donut-wrapper {
        position: relative;
        max-width: 260px;
        height: 260px;
        margin: 0 auto;
    }
    .donut-center-badge {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        pointer-events: none;
    }
    .center-percentage {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
        display: block;
    }
    .center-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
        margin-top: 4px;
        display: block;
    }

    .chart-status-details {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .details-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 4px;
    }
    .status-items-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
    }
    .status-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .status-item.line-green { border-left: 3px solid #10b981; }
    .status-item.line-blue  { border-left: 3px solid #3b82f6; }
    .status-item.line-amber { border-left: 3px solid #f59e0b; }
    .status-item.line-red   { border-left: 3px solid #ef4444; }

    .item-name {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
    }
    .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    .dot-green { background: #10b981; }
    .dot-blue  { background: #3b82f6; }
    .dot-amber { background: #f59e0b; }
    .dot-red   { background: #ef4444; }

    .item-value strong {
        font-size: 0.95rem;
        color: #0f172a;
    }
    .item-value small {
        font-size: 0.775rem;
        color: #64748b;
        margin-left: 2px;
    }

    .details-action {
        margin-top: 10px;
    }
    .btn-monitoring-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.825rem;
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 6px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        transition: all 0.15s ease;
    }
    .btn-monitoring-link:hover {
        background: #dbeafe;
        color: #1d4ed8;
    }

    /* ── PRODI LINE LIST ───────────────────────────────────────── */
    .prodi-line-list {
        display: flex;
        flex-direction: column;
    }
    .prodi-line-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .prodi-line-item:last-child {
        border-bottom: none;
    }
    .prodi-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .prodi-name {
        font-size: 0.9rem;
        color: #1e293b;
    }
    .prodi-stats-inline {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: #64748b;
    }
    .stat-val {
        color: #0f172a;
        font-weight: 600;
    }
    .dot-sep {
        color: #cbd5e1;
    }
    .muted-val {
        color: #94a3b8;
    }
    .prodi-ipk-badge {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 2px;
    }
    .ipk-label {
        font-size: 0.7rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    function runWithChart(callback) {
        if (typeof window.Chart !== 'undefined') {
            callback(window.Chart);
            return;
        }
        var cdnScript = document.createElement('script');
        cdnScript.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
        cdnScript.onload = function() { callback(window.Chart); };
        cdnScript.onerror = function() { console.error('Gagal memuat Chart.js CDN.'); };
        document.head.appendChild(cdnScript);
    }

    document.addEventListener('DOMContentLoaded', function() {
        runWithChart(function(Chart) {
            initSingleDonutChart(Chart);
        });
    });

    function initSingleDonutChart(Chart) {
        var section = document.getElementById('attendance-analytics-section');
        if (!section) return;

        var rawData = JSON.parse(section.dataset.attendance || '{}');
        var apiUrl = "{{ route('admin.dashboard.attendance-analytics') }}";
        var currentTahun = "{{ $stats['year'] }}";

        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#64748b';

        // SATU GRAFIK BULAT (Doughnut / Pie Chart)
        var canvas = document.getElementById('chartAttendanceSingle');
        var singleChart = null;

        if (canvas && rawData.distribution) {
            singleChart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: rawData.distribution.labels,
                    datasets: [{
                        data: rawData.distribution.counts,
                        backgroundColor: rawData.distribution.colors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var val = context.parsed || 0;
                                    return ' ' + label + ': ' + val + ' catatan';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Filter Pills Event Handler
        var filterPills = document.querySelectorAll('.chart-pill');
        var loadingIndicator = document.getElementById('attendance-loading');
        var rangeLabel = document.getElementById('attendance-range-label');

        filterPills.forEach(function(pill) {
            pill.addEventListener('click', function() {
                var range = this.getAttribute('data-range');
                filterPills.forEach(function(p) { p.classList.remove('active'); });
                this.classList.add('active');

                loadData(range);
            });
        });

        function loadData(range) {
            if (loadingIndicator) loadingIndicator.style.display = 'inline-flex';

            var params = new URLSearchParams({ range: range, tahun: currentTahun });

            fetch(apiUrl + '?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (loadingIndicator) loadingIndicator.style.display = 'none';

                if (rangeLabel && data.range_label) {
                    rangeLabel.textContent = 'Menampilkan data: ' + data.range_label;
                }

                // Update Grafik Bulat
                if (singleChart && data.distribution) {
                    singleChart.data.labels = data.distribution.labels;
                    singleChart.data.datasets[0].data = data.distribution.counts;
                    singleChart.update();
                }

                // Update Angka di Tengah Donut
                var centerRate = document.getElementById('donut-center-rate');
                if (centerRate && data.distribution) {
                    centerRate.textContent = data.distribution.rate_hadir + '%';
                }

                // Update Rincian Status
                if (data.distribution && data.distribution.counts) {
                    var elHadir = document.getElementById('donut-val-hadir');
                    var elHadirPct = document.getElementById('donut-pct-hadir');
                    var elIzin = document.getElementById('donut-val-izin');
                    var elIzinPct = document.getElementById('donut-pct-izin');
                    var elSakit = document.getElementById('donut-val-sakit');
                    var elSakitPct = document.getElementById('donut-pct-sakit');
                    var elAlpa = document.getElementById('donut-val-alpa');
                    var elAlpaPct = document.getElementById('donut-pct-alpa');

                    if (elHadir) elHadir.textContent = data.distribution.counts[0];
                    if (elHadirPct) elHadirPct.textContent = '(' + data.distribution.rates[0] + '%)';
                    if (elIzin) elIzin.textContent = data.distribution.counts[1];
                    if (elIzinPct) elIzinPct.textContent = '(' + data.distribution.rates[1] + '%)';
                    if (elSakit) elSakit.textContent = data.distribution.counts[2];
                    if (elSakitPct) elSakitPct.textContent = '(' + data.distribution.rates[2] + '%)';
                    if (elAlpa) elAlpa.textContent = data.distribution.counts[3];
                    if (elAlpaPct) elAlpaPct.textContent = '(' + data.distribution.rates[3] + '%)';
                }
            })
            .catch(function(err) {
                if (loadingIndicator) loadingIndicator.style.display = 'none';
                console.error('Gagal memperbarui grafik:', err);
            });
        }
    }
})();
</script>
@endpush