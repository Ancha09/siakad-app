@extends('layouts.dosen')

@section('title', 'Detail Mahasiswa Wali')
@section('page-title', 'Detail Mahasiswa Wali')
@section('page-subtitle', 'Profil, transkrip, dan riwayat KRS mahasiswa perwalian')

@section('content')
<div class="inner-page">
    <div class="toolbar">
        <div>
            <h2 style="margin:0;">{{ $mahasiswa->nama }}</h2>
            <p style="margin:5px 0 0;color:#64748b;">{{ $mahasiswa->nim }} · {{ $mahasiswa->prodi?->nama_prodi ?? '-' }}</p>
        </div>
        <a href="{{ $listUrl }}" class="btn-outline"><span class="icon-inline"><x-layout-icon name="arrow-left" /> Kembali</span></a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:20px;">
        <div class="page-card"><div class="page-card-body"><small style="color:#64748b;">Nama / NIM</small><div style="font-weight:700;margin-top:5px;">{{ $mahasiswa->nama }}</div><div style="color:#64748b;">{{ $mahasiswa->nim }}</div></div></div>
        <div class="page-card"><div class="page-card-body"><small style="color:#64748b;">Program Studi</small><div style="font-weight:700;margin-top:5px;">{{ $mahasiswa->prodi?->nama_prodi ?? '-' }}</div></div></div>
        <div class="page-card"><div class="page-card-body"><small style="color:#64748b;">Angkatan / Semester</small><div style="font-weight:700;margin-top:5px;">{{ $mahasiswa->angkatan ?? '-' }} / {{ $mahasiswa->semester ? 'Semester '.$mahasiswa->semester : '-' }}</div></div></div>
        <div class="page-card"><div class="page-card-body"><small style="color:#64748b;">Dosen Wali</small><div style="font-weight:700;margin-top:5px;">{{ $mahasiswa->dosenWali?->nama ?? '-' }}</div></div></div>
    </div>

    <div class="khs-summary" style="margin-bottom:20px;">
        <div class="khs-stat"><div class="khs-stat-val">{{ $totalSks }}</div><div class="khs-stat-label">Total SKS Dinilai</div></div>
        <div class="khs-stat"><div class="khs-stat-val">{{ $ipk !== null ? number_format($ipk, 2) : '-' }}</div><div class="khs-stat-label">IPK Kumulatif</div></div>
        <div class="khs-stat"><div class="khs-stat-val">{{ $khsPerSemester->count() }}</div><div class="khs-stat-label">Semester Bernilai</div></div>
    </div>

    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head"><h2 style="margin:0;">Transkrip Nilai</h2></div>
        <div class="page-card-body">
            @forelse($khsPerSemester as $semesterAngka => $nilaiSemester)
                <section style="margin-bottom:28px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                        <h3 style="margin:0;color:#0f172a;">{{ $semesterAngka > 0 ? 'Semester '.$semesterAngka : 'Semester Belum Ditentukan' }}</h3>
                        <span class="badge badge-blue">IPS {{ number_format($ipsPerSemester[$semesterAngka], 2) }}</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Nilai Angka</th><th>Huruf</th><th>Bobot</th><th>Mutu</th></tr></thead>
                            <tbody>
                                @foreach($nilaiSemester as $nilai)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $nilai->krs?->mata_kuliah_efektif?->kode_mk ?? '-' }}</td>
                                        <td><strong>{{ $nilai->krs?->mata_kuliah_efektif?->nama_mk ?? '-' }}</strong></td>
                                        <td>{{ $nilai->sks_efektif }}</td>
                                        <td>{{ $nilai->nilai_angka ?? '-' }}</td>
                                        <td>{{ $nilai->nilai_huruf ?? '-' }}</td>
                                        <td>{{ $nilai->bobot !== null ? number_format((float) $nilai->bobot, 2) : '-' }}</td>
                                        <td>{{ $nilai->bobot !== null ? number_format((float) $nilai->bobot * $nilai->sks_efektif, 2) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @empty
                <div style="text-align:center;padding:40px;color:#64748b;">Belum ada nilai yang dapat ditampilkan.</div>
            @endforelse
        </div>
    </div>

    <div class="page-card">
        <div class="page-card-head"><h2 style="margin:0;">Riwayat KRS</h2></div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>No</th><th>Tahun Akademik</th><th>Semester</th><th>Jumlah MK</th><th>Total SKS</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse($krsPerPeriode as $periode)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $periode['tahun_akademik'] }}</strong></td>
                                <td>{{ $periode['semester_akademik'] }}</td>
                                <td>{{ $periode['jumlah_mata_kuliah'] }} mata kuliah</td>
                                <td>{{ $periode['total_sks'] }} SKS</td>
                                <td><span class="badge badge-blue">{{ $periode['status'] }}</span></td>
                                <td>
                                    <a class="btn-primary" style="padding:7px 11px;font-size:12px;white-space:nowrap;" href="{{ route('dosen.krs.show', [
                                        'mahasiswa' => $mahasiswa,
                                        'tahun_akademik' => $periode['tahun_akademik'],
                                        'semester_akademik' => $periode['semester_akademik'],
                                        'return_url' => request()->fullUrl(),
                                    ]) }}">Lihat KRS</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748b;">Belum ada riwayat KRS.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
