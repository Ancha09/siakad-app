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
            // Kumpulkan seluruh Sub-CPMK dari mata kuliah ini
            $allSubCpmks = collect();
            if ($jadwal->mataKuliah && $jadwal->mataKuliah->cpmks) {
                foreach ($jadwal->mataKuliah->cpmks as $cpmk) {
                    foreach ($cpmk->subCpmks as $sub) {
                        $allSubCpmks->push($sub);
                    }
                }
            }
        @endphp

        <div class="obe-layout-split">
            {{-- Panel Kiri: Form Instrumen Penilaian --}}
            <div class="obe-card">
                <div class="obe-card-head">
                    <h3>
                        <x-layout-icon name="services" />
                        <span>Instrumen Penilaian Kelas</span>
                    </h3>
                    @if($skema->is_finalized)
                        <span class="badge badge-success">Mode Terkunci</span>
                    @else
                        <span class="badge badge-warning">Dapat Diubah</span>
                    @endif
                </div>

                <div class="obe-card-body">
                    @if($skema->is_finalized)
                        <div class="alert alert-info" style="margin-bottom:16px;">
                            Skema instrumen penilaian telah difinalisasi dan terkunci. Jika memerlukan perbaikan bobot atau instrumen, silakan mengajukan pembukaan kunci kepada Admin/Kaprodi.
                        </div>
                    @else
                        <p style="font-size:13px; color:#64748b; margin-top:0; margin-bottom:16px;">
                            Tentukan komponen instrumen penilaian semester (misal: Tugas 1, Kuis, Proyek, UTS, UAS), kaitkan dengan Sub-CPMK, dan tetapkan bobot persen hingga total tepat <strong>100%</strong>.
                        </p>
                    @endif

                    <form action="{{ route('dosen.nilai.skema', $jadwal->id) }}" method="POST" id="form-skema">
                        @csrf

                        <div class="table-wrap">
                            <table style="width:100%;" id="table-instrumen">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">No</th>
                                        <th>Nama Instrumen</th>
                                        <th>Sub-CPMK &amp; CPL</th>
                                        <th style="width:110px;">Bobot (%)</th>
                                        <th style="width:50px; text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="skema-rows">
                                    @forelse($skema->komponens as $idx => $komp)
                                        <tr class="skema-row">
                                            <td class="row-number" style="text-align:center; font-weight:600;">{{ $loop->iteration }}</td>
                                            <td>
                                                <input type="text"
                                                       name="nama_instrumen[]"
                                                       class="form-control input-nama-instrumen"
                                                       placeholder="Contoh: Tugas 1 / UTS"
                                                       value="{{ $komp->nama_instrumen }}"
                                                       required
                                                       {{ $skema->is_finalized ? 'disabled' : '' }}>
                                            </td>
                                            <td>
                                                <select name="sub_cpmk_id[]" class="form-control select-sub-cpmk" {{ $skema->is_finalized ? 'disabled' : '' }}>
                                                    <option value="">-- Tanpa Sub-CPMK --</option>
                                                    @foreach($allSubCpmks as $sub)
                                                        <option value="{{ $sub->id }}"
                                                                data-cpl="{{ $sub->cpl?->kode_cpl ?? '' }}"
                                                                data-cpmk="{{ $sub->cpmk?->kode_cpmk ?? '' }}"
                                                                @selected($komp->sub_cpmk_id == $sub->id)>
                                                            {{ $sub->kode_sub_cpmk }} ({{ $sub->cpl?->kode_cpl ?? 'CPL' }}) - {{ Str::limit($sub->deskripsi, 35) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       name="bobot[]"
                                                       class="form-control input-bobot"
                                                       min="0"
                                                       max="100"
                                                       step="0.01"
                                                       placeholder="0"
                                                       value="{{ $komp->bobot }}"
                                                       required
                                                       {{ $skema->is_finalized ? 'disabled' : '' }}>
                                            </td>
                                            <td style="text-align:center;">
                                                @if(! $skema->is_finalized)
                                                    <button type="button" class="btn-outline btn-remove-row" style="padding:6px 9px; color:#dc2626; border-color:#fca5a5;">
                                                        <x-layout-icon name="trash" />
                                                    </button>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        {{-- Default row jika belum ada komponen --}}
                                        <tr class="skema-row">
                                            <td class="row-number" style="text-align:center; font-weight:600;">1</td>
                                            <td>
                                                <input type="text" name="nama_instrumen[]" class="form-control input-nama-instrumen" placeholder="Contoh: Tugas 1" value="Tugas 1" required>
                                            </td>
                                            <td>
                                                <select name="sub_cpmk_id[]" class="form-control select-sub-cpmk">
                                                    <option value="">-- Pilih Sub-CPMK --</option>
                                                    @foreach($allSubCpmks as $sub)
                                                        <option value="{{ $sub->id }}"
                                                                data-cpl="{{ $sub->cpl?->kode_cpl ?? '' }}"
                                                                data-cpmk="{{ $sub->cpmk?->kode_cpmk ?? '' }}">
                                                            {{ $sub->kode_sub_cpmk }} ({{ $sub->cpl?->kode_cpl ?? 'CPL' }}) - {{ Str::limit($sub->deskripsi, 35) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="bobot[]" class="form-control input-bobot" min="0" max="100" step="0.01" placeholder="20" value="20" required>
                                            </td>
                                            <td style="text-align:center;">
                                                <button type="button" class="btn-outline btn-remove-row" style="padding:6px 9px; color:#dc2626; border-color:#fca5a5;">
                                                    <x-layout-icon name="trash" />
                                                </button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr style="background:#f8fafc; font-weight:700;">
                                        <td colspan="3" style="text-align:right; padding:10px 14px;">Total Bobot Terinput:</td>
                                        <td style="padding:10px 14px;">
                                            <span id="label-total-bobot">{{ $skema->total_bobot }}%</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if(! $skema->is_finalized)
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; flex-wrap:wrap; gap:12px;">
                                <button type="button" class="btn-outline" id="btn-add-row">
                                    <x-layout-icon name="plus" />
                                    <span>Tambah Baris Instrumen</span>
                                </button>

                                <div style="display:flex; gap:10px;">
                                    <button type="submit" name="action" value="draft" class="btn-outline">
                                        <x-layout-icon name="save" />
                                        <span>Simpan Draf</span>
                                    </button>

                                    <button type="submit" name="action" value="save" class="btn-primary" id="btn-submit-skema">
                                        <x-layout-icon name="check" />
                                        <span>Simpan &amp; Lanjut ke Nilai</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Panel Kanan: Ketetapan Program Studi & Validasi Target --}}
            <div>
                {{-- Card Validasi Status Bobot --}}
                <div class="obe-card">
                    <div class="obe-card-head">
                        <h3>
                            <x-layout-icon name="file-chart" />
                            <span>Validasi Bobot &amp; Porsi CPL</span>
                        </h3>
                    </div>
                    <div class="obe-card-body">
                        <div id="box-status-100" style="padding:12px 14px; border-radius:8px; margin-bottom:16px; display:flex; align-items:center; gap:10px;">
                            {{-- Diisi secara dinamis oleh JavaScript --}}
                        </div>

                        <div style="font-size:12px; font-weight:700; color:#475569; margin-bottom:8px; text-transform:uppercase;">
                            Distribusi Bobot ke CPL Terpetakan:
                        </div>
                        <div id="live-cpl-distribution" style="margin-bottom:16px;">
                            {{-- Diisi via JS --}}
                        </div>

                        @if($rps && !empty($rps->porsi_cpl))
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-top:12px;">
                                <div style="font-size:12px; font-weight:700; color:#334155; margin-bottom:6px;">
                                    Target Porsi CPL dari Program Studi (RPS):
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                                    @foreach($rps->porsi_cpl as $cplCode => $targetVal)
                                        <div style="font-size:12px; color:#475569; display:flex; justify-content:space-between;">
                                            <span><strong>{{ $cplCode }}</strong>:</span>
                                            <span>{{ $targetVal }}%</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card Dokumen RPS Resmi --}}
                <div class="obe-card">
                    <div class="obe-card-head">
                        <h3>
                            <x-layout-icon name="file-check" />
                            <span>Dokumen RPS Resmi</span>
                        </h3>
                    </div>
                    <div class="obe-card-body">
                        @if($rps && $rps->file_rps)
                            <p style="font-size:13px; color:#475569; margin-top:0; margin-bottom:12px;">
                                Dokumen RPS resmi Program Studi telah tersedia untuk mata kuliah ini.
                            </p>
                            <a href="{{ route('admin.obe.rps.download', $rps->id) }}" class="btn-outline" target="_blank" style="display:inline-flex; align-items:center; gap:8px;">
                                <x-layout-icon name="download" />
                                <span>Unduh RPS Resmi (PDF)</span>
                            </a>
                        @else
                            <div style="font-size:13px; color:#64748b; font-style:italic;">
                                Dokumen PDF RPS resmi belum diunggah oleh Admin/Kaprodi. Penilaian tetap dapat dilakukan sesuai kurikulum aktif.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card Referensi Sub-CPMK Kurikulum --}}
                <div class="obe-card">
                    <div class="obe-card-head">
                        <h3>
                            <x-layout-icon name="book" />
                            <span>Katalog Sub-CPMK Mata Kuliah</span>
                        </h3>
                        <span class="badge badge-info">{{ $allSubCpmks->count() }} Sub-CPMK</span>
                    </div>
                    <div class="obe-card-body" style="max-height: 380px; overflow-y:auto;">
                        @forelse($jadwal->mataKuliah->cpmks as $cpmk)
                            <div style="margin-bottom:14px; padding-bottom:12px; border-bottom:1px dashed #e2e8f0;">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                                    <span class="cpmk-pill">{{ $cpmk->kode_cpmk }}</span>
                                    <span style="font-size:13px; font-weight:600; color:#1e293b;">{{ $cpmk->deskripsi }}</span>
                                </div>
                                <div style="padding-left:12px; display:flex; flex-direction:column; gap:6px;">
                                    @foreach($cpmk->subCpmks as $sub)
                                        <div style="font-size:12px; color:#475569; background:#f8fafc; padding:6px 8px; border-radius:6px; border:1px solid #f1f5f9;">
                                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                                <strong>{{ $sub->kode_sub_cpmk }}</strong>
                                                <span class="cpl-pill">{{ $sub->cpl?->kode_cpl ?? 'CPL' }}</span>
                                            </div>
                                            <div style="margin-top:2px;">{{ $sub->deskripsi }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div style="font-size:13px; color:#64748b; text-align:center; padding:16px;">
                                Belum ada CPMK / Sub-CPMK yang terdaftar untuk mata kuliah ini di Master Kurikulum OBE.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

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

                        <button type="button" class="btn-primary" onclick="submitNilaiForm()">
                            <x-layout-icon name="save" />
                            <span>Simpan Nilai</span>
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

            {{-- Form & Tabel Input Nilai Mahasiswa --}}
            <form action="{{ route('dosen.nilai.input-obe', $jadwal->id) }}" method="POST" id="form-input-nilai">
                @csrf

                <div class="page-card">
                    <div class="page-card-head">
                        <h2>Daftar Nilai Mahasiswa — Kelas {{ $jadwal->kelas }}</h2>
                        <span class="badge badge-info">{{ $krsList->count() }} Mahasiswa Terdaftar</span>
                    </div>

                    <div class="page-card-body" style="padding:0;">
                        <div class="table-wrap" style="overflow-x:auto;">
                            <table class="table-input-nilai" style="width:100%; border-collapse:collapse;" id="table-nilai">
                                <thead>
                                    <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0;">
                                        <th style="width:40px; text-align:center;">No</th>
                                        <th style="width:110px;">NIM</th>
                                        <th style="min-width:180px;">Nama Mahasiswa</th>
                                        {{-- Kolom Dinamis Instrumen --}}
                                        @foreach($komponens as $komp)
                                            <th style="text-align:center; min-width:90px;">
                                                <div>{{ $komp->nama_instrumen }}</div>
                                                <div style="font-size:11px; color:#64748b; font-weight:400;">{{ $komp->bobot }}%</div>
                                                @if($komp->subCpmk?->cpl)
                                                    <span class="cpl-pill" style="margin-top:2px;">{{ $komp->subCpmk->cpl->kode_cpl }}</span>
                                                @endif
                                            </th>
                                        @endforeach
                                        <th style="text-align:center; min-width:80px; background:#eff6ff;">Nilai Akhir</th>
                                        <th style="text-align:center; min-width:60px; background:#eff6ff;">Huruf</th>
                                        <th style="text-align:center; min-width:60px; background:#eff6ff;">Mutu</th>
                                        <th style="text-align:center; min-width:120px;">Status CPL</th>
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
                                            $nilaiMap = $krs->nilaiKomponens->pluck('nilai_angka', 'komponen_id');
                                        @endphp
                                        <tr class="mhs-row" data-nim="{{ strtolower($krs->mahasiswa?->nim ?? '') }}" data-nama="{{ strtolower($krs->mahasiswa?->nama ?? '') }}">
                                            <td style="text-align:center; font-weight:600; color:#64748b;">{{ $loop->iteration }}</td>
                                            <td style="font-weight:600; font-family:monospace;">{{ $krs->mahasiswa?->nim ?? '-' }}</td>
                                            <td style="font-weight:600; color:#0f172a;">{{ $krs->mahasiswa?->nama ?? '-' }}</td>

                                            {{-- Input Skor Komponen --}}
                                            @foreach($komponens as $komp)
                                                @php
                                                    $val = $nilaiMap->get($komp->id);
                                                @endphp
                                                <td style="text-align:center;">
                                                    <input type="number"
                                                           name="nilai[{{ $krs->id }}][{{ $komp->id }}]"
                                                           class="form-control input-skor mhs-input"
                                                           min="0"
                                                           max="100"
                                                           step="0.01"
                                                           placeholder="0"
                                                           value="{{ $val !== null ? $val : '' }}"
                                                           data-krs="{{ $krs->id }}"
                                                           data-komponen="{{ $komp->id }}"
                                                           data-bobot="{{ $komp->bobot }}"
                                                           data-cpl="{{ $komp->subCpmk?->cpl?->kode_cpl ?? '' }}"
                                                           {{ $skema->is_finalized ? 'readonly' : '' }}>
                                                </td>
                                            @endforeach

                                            {{-- Hasil Hitung Live --}}
                                            <td style="text-align:center; font-weight:800; font-size:14px; background:#eff6ff;" id="cell-na-{{ $krs->id }}">
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
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 7 + $komponens->count() }}" style="text-align:center; padding:40px; color:#64748b;">
                                                Belum ada mahasiswa yang mengambil kelas ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if(! $skema->is_finalized && $krsList->isNotEmpty())
                    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                        <button type="submit" class="btn-primary">
                            <x-layout-icon name="save" />
                            <span>Simpan Draf Nilai</span>
                        </button>
                    </div>
                @endif
            </form>
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
    // TAB 1: KALKULASI DINAMIS SKEMA PENILAIAN
    // -------------------------------------------------------------------------
    @if($tab === 'pengaturan')
    (function(){
        const tableBody = document.getElementById('skema-rows');
        const labelTotal = document.getElementById('label-total-bobot');
        const boxStatus100 = document.getElementById('box-status-100');
        const liveCplBox = document.getElementById('live-cpl-distribution');
        const btnAddRow = document.getElementById('btn-add-row');

        function hitungSkema() {
            let total = 0;
            const cplWeights = {};

            const rows = document.querySelectorAll('.skema-row');
            rows.forEach((row, i) => {
                const numEl = row.querySelector('.row-number');
                if (numEl) numEl.textContent = (i + 1);

                const bobotInput = row.querySelector('.input-bobot');
                const bVal = parseFloat(bobotInput?.value || 0);
                if (!isNaN(bVal)) {
                    total += bVal;

                    const selectSub = row.querySelector('.select-sub-cpmk');
                    const selectedOpt = selectSub ? selectSub.options[selectSub.selectedIndex] : null;
                    const cplCode = selectedOpt ? selectedOpt.getAttribute('data-cpl') : '';

                    if (cplCode) {
                        cplWeights[cplCode] = (cplWeights[cplCode] || 0) + bVal;
                    }
                }
            });

            total = Math.round(total * 100) / 100;
            if (labelTotal) labelTotal.textContent = total + '%';

            // Status 100% box
            if (boxStatus100) {
                if (Math.abs(total - 100) < 0.05) {
                    boxStatus100.style.background = '#dcfce7';
                    boxStatus100.style.border = '1px solid #bbf7d0';
                    boxStatus100.style.color = '#166534';
                    boxStatus100.innerHTML = '<strong>Total Bobot Tepat 100%</strong> — Pengaturan siap disimpan.';
                } else {
                    const selisih = Math.round((100 - total) * 100) / 100;
                    boxStatus100.style.background = '#fef2f2';
                    boxStatus100.style.border = '1px solid #fecaca';
                    boxStatus100.style.color = '#991b1b';
                    boxStatus100.innerHTML = '<strong>Total Bobot Saat Ini: ' + total + '%</strong>. Kurang / lebih ' + selisih + '%. Total harus tepat 100% saat disimpan.';
                }
            }

            // Live CPL Distribution
            if (liveCplBox) {
                if (Object.keys(cplWeights).length === 0) {
                    liveCplBox.innerHTML = '<span style="font-size:12px; color:#94a3b8; font-style:italic;">Belum ada instrumen yang dihubungkan ke Sub-CPMK &amp; CPL.</span>';
                } else {
                    let html = '<div style="display:flex; flex-direction:column; gap:6px;">';
                    for (const [code, w] of Object.entries(cplWeights)) {
                        const pctOf100 = total > 0 ? Math.round((w / total) * 100) : 0;
                        html += `
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:6px 10px; display:flex; justify-content:space-between; align-items:center; font-size:12px;">
                                <div><span class="cpl-pill">${code}</span> <span style="color:#475569; margin-left:4px;">Alokasi Bobot:</span></div>
                                <div><strong>${w}%</strong> <span style="color:#64748b;">(${pctOf100}% porsi)</span></div>
                            </div>
                        `;
                    }
                    html += '</div>';
                    liveCplBox.innerHTML = html;
                }
            }
        }

        // Listener input bobot & select change
        if (tableBody) {
            tableBody.addEventListener('input', e => {
                if (e.target.classList.contains('input-bobot')) hitungSkema();
            });
            tableBody.addEventListener('change', e => {
                if (e.target.classList.contains('select-sub-cpmk')) hitungSkema();
            });
            tableBody.addEventListener('click', e => {
                const btnRemove = e.target.closest('.btn-remove-row');
                if (btnRemove) {
                    const row = btnRemove.closest('.skema-row');
                    const rows = document.querySelectorAll('.skema-row');
                    if (rows.length <= 1) {
                        alert('Minimal harus ada 1 baris instrumen penilaian.');
                        return;
                    }
                    row.remove();
                    hitungSkema();
                }
            });
        }

        // Add row
        if (btnAddRow) {
            btnAddRow.addEventListener('click', () => {
                const rows = document.querySelectorAll('.skema-row');
                const lastRow = rows[rows.length - 1];
                const clone = lastRow.cloneNode(true);

                // Reset values
                const nameInput = clone.querySelector('.input-nama-instrumen');
                const bobotInput = clone.querySelector('.input-bobot');
                const selectSub = clone.querySelector('.select-sub-cpmk');

                if (nameInput) nameInput.value = '';
                if (bobotInput) bobotInput.value = '10';
                if (selectSub) selectSub.selectedIndex = 0;

                tableBody.appendChild(clone);
                hitungSkema();
            });
        }

        // Jalankan saat load
        hitungSkema();
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

        // Live calculation per row on input
        const tableNilai = document.getElementById('table-nilai');
        if (tableNilai) {
            tableNilai.addEventListener('input', function(e){
                if (!e.target.classList.contains('mhs-input')) return;

                const tr = e.target.closest('tr');
                if (!tr) return;

                const krsId = e.target.getAttribute('data-krs');
                const inputs = tr.querySelectorAll('.mhs-input');

                let totalWeightedScore = 0;
                let cplData = {};

                inputs.forEach(inp => {
                    const val = parseFloat(inp.value) || 0;
                    const weight = parseFloat(inp.getAttribute('data-bobot')) || 0;
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

                // Update NA cell
                const cellNa = document.getElementById('cell-na-' + krsId);
                if (cellNa) cellNa.textContent = na.toFixed(2);

                // Update Huruf cell
                const cellHuruf = document.getElementById('cell-huruf-' + krsId);
                if (cellHuruf) {
                    cellHuruf.innerHTML = `<span class="grade-chip ${conv.cls}">${conv.huruf}</span>`;
                }

                // Update Mutu cell
                const cellMutu = document.getElementById('cell-mutu-' + krsId);
                if (cellMutu) cellMutu.textContent = conv.bobot;

                // Update CPL Status cell
                let allCplAchieved = true;
                const unachieved = [];

                for (const [code, item] of Object.entries(cplData)) {
                    const cplScore = item.weight > 0 ? (item.score / item.weight) : 0;
                    if (cplScore < passingGrade) {
                        allCplAchieved = false;
                        unachieved.push(code);
                    }
                }

                const cellCpl = document.getElementById('cell-cpl-' + krsId);
                if (cellCpl) {
                    if (allCplAchieved) {
                        cellCpl.innerHTML = `<span class="badge badge-success" style="font-size:11px; padding:3px 8px;"><svg class="layout-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Lulus CPL</span>`;
                    } else {
                        cellCpl.innerHTML = `<span class="badge badge-warning" style="font-size:11px; padding:3px 8px;" title="Belum: ${unachieved.join(', ')}">Belum Lulus</span>`;
                    }
                }
            });
        }
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
