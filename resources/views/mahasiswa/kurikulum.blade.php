@extends('layouts.mahasiswa')

@section('title', 'Dokumen RPS & Kurikulum Mahasiswa')

@push('styles')
<style>
    .rps-mhs-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 0;
        flex-wrap: wrap;
    }
    .rps-mhs-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        font-size: 13.5px;
        font-weight: 700;
        color: #64748b;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .rps-mhs-tab:hover {
        color: #0f172a;
    }
    .rps-mhs-tab.active {
        color: #1d4ed8;
        border-bottom-color: #1d4ed8;
        background: #eff6ff;
        border-radius: 8px 8px 0 0;
    }
    .rps-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .rps-kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .rps-kpi-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .rps-kpi-val {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
    }
    .rps-kpi-sub {
        font-size: 11.5px;
        color: #64748b;
    }
    .rps-filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 18px;
    }
    .rps-period-card {
        margin-bottom: 22px;
    }
    .btn-rps-dl {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
        border-radius: 7px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .btn-rps-dl:hover {
        background: #dbeafe;
        color: #1e40af;
    }
    .btn-rps-detail {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 6px 11px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 7px;
        background: #f8fafc;
        color: #334155;
        border: 1px solid #cbd5e1;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .btn-rps-detail:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .komp-chip {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        margin: 2px 2px 2px 0;
    }
    .cpl-chip {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        margin: 2px 2px 2px 0;
    }
    .modal-rps-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .modal-rps-box {
        background: #ffffff;
        border-radius: 14px;
        width: 100%;
        max-width: 780px;
        max-height: 88vh;
        overflow-y: auto;
        padding: 22px 24px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
    }
</style>
@endpush

@section('content')
@php
    $totalKrsMk = $krsItems->count();
    $totalKrsWithRps = $krsItems->filter(function ($item) {
        $mk = $item->mata_kuliah_efektif;
        $rps = $mk?->rpsAktif ?? ($mk?->rpsList?->first());
        return $rps !== null;
    })->count();
    $rpsModalPayloads = [];
@endphp

<div class="inner-page">
    <div class="info-alert" style="margin-bottom: 18px;">
        <span class="icon-inline"><x-layout-icon name="book" /></span>
        <div>
            <strong>Pusat Dokumen RPS &amp; Kurikulum Mahasiswa</strong><br>
            Lihat dan unduh dokumen Rencana Pembelajaran Semester (RPS) resmi yang telah disahkan Program Studi serta rincian bobot penilaian pada mata kuliah semester berjalan maupun semester yang pernah Anda ambil.
        </div>
    </div>

    {{-- KPI Summary --}}
    <div class="rps-kpi-grid">
        <div class="rps-kpi-card">
            <div class="rps-kpi-label">Mata Kuliah Diambil (KRS)</div>
            <div class="rps-kpi-val">{{ $totalKrsMk }}</div>
            <div class="rps-kpi-sub">Semester berjalan &amp; riwayat KRS disetujui</div>
        </div>
        <div class="rps-kpi-card">
            <div class="rps-kpi-label">RPS Kelas Saya Tersedia</div>
            <div class="rps-kpi-val" style="color: #16a34a;">{{ $totalKrsWithRps }}</div>
            <div class="rps-kpi-sub">Terkoneksi langsung dengan penilaian dosen</div>
        </div>
        <div class="rps-kpi-card">
            <div class="rps-kpi-label">Total Dokumen RPS Prodi</div>
            <div class="rps-kpi-val" style="color: #1d4ed8;">{{ $rpsProdiList->count() }}</div>
            <div class="rps-kpi-sub">{{ $mahasiswa->prodi?->nama_prodi ?? 'Program Studi' }}</div>
        </div>
    </div>

    {{-- Navigasi Tab --}}
    <div class="rps-mhs-tabs">
        <a href="{{ route('mahasiswa.kurikulum', ['tab' => 'rps-saya']) }}"
           class="rps-mhs-tab {{ $activeTab === 'rps-saya' ? 'active' : '' }}">
            <x-layout-icon name="file-check" />
            <span>1. RPS Mata Kuliah Saya (Semester Aktif &amp; Riwayat)</span>
            <span class="badge badge-blue" style="font-size: 11px;">{{ $totalKrsMk }}</span>
        </a>
        <a href="{{ route('mahasiswa.kurikulum', ['tab' => 'katalog-prodi']) }}"
           class="rps-mhs-tab {{ $activeTab === 'katalog-prodi' ? 'active' : '' }}">
            <x-layout-icon name="book" />
            <span>2. Katalog RPS &amp; Kurikulum Prodi</span>
            <span class="badge badge-blue" style="font-size: 11px;">{{ $rpsProdiList->count() }} RPS</span>
        </a>
    </div>

    {{-- =========================================================================
         TAB 1: RPS MATA KULIAH SAYA (SEMESTER AKTIF & RIWAYAT KRS)
         ========================================================================= --}}
    @if($activeTab === 'rps-saya')
        <div class="rps-filter-bar">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
                <input type="text"
                       id="search-rps-saya"
                       class="form-control"
                       placeholder="Cari Kode MK, Nama Mata Kuliah, atau Dosen..."
                       style="max-width: 340px;"
                       oninput="filterRpsSaya()">

                <select id="filter-periode-rps" class="form-control" style="max-width: 240px;" onchange="filterRpsSaya()">
                    <option value="all">Semua Semester ({{ $krsByPeriod->count() }} Periode)</option>
                    @foreach($krsByPeriod as $periodKey => $items)
                        <option value="{{ $periodKey }}">
                            {{ $periodKey }}{{ $periodKey === $activePeriodKey ? ' — (Semester Berjalan)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="font-size: 12px; color: #64748b;">
                Menampilkan RPS yang disahkan Admin &amp; digunakan pada penilaian perkuliahan.
            </div>
        </div>

        @forelse($krsByPeriod as $periodKey => $periodItems)
            @php
                $isCurrentPeriod = ($periodKey === $activePeriodKey);
            @endphp
            <div class="page-card rps-period-card" data-period-group="{{ $periodKey }}">
                <div class="page-card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <h2 style="margin: 0;">Periode {{ $periodKey }}</h2>
                        @if($isCurrentPeriod)
                            <span class="badge badge-green" style="font-size: 11.5px; padding: 4px 10px;">
                                Semester Berjalan / Aktif
                            </span>
                        @else
                            <span class="badge badge-gray" style="font-size: 11px;">
                                Riwayat Semester Diambil
                            </span>
                        @endif
                    </div>
                    <span class="badge" style="background: #fff; color: #0a1f5c;">{{ $periodItems->count() }} Mata Kuliah</span>
                </div>
                <div class="page-card-body" style="padding: 0;">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">No</th>
                                    <th style="width: 110px;">Kode MK</th>
                                    <th>Mata Kuliah &amp; Jadwal</th>
                                    <th style="width: 65px; text-align: center;">SKS</th>
                                    <th>Dosen Pengampu</th>
                                    <th>Komponen &amp; Bobot Penilaian</th>
                                    <th style="width: 130px; text-align: center;">Status RPS</th>
                                    <th style="width: 210px; text-align: center;">Dokumen &amp; Rincian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($periodItems as $krs)
                                    @php
                                        $jadwal = $krs->jadwal;
                                        $mk = $krs->mata_kuliah_efektif ?? $jadwal?->mataKuliah;
                                        $rps = $mk?->rpsAktif ?? ($mk?->rpsList?->first());
                                        $skema = $jadwal?->skemaPenilaian;
                                        $cpmks = $mk?->cpmks ?? collect();
                                        $hasFile = $rps && $rps->effective_file_path;

                                        // Komponen bobot penilaian (dari RPS atau dari pengaturan Komponen Standar Dosen)
                                        $komponenBobot = [];
                                        if ($rps && !empty($rps->komponen_bobot_default)) {
                                            $komponenBobot = $rps->komponen_bobot_default;
                                        } elseif ($skema) {
                                            foreach ($skema->komponen_standar_efektif as $ks) {
                                                if (!empty($ks['nama']) && !empty($ks['bobot'])) {
                                                    $komponenBobot[$ks['nama']] = $ks['bobot'];
                                                }
                                            }
                                        }

                                        if ($mk) {
                                            $rpsModalPayloads[$mk->id] = [
                                                'id' => $mk->id,
                                                'kode_mk' => $mk->kode_mk,
                                                'nama_mk' => $mk->nama_mk,
                                                'sks' => $mk->sks,
                                                'semester' => $mk->semester,
                                                'dosen' => $jadwal?->semua_dosen_nama ?: ($krs->dosen_efektif?->nama ?? '-'),
                                                'tahun_akademik' => $rps?->tahun_akademik ?: $periodKey,
                                                'passing_grade' => $rps?->target_passing_grade ?? 60,
                                                'has_rps' => (bool) $rps,
                                                'has_file' => (bool) $hasFile,
                                                'download_url' => $hasFile ? route('mahasiswa.rps.download', $mk->id) : null,
                                                'porsi_cpl' => $rps?->porsi_cpl ?? [],
                                                'komponen_bobot' => $komponenBobot,
                                                'cpmks' => $cpmks->map(fn($c) => [
                                                    'kode' => $c->kode_cpmk,
                                                    'deskripsi' => $c->deskripsi,
                                                    'subs' => $c->subCpmks->map(fn($s) => [
                                                        'kode' => $s->kode_sub_cpmk,
                                                        'deskripsi' => $s->deskripsi,
                                                        'cpl' => $s->cpl?->kode_cpl ?? '-',
                                                        'bobot' => $s->bobot_default,
                                                    ])->values()->all(),
                                                ])->values()->all(),
                                            ];
                                        }
                                    @endphp
                                    <tr class="rps-saya-row"
                                        data-search="{{ strtolower(($mk?->kode_mk ?? '') . ' ' . ($mk?->nama_mk ?? '') . ' ' . ($krs->dosen_efektif?->nama ?? '')) }}">
                                        <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="badge badge-blue">{{ $mk?->kode_mk ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $mk?->nama_mk ?? '-' }}</strong>
                                            <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                                Semester {{ $mk?->semester ?? '-' }}
                                                @if($jadwal)
                                                    &bull; Kelas {{ $jadwal->kelas }} ({{ $jadwal->hari }}, {{ $jadwal->jam_mulai }}–{{ $jadwal->jam_selesai }})
                                                @endif
                                            </div>
                                        </td>
                                        <td style="text-align: center; font-weight: 700;">{{ $mk?->sks ?? '-' }}</td>
                                        <td>{{ $jadwal?->semua_dosen_nama ?: ($krs->dosen_efektif?->nama ?? '-') }}</td>
                                        <td>
                                            @if(!empty($komponenBobot))
                                                <div>
                                                    @foreach($komponenBobot as $kNama => $kVal)
                                                        <span class="komp-chip">{{ $kNama }}: {{ $kVal }}%</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span style="font-size: 11.5px; color: #94a3b8;">Mengikuti skema kelas</span>
                                            @endif
                                            @if($rps && !empty($rps->porsi_cpl))
                                                <div style="margin-top: 3px;">
                                                    @foreach($rps->porsi_cpl as $cKode => $cPct)
                                                        <span class="cpl-chip">{{ $cKode }}: {{ $cPct }}%</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            @if($rps)
                                                <span class="badge badge-green" style="font-size: 11px;">RPS Aktif (ACC)</span>
                                            @else
                                                <span class="badge badge-gray" style="font-size: 11px;">Standar / Non-RPS</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; gap: 6px; flex-wrap: wrap; justify-content: center;">
                                                @if($mk)
                                                    <button type="button"
                                                            class="btn-rps-detail"
                                                            data-mk-id="{{ $mk->id }}"
                                                            onclick="openRpsDetailModal(this.getAttribute('data-mk-id'))">
                                                        <x-layout-icon name="book" />
                                                        <span>Detail</span>
                                                    </button>
                                                @endif
                                                @if($hasFile && $mk)
                                                    <a href="{{ route('mahasiswa.rps.download', $mk->id) }}"
                                                       class="btn-rps-dl"
                                                       target="_blank">
                                                        <x-layout-icon name="download" />
                                                        <span>Unduh PDF</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="page-card">
                <div class="page-card-body" style="text-align: center; padding: 44px 20px; color: #64748b;">
                    <span class="empty-state-icon"><x-layout-icon name="book" /></span>
                    <strong style="display: block; margin-top: 8px; color: #0f172a;">Belum Ada Mata Kuliah pada KRS</strong>
                    <p style="margin-top: 4px; font-size: 13px;">
                        Dokumen RPS untuk mata kuliah yang Anda ambil akan otomatis muncul di sini setelah KRS disetujui, atau Anda dapat membuka tab <strong>2. Katalog RPS &amp; Kurikulum Prodi</strong>.
                    </p>
                </div>
            </div>
        @endforelse

    {{-- =========================================================================
         TAB 2: KATALOG RPS & KURIKULUM PROGRAM STUDI
         ========================================================================= --}}
    @else
        {{-- Bagian A: Daftar Seluruh Dokumen RPS Resmi Prodi yang Di-ACC Admin --}}
        <div class="page-card" style="margin-bottom: 24px;">
            <div class="page-card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 style="margin: 0;">Daftar Dokumen RPS Resmi Program Studi ({{ $mahasiswa->prodi?->nama_prodi ?? 'Prodi' }})</h2>
                    <p style="margin: 4px 0 0; font-size: 12px; color: rgba(255,255,255,0.8);">
                        Seluruh dokumen RPS mata kuliah yang telah diunggah dan disahkan oleh Admin / Kaprodi.
                    </p>
                </div>
                <span class="badge" style="background: #fff; color: #0a1f5c;">{{ $rpsProdiList->count() }} Dokumen RPS Aktif</span>
            </div>
            <div class="page-card-body">
                @if($rpsProdiList->isEmpty())
                    <div style="text-align: center; padding: 36px 20px; color: #64748b;">
                        Belum ada dokumen RPS aktif yang diterbitkan untuk program studi ini.
                    </div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px; text-align: center;">No</th>
                                    <th style="width: 110px;">Kode MK</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th style="width: 90px; text-align: center;">Semester</th>
                                    <th style="width: 65px; text-align: center;">SKS</th>
                                    <th>Target CPL &amp; Komponen Bobot</th>
                                    <th style="width: 110px; text-align: center;">Passing Grade</th>
                                    <th style="width: 210px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rpsProdiList as $rpsItem)
                                    @php
                                        $mk = $rpsItem->mataKuliah;
                                        $cpmks = $mk?->cpmks ?? collect();
                                        $hasFile = (bool) $rpsItem->effective_file_path;
                                        if ($mk && !isset($rpsModalPayloads[$mk->id])) {
                                            $rpsModalPayloads[$mk->id] = [
                                                'id' => $mk->id,
                                                'kode_mk' => $mk->kode_mk,
                                                'nama_mk' => $mk->nama_mk,
                                                'sks' => $mk->sks,
                                                'semester' => $mk->semester,
                                                'dosen' => 'Tim Dosen Pengampu Prodi',
                                                'tahun_akademik' => $rpsItem->tahun_akademik ?: 'Aktif',
                                                'passing_grade' => $rpsItem->target_passing_grade ?? 60,
                                                'has_rps' => true,
                                                'has_file' => $hasFile,
                                                'download_url' => $hasFile ? route('mahasiswa.rps.download', $mk->id) : null,
                                                'porsi_cpl' => $rpsItem->porsi_cpl ?? [],
                                                'komponen_bobot' => $rpsItem->komponen_bobot_default ?? [],
                                                'cpmks' => $cpmks->map(fn($c) => [
                                                    'kode' => $c->kode_cpmk,
                                                    'deskripsi' => $c->deskripsi,
                                                    'subs' => $c->subCpmks->map(fn($s) => [
                                                        'kode' => $s->kode_sub_cpmk,
                                                        'deskripsi' => $s->deskripsi,
                                                        'cpl' => $s->cpl?->kode_cpl ?? '-',
                                                        'bobot' => $s->bobot_default,
                                                    ])->values()->all(),
                                                ])->values()->all(),
                                            ];
                                        }
                                    @endphp
                                    <tr>
                                        <td style="text-align: center; color: #64748b;">{{ $loop->iteration }}</td>
                                        <td><span class="badge badge-blue">{{ $mk?->kode_mk ?? '-' }}</span></td>
                                        <td>
                                            <strong>{{ $mk?->nama_mk ?? '-' }}</strong>
                                            <div style="font-size: 11px; color: #64748b;">
                                                Tahun Ajaran: {{ $rpsItem->tahun_akademik ?: 'Kurikulum Aktif' }}
                                                &bull; {{ $cpmks->count() }} CPMK
                                            </div>
                                        </td>
                                        <td style="text-align: center;">Semester {{ $mk?->semester ?? '-' }}</td>
                                        <td style="text-align: center; font-weight: 700;">{{ $mk?->sks ?? '-' }}</td>
                                        <td>
                                            @if(!empty($rpsItem->komponen_bobot_default))
                                                <div>
                                                    @foreach($rpsItem->komponen_bobot_default as $kName => $kWeight)
                                                        <span class="komp-chip">{{ $kName }}: {{ $kWeight }}%</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if(!empty($rpsItem->porsi_cpl))
                                                <div style="margin-top: 3px;">
                                                    @foreach($rpsItem->porsi_cpl as $cKey => $cVal)
                                                        <span class="cpl-chip">{{ $cKey }}: {{ $cVal }}%</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-green">&ge; {{ number_format($rpsItem->target_passing_grade ?? 60, 0) }}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; gap: 6px; justify-content: center;">
                                                @if($mk)
                                                    <button type="button"
                                                            class="btn-rps-detail"
                                                            data-mk-id="{{ $mk->id }}"
                                                            onclick="openRpsDetailModal(this.getAttribute('data-mk-id'))">
                                                        <x-layout-icon name="book" />
                                                        <span>Detail CPMK</span>
                                                    </button>
                                                @endif
                                                @if($hasFile && $mk)
                                                    <a href="{{ route('mahasiswa.rps.download', $mk->id) }}"
                                                       class="btn-rps-dl"
                                                       target="_blank">
                                                        <x-layout-icon name="download" />
                                                        <span>Unduh PDF</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Bagian B: Susunan Kurikulum & Silabus Prodi --}}
        @include('kurikulum.catalog')
    @endif
</div>

{{-- Modal Detail RPS, CPMK, Sub-CPMK & Bobot Penilaian --}}
<div id="modal-rps-detail" class="modal-rps-backdrop" onclick="if(event.target === this) closeRpsDetailModal()">
    <div class="modal-rps-box">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 16px; gap: 12px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span class="badge badge-blue" id="mdl-rps-kode">-</span>
                    <span style="font-size: 12px; color: #64748b;" id="mdl-rps-meta">-</span>
                </div>
                <h3 style="margin: 0; font-size: 18px; color: #0f172a;" id="mdl-rps-nama">Detail RPS Mata Kuliah</h3>
                <div style="font-size: 12px; color: #64748b; margin-top: 3px;" id="mdl-rps-dosen"></div>
            </div>
            <button type="button" onclick="closeRpsDetailModal()" style="background: none; border: none; font-size: 24px; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        {{-- Komponen Bobot & Porsi CPL --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin-bottom: 18px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                <div style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: #475569; margin-bottom: 8px;">
                    Komponen &amp; Bobot Penilaian
                </div>
                <div id="mdl-rps-komponen"></div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                <div style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: #475569; margin-bottom: 8px;">
                    Porsi Ketercapaian CPL &amp; Passing Grade
                </div>
                <div id="mdl-rps-cpl"></div>
            </div>
        </div>

        {{-- Daftar CPMK & Sub-CPMK --}}
        <div>
            <div style="font-size: 13px; font-weight: 800; color: #0f172a; margin-bottom: 10px;">
                Capaian Pembelajaran Mata Kuliah (CPMK &amp; Sub-CPMK)
            </div>
            <div id="mdl-rps-cpmks"></div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 18px;">
            <div id="mdl-rps-download-wrap"></div>
            <button type="button" class="btn-outline" onclick="closeRpsDetailModal()">Tutup</button>
        </div>
    </div>
</div>

@push('scripts')
<script id="rps-modal-data-json" type="application/json">{!! json_encode($rpsModalPayloads) !!}</script>
<script>
    const rpsModalPayloads = JSON.parse(document.getElementById('rps-modal-data-json')?.textContent || '{}');

    function filterRpsSaya() {
        const q = (document.getElementById('search-rps-saya')?.value || '').trim().toLowerCase();
        const period = document.getElementById('filter-periode-rps')?.value || 'all';

        document.querySelectorAll('.rps-period-card').forEach(card => {
            const cardPeriod = card.getAttribute('data-period-group');
            const periodMatch = (period === 'all' || cardPeriod === period);

            let visibleRows = 0;
            card.querySelectorAll('.rps-saya-row').forEach(row => {
                const searchStr = row.getAttribute('data-search') || '';
                const textMatch = !q || searchStr.includes(q);
                if (periodMatch && textMatch) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            card.style.display = (periodMatch && visibleRows > 0) ? '' : 'none';
        });
    }

    function openRpsDetailModal(mkId) {
        const data = rpsModalPayloads[mkId];
        if (!data) return;

        document.getElementById('mdl-rps-kode').textContent = data.kode_mk || '-';
        document.getElementById('mdl-rps-nama').textContent = data.nama_mk || '-';
        document.getElementById('mdl-rps-meta').textContent = `${data.sks || 0} SKS • Semester ${data.semester || '-'} • T.A ${data.tahun_akademik || '-'}`;
        document.getElementById('mdl-rps-dosen').textContent = `Dosen Pengampu: ${data.dosen || '-'}`;

        const kompEl = document.getElementById('mdl-rps-komponen');
        const kompEntries = Object.entries(data.komponen_bobot || {});
        if (kompEntries.length > 0) {
            kompEl.innerHTML = kompEntries.map(([k, v]) => `<span class="komp-chip">${k}: <strong>${v}%</strong></span>`).join(' ');
        } else {
            kompEl.innerHTML = '<span style="font-size:12px; color:#64748b;">Mengikuti ketentuan penilaian kelas dosen pengampu.</span>';
        }

        const cplEl = document.getElementById('mdl-rps-cpl');
        const cplEntries = Object.entries(data.porsi_cpl || {});
        let cplHtml = `<div style="margin-bottom:6px; font-size:12px;">Batas Kelulusan (Passing Grade): <strong>&ge; ${data.passing_grade || 60}</strong></div>`;
        if (cplEntries.length > 0) {
            cplHtml += cplEntries.map(([k, v]) => `<span class="cpl-chip">${k}: ${v}%</span>`).join(' ');
        } else {
            cplHtml += '<span style="font-size:12px; color:#64748b;">Penilaian Standar (Non-CPL)</span>';
        }
        cplEl.innerHTML = cplHtml;

        const cpmksEl = document.getElementById('mdl-rps-cpmks');
        if (data.cpmks && data.cpmks.length > 0) {
            cpmksEl.innerHTML = data.cpmks.map(c => {
                const subsHtml = (c.subs && c.subs.length > 0)
                    ? `<ul style="margin:6px 0 0; padding-left:18px; font-size:12px; color:#334155;">` +
                      c.subs.map(s => `<li style="margin-bottom:4px;"><strong>${s.kode}</strong> (${s.cpl}${s.bobot ? ', ' + s.bobot + '%' : ''}): ${s.deskripsi || '-'}</li>`).join('') +
                      `</ul>`
                    : '';
                return `
                    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; margin-bottom:8px; background:#fff;">
                        <div style="font-weight:700; font-size:12.5px; color:#0f172a;">${c.kode}: <span style="font-weight:500;">${c.deskripsi || '-'}</span></div>
                        ${subsHtml}
                    </div>
                `;
            }).join('');
        } else {
            cpmksEl.innerHTML = '<div style="font-size:12.5px; color:#64748b; padding:12px; background:#f8fafc; border-radius:8px;">Rincian CPMK & Sub-CPMK belum ditambahkan untuk mata kuliah ini.</div>';
        }

        const dlWrap = document.getElementById('mdl-rps-download-wrap');
        if (data.has_file && data.download_url) {
            dlWrap.innerHTML = `<a href="${data.download_url}" target="_blank" class="btn-rps-dl" style="padding:8px 14px; font-size:12.5px;">Unduh Dokumen PDF RPS Resmi</a>`;
        } else {
            dlWrap.innerHTML = `<span style="font-size:12px; color:#94a3b8;">File PDF RPS belum diunggah</span>`;
        }

        document.getElementById('modal-rps-detail').style.display = 'flex';
    }

    function closeRpsDetailModal() {
        document.getElementById('modal-rps-detail').style.display = 'none';
    }
</script>
@endpush
@endsection
