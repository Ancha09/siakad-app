@extends('layouts.admin')

@section('title', 'Detail '.$row->kode_cpl)
@section('page-subtitle', $row->nama_cpl)

@push('styles')
<style>
    .ipk-cpl-table thead tr { background:linear-gradient(135deg,var(--navy) 0%,#0b5b9d 100%); }
    .ipk-cpl-table thead th { color:#fff;border-bottom-color:var(--navy); }
    .ipk-cpl-table tbody tr:nth-child(even) td { background:#f4f8fc; }
    .ipk-cpl-table tbody tr:hover td { background:#e3eff9; }
</style>
@endpush

@section('content')
<div style="margin-bottom:18px;"><a href="{{ $returnUrl }}" class="btn-outline">Kembali ke Daftar CPL</a></div>

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head"><h2>{{ $row->kode_cpl }} - {{ $row->nama_cpl }}</h2></div>
    <div class="page-card-body">
        <div style="padding:16px;border:1px solid #dbe6f1;background:#f8fafc;border-radius:10px;margin-bottom:16px;line-height:1.65;">
            <div style="margin-bottom:10px;"><strong style="color:#0b5b9d;">Deskripsi CPL</strong><br>{{ $row->cpl->deskripsi ?: '-' }}</div>
            <div style="margin-bottom:10px;"><strong style="color:#0b5b9d;">Turunan Visi-Misi</strong><br>{!! nl2br(e($row->cpl->turunan_visi_misi ?: '-')) !!}</div>
            <div><strong style="color:#0b5b9d;">CPL KKNI</strong><br>{{ $row->cpl->cpl_kkni ?: '-' }}</div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;">
            @foreach(['Jumlah Mata Kuliah' => $row->jumlah_mata_kuliah, 'Total SKS Mapping' => $row->total_sks, 'SKS Dihitung' => $row->sks_dihitung, 'Kelengkapan Data' => number_format($row->kelengkapan_persen, 2).'%', 'IPK CPL' => $row->ipk_cpl === null ? '-' : number_format($row->ipk_cpl, 2)] as $label => $value)
                <div style="padding:16px;border:1px solid #dbe6f1;background:#f4f8fc;border-radius:10px;"><small style="color:#64748b;">{{ $label }}</small><div style="font-size:24px;font-weight:700;color:#0b5b9d;">{{ $value }}</div></div>
            @endforeach
        </div>
        <p style="margin:15px 0 0;color:#64748b;">Status: <strong>{{ $row->status }}</strong></p>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>Mapping Mata Kuliah</h2></div>
    <div class="page-card-body">
        <div class="table-wrap" style="overflow-x:auto;">
            <table class="ipk-cpl-table">
                <thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>Semester</th><th>SKS</th><th>Mahasiswa</th><th>Rata-rata Nilai Mutu</th><th>Mutu x SKS</th><th>Status</th><th style="width:145px;">Aksi</th></tr></thead>
                <tbody>
                    @forelse($row->courses as $course)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $course->kode_mata_kuliah }}</strong></td>
                            <td>{{ $course->nama_mata_kuliah }}</td>
                            <td>{{ $course->semester ?? '-' }}</td>
                            <td>{{ $course->sks }}</td>
                            <td>{{ $course->jumlah_mahasiswa }}</td>
                            <td>{{ $course->rata_bobot === null ? '-' : number_format($course->rata_bobot, 2) }}</td>
                            <td>{{ $course->mutu_sks === null ? '-' : number_format($course->mutu_sks, 2) }}</td>
                            <td>{{ $course->status }}</td>
                            <td>
                                @if($course->terhubung)
                                    <a class="btn-primary" style="display:inline-block;padding:7px 11px;white-space:nowrap;" href="{{ route('admin.ipk-cpl.course', [
                                        'prodi' => $program,
                                        'cpl' => $row->cpl,
                                        'mapping' => $course->mapping,
                                        'tahun_akademik' => $filters['tahun_akademik'] ?? null,
                                        'angkatan' => $filters['angkatan'] ?? null,
                                        'tahun_studi' => $filters['tahun_studi'] ?? null,
                                        'return_url' => request()->fullUrl(),
                                    ]) }}">Detail Mahasiswa</a>
                                @else
                                    <span style="color:#64748b;white-space:nowrap;">Belum terhubung</span>
                                @endif
                            </td>
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
