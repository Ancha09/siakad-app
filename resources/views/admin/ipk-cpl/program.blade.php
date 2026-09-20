@extends('layouts.admin')

@section('title', 'CPL Teknik Pertambangan')
@section('page-subtitle', 'Perhitungan IPK CPL berbobot SKS dari nilai resmi mahasiswa')

@push('styles')
<style>
    .ipk-cpl-table thead tr { background:linear-gradient(135deg,var(--navy) 0%,#0b5b9d 100%); }
    .ipk-cpl-table thead th { color:#fff;border-bottom-color:var(--navy); }
    .ipk-cpl-table tbody tr:nth-child(even) td { background:#f4f8fc; }
    .ipk-cpl-table tbody tr:hover td { background:#e3eff9; }
</style>
@endpush

@section('content')
<div style="margin-bottom:18px;"><a href="{{ route('admin.ipk-cpl.index') }}" class="btn-outline">Kembali ke Pilihan Program Studi</a></div>

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head"><h2>Filter CPL Teknik Pertambangan</h2></div>
    <div class="page-card-body">
        <form method="GET" action="{{ route('admin.ipk-cpl.program', $program) }}">
            <div class="krs-form-grid">
                <div class="form-group">
                    <label>Program Studi</label>
                    <select name="program_studi_id" class="form-control">
                        <option value="{{ $program->id }}" selected>{{ $program->jenjang }} {{ $program->nama_prodi }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tahun_akademik">Tahun Akademik</label>
                    <select id="tahun_akademik" name="tahun_akademik" class="form-control">
                        <option value="">Semua tahun akademik</option>
                        @foreach($tahunAkademiks as $tahun)
                            <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="angkatan">Angkatan</label>
                    <select id="angkatan" name="angkatan" class="form-control">
                        <option value="">Semua angkatan</option>
                        @foreach($angkatans as $angkatan)
                            <option value="{{ $angkatan }}" @selected((string) request('angkatan') === (string) $angkatan)>{{ $angkatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="tahun_studi">Tahun Studi</label>
                    <select id="tahun_studi" name="tahun_studi" class="form-control">
                        <option value="">Semua tahun</option>
                        @foreach([1 => 'Semester 1 dan 2', 2 => 'Semester 3 dan 4', 3 => 'Semester 5 dan 6', 4 => 'Semester 7 dan 8'] as $tahun => $semester)
                            <option value="{{ $tahun }}" @selected((string) request('tahun_studi') === (string) $tahun)>Tahun {{ $tahun }} - {{ $semester }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="cpl_id">CPL</label>
                    <select id="cpl_id" name="cpl_id" class="form-control">
                        <option value="">Semua CPL</option>
                        @foreach($cplOptions as $cplOption)
                            <option value="{{ $cplOption->id }}" @selected((string) request('cpl_id') === (string) $cplOption->id)>{{ $cplOption->kode_cpl }} - {{ $cplOption->nama_cpl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="mata_kuliah_id">Mata Kuliah</label>
                    <select id="mata_kuliah_id" name="mata_kuliah_id" class="form-control">
                        <option value="">Semua mata kuliah</option>
                        @foreach($courseOptions as $courseOption)
                            <option value="{{ $courseOption->mata_kuliah_id }}" @selected((string) request('mata_kuliah_id') === (string) $courseOption->mata_kuliah_id)>{{ $courseOption->kode_sumber }} - {{ $courseOption->nama_sumber }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
                <button class="btn-primary" type="submit">Terapkan Filter</button>
                <a href="{{ route('admin.ipk-cpl.program', $program) }}" class="btn-outline">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
    @foreach(['Jumlah CPL' => $summary['jumlah_cpl'], 'Mapping Mata Kuliah' => $summary['jumlah_mapping'], 'CPL Memiliki Nilai' => $summary['cpl_bernilai'], 'Belum Terhubung Master' => $summary['belum_terhubung']] as $label => $value)
        <div style="padding:17px;border:1px solid #dbe6f1;background:#f4f8fc;border-radius:10px;"><small style="color:#64748b;">{{ $label }}</small><div style="font-size:26px;font-weight:700;color:#0b5b9d;">{{ $value }}</div></div>
    @endforeach
</div>

<div class="page-card">
    <div class="page-card-head"><h2>Daftar CPL Teknik Pertambangan</h2></div>
    <div class="page-card-body">
        <div class="table-wrap" style="overflow-x:auto;">
            <table class="ipk-cpl-table">
                <thead><tr><th>No</th><th>Kode CPL</th><th>Nama CPL</th><th>Mata Kuliah</th><th>Total SKS</th><th>IPK CPL</th><th>Status Kelengkapan</th><th style="width:110px;">Aksi</th></tr></thead>
                <tbody>
                    @forelse($rows as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $item->kode_cpl }}</strong></td>
                            <td style="min-width:260px;">{{ $item->nama_cpl }}</td>
                            <td>{{ $item->jumlah_mata_kuliah }}</td>
                            <td>{{ $item->total_sks }}</td>
                            <td><strong>{{ $item->ipk_cpl === null ? '-' : number_format($item->ipk_cpl, 2) }}</strong></td>
                            <td>{{ $item->status }}</td>
                            <td><a class="btn-primary" style="display:inline-block;padding:7px 11px;white-space:nowrap;" href="{{ route('admin.ipk-cpl.cpl', [
                                'prodi' => $program,
                                'cpl' => $item->cpl,
                                'tahun_akademik' => request('tahun_akademik'),
                                'angkatan' => request('angkatan'),
                                'tahun_studi' => request('tahun_studi'),
                                'mata_kuliah_id' => request('mata_kuliah_id'),
                                'return_url' => request()->fullUrl(),
                            ]) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center;padding:36px;color:#64748b;">Belum ada data untuk filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
