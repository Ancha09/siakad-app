@extends('layouts.admin')

@section('title', 'CPL Teknik Pertambangan')
@section('page-subtitle', 'Perhitungan IPK CPL berbobot SKS dari nilai resmi mahasiswa')

@push('styles')
<style>
    .ipk-cpl-table thead tr { background:linear-gradient(135deg,var(--navy) 0%,#0b5b9d 100%); }
    .ipk-cpl-table thead th { color:#fff;border-bottom-color:var(--navy); }
    .ipk-cpl-table tbody tr:nth-child(even) td { background:#f4f8fc; }
    .ipk-cpl-table tbody tr:hover td { background:#e3eff9; }
    .cpl-chart-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;margin-bottom:20px; }
    .cpl-chart-canvas { position:relative;width:100%;height:300px;min-height:0;overflow:hidden; }
    .cpl-chart-canvas canvas { display:block;max-width:100%;max-height:300px; }
    .cpl-chart-empty { position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#64748b;text-align:center; }
    .cpl-chart-empty[hidden] { display:none !important; }
    .mapping-action { min-width:330px; }
    .mapping-action .manual-course-picker { min-width:280px; }
    @media (max-width:720px) {
        .cpl-chart-grid { grid-template-columns:1fr; }
        .cpl-chart-canvas { height:240px; }
        .cpl-chart-canvas canvas { max-height:240px; }
    }
</style>
@endpush

@section('content')
@php
    $downloadFilters = array_filter(request()->only(['tahun_akademik', 'angkatan', 'tahun_studi', 'cpl_id', 'mata_kuliah_id']), fn ($value) => filled($value));
@endphp
<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    <a href="{{ route('admin.ipk-cpl.index') }}" class="btn-outline">Kembali ke Pilihan Program Studi</a>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('admin.ipk-cpl.program.pdf', ['prodi' => $program] + $downloadFilters) }}" class="btn-outline">Download PDF</a>
        <a href="{{ route('admin.ipk-cpl.program.excel', ['prodi' => $program] + $downloadFilters) }}" class="btn-primary">Download Excel</a>
    </div>
</div>

@if(session('success'))
    <div style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:13px 16px;border-radius:10px;margin-bottom:18px;">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:13px 16px;border-radius:10px;margin-bottom:18px;">
        {{ $errors->first() }}
    </div>
@endif

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head"><h2>Filter CPL Teknik Pertambangan</h2></div>
    <div class="page-card-body">
        <form method="GET" action="{{ route('admin.ipk-cpl.program', $program) }}">
            <div class="krs-form-grid">
                <div class="form-group">
                    <label>Program Studi</label>
                    <select name="program_studi_id" class="form-control">
                        <option value="{{ $program->id }}" selected>{{ $program->jenjang }} {{ $program->nama_prodi }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tahun_akademik">Tahun Akademik</label>
                    <select id="tahun_akademik" name="tahun_akademik" class="form-control">
                        <option value="">Semua tahun akademik</option>
                        @foreach($tahunAkademiks as $tahun)
                            <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="angkatan">Angkatan</label>
                    <select id="angkatan" name="angkatan" class="form-control">
                        <option value="">Semua angkatan</option>
                        @foreach($angkatans as $angkatan)
                            <option value="{{ $angkatan }}" @selected((string) request('angkatan') === (string) $angkatan)>{{ $angkatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="tahun_studi">Tahun Studi</label>
                    <select id="tahun_studi" name="tahun_studi" class="form-control">
                        <option value="">Semua tahun</option>
                        @foreach([1 => 'Semester 1 dan 2', 2 => 'Semester 3 dan 4', 3 => 'Semester 5 dan 6', 4 => 'Semester 7 dan 8'] as $tahun => $semester)
                            <option value="{{ $tahun }}" @selected((string) request('tahun_studi') === (string) $tahun)>Tahun {{ $tahun }} - {{ $semester }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="cpl_id">CPL</label>
                    <select id="cpl_id" name="cpl_id" class="form-control">
                        <option value="">Semua CPL</option>
                        @foreach($cplOptions as $cplOption)
                            <option value="{{ $cplOption->id }}" @selected((string) request('cpl_id') === (string) $cplOption->id)>{{ $cplOption->kode_cpl }} - {{ $cplOption->nama_cpl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="mata_kuliah_id">Mata Kuliah</label>
                    <select id="mata_kuliah_id" name="mata_kuliah_id" class="form-control">
                        <option value="">Semua mata kuliah</option>
                        @foreach($courseOptions as $courseOption)
                            <option value="{{ $courseOption->mata_kuliah_id }}" @selected((string) request('mata_kuliah_id') === (string) $courseOption->mata_kuliah_id)>{{ $courseOption->kode_sumber }} - {{ $courseOption->nama_sumber }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
                <button class="btn-primary" type="submit">Terapkan Filter</button>
                <a href="{{ route('admin.ipk-cpl.program', $program) }}" class="btn-outline">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
    @foreach(['CPL Terimport' => $mappingSummary['jumlah_cpl'], 'Mapping Terimport' => $mappingSummary['jumlah_mapping'], 'Mapping Cocok Master' => $mappingSummary['cocok_master'], 'Belum Cocok Master' => $mappingSummary['belum_cocok_master']] as $label => $value)
        <div style="padding:17px;border:1px solid #dbe6f1;background:#f4f8fc;border-radius:10px;"><small style="color:#64748b;">{{ $label }}</small><div style="font-size:26px;font-weight:700;color:#0b5b9d;">{{ $value }}</div></div>
    @endforeach
</div>

@if($mappingSummary['belum_cocok_master'] > 0)
    <div style="margin:-5px 0 20px;text-align:right;">
        <a class="btn-outline" href="#mapping-belum-cocok">Lihat mapping belum cocok</a>
    </div>
@endif

<div class="cpl-chart-grid">
    <div class="page-card">
        <div class="page-card-head"><h2>Grafik IPK CPL Tahun Akademik {{ request('tahun_akademik') ?: 'Semua Tahun' }}</h2></div>
        <div class="page-card-body">
            <div class="cpl-chart-canvas">
                <canvas id="ipkCplChart" aria-label="Grafik batang IPK CPL"></canvas>
                <div id="ipkCplChartEmpty" class="cpl-chart-empty" hidden>Belum ada data untuk grafik.</div>
            </div>
        </div>
    </div>
    <div class="page-card">
        <div class="page-card-head"><h2>Kelengkapan Data CPL</h2></div>
        <div class="page-card-body">
            <div class="cpl-chart-canvas">
                <canvas id="cplCompletenessChart" aria-label="Grafik kelengkapan data CPL"></canvas>
                <div id="cplCompletenessChartEmpty" class="cpl-chart-empty" hidden>Belum ada data untuk grafik.</div>
            </div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>Daftar CPL Teknik Pertambangan</h2></div>
    <div class="page-card-body">
        <div class="table-wrap" style="overflow-x:auto;">
            <table class="ipk-cpl-table">
                <thead><tr><th>No</th><th>Kode CPL</th><th>Nama CPL</th><th>Mata Kuliah</th><th>SKS Dihitung</th><th>Total SKS</th><th>Kelengkapan</th><th>IPK CPL</th><th>Status</th><th style="width:110px;">Aksi</th></tr></thead>
                <tbody>
                    @forelse($rows as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $item->kode_cpl }}</strong></td>
                            <td style="min-width:260px;">{{ $item->nama_cpl }}</td>
                            <td>{{ $item->jumlah_mata_kuliah }}</td>
                            <td>{{ $item->sks_dihitung }}</td>
                            <td>{{ $item->total_sks }}</td>
                            <td>{{ number_format($item->kelengkapan_persen, 2) }}%</td>
                            <td><strong>{{ $item->ipk_cpl === null ? '-' : number_format($item->ipk_cpl, 2) }}</strong></td>
                            <td>{{ $item->status }}</td>
                            <td><a class="btn-primary" style="display:inline-block;padding:7px 11px;white-space:nowrap;" href="{{ route('admin.ipk-cpl.cpl', [
                                'prodi' => $program,
                                'cpl' => $item->cpl,
                                'tahun_akademik' => request('tahun_akademik'),
                                'angkatan' => request('angkatan'),
                                'tahun_studi' => request('tahun_studi'),
                                'mata_kuliah_id' => request('mata_kuliah_id'),
                                'return_url' => request()->fullUrl(),
                            ]) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" style="text-align:center;padding:36px;color:#64748b;">Belum ada data untuk filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="page-card" id="mapping-belum-cocok" style="margin-top:20px;scroll-margin-top:20px;">
    <div class="page-card-head"><h2>Mapping Belum Cocok dengan Master</h2></div>
    <div class="page-card-body">
        <p style="margin:0 0 16px;color:#64748b;">Mapping pada bagian ini tidak ikut dihitung sampai terhubung ke master mata kuliah Teknik Pertambangan.</p>
        <div class="table-wrap" style="overflow-x:auto;">
            <table class="ipk-cpl-table">
                <thead><tr><th>No</th><th>Kode Excel</th><th>Nama Excel</th><th>CPL</th><th>Status</th><th>Saran Kemungkinan</th><th>Aksi Mapping Manual</th></tr></thead>
                <tbody>
                    @forelse($unmatchedRows as $unmatched)
                        @php($mapping = $unmatched->mapping)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $mapping->kode_sumber }}</strong></td>
                            <td>{{ $mapping->nama_sumber }}</td>
                            <td>{{ $unmatched->cpl_list ?: '-' }}</td>
                            <td><span style="color:#b45309;font-weight:700;white-space:nowrap;">Belum cocok master</span></td>
                            <td style="min-width:230px;">
                                @forelse($unmatched->suggestions as $suggestion)
                                    <div style="margin-bottom:5px;">
                                        <strong>{{ $suggestion->course->kode_mk }}</strong> - {{ $suggestion->course->nama_mk }}
                                        <small style="display:block;color:#64748b;">{{ $suggestion->reason }}</small>
                                    </div>
                                @empty
                                    <span style="color:#64748b;">Tidak ada saran yang cukup dekat</span>
                                @endforelse
                            </td>
                            <td class="mapping-action">
                                <form method="POST" action="{{ route('admin.ipk-cpl.mapping.update', ['prodi' => $program, 'cpl' => $mapping->cpl_id, 'mapping' => $mapping]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                    <x-searchable-course-select
                                        :courses="$mappingCourseOptions"
                                        :selected="null"
                                        :input-id="'mapping-course-'.$mapping->id"
                                    />
                                    <button type="submit" class="btn-primary" style="margin-top:8px;padding:8px 12px;">Hubungkan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:36px;color:#166534;">Semua mapping sudah terhubung ke master mata kuliah.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script id="ipkCplChartPayload" type="application/json">@json($chart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script>
(function () {
    'use strict';

    const initIpkCplCharts = function () {
        const ipkCanvas = document.getElementById('ipkCplChart');
        const completenessCanvas = document.getElementById('cplCompletenessChart');
        const payloadElement = document.getElementById('ipkCplChartPayload');

        if (!ipkCanvas || !completenessCanvas || !payloadElement) return;
        if (ipkCanvas.dataset.chartInitialized === 'true'
            || completenessCanvas.dataset.chartInitialized === 'true'
            || ipkCanvas.dataset.chartLoading === 'true') return;

        let data;
        try {
            data = JSON.parse(payloadElement.textContent || '{}');
        } catch (error) {
            data = {};
        }

        data.labels = Array.isArray(data.labels) ? data.labels.slice(0, 9) : [];
        data.names = Array.isArray(data.names) ? data.names.slice(0, 9) : [];
        data.ipk = Array.isArray(data.ipk) ? data.ipk.slice(0, 9) : [];
        data.sks_dihitung = Array.isArray(data.sks_dihitung) ? data.sks_dihitung.slice(0, 9) : [];
        data.total_sks = Array.isArray(data.total_sks) ? data.total_sks.slice(0, 9) : [];
        data.kelengkapan = Array.isArray(data.kelengkapan) ? data.kelengkapan.slice(0, 9) : [];

        const showEmpty = function (canvas, emptyId) {
            canvas.hidden = true;
            const empty = document.getElementById(emptyId);
            if (empty) empty.hidden = false;
        };

        const destroyChart = function (globalKey, canvas) {
            const registeredChart = window.Chart && typeof window.Chart.getChart === 'function'
                ? window.Chart.getChart(canvas)
                : null;
            const globalChart = window[globalKey];

            if (registeredChart && typeof registeredChart.destroy === 'function') {
                registeredChart.destroy();
            }
            if (globalChart && globalChart !== registeredChart && typeof globalChart.destroy === 'function') {
                globalChart.destroy();
            }
            window[globalKey] = null;
        };

        const renderCharts = function () {
            if (typeof window.Chart === 'undefined') {
                showEmpty(ipkCanvas, 'ipkCplChartEmpty');
                showEmpty(completenessCanvas, 'cplCompletenessChartEmpty');
                return;
            }

            destroyChart('ipkCplChart', ipkCanvas);
            destroyChart('ipkCplCompletenessChart', completenessCanvas);
            delete ipkCanvas.dataset.chartLoading;
            ipkCanvas.dataset.chartInitialized = 'true';
            completenessCanvas.dataset.chartInitialized = 'true';

            const tooltipDetails = function (index) {
                return [
                    data.names[index] || data.labels[index] || '-',
                    'IPK CPL: ' + (data.ipk[index] === null || data.ipk[index] === undefined ? 'Belum ada nilai' : Number(data.ipk[index]).toFixed(2)),
                    'SKS dihitung: ' + Number(data.sks_dihitung[index] || 0),
                    'Total SKS mapping: ' + Number(data.total_sks[index] || 0),
                    'Kelengkapan: ' + Number(data.kelengkapan[index] || 0).toFixed(2) + '%'
                ];
            };
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                parsing: false,
                normalized: true,
                devicePixelRatio: Math.min(window.devicePixelRatio || 1, 2),
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { afterBody: items => items.length ? tooltipDetails(items[0].dataIndex) : [] } }
                }
            };

            const hasIpkData = data.labels.length > 0
                && data.ipk.some(value => value !== null && value !== undefined && Number.isFinite(Number(value)));

            if (hasIpkData) {
                window.ipkCplChart = new window.Chart(ipkCanvas, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{ label: 'IPK CPL', data: data.ipk, backgroundColor: '#0b5b9d', borderColor: '#063b65', borderWidth: 1, borderRadius: 6 }]
                    },
                    options: { ...commonOptions, scales: { y: { beginAtZero: true, max: 4, ticks: { stepSize: 0.5 } } } }
                });
            } else {
                showEmpty(ipkCanvas, 'ipkCplChartEmpty');
            }

            if (data.labels.length > 0) {
                window.ipkCplCompletenessChart = new window.Chart(completenessCanvas, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{ label: 'Kelengkapan Data', data: data.kelengkapan, backgroundColor: '#f59e0b', borderColor: '#b45309', borderWidth: 1, borderRadius: 6 }]
                    },
                    options: { ...commonOptions, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: value => value + '%' } } } }
                });
            } else {
                showEmpty(completenessCanvas, 'cplCompletenessChartEmpty');
            }
        };

        if (typeof window.Chart !== 'undefined') {
            renderCharts();
            return;
        }

        ipkCanvas.dataset.chartLoading = 'true';
        const existingLoader = document.getElementById('ipkCplChartJsFallback');
        if (existingLoader) {
            existingLoader.addEventListener('load', renderCharts, { once: true });
            return;
        }

        const fallbackScript = document.createElement('script');
        fallbackScript.id = 'ipkCplChartJsFallback';
        fallbackScript.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
        fallbackScript.addEventListener('load', renderCharts, { once: true });
        fallbackScript.addEventListener('error', function () {
            delete ipkCanvas.dataset.chartLoading;
            showEmpty(ipkCanvas, 'ipkCplChartEmpty');
            showEmpty(completenessCanvas, 'cplCompletenessChartEmpty');
        }, { once: true });
        document.head.appendChild(fallbackScript);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initIpkCplCharts, { once: true });
    } else {
        initIpkCplCharts();
    }
})();
</script>
@endpush
