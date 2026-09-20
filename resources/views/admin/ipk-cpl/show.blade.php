@extends('layouts.admin')

@section('title', 'Detail Mahasiswa IPK CPL')
@section('page-subtitle', $course->kode_mk.' - '.$course->nama_mk)

@section('content')
<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    <a href="{{ $returnUrl }}" class="btn-outline">Kembali ke IPK CPL</a>
</div>

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head"><h2>Ringkasan Mata Kuliah</h2></div>
    <div class="page-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:13px;">
            @foreach([
                'Kode Mata Kuliah' => $course->kode_mk,
                'Nama Mata Kuliah' => $course->nama_mk,
                'Tahun Akademik' => $filters['tahun_akademik'],
                'Semester Akademik' => $filters['semester_akademik'],
                'Semester Angka' => $summary?->semester_angka ? 'Semester '.$summary->semester_angka : '-',
                'Program Studi' => $summary?->prodi ?? '-',
                'Jumlah Mahasiswa' => $summary?->jumlah_mahasiswa ?? 0,
                'Rata-rata Nilai' => $summary?->rata_nilai === null ? '-' : number_format($summary->rata_nilai, 2),
                'Rata-rata Bobot' => $summary?->rata_bobot === null ? '-' : number_format($summary->rata_bobot, 2),
            ] as $label => $value)
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                    <small style="color:#64748b;">{{ $label }}</small><div style="font-weight:700;margin-top:4px;">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>Mahasiswa yang Dihitung</h2></div>
    <div class="page-card-body">
        <div class="table-wrap" style="overflow-x:auto;">
            <table>
                <thead><tr><th>No</th><th>NIM</th><th>Mahasiswa</th><th>Program Studi</th><th>Angkatan</th><th>Nilai Angka</th><th>Nilai Huruf</th><th>Bobot / Indeks</th><th>SKS</th><th>Mutu</th></tr></thead>
                <tbody>
                    @forelse($grades as $grade)
                        @php
                            $student = $grade->krs?->mahasiswa;
                            $mutu = is_numeric($grade->bobot) && $grade->sks_efektif > 0 ? (float) $grade->bobot * $grade->sks_efektif : null;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $student?->nim ?? '-' }}</td>
                            <td><strong>{{ $student?->nama ?? '-' }}</strong></td>
                            <td>{{ $student?->prodi?->jenjang }} {{ $student?->prodi?->nama_prodi ?? '-' }}</td>
                            <td>{{ $student?->angkatan ?? $student?->kelas?->angkatan ?? '-' }}</td>
                            <td>{{ $grade->nilai_angka ?? '-' }}</td>
                            <td><strong>{{ $grade->nilai_huruf ?? '-' }}</strong></td>
                            <td>{{ $grade->bobot === null ? '-' : number_format((float) $grade->bobot, 2) }}</td>
                            <td>{{ $grade->sks_efektif }}</td>
                            <td>{{ $mutu === null ? '-' : number_format($mutu, 2) }}</td>
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
