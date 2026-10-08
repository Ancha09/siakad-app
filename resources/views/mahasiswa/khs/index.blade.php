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
            $rps = $item->krs->mata_kuliah_efektif?->rpsAktif ?? $item->krs->jadwal?->mataKuliah?->rpsAktif;
            $pg = (float) ($rps?->target_passing_grade ?? 60.0);
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
            <div class="modal-box-custom">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
                    <div>
                        <h3 style="margin:0; font-size:16px; font-weight:700; color:#0f172a;">Rincian Penilaian OBE</h3>
                        <div style="font-size:13px; color:#64748b; margin-top:3px;">
                            {{ $item->krs->mata_kuliah_efektif?->kode_mk }} — {{ $item->krs->mata_kuliah_efektif?->nama_mk }} ({{ $item->sks_efektif }} SKS)
                        </div>
                    </div>
                    <button type="button" onclick="closeObeModal('modal-obe-{{ $item->id }}')" style="background:none; border:none; cursor:pointer; color:#64748b;">
                        <x-layout-icon name="x" />
                    </button>
                </div>

                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:18px; text-align:center;">
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Nilai Akhir</div>
                        <div style="font-size:22px; font-weight:800; color:#0f172a; margin-top:2px;">{{ $item->nilai_angka ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Nilai Huruf</div>
                        <div style="font-size:22px; font-weight:800; color:#2563eb; margin-top:2px;">{{ $item->nilai_huruf ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">Mutu / Bobot</div>
                        <div style="font-size:22px; font-weight:800; color:#0f172a; margin-top:2px;">{{ $item->bobot ?? '-' }}</div>
                    </div>
                </div>

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

                @if(!empty($cplScores))
                    <div style="margin-bottom:16px;">
                        <div style="font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">Ketercapaian Capaian Pembelajaran Lulusan (CPL):</div>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            @foreach($cplScores as $cCode => $dt)
                                @php
                                    $score = $dt['weight'] > 0 ? round($dt['weighted'] / $dt['weight'], 2) : 0;
                                    $isLulus = $score >= $pg;
                                @endphp
                                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <span style="font-weight:700; color:#0369a1; background:#e0f2fe; padding:2px 6px; border-radius:4px; font-size:11px;">{{ $cCode }}</span>
                                            <span style="font-size:12px; font-weight:600;">Skor: {{ $score }}</span>
                                        </div>
                                        @if($dt['desc'])
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;">{{ Str::limit($dt['desc'], 60) }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        @if($isLulus)
                                            <span class="badge badge-success" style="font-size:11px; padding:3px 8px;">
                                                <x-layout-icon name="check" /> Lulus CPL
                                            </span>
                                        @else
                                            <span class="badge badge-warning" style="font-size:11px; padding:3px 8px;">
                                                Belum Tercapai
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div style="display:flex; justify-content:flex-end; margin-top:20px;">
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
