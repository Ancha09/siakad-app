@extends('layouts.admin')

@section('title', 'Detail Mahasiswa '.$mapping->kode_sumber)
@section('page-subtitle', $cpl->kode_cpl.' - '.$mapping->nama_sumber)

@section('content')
<div style="margin-bottom:18px;"><a href="{{ $returnUrl }}" class="btn-outline">Kembali ke Detail CPL</a></div>

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head"><h2>{{ $mapping->kode_sumber }} - {{ $mapping->nama_sumber }}</h2></div>
    <div class="page-card-body">
        <p style="margin:0;color:#64748b;">{{ $program->jenjang }} {{ $program->nama_prodi }} · {{ $cpl->kode_cpl }} · Semester {{ $mapping->semester ?? '-' }} · {{ $mapping->sks ?? $mapping->mataKuliah?->sks ?? 0 }} SKS</p>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>Mahasiswa yang Dihitung</h2></div>
    <div class="page-card-body">
        <div class="table-wrap" style="overflow-x:auto;">
            <table>
                <thead><tr><th>No</th><th>NIM</th><th>Nama Mahasiswa</th><th>Angkatan</th><th>Program Studi</th><th>Nilai Angka</th><th>Nilai Huruf</th><th>Bobot / Indeks</th><th>SKS</th></tr></thead>
                <tbody>
                    @forelse($grades as $grade)
                        @php($student = $grade->krs?->mahasiswa)
                        <tr>
                            <td>{{ ($grades->firstItem() ?? 1) + $loop->index }}</td>
                            <td>{{ $student?->nim ?? '-' }}</td>
                            <td><strong>{{ $student?->nama ?? '-' }}</strong></td>
                            <td>{{ $student?->angkatan ?? '-' }}</td>
                            <td>{{ $student?->prodi?->jenjang }} {{ $student?->prodi?->nama_prodi ?? '-' }}</td>
                            <td>{{ $grade->nilai_angka ?? '-' }}</td>
                            <td>{{ $grade->nilai_huruf ?? '-' }}</td>
                            <td>{{ $grade->bobot === null ? '-' : number_format((float) $grade->bobot, 2) }}</td>
                            <td>{{ $grade->sks_efektif }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="text-align:center;padding:36px;color:#64748b;">Belum ada nilai mahasiswa untuk filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:18px;">
            {{ $grades->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
