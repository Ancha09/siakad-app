@extends('layouts.mahasiswa')

@section('title', 'Jadwal Kuliah')

@section('content')
<div class="inner-page">
    <div class="info-alert"><span class="icon-inline"><x-layout-icon name="calendar" /></span><div><strong>Jadwal Kuliah</strong><br>Mata kuliah berdasarkan KRS yang telah disetujui @if(!empty($targetTahun))(Periode Aktif: {{ $targetTahun }} - {{ $targetSemester }})@endif.</div></div>

    @if(isset($periodeList) && $periodeList->count() > 1)
        <div style="margin-bottom:16px;">
            <form method="GET" action="{{ route('mahasiswa.jadwal') }}" style="display:inline-flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <label for="periode-select" style="font-size:13px; font-weight:600; color:#0a1f5c;">Pilih Periode Semester:</label>
                <select name="periode" id="periode-select" class="form-control" style="max-width:280px; padding:6px 12px; border:1px solid #cbd5e1; border-radius:8px;" onchange="this.form.submit()">
                    @foreach($periodeList as $p)
                        <option value="{{ $p['key'] }}" @selected(($selectedPeriodKey ?? '') === $p['key'])>
                            {{ $p['label'] }} ({{ $p['count'] }} MK)
                        </option>
                    @endforeach
                </select>
                @if(request()->filled('periode'))
                    <a href="{{ route('mahasiswa.jadwal') }}" class="btn-outline" style="font-size:12px; padding:5px 10px; border-radius:8px; text-decoration:none;">Reset ke Periode Aktif</a>
                @endif
            </form>
        </div>
    @endif

    <div class="page-card">
        <div class="page-card-head">
            <h2 class="icon-heading"><x-layout-icon name="calendar" /> Jadwal Kuliah Saya @if(!empty($targetTahun))<small style="font-size:14px; color:#64748b; font-weight:normal;">({{ $targetTahun }} {{ $targetSemester }})</small>@endif</h2>
            <span class="badge" style="background:#fff;color:#0a1f5c;">{{ $krsItems->count() }} mata kuliah{{ isset($totalSks) && $totalSks > 0 ? ' ('.$totalSks.' SKS)' : '' }}</span>
        </div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>No</th><th>Kode MK</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen Pengampu</th><th>Hari / Jam</th><th>Ruangan</th><th>Periode</th><th>Status KRS</th></tr></thead>
                    <tbody>
                        @forelse($krsItems as $item)
                            @php($jadwal = $item->jadwal)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="badge badge-blue">{{ $item->mata_kuliah_efektif?->kode_mk ?? '-' }}</span></td>
                                <td>
                                    <strong>{{ $item->mata_kuliah_efektif?->nama_mk ?? '-' }}</strong>
                                    @if($kelasNama = ($item->kelas_efektif?->nama_kelas ?? $jadwal?->kelas))
                                        <br><small style="color:#64748b; font-weight:500;">Kelas {{ $kelasNama }}</small>
                                    @endif
                                    @php($rps = $item->mata_kuliah_efektif?->rpsAktif ?? $jadwal?->mataKuliah?->rpsAktif)
                                    @if($rps && $item->mata_kuliah_efektif)
                                        <div style="margin-top:4px; display:flex; gap:5px; flex-wrap:wrap; align-items:center;">
                                            <a href="{{ route('mahasiswa.kurikulum') }}" class="btn-outline" style="font-size:11px; padding:2px 7px; display:inline-flex; align-items:center; gap:4px; text-decoration:none; color:#0a1f5c; border-color:#cbd5e1;" title="Lihat Detail RPS, CPMK & Bobot">
                                                <x-layout-icon name="book" /> Detail RPS
                                            </a>
                                            @if($rps->effective_file_path)
                                                <a href="{{ route('mahasiswa.rps.download', $item->mata_kuliah_efektif->id) }}" class="btn-primary" style="font-size:11px; padding:2px 8px; display:inline-flex; align-items:center; gap:4px; text-decoration:none;" target="_blank">
                                                    <x-layout-icon name="download" /> RPS (PDF)
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $item->mata_kuliah_efektif?->sks ?? '-' }}</td>
                                <td>{{ $item->semua_dosen_nama ?: ($item->dosen_efektif?->nama ?? '-') }}</td>
                                <td><strong>{{ $jadwal?->hari ?? '-' }}</strong><br><small style="color:#64748b;">{{ $jadwal?->jam_mulai ?? '-' }} - {{ $jadwal?->jam_selesai ?? '-' }}</small></td>
                                <td>{{ $jadwal?->ruangan?->nama_ruangan ?? '-' }}</td>
                                <td>{{ $item->tahun_akademik ?? '-' }}<br><small style="color:#64748b;">{{ $item->semester_akademik ?? '-' }}</small></td>
                                <td><span class="badge badge-green">{{ $item->status ?? '-' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" style="text-align:center;padding:40px;color:#64748b;"><span class="empty-state-icon"><x-layout-icon name="calendar" /></span><strong style="display:block;margin-top:8px;">Belum ada mata kuliah yang diambil.</strong></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
