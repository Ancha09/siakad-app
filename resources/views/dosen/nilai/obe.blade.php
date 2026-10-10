@extends('layouts.dosen')

@section('title', 'Penilaian OBE - ' . ($jadwal->mataKuliah->nama_mk ?? 'Mata Kuliah'))

@push('styles')
<style>
    .obe-nav-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .obe-nav-tab {
        padding: 11px 20px;
        border-radius: 8px 8px 0 0;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: transparent;
        transition: all 0.2s ease;
    }
    .obe-nav-tab:hover {
        color: #1e40af;
        background: #f8fafc;
    }
    .obe-nav-tab.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
        background: #eff6ff;
    }

    .obe-info-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 22px;
        margin-bottom: 22px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .obe-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 16px;
    }
    .obe-info-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 4px;
    }
    .obe-info-value {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
    }

    .obe-layout-split {
        display: grid;
        grid-template-columns: 7fr 5fr;
        gap: 22px;
        align-items: flex-start;
    }
    @media (max-width: 1024px) {
        .obe-layout-split {
            grid-template-columns: 1fr;
        }
    }

    .obe-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        margin-bottom: 22px;
    }
    .obe-card-head {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        gap: 12px;
        flex-wrap: wrap;
    }
    .obe-card-head h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .obe-card-body {
        padding: 20px;
    }

    .cpl-pill {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 700;
        background: #e0f2fe;
        color: #0369a1;
        white-space: nowrap;
    }
    .cpmk-pill {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 700;
        background: #fef3c7;
        color: #92400e;
        white-space: nowrap;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .kpi-title {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .kpi-value {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
    }
    .kpi-subtitle {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
    }

    .obe-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }
    .obe-search-box {
        position: relative;
        flex: 1;
        max-width: 380px;
        min-width: 240px;
    }
    .obe-search-box input {
        width: 100%;
        padding-left: 36px;
    }
    .obe-search-box .icon-search-inside {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }

    .grade-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        min-width: 32px;
    }
    .grade-A { background: #dcfce7; color: #15803d; }
    .grade-B { background: #e0f2fe; color: #0369a1; }
    .grade-C { background: #fef9c3; color: #854d0e; }
    .grade-D { background: #ffedd5; color: #c2410c; }
    .grade-E { background: #fee2e2; color: #b91c1c; }

    .progress-bar-bg {
        width: 100%;
        height: 10px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 0.3s ease;
    }
    .progress-green { background: #16a34a; }
    .progress-blue { background: #2563eb; }
    .progress-yellow { background: #ca8a04; }
    .progress-red { background: #dc2626; }

    .modal-backdrop-custom {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
    }
    .modal-box-custom {
        background: #fff;
        border-radius: 12px;
        width: 100%;
        max-width: 580px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
    }
    .modal-box-large {
        max-width: 1180px;
        width: 95%;
        max-height: 92vh;
        padding: 22px 26px;
    }
    .mhs-row-clickable {
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .mhs-row-clickable:hover {
        background: #f8fafc;
    }
    .mhs-name-btn {
        color: #1d4ed8;
        font-weight: 700;
        text-decoration: none;
        border: none;
        background: transparent;
        cursor: pointer;
        padding: 0;
        font-size: 13.5px;
        text-align: left;
    }
    .mhs-name-btn:hover {
        text-decoration: underline;
        color: #1e40af;
    }
    .toast-popup {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #0f172a;
        color: #ffffff;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);
        z-index: 10000;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.3s ease;
    }

    .table-input-nilai td {
        vertical-align: middle;
        padding: 8px 10px;
    }
    .table-input-nilai th {
        vertical-align: middle;
        padding: 10px;
        font-size: 12px;
        white-space: nowrap;
    }
    .input-skor {
        width: 82px;
        text-align: center;
        font-weight: 600;
        padding: 6px 4px;
    }

    /* ================= MATRIX ALOKASI BOBOT CSS ================= */
    .matrix-container {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        margin-bottom: 20px;
    }
    .table-matrix {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 850px;
    }
    .table-matrix th, .table-matrix td {
        border: 1px solid #e2e8f0;
        padding: 7px 10px;
        vertical-align: middle;
    }
    .matrix-cpmk-head {
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 11.5px;
        font-weight: 700;
        text-align: center;
        letter-spacing: 0.5px;
        padding: 6px 4px;
    }
    .matrix-sub-head {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
        min-width: 48px;
        padding: 6px 4px;
    }
    .matrix-row-nama {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        border: none;
        background: transparent;
        width: 100%;
        padding: 2px 0;
        outline: none;
    }
    .matrix-row-nama:focus {
        border-bottom: 1px solid #3b82f6;
    }
    .matrix-row-ket {
        font-size: 11px;
        color: #64748b;
        border: none;
        background: transparent;
        width: 100%;
        padding: 2px 0;
        outline: none;
    }
    .matrix-row-ket:focus {
        border-bottom: 1px dashed #94a3b8;
    }
    .matrix-input-cell {
        width: 44px;
        height: 32px;
        text-align: center;
        font-weight: 700;
        font-size: 13px;
        color: #0f172a;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 2px 4px;
        background: #ffffff;
        transition: all 0.15s ease;
        margin: 0 auto;
        display: block;
    }
    .matrix-input-cell:focus {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 0 0 2px rgba(37,99,235,0.15);
        outline: none;
    }
    .matrix-input-cell:disabled {
        background: #f8fafc;
        color: #64748b;
        border-color: #e2e8f0;
    }
    .row-bobot-val {
        font-weight: 800;
        color: #0f172a;
        font-size: 13px;
        display: block;
        text-align: center;
    }
    .col-bobot-val {
        font-weight: 800;
        color: #0f172a;
        font-size: 13px;
        display: block;
        text-align: center;
    }
    .grand-total-val {
        font-weight: 800;
        font-size: 14px;
        color: #15803d;
        text-align: center;
        display: block;
    }
    .grand-total-val.invalid {
        color: #dc2626;
    }

    /* 3-Column Analytics Layout */
    .obe-analytics-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-top: 22px;
        align-items: stretch;
    }
    @media (max-width: 1024px) {
        .obe-analytics-grid {
            grid-template-columns: 1fr;
        }
    }
    .analytics-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
    }
    .analytics-card-head {
        padding: 14px 18px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .analytics-card-head h4 {
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }
    .analytics-card-head .subtitle {
        font-size: 11px;
        color: #64748b;
        margin-top: 3px;
    }
    .analytics-card-body {
        padding: 16px 18px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .rollup-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12.5px;
        padding: 7px 0;
        border-bottom: 1px dashed #f1f5f9;
        color: #334155;
    }
    .rollup-item:last-child {
        border-bottom: none;
    }
    .badge-findings {
        background: #ffedd5;
        color: #c2410c;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 11.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .pill-compare {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 700;
        font-family: monospace;
    }
    .pill-diff {
        background: #2563eb;
        color: #ffffff;
    }
    .pill-same {
        background: #f1f5f9;
        color: #334155;
    }
    .pill-total {
        background: #ecfdf5;
        color: #047857;
        font-weight: 800;
    }
    .finding-item {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 12px;
        font-size: 12px;
        color: #334155;
        line-height: 1.45;
    }
    .finding-num {
        width: 19px;
        height: 19px;
        border-radius: 50%;
        background: #ffedd5;
        color: #c2410c;
        font-weight: 700;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .badge-target-empty {
        font-size: 11px;
        color: #c2410c;
        background: #fff7ed;
        border: 1px solid #ffedd5;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="inner-page">

    {{-- Alert Notifikasi --}}
    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            {{ session('error') }}
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info" style="margin-bottom: 20px;">
            {{ session('info') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info Kelas & Mata Kuliah --}}
    <div class="obe-info-card">
        <div class="obe-info-grid">
            <div>
                <div class="obe-info-label">Mata Kuliah</div>
                <div class="obe-info-value">{{ $jadwal->mataKuliah->nama_mk ?? '-' }}</div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $jadwal->mataKuliah->kode_mk ?? '-' }} &bull; {{ $jadwal->mataKuliah->sks ?? 0 }} SKS</div>
            </div>
            <div>
                <div class="obe-info-label">Kelas &amp; Ruangan</div>
                <div class="obe-info-value">Kelas {{ $jadwal->kelas }} &bull; {{ $jadwal->ruangan->nama_ruangan ?? '-' }}</div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $jadwal->hari }}, {{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }}</div>
            </div>
            <div>
                <div class="obe-info-label">Dosen Pengampu</div>
                <div class="obe-info-value">{{ $jadwal->semua_dosen_nama ?: ($jadwal->dosen->nama ?? '-') }}</div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $jadwal->mataKuliah->prodi->nama_prodi ?? 'Prodi' }}</div>
            </div>
            <div>
                <div class="obe-info-label">Status Penilaian OBE</div>
                <div style="margin-top: 4px;">
                    @if($skema->is_finalized)
                        <span class="badge badge-success" style="display:inline-flex; align-items:center; gap:5px;">
                            <x-layout-icon name="lock" /> Final (Terkunci)
                        </span>
                        <div style="font-size:11px; color:#64748b; margin-top:3px;">
                            Difinalisasi: {{ $skema->finalized_at?->format('d/m/Y H:i') }}
                        </div>
                    @else
                        <span class="badge badge-warning" style="display:inline-flex; align-items:center; gap:5px;">
                            <x-layout-icon name="edit" /> Draf Terbuka
                        </span>
                        <div style="font-size:11px; color:#64748b; margin-top:3px;">
                            Target Passing Grade: {{ number_format($rps?->target_passing_grade ?? 60, 0) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Navigasi 3 Tab --}}
    <div class="obe-nav-tabs">
        <a href="{{ route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'pengaturan']) }}"
           class="obe-nav-tab {{ $tab === 'pengaturan' ? 'active' : '' }}">
            <x-layout-icon name="services" />
            <span>1. Pengaturan Penilaian RPS</span>
            @if($skema->komponens->count() > 0)
                <span class="badge badge-info" style="font-size:11px; padding:1px 6px;">{{ $skema->komponens->count() }}</span>
            @endif
        </a>

        <a href="{{ route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'input']) }}"
           class="obe-nav-tab {{ $tab === 'input' ? 'active' : '' }}">
            <x-layout-icon name="clipboard" />
            <span>2. Input Nilai Mahasiswa</span>
            <span class="badge badge-info" style="font-size:11px; padding:1px 6px;">{{ $krsList->count() }} Mhs</span>
        </a>

        <a href="{{ route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'capaian']) }}"
           class="obe-nav-tab {{ $tab === 'capaian' ? 'active' : '' }}">
            <x-layout-icon name="chart" />
            <span>3. Capaian CPL &amp; CPMK</span>
        </a>
    </div>

    {{-- =========================================================================
         TAB 1: PENGATURAN INSTRUMEN PENILAIAN RPS
         ========================================================================= --}}
    @if($tab === 'pengaturan')
        @php
            $cpmks = $jadwal->mataKuliah?->cpmks()->with(['subCpmks.cpl'])->orderBy('id')->get() ?? collect();
            $allSubCpmks = collect();
            foreach ($cpmks as $cpmk) {
                foreach ($cpmk->subCpmks as $sub) {
                    $allSubCpmks->push($sub);
                }
            }

            $matrixRows = $matrixData['rows'] ?? [];
            $komponenRps = $matrixData['komponen_rps'] ?? [];
            $temuanList = $matrixData['temuan'] ?? [];

            // Group Sub-CPMK codes for label S1-S2, S3-S6, etc.
            $cpmkSubRanges = [];
            $subCounter = 1;
            foreach ($cpmks as $cpmk) {
                $count = $cpmk->subCpmks->count();
                if ($count > 0) {
                    $start = $subCounter;
                    $end = $subCounter + $count - 1;
                    $cpmkSubRanges[$cpmk->id] = ($start === $end) ? "S{$start}" : "S{$start}–S{$end}";
                    $subCounter += $count;
                } else {
                    $cpmkSubRanges[$cpmk->id] = '-';
                }
            }

            // Target porsi CPL dari prodi / RPS
            $targetPorsiCpl = $rps?->porsi_cpl ?? [];
        @endphp

        @if($allSubCpmks->isEmpty())
            <div class="obe-card">
                <div class="obe-card-body" style="text-align: center; padding: 48px 20px;">
                    <div style="max-width: 520px; margin: 0 auto;">
                        <span class="empty-state-icon" style="font-size: 38px; display: inline-block; margin-bottom: 14px; color: #f59e0b;">
                            <x-layout-icon name="book" />
                        </span>
                        <h3 style="margin-top: 0; color: #0f172a;">Data CPMK &amp; Sub-CPMK Belum Tersedia</h3>
                        <p style="color: #64748b; font-size: 13.5px; margin-bottom: 22px; line-height: 1.5;">
                            Mata kuliah <strong>{{ $jadwal->mataKuliah->nama_mk ?? 'ini' }}</strong> belum memiliki data pemetaan CPMK dan Sub-CPMK pada kurikulum aktif. Matriks alokasi instrumen membutuhkan data Sub-CPMK untuk membagi persentase penilaian.
                        </p>
                        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                            <a href="{{ route('admin.obe.index', ['tab' => 'cpmk']) }}" class="btn-primary" target="_blank">
                                <x-layout-icon name="plus" />
                                <span>Atur CPMK &amp; Sub-CPMK di Kurikulum</span>
                            </a>
                            <a href="{{ route('admin.obe.index', ['tab' => 'rps']) }}" class="btn-outline" target="_blank">
                                <x-layout-icon name="upload" />
                                <span>Ekstrak dari Dokumen RPS PDF</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Card Matriks Utama --}}
            <div class="obe-card">
                <div class="obe-card-head" style="align-items: flex-start;">
                    <div>
                        <h3 style="margin-bottom: 4px;">
                            <x-layout-icon name="services" />
                            <span>Alokasi bobot instrumen ke Sub-CPMK</span>
                        </h3>
                        <div style="font-size: 12.5px; color: #64748b; font-weight: 500;">
                            Diisi otomatis dari rincian pertemuan di RPS. UTS dan UAS dipecah per bagian soal agar setiap Sub-CPMK punya skor sendiri.
                        </div>
                    </div>
                    <div>
                        <span class="badge-findings">
                            <svg class="layout-icon" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>{{ count($temuanList) }} temuan pada RPS</span>
                        </span>
                    </div>
                </div>

                <div class="obe-card-body" style="padding: 18px 20px;">
                    @if($isMatrixLocked)
                        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:12.5px; color:#1e40af; display:flex; align-items:center; gap:10px;">
                            <x-layout-icon name="lock" />
                            <div>
                                <strong>Matriks Terkunci (Baku):</strong> Bobot penilaian mengacu pada dokumen kurikulum RPS resmi yang telah ditetapkan program studi. Seluruh sel bobot bersifat read-only untuk menjaga konsistensi OBE. Pembukaan kunci hanya dapat dilakukan melalui perizinan Administrator / Kaprodi.
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('dosen.nilai.skema', $jadwal->id) }}" method="POST" id="form-matrix-skema">
                        @csrf

                        <div class="matrix-container">
                            <table class="table-matrix" id="table-matrix-alokasi">
                                <thead>
                                    {{-- Baris 1: Grup Header CPMK --}}
                                    <tr>
                                        <th style="min-width: 220px; background: #ffffff; border-bottom: none;"></th>
                                        <th style="width: 75px; background: #ffffff; border-bottom: none; text-align: center;"></th>
                                        @foreach($cpmks as $cpmk)
                                            @if($cpmk->subCpmks->count() > 0)
                                                <th colspan="{{ $cpmk->subCpmks->count() }}" class="matrix-cpmk-head" title="{{ $cpmk->deskripsi }}">
                                                    {{ $cpmk->kode_cpmk }}
                                                </th>
                                            @endif
                                        @endforeach
                                        @if(! $isMatrixLocked)
                                            <th style="width: 44px; background: #ffffff; border-bottom: none;"></th>
                                        @endif
                                    </tr>
                                    {{-- Baris 2: Header Komponen, Bobot, dan Sub-CPMK --}}
                                    <tr>
                                        <th style="background: #f8fafc; font-weight: 700; color: #334155;">Komponen</th>
                                        <th style="background: #f8fafc; font-weight: 700; color: #334155; text-align: center;">Bobot</th>
                                        @php $subIter = 1; @endphp
                                        @foreach($cpmks as $cpmk)
                                            @foreach($cpmk->subCpmks as $sub)
                                                <th class="matrix-sub-head matrix-sub-col"
                                                    data-sub-id="{{ $sub->id }}"
                                                    title="{{ $sub->kode_sub_cpmk }}: {{ $sub->deskripsi }} ({{ $sub->cpl?->kode_cpl ?? 'CPL' }})">
                                                    S{{ $subIter++ }}
                                                </th>
                                            @endforeach
                                        @endforeach
                                        @if(! $isMatrixLocked)
                                            <th style="background: #f8fafc; text-align: center; font-size: 11px; color: #64748b;">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody id="matrix-rows-body">
                                    @forelse($matrixRows as $rIdx => $row)
                                        @php
                                            $rowSum = 0;
                                            foreach ($allSubCpmks as $sub) {
                                                $val = $row['allocations'][$sub->id] ?? ($row['allocations'][(string)$sub->id] ?? 0);
                                                $rowSum += (float) $val;
                                            }
                                        @endphp
                                        <tr class="matrix-data-row" data-row-index="{{ $rIdx }}">
                                            <td>
                                                <input type="text"
                                                       name="matrix_rows[{{ $rIdx }}][nama]"
                                                       class="matrix-row-nama"
                                                       placeholder="Nama Komponen (mis: Kuis)"
                                                       value="{{ $row['nama'] }}"
                                                       required
                                                       {{ $isMatrixLocked ? 'disabled' : '' }}>
                                                <input type="text"
                                                       name="matrix_rows[{{ $rIdx }}][keterangan]"
                                                       class="matrix-row-ket"
                                                       placeholder="Rincian pertemuan (mis: P1, 2, 5...)"
                                                       value="{{ $row['keterangan'] ?? '' }}"
                                                       {{ $isMatrixLocked ? 'disabled' : '' }}>
                                            </td>
                                            <td>
                                                <span class="row-bobot-val" id="row-bobot-{{ $rIdx }}">{{ $rowSum > 0 ? (round($rowSum, 1) . '%') : '0%' }}</span>
                                            </td>
                                            @foreach($allSubCpmks as $sub)
                                                @php
                                                    $allocVal = $row['allocations'][$sub->id] ?? ($row['allocations'][(string)$sub->id] ?? null);
                                                    $allocDisplay = ($allocVal !== null && (float)$allocVal > 0) ? (float)$allocVal : '';
                                                @endphp
                                                <td style="text-align: center; padding: 4px;">
                                                    <input type="number"
                                                           step="0.5"
                                                           min="0"
                                                           max="100"
                                                           class="matrix-input-cell"
                                                           name="matrix_rows[{{ $rIdx }}][allocations][{{ $sub->id }}]"
                                                           value="{{ $allocDisplay }}"
                                                           placeholder="-"
                                                           data-row="{{ $rIdx }}"
                                                           data-sub="{{ $sub->id }}"
                                                           data-cpmk="{{ $sub->cpmk_id }}"
                                                           data-cpl="{{ $sub->cpl?->kode_cpl ?? 'CPL' }}"
                                                           {{ $isMatrixLocked ? 'disabled' : '' }}>
                                                </td>
                                            @endforeach
                                            @if(! $isMatrixLocked)
                                                <td style="text-align: center; padding: 4px;">
                                                    <button type="button" class="btn-outline btn-remove-matrix-row" style="padding: 4px 7px; color: #dc2626; border-color: #fca5a5;" title="Hapus komponen">
                                                        <x-layout-icon name="trash" />
                                                    </button>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        {{-- Default 1 row jika belum ada data --}}
                                        <tr class="matrix-data-row" data-row-index="0">
                                            <td>
                                                <input type="text" name="matrix_rows[0][nama]" class="matrix-row-nama" placeholder="Nama Komponen" value="Tugas 1" required>
                                                <input type="text" name="matrix_rows[0][keterangan]" class="matrix-row-ket" placeholder="Rincian pertemuan" value="Pertemuan 3">
                                            </td>
                                            <td>
                                                <span class="row-bobot-val" id="row-bobot-0">0%</span>
                                            </td>
                                            @foreach($allSubCpmks as $sub)
                                                <td style="text-align: center; padding: 4px;">
                                                    <input type="number" step="0.5" min="0" max="100" class="matrix-input-cell" name="matrix_rows[0][allocations][{{ $sub->id }}]" value="" placeholder="-" data-row="0" data-sub="{{ $sub->id }}" data-cpmk="{{ $sub->cpmk_id }}" data-cpl="{{ $sub->cpl?->kode_cpl ?? 'CPL' }}">
                                                </td>
                                            @endforeach
                                            <td style="text-align: center; padding: 4px;">
                                                <button type="button" class="btn-outline btn-remove-matrix-row" style="padding: 4px 7px; color: #dc2626; border-color: #fca5a5;" title="Hapus komponen">
                                                    <x-layout-icon name="trash" />
                                                </button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f8fafc; font-weight: 700; border-top: 2px solid #cbd5e1;">
                                        <td style="padding: 10px 12px; color: #0f172a; font-weight: 800;">Bobot Sub-CPMK</td>
                                        <td style="text-align: center;">
                                            <span class="grand-total-val" id="grand-total-bobot">100%</span>
                                        </td>
                                        @foreach($allSubCpmks as $sub)
                                            <td style="text-align: center;">
                                                <span class="col-bobot-val" id="col-bobot-{{ $sub->id }}">-</span>
                                            </td>
                                        @endforeach
                                        @if(! $isMatrixLocked)
                                            <td></td>
                                        @endif
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if(! $isMatrixLocked)
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" class="btn-outline" id="btn-add-matrix-row">
                                        <x-layout-icon name="plus" />
                                        <span>Tambah Komponen</span>
                                    </button>
                                    <button type="submit" name="action" value="reset_rps" class="btn-outline" style="color: #0369a1; border-color: #bae6fd; background: #f0f9ff;" onclick="return confirm('Muat ulang matriks alokasi dari template RPS? Perubahan yang belum disimpan akan direset.')">
                                        <x-layout-icon name="refresh" />
                                        <span>Muat Ulang dari RPS</span>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- 3-Column Analytics Grid Sesuai Mockup Mas Dello --}}
                        <div class="obe-analytics-grid">
                            {{-- KOTAK 1: Turunan ke CPMK dan CPL --}}
                            <div class="analytics-card">
                                <div class="analytics-card-head">
                                    <h4>Turunan ke CPMK dan CPL</h4>
                                </div>
                                <div class="analytics-card-body">
                                    <div style="margin-bottom: 14px;">
                                        @foreach($cpmks as $cpmk)
                                            @php
                                                $firstSubCpl = $cpmk->subCpmks->first()?->cpl?->kode_cpl ?? 'CPL';
                                            @endphp
                                            <div class="rollup-item">
                                                <span>
                                                     <strong>{{ $cpmk->kode_cpmk }}</strong>
                                                    <span style="color: #64748b; font-size: 11px;">{{ $cpmkSubRanges[$cpmk->id] ?? '' }}</span>
                                                    &rarr; <span class="cpl-pill">{{ $firstSubCpl }}*</span>
                                                </span>
                                                <span style="font-weight: 700; color: #0f172a;" id="rollup-cpmk-{{ $cpmk->id }}">0%</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-top: auto; padding-top: 10px; border-top: 1px solid #e2e8f0; margin-bottom: 8px;">
                                        Bobot CPL hasil turunan vs ketetapan prodi
                                    </div>

                                    @php
                                        $uniqueCpls = $allSubCpmks->pluck('cpl')->filter()->unique('id');
                                    @endphp
                                    @foreach($uniqueCpls as $cpl)
                                        @php
                                            $targetVal = $targetPorsiCpl[$cpl->kode_cpl] ?? null;
                                        @endphp
                                        <div class="rollup-item">
                                            <span><strong>{{ $cpl->kode_cpl }}</strong></span>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <strong id="rollup-cpl-{{ $cpl->kode_cpl }}">0%</strong>
                                                @if($targetVal !== null)
                                                    <span style="font-size: 11px; color: #047857;">target: {{ $targetVal }}%</span>
                                                @else
                                                    <span class="badge-target-empty">target: belum ada</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- KOTAK 2: Cek tabel komponen RPS --}}
                            <div class="analytics-card">
                                <div class="analytics-card-head">
                                    <h4>Cek tabel komponen RPS</h4>
                                    <div class="subtitle">Jumlah bobot mingguan vs tabel "Komponen dan bobot penilaian"</div>
                                </div>
                                <div class="analytics-card-body">
                                    <div class="rollup-item">
                                        <span style="max-width: 200px;">Kuis, keaktifan, kerja sama tim</span>
                                        <span class="pill-compare pill-diff" id="comp-kuis">20 / 15</span>
                                    </div>
                                    <div class="rollup-item">
                                        <span style="max-width: 200px;">Tugas (terstruktur, studi kasus, proyek)</span>
                                        <span class="pill-compare pill-diff" id="comp-tugas">25 / 30</span>
                                    </div>
                                    <div class="rollup-item">
                                        <span>UTS</span>
                                        <span class="pill-compare pill-same" id="comp-uts">25 / 25</span>
                                    </div>
                                    <div class="rollup-item">
                                        <span>UAS</span>
                                        <span class="pill-compare pill-same" id="comp-uas">30 / 30</span>
                                    </div>
                                    <div class="rollup-item" style="margin-top: auto; padding-top: 12px; border-top: 1px solid #e2e8f0; font-weight: 800;">
                                        <span>Total</span>
                                        <span class="pill-compare pill-total" id="comp-total">100 / 100</span>
                                    </div>
                                </div>
                            </div>

                            {{-- KOTAK 3: Temuan pada RPS & Aksi --}}
                            <div class="analytics-card">
                                <div class="analytics-card-head">
                                    <h4>Temuan pada RPS</h4>
                                    <div class="subtitle">Catatan audit sinkronisasi rincian vs ringkasan bobot</div>
                                </div>
                                <div class="analytics-card-body">
                                    <div style="margin-bottom: 16px;">
                                        @forelse($temuanList as $fIdx => $temuan)
                                            <div class="finding-item">
                                                <div class="finding-num">{{ $loop->iteration }}</div>
                                                <div>{{ $temuan }}</div>
                                            </div>
                                        @empty
                                            <div style="font-size: 12.5px; color: #16a34a; font-weight: 600;">
                                                Seluruh pemetaan dan pembagian bobot RPS telah selaras dan terverifikasi.
                                            </div>
                                        @endforelse
                                    </div>

                                    <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid #e2e8f0;">
                                        @if(! $isMatrixLocked)
                                            <div style="display: flex; gap: 8px;">
                                                <button type="submit" name="action" value="draft" class="btn-outline" style="flex: 1; text-align: center; font-size: 12.5px;">
                                                    Simpan draf
                                                </button>
                                                <button type="submit" name="action" value="save" class="btn-primary" style="flex: 1.5; text-align: center; justify-content: center; font-size: 13px;">
                                                    <x-layout-icon name="check" />
                                                    <span>Simpan &amp; Lanjut ke Input Nilai</span>
                                                </button>
                                            </div>
                                        @else
                                            <a href="{{ route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'input']) }}" class="btn-primary" style="width: 100%; text-align: center; justify-content: center; font-size: 13px; text-decoration: none;">
                                                <span>Lanjut ke Input Nilai</span>
                                                <x-layout-icon name="arrow-right" />
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    {{-- =========================================================================
         TAB 2: INPUT NILAI MAHASISWA
         ========================================================================= --}}
    @elseif($tab === 'input')
        @php
            $komponens = $skema->komponens;
        @endphp

        @if($komponens->isEmpty())
            <div class="obe-card">
                <div class="obe-card-body" style="text-align:center; padding:40px 20px;">
                    <div style="max-width:480px; margin:0 auto;">
                        <span class="empty-state-icon" style="font-size:36px; display:inline-block; margin-bottom:12px; color:#2563eb;">
                            <x-layout-icon name="services" />
                        </span>
                        <h3 style="margin-top:0; color:#0f172a;">Pengaturan Penilaian Belum Diisi</h3>
                        <p style="color:#64748b; font-size:14px; margin-bottom:20px;">
                            Sebelum menginput nilai mahasiswa, silakan atur instrumen penilaian (Tugas, Kuis, UTS, UAS) dan bobot persentasenya terlebih dahulu pada Tab 1.
                        </p>
                        <a href="{{ route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'pengaturan']) }}" class="btn-primary">
                            <x-layout-icon name="services" />
                            <span>Buka Tab Pengaturan Penilaian</span>
                        </a>
                    </div>
                </div>
            </div>
        @else
            {{-- Toolbar Aksi: Search, Excel, Finalisasi --}}
            <div class="obe-toolbar">
                <div class="obe-search-box">
                    <span class="icon-search-inside">
                        <x-layout-icon name="search" />
                    </span>
                    <input type="text"
                           id="filter-mhs"
                           class="form-control"
                           placeholder="Cari NIM atau Nama Mahasiswa...">
                </div>

                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <a href="{{ route('dosen.nilai.template', $jadwal->id) }}" class="btn-outline" title="Unduh format spreadsheet">
                        <x-layout-icon name="download" />
                        <span>Unduh Template Excel</span>
                    </a>

                    @if(! $skema->is_finalized)
                        <button type="button" class="btn-outline" onclick="openModal('modal-import-excel')">
                            <x-layout-icon name="file-check" />
                            <span>Impor Excel</span>
                        </button>

                        <button type="button" class="btn-primary" onclick="openFirstStudent()">
                            <x-layout-icon name="edit" />
                            <span>Mulai Input Nilai</span>
                        </button>

                        <button type="button" class="btn-primary" style="background:#16a34a; border-color:#16a34a;" onclick="openModal('modal-finalize')">
                            <x-layout-icon name="lock" />
                            <span>Finalisasi Nilai</span>
                        </button>
                    @else
                        <span class="badge badge-success" style="padding:8px 14px; display:inline-flex; align-items:center; gap:6px;">
                            <x-layout-icon name="lock" />
                            <span>Nilai Terkunci (Final)</span>
                        </span>
                    @endif
                </div>
            </div>

            {{-- Master Table: Daftar Nilai Mahasiswa (Clean Master View) --}}
            <div class="page-card">
                <div class="page-card-head" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h2 style="margin:0;">Daftar Nilai Mahasiswa — Kelas {{ $jadwal->kelas }}</h2>
                        <div style="font-size:12.5px; color:#64748b; margin-top:3px;">
                            Klik pada nama mahasiswa atau tombol <strong>Detail &amp; Input</strong> untuk membuka formulir matriks OBE per mahasiswa.
                        </div>
                    </div>
                    <span class="badge badge-info">{{ $krsList->count() }} Mahasiswa Terdaftar</span>
                </div>

                <div class="page-card-body" style="padding:0;">
                    <div class="table-wrap">
                        <table class="table-input-nilai" style="width:100%; border-collapse:collapse;" id="table-nilai">
                            <thead>
                                <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0;">
                                    <th style="width:45px; text-align:center;">No</th>
                                    <th style="width:115px;">NIM</th>
                                    <th style="min-width:200px;">Nama Mahasiswa</th>
                                    <th style="text-align:center; width:110px; background:#eff6ff;">Nilai Akhir</th>
                                    <th style="text-align:center; width:80px; background:#eff6ff;">Huruf</th>
                                    <th style="text-align:center; width:80px; background:#eff6ff;">Mutu</th>
                                    <th style="text-align:center; width:170px;">Status Ketercapaian CPL</th>
                                    <th style="text-align:center; width:150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($krsList as $krs)
                                    @php
                                        $ass = $studentAssessments[$krs->id] ?? [
                                            'na' => 0,
                                            'nilai_huruf' => 'E',
                                            'bobot' => 0.0,
                                            'all_cpl_achieved' => false,
                                            'unachieved_cpls' => [],
                                        ];
                                    @endphp
                                    <tr class="mhs-row mhs-row-clickable"
                                        data-nim="{{ strtolower($krs->mahasiswa?->nim ?? '') }}"
                                        data-nama="{{ strtolower($krs->mahasiswa?->nama ?? '') }}"
                                        onclick="openStudentDrilldown({{ $krs->id }})">
                                        <td style="text-align:center; font-weight:600; color:#64748b;">{{ $loop->iteration }}</td>
                                        <td style="font-weight:700; font-family:monospace; color:#334155;">{{ $krs->mahasiswa?->nim ?? '-' }}</td>
                                        <td>
                                            <button type="button" class="mhs-name-btn" onclick="event.stopPropagation(); openStudentDrilldown({{ $krs->id }})">
                                                <strong>{{ $krs->mahasiswa?->nama ?? '-' }}</strong>
                                            </button>
                                        </td>
                                        <td style="text-align:center; font-weight:800; font-size:14px; background:#eff6ff; color:#0f172a;" id="cell-na-{{ $krs->id }}">
                                            {{ number_format($ass['na'], 2) }}
                                        </td>
                                        <td style="text-align:center; background:#eff6ff;" id="cell-huruf-{{ $krs->id }}">
                                            <span class="grade-chip grade-{{ substr($ass['nilai_huruf'], 0, 1) }}">
                                                {{ $ass['nilai_huruf'] }}
                                            </span>
                                        </td>
                                        <td style="text-align:center; font-weight:600; background:#eff6ff;" id="cell-mutu-{{ $krs->id }}">
                                            {{ number_format($ass['bobot'], 2) }}
                                        </td>
                                        <td style="text-align:center;" id="cell-cpl-{{ $krs->id }}">
                                            @if($ass['all_cpl_achieved'])
                                                <span class="badge badge-success" style="font-size:11px; padding:3px 8px;">
                                                    <x-layout-icon name="check" /> Lulus CPL
                                                </span>
                                            @else
                                                <span class="badge badge-warning" style="font-size:11px; padding:3px 8px;" title="Belum: {{ implode(', ', $ass['unachieved_cpls']) }}">
                                                    Belum Lulus
                                                </span>
                                            @endif
                                        </td>
                                        <td style="text-align:center;" onclick="event.stopPropagation();">
                                            <button type="button"
                                                    class="btn-primary"
                                                    style="font-size:12px; padding:5px 12px; display:inline-flex; align-items:center; gap:6px;"
                                                    onclick="openStudentDrilldown({{ $krs->id }})">
                                                <x-layout-icon name="edit" />
                                                <span>Detail &amp; Input</span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" style="text-align:center; padding:40px; color:#64748b;">
                                            Belum ada mahasiswa yang mengambil kelas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- =========================================================================
                 MODAL DRILLDOWN INPUT NILAI OBE PER MAHASISWA (MATRIKS 2D)
                 ========================================================================= --}}
            <div id="modal-drilldown-mhs" class="modal-backdrop-custom" style="display:none;">
                <div class="modal-box-custom modal-box-large">
                    {{-- Header Modal --}}
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; border-bottom:1px solid #e2e8f0; padding-bottom:14px; gap:12px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div id="modal-mhs-avatar" style="width:44px; height:44px; border-radius:50%; background:#dbeafe; color:#1d4ed8; font-weight:800; font-size:16px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                M
                            </div>
                            <div>
                                <h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a;" id="modal-mhs-nama">Nama Mahasiswa</h3>
                                <div style="font-size:12.5px; color:#64748b; margin-top:2px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                    <span>NIM: <strong id="modal-mhs-nim" style="font-family:monospace; color:#0f172a;">-</strong></span>
                                    <span>&bull;</span>
                                    <span>Kelas: <strong>{{ $jadwal->kelas }}</strong></span>
                                    <span>&bull;</span>
                                    <span>SKS: <strong>{{ $jadwal->mataKuliah->sks ?? '-' }} SKS</strong></span>
                                </div>
                            </div>
                        </div>

                        {{-- Navigasi Cepat Mahasiswa --}}
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button type="button" class="btn-outline" id="btn-modal-prev" onclick="navigateStudent(-1)" style="font-size:12px; padding:6px 10px;" title="Mahasiswa Sebelumnya (Alt + Panah Kiri)">
                                &larr; Sebelumnya
                            </button>
                            <span id="modal-student-counter" style="font-size:12px; font-weight:700; color:#475569; min-width:55px; text-align:center;">1 / 1</span>
                            <button type="button" class="btn-outline" id="btn-modal-next" onclick="navigateStudent(1)" style="font-size:12px; padding:6px 10px;" title="Mahasiswa Berikutnya (Alt + Panah Kanan)">
                                Berikutnya &rarr;
                            </button>
                            <button type="button" onclick="closeDrilldownModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#64748b; line-height:1; margin-left:8px;" title="Tutup (Esc)">
                                &times;
                            </button>
                        </div>
                    </div>

                    {{-- Live KPI Summary Banner --}}
                    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:18px;">
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Nilai Akhir (NA)</div>
                            <div id="modal-kpi-na" style="font-size:24px; font-weight:800; color:#0f172a; margin-top:2px;">0.00</div>
                        </div>
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Nilai Huruf</div>
                            <div id="modal-kpi-huruf" style="margin-top:4px;">
                                <span class="grade-chip grade-E" style="font-size:14px; padding:4px 12px;">E</span>
                            </div>
                        </div>
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Bobot Mutu</div>
                            <div id="modal-kpi-mutu" style="font-size:24px; font-weight:800; color:#0f172a; margin-top:2px;">0.00</div>
                        </div>
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                            <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700; margin-bottom:4px;">Ketercapaian CPL</div>
                            <div id="modal-kpi-cpl">
                                <span class="badge badge-warning" style="font-size:11.5px; padding:4px 10px;">Belum Lulus</span>
                            </div>
                        </div>
                    </div>

                    {{-- 2D Matrix Input Table --}}
                    <div style="margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <div style="font-size:12.5px; font-weight:700; color:#0f172a;">
                                Matriks Instrumen Penilaian &times; Sub-CPMK:
                            </div>
                            <div style="font-size:11.5px; color:#64748b;">
                                Input skor mentah (skala 0 - 100). Bobot (%) telah baku dari RPS.
                            </div>
                        </div>

                        <div class="matrix-container" style="margin-bottom:0; max-height:42vh; overflow-y:auto;">
                            <table class="table-matrix" id="table-modal-matrix">
                                <thead>
                                    {{-- Baris 1: Header CPMK --}}
                                    <tr>
                                        <th style="min-width:200px; background:#ffffff; border-bottom:none;"></th>
                                        <th style="width:70px; background:#ffffff; border-bottom:none; text-align:center;"></th>
                                        @foreach($cpmks as $cpmk)
                                            @if($cpmk->subCpmks->count() > 0)
                                                <th colspan="{{ $cpmk->subCpmks->count() }}" class="matrix-cpmk-head" title="{{ $cpmk->deskripsi }}">
                                                    {{ $cpmk->kode_cpmk }}
                                                </th>
                                            @endif
                                        @endforeach
                                    </tr>
                                    {{-- Baris 2: Header Komponen, Bobot, Sub-CPMK --}}
                                    <tr>
                                        <th style="background:#f8fafc; font-weight:700; color:#334155;">Komponen Penilaian</th>
                                        <th style="background:#f8fafc; font-weight:700; color:#334155; text-align:center;">Bobot</th>
                                        @php $subIter = 1; @endphp
                                        @foreach($cpmks as $cpmk)
                                            @foreach($cpmk->subCpmks as $sub)
                                                <th class="matrix-sub-head" style="min-width:54px;" title="{{ $sub->kode_sub_cpmk }}: {{ $sub->deskripsi }} ({{ $sub->cpl?->kode_cpl ?? 'CPL' }})">
                                                    S{{ $subIter++ }}
                                                </th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($matrixRows as $rIdx => $row)
                                        @php
                                            $rowSum = 0;
                                            foreach ($allSubCpmks as $sub) {
                                                $val = $row['allocations'][$sub->id] ?? ($row['allocations'][(string)$sub->id] ?? 0);
                                                $rowSum += (float) $val;
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                <div style="font-weight:700; color:#0f172a; font-size:12.5px;">{{ $row['nama'] }}</div>
                                                @if(!empty($row['keterangan']))
                                                    <div style="font-size:11px; color:#64748b;">{{ $row['keterangan'] }}</div>
                                                @endif
                                            </td>
                                            <td style="text-align:center; font-weight:800; font-size:12.5px; color:#0f172a;">
                                                {{ round($rowSum, 1) }}%
                                            </td>
                                            @foreach($allSubCpmks as $sub)
                                                @php
                                                    $alloc = (float) ($row['allocations'][$sub->id] ?? ($row['allocations'][(string)$sub->id] ?? 0));
                                                    $komp = $komponenMatrixMap[$rIdx][$sub->id] ?? null;
                                                @endphp
                                                @if($alloc > 0 && $komp)
                                                    <td style="text-align:center; padding:5px 4px; vertical-align:middle; background:#ffffff;">
                                                        <input type="number"
                                                               step="0.5"
                                                               min="0"
                                                               max="100"
                                                               class="matrix-score-input"
                                                               data-komponen-id="{{ $komp->id }}"
                                                               data-row="{{ $rIdx }}"
                                                               data-sub="{{ $sub->id }}"
                                                               data-weight="{{ $alloc }}"
                                                               data-cpl="{{ $sub->cpl?->kode_cpl ?? '' }}"
                                                               placeholder="0"
                                                               {{ $skema->is_finalized ? 'disabled' : '' }}
                                                               style="width:50px; height:32px; text-align:center; font-weight:700; font-size:13px; border:1px solid #cbd5e1; border-radius:6px; margin:0 auto; display:block;">
                                                        <span style="font-size:10px; color:#64748b; font-weight:600; display:block; margin-top:2px;">{{ $alloc }}%</span>
                                                    </td>
                                                @else
                                                    <td style="text-align:center; background:#f8fafc; color:#cbd5e1; font-weight:700; font-size:13px;">
                                                        -
                                                    </td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="background:#f8fafc; font-weight:700; border-top:2px solid #cbd5e1;">
                                        <td style="padding:8px 10px; color:#0f172a; font-weight:800; font-size:12px;">Bobot Sub-CPMK</td>
                                        <td style="text-align:center; font-weight:800; color:#15803d; font-size:12px;">100%</td>
                                        @foreach($allSubCpmks as $sub)
                                            @php
                                                $colSum = 0;
                                                foreach ($matrixRows as $r) {
                                                    $colSum += (float) ($r['allocations'][$sub->id] ?? ($r['allocations'][(string)$sub->id] ?? 0));
                                                }
                                            @endphp
                                            <td style="text-align:center; font-weight:800; font-size:12px; color:#0f172a;">
                                                {{ $colSum > 0 ? (round($colSum, 1) . '%') : '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Rincian Ketercapaian CPL --}}
                    <div style="margin-bottom:18px;">
                        <div style="font-size:12.5px; font-weight:700; color:#0f172a; margin-bottom:8px;">
                            Status Ketercapaian CPL Mahasiswa:
                        </div>
                        <div id="modal-cpl-list" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:10px;">
                            {{-- Diisi secara dinamis oleh JavaScript --}}
                        </div>
                    </div>

                    {{-- Modal Footer Actions --}}
                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e2e8f0; padding-top:14px; gap:12px; flex-wrap:wrap;">
                        <div style="font-size:12px; color:#64748b;">
                            Gunakan tombol <kbd style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; padding:2px 5px; font-size:11px;">Tab</kbd> untuk berpindah antar sel nilai.
                        </div>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <button type="button" class="btn-outline" onclick="closeDrilldownModal()">Tutup</button>
                            @if(! $skema->is_finalized)
                                <button type="button" class="btn-primary" id="btn-save-current-mhs" onclick="saveCurrentStudent()">
                                    <x-layout-icon name="save" />
                                    <span id="label-btn-save-mhs">Simpan Nilai Mahasiswa</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

    {{-- =========================================================================
         TAB 3: ANALITIK KETERCAPAIAN CPL & CPMK
         ========================================================================= --}}
    @elseif($tab === 'capaian')
        @php
            $pg = $capaianKelas['passing_grade'] ?? 60.0;
            $totalMhs = $capaianKelas['total_mahasiswa'] ?? 0;
            $cplReport = $capaianKelas['cpl_report'] ?? [];
            $cpmkReport = $capaianKelas['cpmk_report'] ?? [];
            $gradeCounts = $capaianKelas['grade_counts'] ?? [];

            // Hitung rata-rata Nilai Akhir kelas
            $sumNa = 0;
            $lulusNaCount = 0;
            foreach ($studentAssessments as $st) {
                $sumNa += $st['na'];
                if ($st['na'] >= $pg) {
                    $lulusNaCount++;
                }
            }
            $avgNa = $totalMhs > 0 ? round($sumNa / $totalMhs, 2) : 0;
            $pctLulusNa = $totalMhs > 0 ? round(($lulusNaCount / $totalMhs) * 100, 1) : 0;
        @endphp

        {{-- KPI Cards --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-title">
                    <x-layout-icon name="users" />
                    <span>Total Mahasiswa</span>
                </div>
                <div class="kpi-value">{{ $totalMhs }}</div>
                <div class="kpi-subtitle">Terdaftar di Kelas {{ $jadwal->kelas }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-title">
                    <x-layout-icon name="chart" />
                    <span>Rata-Rata Nilai Akhir</span>
                </div>
                <div class="kpi-value">{{ $avgNa }}</div>
                <div class="kpi-subtitle">Standar Kelulusan: &ge; {{ number_format($pg, 0) }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-title">
                    <x-layout-icon name="file-check" />
                    <span>Mahasiswa Lulus Mata Kuliah</span>
                </div>
                <div class="kpi-value" style="color:#16a34a;">{{ $lulusNaCount }} <span style="font-size:14px; font-weight:600;">({{ $pctLulusNa }}%)</span></div>
                <div class="kpi-subtitle">{{ $totalMhs - $lulusNaCount }} Mahasiswa Tidak Lulus</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-title">
                    <x-layout-icon name="lock" />
                    <span>Status Finalisasi</span>
                </div>
                <div class="kpi-value" style="font-size:20px;">
                    @if($skema->is_finalized)
                        <span class="badge badge-success">Finalisasi</span>
                    @else
                        <span class="badge badge-warning">Draf Terbuka</span>
                    @endif
                </div>
                <div class="kpi-subtitle">
                    {{ $skema->is_finalized ? 'Tersinkron ke KHS' : 'Belum difinalisasi' }}
                </div>
            </div>
        </div>

        {{-- Section 1: Ketercapaian CPL --}}
        <div class="obe-card">
            <div class="obe-card-head">
                <h3>
                    <x-layout-icon name="chart" />
                    <span>Ketercapaian Capaian Pembelajaran Lulusan (CPL)</span>
                </h3>
                <span class="badge badge-info">Target Nilai &ge; {{ number_format($pg, 0) }}</span>
            </div>
            <div class="obe-card-body">
                @if(empty($cplReport))
                    <div style="text-align:center; padding:30px; color:#64748b;">
                        Belum ada pemetaan CPL pada instrumen penilaian atau nilai mahasiswa belum diisi.
                    </div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:120px;">Kode CPL</th>
                                    <th>Target Passing Grade</th>
                                    <th>Rata-Rata Skor Kelas</th>
                                    <th>Mahasiswa Lulus (&ge; {{ number_format($pg,0) }})</th>
                                    <th style="min-width:200px;">Tingkat Kelulusan (%)</th>
                                    <th style="text-align:center;">Evaluasi Ketercapaian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cplReport as $cplCode => $stat)
                                    @php
                                        $barColor = $stat['pass_rate'] >= 75 ? 'progress-green' : ($stat['pass_rate'] >= 50 ? 'progress-yellow' : 'progress-red');
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="cpl-pill" style="font-size:12px; padding:4px 9px;">{{ $cplCode }}</span>
                                        </td>
                                        <td>&ge; {{ number_format($stat['target_passing_grade'], 1) }}</td>
                                        <td><strong>{{ number_format($stat['avg_score'], 2) }}</strong></td>
                                        <td>{{ $stat['pass_count'] }} dari {{ $totalMhs }} mahasiswa</td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div class="progress-bar-bg" style="flex:1;">
                                                    <div class="progress-bar-fill {{ $barColor }}" style="width: {{ min(100, $stat['pass_rate']) }}%;"></div>
                                                </div>
                                                <span style="font-weight:700; font-size:12px; min-width:44px;">{{ $stat['pass_rate'] }}%</span>
                                            </div>
                                        </td>
                                        <td style="text-align:center;">
                                            @if($stat['pass_rate'] >= 75)
                                                <span class="badge badge-success">Tercapai Sangat Baik</span>
                                            @elseif($stat['pass_rate'] >= 50)
                                                <span class="badge badge-warning">Tercapai Cukup</span>
                                            @else
                                                <span class="badge badge-danger">Perlu Tindak Lanjut</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Section 2: Ketercapaian CPMK & Distribusi Nilai Huruf --}}
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:22px; margin-bottom:22px;">
            {{-- Ketercapaian CPMK --}}
            <div class="obe-card">
                <div class="obe-card-head">
                    <h3>
                        <x-layout-icon name="book" />
                        <span>Ketercapaian CPMK Kelas</span>
                    </h3>
                </div>
                <div class="obe-card-body">
                    @if(empty($cpmkReport))
                        <div style="text-align:center; padding:24px; color:#64748b;">
                            Belum ada ketercapaian CPMK yang terhitung.
                        </div>
                    @else
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            @foreach($cpmkReport as $cpmkCode => $st)
                                @php
                                    $fillCls = $st['pass_rate'] >= 70 ? 'progress-green' : ($st['pass_rate'] >= 50 ? 'progress-yellow' : 'progress-red');
                                @endphp
                                <div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; font-size:13px;">
                                        <span><strong class="cpmk-pill">{{ $cpmkCode }}</strong> Rata-rata Skor: <strong>{{ $st['avg_score'] }}</strong></span>
                                        <span style="font-weight:700;">{{ $st['pass_rate'] }}% Lulus</span>
                                    </div>
                                    <div class="progress-bar-bg">
                                        <div class="progress-bar-fill {{ $fillCls }}" style="width: {{ min(100, $st['pass_rate']) }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Distribusi Nilai Huruf --}}
            <div class="obe-card">
                <div class="obe-card-head">
                    <h3>
                        <x-layout-icon name="chart" />
                        <span>Distribusi Nilai Huruf STTMI</span>
                    </h3>
                </div>
                <div class="obe-card-body">
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px;">
                        @foreach(['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'E'] as $gr)
                            @php
                                $cnt = $gradeCounts[$gr] ?? 0;
                                $pct = $totalMhs > 0 ? round(($cnt / $totalMhs) * 100, 1) : 0;
                            @endphp
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; text-align:center;">
                                <span class="grade-chip grade-{{ substr($gr, 0, 1) }}" style="font-size:14px; padding:4px 10px;">{{ $gr }}</span>
                                <div style="font-size:20px; font-weight:800; margin-top:6px; color:#0f172a;">{{ $cnt }}</div>
                                <div style="font-size:11px; color:#64748b;">{{ $pct }}% Mahasiswa</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Rincian Ketercapaian Per Mahasiswa --}}
        <div class="page-card">
            <div class="page-card-head">
                <h2>Rincian Ketercapaian CPL Mahasiswa Individual</h2>
                <div class="obe-search-box" style="max-width:280px;">
                    <span class="icon-search-inside"><x-layout-icon name="search" /></span>
                    <input type="text" id="filter-mhs-capaian" class="form-control" placeholder="Cari nama mahasiswa...">
                </div>
            </div>
            <div class="page-card-body">
                <div class="table-wrap">
                    <table id="table-mhs-capaian">
                        <thead>
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th style="text-align:center;">Nilai Akhir (NA)</th>
                                <th style="text-align:center;">Huruf</th>
                                <th>Skor per CPL</th>
                                <th style="text-align:center;">Status Evaluasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($krsList as $krs)
                                @php
                                    $ass = $studentAssessments[$krs->id] ?? [
                                        'na' => 0,
                                        'nilai_huruf' => 'E',
                                        'all_cpl_achieved' => false,
                                        'unachieved_cpls' => [],
                                        'cpl_results' => [],
                                    ];
                                @endphp
                                <tr class="mhs-capaian-row" data-search="{{ strtolower(($krs->mahasiswa?->nim ?? '') . ' ' . ($krs->mahasiswa?->nama ?? '')) }}">
                                    <td style="text-align:center; font-weight:600;">{{ $loop->iteration }}</td>
                                    <td style="font-family:monospace; font-weight:600;">{{ $krs->mahasiswa?->nim ?? '-' }}</td>
                                    <td style="font-weight:600;">{{ $krs->mahasiswa?->nama ?? '-' }}</td>
                                    <td style="text-align:center; font-weight:800;">{{ number_format($ass['na'], 2) }}</td>
                                    <td style="text-align:center;">
                                        <span class="grade-chip grade-{{ substr($ass['nilai_huruf'], 0, 1) }}">{{ $ass['nilai_huruf'] }}</span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                            @foreach($ass['cpl_results'] as $cplK => $cRes)
                                                <span class="badge {{ $cRes['achieved'] ? 'badge-success' : 'badge-danger' }}" style="font-size:11px;" title="{{ $cRes['achieved'] ? 'Lulus' : 'Belum Lulus' }}">
                                                    {{ $cplK }}: {{ number_format($cRes['score'], 1) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td style="text-align:center;">
                                        @if($ass['all_cpl_achieved'])
                                            <span class="badge badge-success">Lulus Semua CPL</span>
                                        @else
                                            <span class="badge badge-warning" title="Belum: {{ implode(', ', $ass['unachieved_cpls']) }}">
                                                Perbaikan {{ implode(', ', $ass['unachieved_cpls']) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:30px; color:#64748b;">
                                        Belum ada data mahasiswa.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>

{{-- =========================================================================
     MODAL: IMPOR NILAI EXCEL / CSV
     ========================================================================= --}}
<div id="modal-import-excel" class="modal-backdrop-custom" style="display:none;">
    <div class="modal-box-custom">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; font-size:16px; font-weight:700;">Impor Nilai Mahasiswa dari Excel / CSV</h3>
            <button type="button" onclick="closeModal('modal-import-excel')" style="background:none; border:none; cursor:pointer; color:#64748b;">
                <x-layout-icon name="x" />
            </button>
        </div>

        <form action="{{ route('dosen.nilai.import', $jadwal->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px; margin-bottom:16px; font-size:13px; color:#1e40af;">
                <div style="font-weight:700; margin-bottom:4px;">Petunjuk Impor File:</div>
                <ol style="margin:0; padding-left:18px;">
                    <li>Unduh terlebih dahulu <strong>Template Excel</strong> melalui tombol toolbar di atas.</li>
                    <li>Isi nilai mahasiswa (0 - 100) pada kolom instrumen tanpa mengubah urutan kolom NIM dan Nama.</li>
                    <li>Simpan dan unggah kembali file format CSV / Excel di bawah ini.</li>
                </ol>
            </div>

            <div style="margin-bottom:20px;">
                <label for="import-file" style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Pilih File Excel / CSV:</label>
                <input type="file" id="import-file" name="file" class="form-control" accept=".csv, .xlsx, .xls, text/csv" required>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn-outline" onclick="closeModal('modal-import-excel')">Batal</button>
                <button type="submit" class="btn-primary">
                    <x-layout-icon name="download" />
                    <span>Mulai Impor</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================================
     MODAL: KONFIRMASI FINALISASI NILAI
     ========================================================================= --}}
<div id="modal-finalize" class="modal-backdrop-custom" style="display:none;">
    <div class="modal-box-custom">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; font-size:16px; font-weight:700; color:#0f172a;">Konfirmasi Finalisasi Nilai Kelas</h3>
            <button type="button" onclick="closeModal('modal-finalize')" style="background:none; border:none; cursor:pointer; color:#64748b;">
                <x-layout-icon name="x" />
            </button>
        </div>

        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px; margin-bottom:18px; font-size:13px; color:#991b1b;">
            <div style="font-weight:700; margin-bottom:4px;">Peringatan Akademik Penting:</div>
            <p style="margin:0 0 6px;">
                Setelah difinalisasi:
            </p>
            <ul style="margin:0; padding-left:18px;">
                <li>Skema instrumen dan nilai seluruh mahasiswa kelas ini akan <strong>terkunci</strong> secara permanen.</li>
                <li>Nilai Akhir (NA), Nilai Huruf, dan Mutu akan langsung disinkronkan ke <strong>KHS</strong> dan perhitungan <strong>IPK Mahasiswa</strong>.</li>
                <li>Perubahan nilai setelah finalisasi hanya dapat dibuka kembali melalui izin <strong>Administrator / Kaprodi</strong>.</li>
            </ul>
        </div>

        <form action="{{ route('dosen.nilai.finalisasi', $jadwal->id) }}" method="POST">
            @csrf

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn-outline" onclick="closeModal('modal-finalize')">Batal</button>
                <button type="submit" class="btn-primary" style="background:#16a34a; border-color:#16a34a;">
                    <x-layout-icon name="lock" />
                    <span>Ya, Finalisasi Nilai Sekarang</span>
                </button>
            </div>
        </form>
    </div>
</div>



{{-- =========================================================================
     JAVASCRIPT LOGIC
     ========================================================================= --}}
@push('scripts')
<script>
    function openModal(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'flex';
    }
    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }

    // Tutup modal jika klik di luar box
    document.querySelectorAll('.modal-backdrop-custom').forEach(b => {
        b.addEventListener('click', e => {
            if (e.target === b) b.style.display = 'none';
        });
    });

    function submitNilaiForm() {
        const form = document.getElementById('form-input-nilai');
        if (form) form.submit();
    }

    // -------------------------------------------------------------------------
    // TAB 1: KALKULASI DINAMIS MATRIKS ALOKASI BOBOT INSTRUMEN KE SUB-CPMK
    // -------------------------------------------------------------------------
    @if($tab === 'pengaturan')
    (function(){
        const tableMatrix = document.getElementById('table-matrix-alokasi');
        const rowsBody = document.getElementById('matrix-rows-body');
        const btnAddRow = document.getElementById('btn-add-matrix-row');

        function hitungMatrix() {
            let grandTotal = 0;
            const colTotals = {};
            const cpmkTotals = {};
            const cplTotals = {};

            // Inisialisasi total kolom Sub-CPMK
            document.querySelectorAll('.matrix-sub-col').forEach(th => {
                const subId = th.getAttribute('data-sub-id');
                if (subId) colTotals[subId] = 0;
            });

            // Loop setiap baris instrumen
            const rows = document.querySelectorAll('.matrix-data-row');
            rows.forEach((row, rIdx) => {
                row.setAttribute('data-row-index', rIdx);
                let rowSum = 0;

                row.querySelectorAll('.matrix-input-cell').forEach(inp => {
                    const val = parseFloat(inp.value) || 0;
                    const subId = inp.getAttribute('data-sub');
                    const cpmkId = inp.getAttribute('data-cpmk');
                    const cplCode = inp.getAttribute('data-cpl');

                    rowSum += val;

                    if (subId) colTotals[subId] = (colTotals[subId] || 0) + val;
                    if (cpmkId) cpmkTotals[cpmkId] = (cpmkTotals[cpmkId] || 0) + val;
                    if (cplCode) cplTotals[cplCode] = (cplTotals[cplCode] || 0) + val;
                });

                grandTotal += rowSum;

                // Update badge bobot baris
                const rowBobotBadge = row.querySelector('.row-bobot-val');
                if (rowBobotBadge) {
                    rowBobotBadge.textContent = (Math.round(rowSum * 10) / 10) + '%';
                }
            });

            // Update badge total per kolom Sub-CPMK
            for (const [subId, sum] of Object.entries(colTotals)) {
                const colBadge = document.getElementById('col-bobot-' + subId);
                if (colBadge) {
                    colBadge.textContent = sum > 0 ? (Math.round(sum * 10) / 10) : '-';
                }
            }

            // Update grand total
            const grandBadge = document.getElementById('grand-total-bobot');
            if (grandBadge) {
                const roundedGrand = Math.round(grandTotal * 10) / 10;
                grandBadge.textContent = roundedGrand + '%';
                if (Math.abs(roundedGrand - 100) < 0.1) {
                    grandBadge.classList.remove('invalid');
                    grandBadge.style.color = '#15803d';
                } else {
                    grandBadge.classList.add('invalid');
                    grandBadge.style.color = '#dc2626';
                }
            }

            // Update Card 1: Turunan ke CPMK
            for (const [cpmkId, sum] of Object.entries(cpmkTotals)) {
                const cpmkBadge = document.getElementById('rollup-cpmk-' + cpmkId);
                if (cpmkBadge) {
                    cpmkBadge.textContent = (Math.round(sum * 10) / 10) + '%';
                }
            }

            // Update Card 1: Turunan ke CPL
            for (const [cplCode, sum] of Object.entries(cplTotals)) {
                const cplBadge = document.getElementById('rollup-cpl-' + cplCode);
                if (cplBadge) {
                    cplBadge.textContent = (Math.round(sum * 10) / 10) + '%';
                }
            }

            // Update Card 2: Cek tabel komponen RPS
            let kuisWeekly = 0;
            let tugasWeekly = 0;
            let utsWeekly = 0;
            let uasWeekly = 0;

            rows.forEach(row => {
                const nameInp = row.querySelector('.matrix-row-nama');
                const name = (nameInp ? nameInp.value : '').toLowerCase();
                let rSum = 0;
                row.querySelectorAll('.matrix-input-cell').forEach(inp => {
                    rSum += parseFloat(inp.value) || 0;
                });

                if (name.includes('kuis') || name.includes('keaktifan')) {
                    kuisWeekly += rSum;
                } else if (name.includes('uts') || name.includes('tengah')) {
                    utsWeekly += rSum;
                } else if (name.includes('uas') || name.includes('akhir')) {
                    uasWeekly += rSum;
                } else {
                    tugasWeekly += rSum;
                }
            });

            updatePillDiff('comp-kuis', kuisWeekly, 15);
            updatePillDiff('comp-tugas', tugasWeekly, 30);
            updatePillDiff('comp-uts', utsWeekly, 25);
            updatePillDiff('comp-uas', uasWeekly, 30);

            const compTotal = document.getElementById('comp-total');
            if (compTotal) {
                compTotal.textContent = Math.round(grandTotal) + ' / 100';
            }
        }

        function updatePillDiff(elId, weeklyVal, targetVal) {
            const el = document.getElementById(elId);
            if (!el) return;
            const w = Math.round(weeklyVal * 10) / 10;
            el.textContent = w + ' / ' + targetVal;
            if (Math.abs(w - targetVal) > 0.1) {
                el.className = 'pill-compare pill-diff';
            } else {
                el.className = 'pill-compare pill-same';
            }
        }

        // Event listener delegasi input pada tabel matriks
        if (rowsBody) {
            rowsBody.addEventListener('input', e => {
                if (e.target.classList.contains('matrix-input-cell') || e.target.classList.contains('matrix-row-nama')) {
                    hitungMatrix();
                }
            });

            rowsBody.addEventListener('click', e => {
                const btnRemove = e.target.closest('.btn-remove-matrix-row');
                if (btnRemove) {
                    const row = btnRemove.closest('.matrix-data-row');
                    const allRows = rowsBody.querySelectorAll('.matrix-data-row');
                    if (allRows.length <= 1) {
                        alert('Minimal harus ada 1 komponen penilaian pada matriks.');
                        return;
                    }
                    if (confirm('Hapus baris komponen ini dari matriks?')) {
                        row.remove();
                        hitungMatrix();
                    }
                }
            });
        }

        // Tambah baris baru ke matriks
        if (btnAddRow && rowsBody) {
            btnAddRow.addEventListener('click', () => {
                const allRows = rowsBody.querySelectorAll('.matrix-data-row');
                const lastRow = allRows[allRows.length - 1];
                const newIdx = allRows.length;
                const clone = lastRow.cloneNode(true);

                clone.setAttribute('data-row-index', newIdx);

                const namaInp = clone.querySelector('.matrix-row-nama');
                if (namaInp) {
                    namaInp.name = `matrix_rows[${newIdx}][nama]`;
                    namaInp.value = 'Tugas Baru';
                }

                const ketInp = clone.querySelector('.matrix-row-ket');
                if (ketInp) {
                    ketInp.name = `matrix_rows[${newIdx}][keterangan]`;
                    ketInp.value = 'Rincian pertemuan';
                }

                const rowBadge = clone.querySelector('.row-bobot-val');
                if (rowBadge) {
                    rowBadge.id = 'row-bobot-' + newIdx;
                    rowBadge.textContent = '0%';
                }

                clone.querySelectorAll('.matrix-input-cell').forEach(inp => {
                    const subId = inp.getAttribute('data-sub');
                    inp.name = `matrix_rows[${newIdx}][allocations][${subId}]`;
                    inp.value = '';
                    inp.setAttribute('data-row', newIdx);
                });

                rowsBody.appendChild(clone);
                hitungMatrix();
            });
        }

        // Jalankan perhitungan awal saat halaman dibuka
        hitungMatrix();
    })();
    @endif

    // -------------------------------------------------------------------------
    // TAB 2: LIVE SEARCH MAHASISWA & REAL-TIME NA CALCULATION
    // -------------------------------------------------------------------------
    @if($tab === 'input')
    (function(){
        // Live Search Filter
        const searchInput = document.getElementById('filter-mhs');
        if (searchInput) {
            searchInput.addEventListener('input', function(){
                const q = this.value.trim().toLowerCase();
                document.querySelectorAll('#table-nilai tbody tr.mhs-row').forEach(tr => {
                    const nim = tr.getAttribute('data-nim') || '';
                    const nama = tr.getAttribute('data-nama') || '';
                    if (nim.includes(q) || nama.includes(q)) {
                        tr.style.display = '';
                    } else {
                        tr.style.display = 'none';
                    }
                });
            });
        }

        const passingGrade = {{ (float) ($rps?->target_passing_grade ?? 60.0) }};

        function konversiNilai(angka) {
            if (angka >= 85) return { huruf: 'A', bobot: '4.00', cls: 'grade-A' };
            if (angka >= 80) return { huruf: 'A-', bobot: '3.75', cls: 'grade-A' };
            if (angka >= 75) return { huruf: 'B+', bobot: '3.50', cls: 'grade-B' };
            if (angka >= 70) return { huruf: 'B', bobot: '3.00', cls: 'grade-B' };
            if (angka >= 65) return { huruf: 'B-', bobot: '2.75', cls: 'grade-B' };
            if (angka >= 60) return { huruf: 'C+', bobot: '2.50', cls: 'grade-C' };
            if (angka >= 55) return { huruf: 'C', bobot: '2.00', cls: 'grade-C' };
            if (angka >= 40) return { huruf: 'D', bobot: '1.00', cls: 'grade-D' };
            return { huruf: 'E', bobot: '0.00', cls: 'grade-E' };
        }

        @php
            $studentsJs = [];
            foreach ($krsList as $krs) {
                $ass = $studentAssessments[$krs->id] ?? [
                    'na' => 0,
                    'nilai_huruf' => 'E',
                    'bobot' => 0.0,
                    'all_cpl_achieved' => false,
                    'unachieved_cpls' => [],
                    'cpl_results' => [],
                ];
                $mScores = [];
                foreach ($krs->nilaiKomponens as $nk) {
                    $mScores[(int)$nk->komponen_id] = (float) $nk->nilai_angka;
                }
                $studentsJs[] = [
                    'id' => (int) $krs->id,
                    'nim' => $krs->mahasiswa?->nim ?? '-',
                    'nama' => $krs->mahasiswa?->nama ?? '-',
                    'na' => (float) ($ass['na'] ?? 0),
                    'huruf' => $ass['nilai_huruf'] ?? 'E',
                    'mutu' => (float) ($ass['bobot'] ?? 0),
                    'all_cpl_achieved' => (bool) ($ass['all_cpl_achieved'] ?? false),
                    'unachieved_cpls' => $ass['unachieved_cpls'] ?? [],
                    'cpl_results' => $ass['cpl_results'] ?? [],
                    'scores' => $mScores,
                ];
            }
        @endphp

        const studentsData = @json($studentsJs);
        let currentStudentIndex = 0;

        function showToast(message, type = 'success') {
            let toast = document.getElementById('global-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'global-toast';
                toast.className = 'toast-popup';
                document.body.appendChild(toast);
            }
            const iconSvg = type === 'success'
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
            toast.innerHTML = iconSvg + `<span>${message}</span>`;
            toast.style.display = 'flex';
            toast.style.opacity = '1';
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => { toast.style.display = 'none'; }, 300);
            }, 3000);
        }

        window.openFirstStudent = function() {
            if (studentsData && studentsData.length > 0) {
                openStudentDrilldown(studentsData[0].id);
            }
        };

        window.openStudentDrilldown = function(krsId) {
            const idx = studentsData.findIndex(s => s.id === krsId);
            if (idx === -1) return;
            currentStudentIndex = idx;
            renderModalStudent();
            openModal('modal-drilldown-mhs');
        };

        window.closeDrilldownModal = function() {
            closeModal('modal-drilldown-mhs');
        };

        window.navigateStudent = function(dir) {
            captureModalInputsToMemory();

            const newIdx = currentStudentIndex + dir;
            if (newIdx < 0 || newIdx >= studentsData.length) return;
            currentStudentIndex = newIdx;
            renderModalStudent();
        };

        function captureModalInputsToMemory() {
            if (!studentsData[currentStudentIndex]) return;
            const currentScores = studentsData[currentStudentIndex].scores || {};
            document.querySelectorAll('#table-modal-matrix .matrix-score-input').forEach(inp => {
                const kompId = inp.getAttribute('data-komponen-id');
                if (kompId) {
                    currentScores[kompId] = inp.value !== '' ? parseFloat(inp.value) : '';
                }
            });
            studentsData[currentStudentIndex].scores = currentScores;
        }

        function renderModalStudent() {
            const s = studentsData[currentStudentIndex];
            if (!s) return;

            // Update Header Mahasiswa
            const avatarEl = document.getElementById('modal-mhs-avatar');
            if (avatarEl) {
                avatarEl.textContent = s.nama ? s.nama.trim().charAt(0).toUpperCase() : 'M';
            }
            const namaEl = document.getElementById('modal-mhs-nama');
            if (namaEl) namaEl.textContent = s.nama;
            const nimEl = document.getElementById('modal-mhs-nim');
            if (nimEl) nimEl.textContent = s.nim;

            // Navigasi
            const counterEl = document.getElementById('modal-student-counter');
            if (counterEl) {
                counterEl.textContent = `${currentStudentIndex + 1} / ${studentsData.length}`;
            }
            const prevBtn = document.getElementById('btn-modal-prev');
            if (prevBtn) prevBtn.disabled = (currentStudentIndex === 0);
            const nextBtn = document.getElementById('btn-modal-next');
            if (nextBtn) nextBtn.disabled = (currentStudentIndex === studentsData.length - 1);

            // Isi nilai-nilai input matriks
            const inputs = document.querySelectorAll('#table-modal-matrix .matrix-score-input');
            inputs.forEach(inp => {
                const kompId = inp.getAttribute('data-komponen-id');
                const val = (s.scores && s.scores[kompId] !== undefined) ? s.scores[kompId] : '';
                inp.value = (val !== '' && val !== null && !isNaN(val)) ? val : '';
            });

            // Jalankan kalkulasi live untuk modal
            hitungLiveModal();
        }

        function hitungLiveModal() {
            let totalWeightedScore = 0;
            let cplData = {};

            const inputs = document.querySelectorAll('#table-modal-matrix .matrix-score-input');
            inputs.forEach(inp => {
                const val = parseFloat(inp.value) || 0;
                const weight = parseFloat(inp.getAttribute('data-weight')) || 0;
                const cplCode = inp.getAttribute('data-cpl') || '';

                totalWeightedScore += (val * weight) / 100.0;

                if (cplCode) {
                    if (!cplData[cplCode]) {
                        cplData[cplCode] = { score: 0, weight: 0 };
                    }
                    cplData[cplCode].score += (val * weight);
                    cplData[cplCode].weight += weight;
                }
            });

            const na = Math.min(100, Math.max(0, Math.round(totalWeightedScore * 100) / 100));
            const conv = konversiNilai(na);

            // Update KPI cards di modal
            const kpiNa = document.getElementById('modal-kpi-na');
            if (kpiNa) kpiNa.textContent = na.toFixed(2);

            const kpiHuruf = document.getElementById('modal-kpi-huruf');
            if (kpiHuruf) {
                kpiHuruf.innerHTML = `<span class="grade-chip ${conv.cls}" style="font-size:14px; padding:4px 12px;">${conv.huruf}</span>`;
            }

            const kpiMutu = document.getElementById('modal-kpi-mutu');
            if (kpiMutu) kpiMutu.textContent = conv.bobot;

            // Update CPL cards di modal
            let allCplAchieved = true;
            const cplCardsHtml = [];

            for (const [code, item] of Object.entries(cplData)) {
                const cplScore = item.weight > 0 ? (item.score / item.weight) : 0;
                const passed = cplScore >= passingGrade;
                if (!passed) allCplAchieved = false;

                cplCardsHtml.push(`
                    <div style="background:${passed ? '#f0fdf4' : '#fef2f2'}; border:1px solid ${passed ? '#bbf7d0' : '#fecaca'}; border-radius:8px; padding:8px 12px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:${passed ? '#15803d' : '#b91c1c'}; font-size:12.5px;">${code}</strong>
                            <span style="font-size:11px; color:#64748b; margin-left:4px;">(Bobot: ${Math.round(item.weight*10)/10}%)</span>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:800; font-size:13px; color:${passed ? '#15803d' : '#b91c1c'};">${cplScore.toFixed(1)}</div>
                            <span class="badge ${passed ? 'badge-success' : 'badge-danger'}" style="font-size:10px; padding:2px 6px;">${passed ? 'Lulus' : 'Belum'}</span>
                        </div>
                    </div>
                `);
            }

            const kpiCpl = document.getElementById('modal-kpi-cpl');
            if (kpiCpl) {
                kpiCpl.innerHTML = allCplAchieved
                    ? `<span class="badge badge-success" style="font-size:11.5px; padding:4px 10px;"><svg class="layout-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Lulus Semua CPL</span>`
                    : `<span class="badge badge-warning" style="font-size:11.5px; padding:4px 10px;">Perbaikan CPL</span>`;
            }

            const cplListEl = document.getElementById('modal-cpl-list');
            if (cplListEl) {
                cplListEl.innerHTML = cplCardsHtml.length > 0
                    ? cplCardsHtml.join('')
                    : '<div style="color:#64748b; font-size:12px; grid-column:1/-1;">Belum ada bobot CPL yang terpetakan.</div>';
            }
        }

        // Live calculation pada table modal matriks
        const tableModalMatrix = document.getElementById('table-modal-matrix');
        if (tableModalMatrix) {
            tableModalMatrix.addEventListener('input', function(e) {
                if (e.target.classList.contains('matrix-score-input')) {
                    hitungLiveModal();
                }
            });
        }

        // Simpan nilai mahasiswa aktif via AJAX
        window.saveCurrentStudent = async function() {
            const s = studentsData[currentStudentIndex];
            if (!s) return;

            captureModalInputsToMemory();

            const btnSave = document.getElementById('btn-save-current-mhs');
            const lblBtn = document.getElementById('label-btn-save-mhs');
            const origText = lblBtn ? lblBtn.textContent : 'Simpan Nilai Mahasiswa';

            if (btnSave) {
                btnSave.disabled = true;
                if (lblBtn) lblBtn.textContent = 'Menyimpan...';
            }

            const payloadScores = {};
            const inputs = document.querySelectorAll('#table-modal-matrix .matrix-score-input');
            inputs.forEach(inp => {
                const kompId = inp.getAttribute('data-komponen-id');
                if (kompId) {
                    payloadScores[kompId] = inp.value !== '' ? parseFloat(inp.value) : 0;
                }
            });

            try {
                const res = await fetch("{{ route('dosen.nilai.input', $jadwal->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nilai: {
                            [s.id]: payloadScores
                        }
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(`Nilai untuk ${s.nama} berhasil disimpan!`, 'success');

                    const updatedAss = data.assessments ? data.assessments[s.id] : null;
                    if (updatedAss) {
                        s.na = parseFloat(updatedAss.na) || 0;
                        s.huruf = updatedAss.nilai_huruf;
                        s.mutu = parseFloat(updatedAss.bobot) || 0;
                        s.all_cpl_achieved = !!updatedAss.all_cpl_achieved;
                        s.unachieved_cpls = updatedAss.unachieved_cpls || [];
                        s.cpl_results = updatedAss.cpl_results || [];

                        // Perbarui baris tabel luar
                        const cellNa = document.getElementById('cell-na-' + s.id);
                        if (cellNa) cellNa.textContent = s.na.toFixed(2);

                        const cellHuruf = document.getElementById('cell-huruf-' + s.id);
                        if (cellHuruf) {
                            const conv = konversiNilai(s.na);
                            cellHuruf.innerHTML = `<span class="grade-chip ${conv.cls}">${s.huruf}</span>`;
                        }

                        const cellMutu = document.getElementById('cell-mutu-' + s.id);
                        if (cellMutu) cellMutu.textContent = s.mutu.toFixed(2);

                        const cellCpl = document.getElementById('cell-cpl-' + s.id);
                        if (cellCpl) {
                            if (s.all_cpl_achieved) {
                                cellCpl.innerHTML = `<span class="badge badge-success" style="font-size:11px; padding:3px 8px;"><svg class="layout-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Lulus CPL</span>`;
                            } else {
                                const unachievedStr = s.unachieved_cpls.join(', ');
                                cellCpl.innerHTML = `<span class="badge badge-warning" style="font-size:11px; padding:3px 8px;" title="Belum: ${unachievedStr}">Belum Lulus</span>`;
                            }
                        }
                    }
                } else {
                    showToast(data.message || 'Gagal menyimpan nilai.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Terjadi kesalahan koneksi saat menyimpan nilai.', 'error');
            } finally {
                if (btnSave) {
                    btnSave.disabled = false;
                    if (lblBtn) lblBtn.textContent = origText;
                }
            }
        };

        // Keyboard Shortcuts: Esc to close, Alt+Left/Right for prev/next
        window.addEventListener('keydown', function(e) {
            const modal = document.getElementById('modal-drilldown-mhs');
            if (modal && modal.style.display !== 'none') {
                if (e.key === 'Escape') {
                    closeDrilldownModal();
                } else if (e.altKey && e.key === 'ArrowLeft') {
                    e.preventDefault();
                    navigateStudent(-1);
                } else if (e.altKey && e.key === 'ArrowRight') {
                    e.preventDefault();
                    navigateStudent(1);
                }
            }
        });
    })();
    @endif

    // -------------------------------------------------------------------------
    // TAB 3: LIVE SEARCH CAPAIAN MAHASISWA
    // -------------------------------------------------------------------------
    @if($tab === 'capaian')
    (function(){
        const searchInput = document.getElementById('filter-mhs-capaian');
        if (searchInput) {
            searchInput.addEventListener('input', function(){
                const q = this.value.trim().toLowerCase();
                document.querySelectorAll('#table-mhs-capaian tbody tr.mhs-capaian-row').forEach(tr => {
                    const searchAttr = tr.getAttribute('data-search') || '';
                    if (searchAttr.includes(q)) {
                        tr.style.display = '';
                    } else {
                        tr.style.display = 'none';
                    }
                });
            });
        }
    })();
    @endif
</script>
@endpush

@endsection

