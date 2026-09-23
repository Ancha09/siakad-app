@extends('layouts.admin')

@section('title', 'CPL Teknik Pertambangan')
@section('page-subtitle', 'Perhitungan IPK CPL berbobot SKS dari nilai resmi mahasiswa')

@push('styles')
<style>
    .ipk-cpl-table thead tr { background:linear-gradient(135deg,var(--navy) 0%,#0b5b9d 100%); }
    .ipk-cpl-table thead th { color:#fff;border-bottom-color:var(--navy); }
    .ipk-cpl-table tbody tr:nth-child(even) td { background:#f4f8fc; }
    .ipk-cpl-table tbody tr:hover td { background:#e3eff9; }
    .cpl-chart-card { margin-bottom:20px; }
    .cpl-chart-canvas { position:relative;width:100%;height:390px;min-height:0;overflow:hidden; }
    .cpl-chart-canvas canvas { display:block;width:100% !important;height:100% !important;max-width:none;max-height:none; }
    .cpl-chart-empty { position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#64748b;text-align:center; }
    .cpl-chart-empty[hidden] { display:none !important; }
    .cpl-multi-badge { display:inline-block;margin-left:6px;padding:3px 7px;border-radius:999px;background:#dbeafe;color:#1e40af;font-size:11px;font-weight:700;white-space:nowrap; }
    @media (max-width:720px) {
        .cpl-chart-canvas { height:320px; }
    }
</style>
@endpush

@section('content')
@php
    $downloadFilters = collect($filters)
        ->only(['tahun_akademik', 'angkatan', 'tahun_studi', 'cpl_id', 'mata_kuliah_id'])
        ->filter(fn ($value) => filled($value))
        ->all();
    $manualOverrideGroups = $manualOverrides
        ->groupBy(fn (array $item) => implode('|', [
            $item['source'] ?? '-',
            $item['target'] ?? '-',
            $item['reason'] ?? '-',
        ]))
        ->map(function ($items) {
            $first = $items->first();

            return [
                'source' => $first['source'] ?? '-',
                'target' => $first['target'] ?? '-',
                'reason' => $first['reason'] ?? '-',
                'cpls' => $items->pluck('cpl')->filter()->unique()->sort()->implode(', '),
            ];
        })
        ->values();
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
@if(blank($filters['tahun_akademik'] ?? null))
    <div style="background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;padding:13px 16px;border-radius:10px;margin-bottom:18px;">
        Pilih tahun akademik terlebih dahulu untuk menampilkan IPK CPL.
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
                        <option value="">Pilih tahun akademik</option>
                        @foreach($tahunAkademiks as $tahun)
                            <option value="{{ $tahun }}" @selected(($filters['tahun_akademik'] ?? null) === $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="angkatan">Angkatan</label>
                    <select id="angkatan" name="angkatan" class="form-control">
                        <option value="">Semua angkatan</option>
                        @foreach($angkatans as $angkatan)
                            <option value="{{ $angkatan }}" @selected((string) ($filters['angkatan'] ?? '') === (string) $angkatan)>{{ $angkatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="tahun_studi">Tahun Studi</label>
                    <select id="tahun_studi" name="tahun_studi" class="form-control">
                        <option value="">Semua tahun</option>
                        @foreach([1 => 'Semester 1 dan 2', 2 => 'Semester 3 dan 4', 3 => 'Semester 5 dan 6', 4 => 'Semester 7 dan 8'] as $tahun => $semester)
                            <option value="{{ $tahun }}" @selected((string) ($filters['tahun_studi'] ?? '') === (string) $tahun)>Tahun {{ $tahun }} - {{ $semester }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="cpl_id">CPL</label>
                    <select id="cpl_id" name="cpl_id" class="form-control">
                        <option value="">Semua CPL</option>
                        @foreach($cplOptions as $cplOption)
                            <option value="{{ $cplOption->id }}" @selected((string) ($filters['cpl_id'] ?? '') === (string) $cplOption->id)>{{ $cplOption->kode_cpl }} - {{ $cplOption->nama_cpl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="mata_kuliah_id">Mata Kuliah</label>
                    <select id="mata_kuliah_id" name="mata_kuliah_id" class="form-control">
                        <option value="">Semua mata kuliah</option>
                        @foreach($courseOptions as $courseOption)
                            <option value="{{ $courseOption->mata_kuliah_id }}" @selected((string) ($filters['mata_kuliah_id'] ?? '') === (string) $courseOption->mata_kuliah_id)>{{ $courseOption->kode_sumber }} - {{ $courseOption->nama_sumber }}</option>
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
    @foreach(['CPL Terimport' => $mappingSummary['jumlah_cpl'], 'Mapping Terimport' => $mappingSummary['jumlah_mapping'], 'Mapping Aman' => $mappingSummary['aman'], 'Mapping Manual Override' => $mappingSummary['manual_override'], 'Mapping Bermasalah' => $mappingSummary['bermasalah'], 'Belum Cocok Master' => $mappingSummary['belum_cocok_master']] as $label => $value)
        <div style="padding:17px;border:1px solid #dbe6f1;background:#f4f8fc;border-radius:10px;"><small style="color:#64748b;">{{ $label }}</small><div style="font-size:26px;font-weight:700;color:#0b5b9d;">{{ $value }}</div></div>
    @endforeach
</div>

@if($manualOverrideGroups->isNotEmpty())
    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head"><h2>Manual Override Akreditasi</h2></div>
        <div class="page-card-body">
            <div class="table-wrap" style="overflow-x:auto;">
                <table class="ipk-cpl-table">
                    <thead><tr><th>No</th><th>Sumber CPL</th><th>Master Tujuan</th><th>CPL</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @foreach($manualOverrideGroups as $override)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $override['source'] }}</td>
                                <td>{{ $override['target'] }}</td>
                                <td>
                                    {{ $override['cpls'] ?: '-' }}
                                    @if(str_contains($override['cpls'], ','))
                                        <span class="cpl-multi-badge">Multi-CPL</span>
                                    @endif
                                </td>
                                <td>{{ $override['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<div class="page-card cpl-chart-card">
    <div class="page-card-head"><h2>Grafik IPK CPL Tahun Akademik {{ $filters['tahun_akademik'] ?? 'Belum dipilih' }}</h2></div>
    <div class="page-card-body">
        <div class="cpl-chart-canvas">
            <canvas id="ipkCplChart" aria-label="Grafik batang IPK CPL"></canvas>
            <div id="ipkCplChartEmpty" class="cpl-chart-empty" hidden>Belum ada data IPK CPL untuk filter ini.</div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>Daftar CPL Teknik Pertambangan</h2></div>
    <div class="page-card-body">
        <div class="table-wrap" style="overflow-x:auto;">
            <table class="ipk-cpl-table">
                <thead><tr><th>No</th><th>Kode CPL</th><th>Nama CPL</th><th>Mata Kuliah</th><th>MK Bernilai</th><th>SKS Dihitung</th><th>Total SKS</th><th>IPK CPL</th><th style="width:110px;">Aksi</th></tr></thead>
                <tbody>
                    @forelse($rows as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $item->kode_cpl }}</strong></td>
                            <td style="min-width:260px;">{{ $item->nama_cpl }}</td>
                            <td>{{ $item->jumlah_mata_kuliah }}</td>
                            <td>{{ $item->mata_kuliah_bernilai }}</td>
                            <td>{{ $item->sks_dihitung }}</td>
                            <td>{{ $item->total_sks }}</td>
                            <td><strong>{{ $item->ipk_cpl === null ? '-' : number_format($item->ipk_cpl, 2) }}</strong></td>
                            <td><a class="btn-primary" style="display:inline-block;padding:7px 11px;white-space:nowrap;" href="{{ route('admin.ipk-cpl.cpl', [
                                'prodi' => $program,
                                'cpl' => $item->cpl,
                                'tahun_akademik' => $filters['tahun_akademik'] ?? null,
                                'angkatan' => $filters['angkatan'] ?? null,
                                'tahun_studi' => $filters['tahun_studi'] ?? null,
                                'mata_kuliah_id' => $filters['mata_kuliah_id'] ?? null,
                                'return_url' => request()->fullUrl(),
                            ]) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="text-align:center;padding:36px;color:#64748b;">Belum ada data untuk filter yang dipilih.</td></tr>
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

    const initIpkCplChart = function () {
        const ipkCanvas = document.getElementById('ipkCplChart');
        const payloadElement = document.getElementById('ipkCplChartPayload');

        if (!ipkCanvas || !payloadElement) return;
        if (ipkCanvas.dataset.chartInitialized === 'true') return;

        let data;
        try {
            data = JSON.parse(payloadElement.textContent || '{}');
        } catch (error) {
            data = {};
        }

        const labels = Array.isArray(data.labels) ? data.labels.slice(0, 9) : [];
        const ipkValues = labels.map(function (_, index) {
            const source = Array.isArray(data.ipk) ? data.ipk[index] : null;
            const value = Number(source);
            return source !== null && source !== undefined && Number.isFinite(value) ? value : null;
        });
        const showEmpty = function (canvas, emptyId) {
            canvas.hidden = true;
            const empty = document.getElementById(emptyId);
            if (empty) empty.hidden = false;
        };

        const showCanvas = function (canvas, emptyId) {
            canvas.hidden = false;
            const empty = document.getElementById(emptyId);
            if (empty) empty.hidden = true;
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

        const renderChart = function () {
            if (typeof window.Chart === 'undefined') {
                showEmpty(ipkCanvas, 'ipkCplChartEmpty');
                return;
            }

            destroyChart('ipkCplChart', ipkCanvas);
            ipkCanvas.dataset.chartInitialized = 'true';
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                normalized: true,
                devicePixelRatio: Math.min(window.devicePixelRatio || 1, 2),
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: '#334155',
                            boxWidth: 14,
                            padding: 18,
                            font: { size: 13, weight: '600' }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#334155', padding: 8, font: { size: 13, weight: '600' } },
                        grid: { display: false }
                    },
                    y: {
                        ticks: { color: '#334155', padding: 8, font: { size: 12 } },
                        grid: { color: '#e2e8f0' }
                    }
                }
            };

            const hasIpkData = labels.length > 0 && ipkValues.some(value => value !== null);

            if (hasIpkData) {
                showCanvas(ipkCanvas, 'ipkCplChartEmpty');
                window.ipkCplChart = new window.Chart(ipkCanvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'IPK CPL',
                            data: ipkValues,
                            backgroundColor: '#0b5b9d',
                            borderColor: '#063b65',
                            borderWidth: 1,
                            borderRadius: 6,
                            barPercentage: 0.72,
                            categoryPercentage: 0.72,
                            maxBarThickness: 52
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            ...commonOptions.scales,
                            y: {
                                ...commonOptions.scales.y,
                                beginAtZero: true,
                                max: 4,
                                ticks: { color: '#334155', stepSize: 0.5, padding: 8, font: { size: 12 } }
                            }
                        }
                    }
                });
            } else {
                showEmpty(ipkCanvas, 'ipkCplChartEmpty');
            }

        };

        if (typeof window.Chart !== 'undefined') {
            renderChart();
            return;
        }

        showEmpty(ipkCanvas, 'ipkCplChartEmpty');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initIpkCplChart, { once: true });
    } else {
        initIpkCplChart();
    }
})();
</script>
@endpush
