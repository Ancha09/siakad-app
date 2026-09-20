@extends('layouts.admin')

@section('title', 'Detail KHS Mahasiswa')
@section('page-subtitle', $mahasiswa->nama.' - '.$tahunAkademik.' '.$semesterAkademik)

@section('content')
<div style="margin-bottom:18px;"><a href="{{ $returnUrl }}" class="btn-outline">Kembali ke KHS Admin</a></div>

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head"><h2>Identitas dan Semester</h2></div>
    <div class="page-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:13px;">
            @foreach([
                'Nama Mahasiswa' => $mahasiswa->nama,
                'NIM' => $mahasiswa->nim,
                'Program Studi' => trim(($mahasiswa->prodi?->jenjang ?? '').' '.($mahasiswa->prodi?->nama_prodi ?? '-')),
                'Kelas' => $mahasiswa->kelas?->nama_kelas ?? '-',
                'Angkatan' => $mahasiswa->angkatan ?? $mahasiswa->kelas?->angkatan ?? '-',
                'Tahun Akademik' => $tahunAkademik,
                'Semester Akademik' => $semesterAkademik,
            ] as $label => $value)
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                    <small style="color:#64748b;">{{ $label }}</small><div style="font-weight:700;margin-top:4px;">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
    <div style="padding:17px;border:1px solid #dbeafe;background:#eff6ff;border-radius:10px;"><small style="color:#64748b;">Total SKS Semester</small><div style="font-size:26px;font-weight:700;color:#1d4ed8;">{{ $totalSks }}</div></div>
    <div style="padding:17px;border:1px solid #dcfce7;background:#f0fdf4;border-radius:10px;"><small style="color:#64748b;">IPS Semester</small><div style="font-size:26px;font-weight:700;color:#15803d;">{{ number_format($ips, 2) }}</div></div>
    <div style="padding:17px;border:1px solid #ede9fe;background:#f5f3ff;border-radius:10px;"><small style="color:#64748b;">IPK Kumulatif s.d. Semester Ini</small><div style="font-size:26px;font-weight:700;color:#6d28d9;">{{ number_format($ipk, 2) }}</div></div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>Daftar Nilai Semester</h2></div>
    <div class="page-card-body">
        <div class="table-wrap" style="overflow-x:auto;">
            <table>
                <thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>Semester</th><th>SKS</th><th>Nilai Angka</th><th>Nilai Huruf</th><th>Bobot / Indeks</th><th>Mutu</th><th>Dosen</th></tr></thead>
                <tbody>
                    @forelse($grades as $grade)
                        @php
                            $course = $grade->krs?->mata_kuliah_efektif;
                            $mutu = is_numeric($grade->bobot) && $grade->sks_efektif > 0 ? (float) $grade->bobot * $grade->sks_efektif : null;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $course?->kode_mk ?? '-' }}</strong></td>
                            <td>{{ $course?->nama_mk ?? '-' }}</td>
                            <td>{{ $course?->semester ? 'Semester '.$course->semester : ($grade->krs?->semester ? 'Semester '.$grade->krs->semester : '-') }}</td>
                            <td>{{ $grade->sks_efektif }}</td>
                            <td>{{ $grade->nilai_angka ?? '-' }}</td>
                            <td><strong>{{ $grade->nilai_huruf ?? '-' }}</strong></td>
                            <td>{{ $grade->bobot === null ? '-' : number_format((float) $grade->bobot, 2) }}</td>
                            <td>{{ $mutu === null ? '-' : number_format($mutu, 2) }}</td>
                            <td>{{ $grade->dosen_efektif?->nama ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" style="text-align:center;padding:36px;color:#64748b;">Belum ada data untuk filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
