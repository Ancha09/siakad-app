@extends('layouts.mahasiswa')

@section('title', 'KHS Mahasiswa')

@push('styles')
<style>
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
        max-width: 1080px;
        width: 95%;
        max-height: 92vh;
        padding: 24px;
    }
    .table-matrix-khs {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        text-align: left;
    }
    .table-matrix-khs th, .table-matrix-khs td {
        border: 1px solid #e2e8f0;
        padding: 8px 10px;
        vertical-align: middle;
    }
    .table-matrix-khs th.matrix-cpmk-head {
        background: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
        text-align: center;
        font-size: 12px;
    }
    .table-matrix-khs th.matrix-sub-head {
        background: #f0f9ff;
        color: #0284c7;
        font-weight: 700;
        text-align: center;
        font-size: 11px;
    }
</style>
@endpush

@section('content')

<div class="page-card">

    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="page-card-head">

        <h2><x-layout-icon name="file-check" /> Kartu Hasil Studi</h2>

    </div>


    {{-- =====================================================
         IDENTITAS MAHASISWA
    ====================================================== --}}

    <div class="page-card-body">

        <div style="display:flex;justify-content:flex-end;margin-bottom:18px;">
            <a href="{{ route('mahasiswa.transkrip.pdf') }}" class="btn-primary">
                Download Transkrip PDF
            </a>
        </div>

        @if(session('success'))
            <div style="background:#dcfce7;color:#166534;padding:13px 16px;border-radius:10px;margin-bottom:18px;">
                {{ session('success') }}
            </div>
        @endif

        <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:14px 16px;border-radius:10px;margin-bottom:20px;">
            <span class="icon-inline"><x-layout-icon name="lock" /></span> Nilai mata kuliah terbuka setelah kuesionernya diisi. IPS dan IPK kumulatif tetap terkunci sampai seluruh kuesioner yang wajib selesai.
            @if($jumlahKuesionerTertunda > 0)
                <a href="{{ route('mahasiswa.kuesioner') }}" style="display:inline-block;margin-left:8px;color:#9a3412;font-weight:700;">Isi {{ $jumlahKuesionerTertunda }} kuesioner tertunda →</a>
            @endif
        </div>

        <div style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:15px;
            margin-bottom:25px;
        ">

            <div style="
                background:#f8fafc;
                padding:18px;
                border-radius:10px;
            ">

                <small style="color:#64748b;">
                    NIM
                </small>

                <div style="
                    font-weight:600;
                    margin-top:5px;
                ">
                    {{ $mahasiswa->nim ?? '-' }}
                </div>

            </div>


            <div style="
                background:#f8fafc;
                padding:18px;
                border-radius:10px;
            ">

                <small style="color:#64748b;">
                    Nama Mahasiswa
                </small>

                <div style="
                    font-weight:600;
                    margin-top:5px;
                ">
                    {{ $mahasiswa->nama ?? '-' }}
                </div>

            </div>


            <div style="
                background:#f8fafc;
                padding:18px;
                border-radius:10px;
            ">

                <small style="color:#64748b;">
                    Program Studi
                </small>

                <div style="
                    font-weight:600;
                    margin-top:5px;
                ">
                    {{ $mahasiswa->prodi->nama_prodi ?? '-' }}
                </div>

            </div>


            <div style="
                background:#f8fafc;
                padding:18px;
                border-radius:10px;
            ">

                <small style="color:#64748b;">
                    Kelas
                </small>

                <div style="
                    font-weight:600;
                    margin-top:5px;
                ">
                    {{ $mahasiswa->kelas->nama_kelas ?? '-' }}
                </div>

            </div>

        </div>


        {{-- =====================================================
             RINGKASAN AKADEMIK
        ====================================================== --}}

        <div style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
            gap:15px;
            margin-bottom:30px;
        ">

            {{-- TOTAL SKS --}}

            <div style="
                background:#eff6ff;
                border-radius:12px;
                padding:20px;
                border:1px solid #dbeafe;
            ">

                <div style="
                    font-size:14px;
                    color:#64748b;
                ">
                    Total SKS
                </div>

                <div style="
                    font-size:28px;
                    font-weight:700;
                    margin-top:5px;
                ">
                    {{ $totalSks }}
                </div>

                <small>
                    SKS ditempuh
                </small>

            </div>


            {{-- IPK --}}

            <div style="
                background:#f0fdf4;
                border-radius:12px;
                padding:20px;
                border:1px solid #dcfce7;
            ">

                <div style="
                    font-size:14px;
                    color:#64748b;
                ">
                    IPK
                </div>

                <div style="
                    font-size:28px;
                    font-weight:700;
                    margin-top:5px;
                ">
                    @if($ipk !== null)
                        {{ number_format($ipk, 2) }}
                    @elseif($jumlahKuesionerTertunda > 0)
                        <span style="font-size:14px;font-weight:500;">{{ \App\Services\MahasiswaNilaiService::LOCKED_PLACEHOLDER }}</span>
                    @else
                        -
                    @endif
                </div>

                <small>
                    Indeks Prestasi Kumulatif
                </small>

            </div>

        </div>


        {{-- =====================================================
             DATA KHS PER SEMESTER
        ====================================================== --}}

        @forelse($khsPerSemester as $semester => $data)

            <div style="margin-bottom:35px;">

                {{-- HEADER SEMESTER --}}

                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:15px;
                    margin-bottom:15px;
                    flex-wrap:wrap;
                ">

                    <div>

                        <h3 style="margin:0;">
                            <x-layout-icon name="book" /> {{ $semester }}
                        </h3>

                    </div>


                    {{-- IPS --}}

                    <div style="
                        background:#f8fafc;
                        padding:10px 18px;
                        border-radius:8px;
                    ">

                        <strong>
                            IPS:
                        </strong>

                        <span style="
                            font-size:18px;
                            font-weight:700;
                            margin-left:5px;
                        ">

                            @if(($ipsPerSemester[$semester] ?? null) !== null)
                                {{ number_format($ipsPerSemester[$semester], 2) }}
                            @else
                                <span style="font-size:13px;font-weight:500;">{{ \App\Services\MahasiswaNilaiService::LOCKED_PLACEHOLDER }}</span>
                            @endif

                        </span>

                    </div>

                </div>


                {{-- TABEL KHS --}}

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>No</th>

                                <th>Kode</th>

                                <th>Mata Kuliah</th>

                                <th>Dosen</th>

                                <th>SKS</th>

                                <th>Nilai</th>

                                <th>Huruf</th>

                                <th>Bobot</th>

                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($data as $index => $item)

                                <tr>

                                    {{-- NO --}}

                                    <td>
                                        {{ $index + 1 }}
                                    </td>


                                    {{-- KODE --}}

                                    <td>
                                        {{ $item->krs?->mata_kuliah_efektif?->kode_mk ?? '-' }}
                                    </td>


                                    {{-- MATA KULIAH --}}

                                    <td>

                                        <strong>
                                            {{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-' }}
                                        </strong>
                                        @php
                                            $rpsAktif = $item->krs?->mata_kuliah_efektif?->rpsAktif ?? $item->krs?->jadwal?->mataKuliah?->rpsAktif;
                                        @endphp
                                        @if($rpsAktif && $rpsAktif->file_rps && $item->krs?->mata_kuliah_efektif)
                                            <div style="margin-top:3px;">
                                                <a href="{{ route('mahasiswa.rps.download', $item->krs->mata_kuliah_efektif->id) }}" class="btn-outline" style="font-size:11px; padding:2px 7px; display:inline-flex; align-items:center; gap:4px; text-decoration:none;" target="_blank">
                                                    <x-layout-icon name="download" /> RPS (PDF)
                                                </a>
                                            </div>
                                        @endif

                                    </td>


                                    {{-- DOSEN --}}

                                    <td>
                                        {{ $item->dosen_efektif?->nama ?? '-' }}
                                    </td>


                                    {{-- SKS --}}

                                    <td>

                                        {{ $item->sks_efektif }}

                                    </td>


                                    {{-- NILAI ANGKA --}}

                                    @if(! $item->krs?->kuesioner)
                                        <td colspan="3">{{ \App\Services\MahasiswaNilaiService::LOCKED_PLACEHOLDER }}</td>
                                    @else
                                    <td>

                                        {{ $item->nilai_angka ?? '-' }}

                                    </td>


                                    {{-- NILAI HURUF --}}

                                    <td>

                                        <strong>
                                            {{ $item->nilai_huruf ?? '-' }}
                                        </strong>

                                    </td>


                                    {{-- BOBOT --}}

                                    <td>

                                        {{ $item->bobot ?? '-' }}

                                    </td>
                                    @endif


                                    {{-- STATUS KUESIONER --}}

                                    <td>
                                        @if($item->krs?->kuesioner)
                                            <span class="badge-success">Nilai terbuka</span>
                                            @if($item->krs?->nilaiKomponens && $item->krs->nilaiKomponens->isNotEmpty())
                                                <div style="margin-top:4px;">
                                                    <button type="button" class="btn-outline" style="font-size:11px; padding:3px 8px; display:inline-flex; align-items:center; gap:4px;" onclick="openObeModal('modal-obe-{{ $item->id }}')">
                                                        <x-layout-icon name="chart" /> Rincian OBE
                                                    </button>
                                                </div>
                                            @endif
                                        @else
                                            <a href="{{ route('mahasiswa.kuesioner.create', $item->krs_id) }}"
                                               class="btn-primary"
                                               style="display:inline-block;padding:8px 12px;white-space:nowrap;">
                                                Isi Kuesioner
                                            </a>
                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        @empty

            {{-- =================================================
                 BELUM ADA KHS
            ================================================== --}}

            <div style="
                text-align:center;
                padding:50px 20px;
                background:#f8fafc;
                border-radius:12px;
            ">

                <div style="
                    font-size:45px;
                    margin-bottom:10px;
                ">
                    <x-layout-icon name="file-check" />
                </div>

                <h3>
                    Belum Ada Data KHS
                </h3>

                <p style="
                    color:#64748b;
                    margin:0;
                ">
                    Data hasil studi Anda belum tersedia.
                </p>

            </div>

        @endforelse

    </div>

</div>

{{-- MODALS RINCIAN NILAI OBE --}}
@foreach($khs as $item)
    @if($item->krs?->nilaiKomponens && $item->krs->nilaiKomponens->isNotEmpty())
        @php
            $mk = $item->krs->mata_kuliah_efektif ?? $item->krs->jadwal?->mataKuliah;
            $rps = $mk?->rpsAktif ?? $mk?->rpsList()->latest()->first();
            $pg = (float) ($rps?->target_passing_grade ?? 60.0);
            $skema = $item->krs->jadwal?->skemaPenilaian;
            $matrixData = $skema?->matrix_alokasi;
            $cpmks = $mk?->cpmks ?? collect();
            $allSubCpmks = $cpmks->pluck('subCpmks')->flatten();
            $hasMatrix = !empty($matrixData['rows']) && $allSubCpmks->isNotEmpty();

            // Peta komponen matriks jika ada matriks
            $matrixMap = [];
            if ($hasMatrix && $skema) {
                $kompList = $skema->komponens;
                foreach ($matrixData['rows'] as $rIdx => $r) {
                    $rNama = trim($r['nama'] ?? '');
                    foreach ($r['allocations'] ?? [] as $subId => $bVal) {
                        if ((float)$bVal <= 0) continue;
                        $subId = (int)$subId;
                        $mKomp = $kompList->first(function($k) use ($subId, $rNama) {
                            return (int)$k->sub_cpmk_id === $subId && str_starts_with($k->nama_instrumen, $rNama);
                        });
                        if (!$mKomp) {
                            $mKomp = $kompList->firstWhere('sub_cpmk_id', $subId);
                        }
                        if ($mKomp) {
                            $matrixMap[$rIdx][$subId] = $mKomp;
                        }
                    }
                }
            }

            $nilaiMap = $item->krs->nilaiKomponens->pluck('nilai_angka', 'komponen_id');

            // Hitung Ketercapaian CPL
            $cplScores = [];
            foreach($item->krs->nilaiKomponens as $nk) {
                $cpl = $nk->komponen?->subCpmk?->cpl;
                if ($cpl) {
                    $cCode = $cpl->kode_cpl;
                    if (!isset($cplScores[$cCode])) {
                        $cplScores[$cCode] = ['weighted' => 0.0, 'weight' => 0.0, 'desc' => $cpl->deskripsi];
                    }
                    $w = (float) ($nk->komponen->bobot ?? 0);
                    $cplScores[$cCode]['weighted'] += ((float) $nk->nilai_angka * $w);
                    $cplScores[$cCode]['weight'] += $w;
                }
            }
        @endphp
        <div id="modal-obe-{{ $item->id }}" class="modal-backdrop-custom" style="display:none;">
            <div class="modal-box-custom {{ $hasMatrix ? 'modal-box-large' : '' }}">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; border-bottom:1px solid #e2e8f0; padding-bottom:14px; gap:12px; flex-wrap:wrap;">
                    <div>
                        <h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a;">Rincian Penilaian Matriks OBE</h3>
                        <div style="font-size:13px; color:#64748b; margin-top:3px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <span style="font-weight:700; color:#1e293b;">{{ $item->krs->mata_kuliah_efektif?->kode_mk }} — {{ $item->krs->mata_kuliah_efektif?->nama_mk }}</span>
                            <span>&bull;</span>
                            <span>{{ $item->sks_efektif }} SKS</span>
                            <span>&bull;</span>
                            <span>Kelas {{ $item->krs->jadwal?->kelas ?? '-' }}</span>
                        </div>
                    </div>
                    <button type="button" onclick="closeObeModal('modal-obe-{{ $item->id }}')" style="background:none; border:none; cursor:pointer; color:#64748b; font-size:22px; line-height:1;" title="Tutup">
                        &times;
                    </button>
                </div>

                {{-- Banner Nilai Akhir --}}
                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:18px; text-align:center;">
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Nilai Akhir (NA)</div>
                        <div style="font-size:22px; font-weight:800; color:#0f172a; margin-top:2px;">{{ $item->nilai_angka ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Nilai Huruf</div>
                        <div style="font-size:22px; font-weight:800; color:#2563eb; margin-top:2px;">{{ $item->nilai_huruf ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Bobot Mutu</div>
                        <div style="font-size:22px; font-weight:800; color:#0f172a; margin-top:2px;">{{ $item->bobot ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Standar Kelulusan</div>
                        <div style="font-size:13px; font-weight:700; color:#15803d; margin-top:6px;">&ge; {{ number_format($pg, 0) }}</div>
                    </div>
                </div>

                @if($hasMatrix)
                    {{-- 2D Matrix Table --}}
                    <div style="margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <div style="font-size:13px; font-weight:700; color:#0f172a;">
                                Matriks Skor Penilaian &times; Sub-CPMK:
                            </div>
                            <div style="font-size:11.5px; color:#64748b;">
                                Nilai mentah (skala 0 - 100) dan alokasi bobot (%) dari RPS
                            </div>
                        </div>

                        <div style="border:1px solid #e2e8f0; border-radius:10px; overflow-x:auto; background:#ffffff;">
                            <table class="table-matrix-khs">
                                <thead>
                                    {{-- Baris 1: Header CPMK --}}
                                    <tr>
                                        <th style="min-width:180px; background:#ffffff; border-bottom:none;"></th>
                                        <th style="width:65px; background:#ffffff; border-bottom:none; text-align:center;"></th>
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
                                    @foreach($matrixData['rows'] as $rIdx => $row)
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
                                            <td style="text-align:center; font-weight:800; font-size:12px; color:#0f172a; background:#f8fafc;">
                                                {{ round($rowSum, 1) }}%
                                            </td>
                                            @foreach($allSubCpmks as $sub)
                                                @php
                                                    $alloc = (float) ($row['allocations'][$sub->id] ?? ($row['allocations'][(string)$sub->id] ?? 0));
                                                    $komp = $matrixMap[$rIdx][$sub->id] ?? null;
                                                    $scoreVal = $komp ? $nilaiMap->get($komp->id) : null;
                                                @endphp
                                                @if($alloc > 0 && $komp)
                                                    <td style="text-align:center; padding:6px 4px; vertical-align:middle; background:#ffffff;">
                                                        <div style="font-weight:800; font-size:13px; color:{{ $scoreVal !== null ? '#0f172a' : '#94a3b8' }};">
                                                            {{ $scoreVal !== null ? number_format($scoreVal, 1) : '-' }}
                                                        </div>
                                                        <span style="font-size:10px; color:#64748b; font-weight:600; display:block; margin-top:2px;">
                                                            ({{ $alloc }}%)
                                                        </span>
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
                                        <td style="padding:8px 10px; color:#0f172a; font-weight:800; font-size:12px;">Total Bobot Sub-CPMK</td>
                                        <td style="text-align:center; font-weight:800; color:#15803d; font-size:12px;">100%</td>
                                        @foreach($allSubCpmks as $sub)
                                            @php
                                                $colSum = 0;
                                                foreach ($matrixData['rows'] as $r) {
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
                @else
                    {{-- Fallback: Flat Table Komponen --}}
                    <div style="margin-bottom:18px;">
                        <div style="font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">Komponen Instrumen Penilaian:</div>
                        <div class="table-wrap">
                            <table style="width:100%; font-size:12px;">
                                <thead>
                                    <tr style="background:#f1f5f9;">
                                        <th>Instrumen</th>
                                        <th>Sub-CPMK &amp; CPL</th>
                                        <th style="text-align:center;">Bobot</th>
                                        <th style="text-align:center;">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($item->krs->nilaiKomponens as $nk)
                                        <tr>
                                            <td><strong>{{ $nk->komponen?->nama_instrumen ?? '-' }}</strong></td>
                                            <td>
                                                @if($nk->komponen?->subCpmk)
                                                    <span>{{ $nk->komponen->subCpmk->kode_sub_cpmk }}</span>
                                                    @if($nk->komponen->subCpmk->cpl)
                                                        <span style="display:inline-block; padding:1px 6px; border-radius:4px; font-size:10px; font-weight:700; background:#e0f2fe; color:#0369a1; margin-left:4px;">
                                                            {{ $nk->komponen->subCpmk->cpl->kode_cpl }}
                                                        </span>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="text-align:center;">{{ $nk->komponen?->bobot ?? 0 }}%</td>
                                            <td style="text-align:center; font-weight:700;">{{ $nk->nilai_angka }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Ketercapaian CPL Mahasiswa --}}
                @if(!empty($cplScores))
                    <div style="margin-bottom:16px;">
                        <div style="font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">Ketercapaian Capaian Pembelajaran Lulusan (CPL):</div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px;">
                            @foreach($cplScores as $cCode => $dt)
                                @php
                                    $score = $dt['weight'] > 0 ? round($dt['weighted'] / $dt['weight'], 2) : 0;
                                    $isLulus = $score >= $pg;
                                @endphp
                                <div style="background:{{ $isLulus ? '#f0fdf4' : '#fef2f2' }}; border:1px solid {{ $isLulus ? '#bbf7d0' : '#fecaca' }}; border-radius:8px; padding:10px 12px; display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <span style="font-weight:700; color:{{ $isLulus ? '#15803d' : '#b91c1c' }}; font-size:12px;">{{ $cCode }}</span>
                                            <span style="font-size:11px; color:#64748b;">(Bobot: {{ round($dt['weight'], 1) }}%)</span>
                                        </div>
                                        @if($dt['desc'])
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;">{{ Str::limit($dt['desc'], 50) }}</div>
                                        @endif
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-weight:800; font-size:13.5px; color:{{ $isLulus ? '#15803d' : '#b91c1c' }};">{{ $score }}</div>
                                        @if($isLulus)
                                            <span class="badge badge-success" style="font-size:10px; padding:2px 6px;">
                                                <x-layout-icon name="check" /> Lulus
                                            </span>
                                        @else
                                            <span class="badge badge-warning" style="font-size:10px; padding:2px 6px;">
                                                Belum
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div style="display:flex; justify-content:flex-end; margin-top:20px; border-top:1px solid #e2e8f0; padding-top:14px;">
                    <button type="button" class="btn-outline" onclick="closeObeModal('modal-obe-{{ $item->id }}')">Tutup</button>
                </div>
            </div>
        </div>
    @endif
@endforeach

@push('scripts')
<script>
function openObeModal(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}
function closeObeModal(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'none';
}
document.querySelectorAll('.modal-backdrop-custom').forEach(function(b) {
    b.addEventListener('click', function(e) {
        if (e.target === b) b.style.display = 'none';
    });
});
</script>
@endpush

@endsection
