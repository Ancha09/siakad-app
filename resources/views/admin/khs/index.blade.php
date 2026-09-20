@extends('layouts.admin')

@section('title', 'KHS Admin')
@section('page-subtitle', 'Rekap hasil studi mahasiswa per semester akademik')

@section('content')
<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head">
        <div>
            <h2 class="icon-heading"><x-layout-icon name="file-chart" /> Filter KHS Mahasiswa</h2>
            <p style="margin:5px 0 0;color:#64748b;font-size:13px;">Setiap baris mewakili satu mahasiswa pada satu semester akademik.</p>
        </div>
    </div>
    <div class="page-card-body">
        <form method="GET" action="{{ route('admin.khs') }}">
            <div class="krs-form-grid">
                <div class="form-group">
                    <label for="search">Nama atau NIM</label>
                    <input id="search" type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama atau NIM" list="khs-student-suggestions" autocomplete="off">
                    <datalist id="khs-student-suggestions">
                        @foreach($studentSuggestions as $student)
                            <option value="{{ $student->nama }}">{{ $student->nim }}</option>
                            <option value="{{ $student->nim }}">{{ $student->nama }}</option>
                        @endforeach
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="tahun_akademik">Tahun Akademik</label>
                    <select id="tahun_akademik" name="tahun_akademik" class="form-control">
                        <option value="">Semua tahun</option>
                        @foreach($tahunAkademiks as $tahun)
                            <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester_akademik">Semester Akademik</label>
                    <select id="semester_akademik" name="semester_akademik" class="form-control">
                        <option value="">Semua semester</option>
                        @foreach(['Ganjil', 'Genap'] as $semester)
                            <option value="{{ $semester }}" @selected(request('semester_akademik') === $semester)>{{ $semester }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester_angka">Semester Angka</label>
                    <select id="semester_angka" name="semester_angka" class="form-control">
                        <option value="">Semua semester angka</option>
                        @foreach($semesterAngkas as $semester)
                            <option value="{{ $semester }}" @selected((string) request('semester_angka') === (string) $semester)>Semester {{ $semester }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="prodi_id">Program Studi</label>
                    <select id="prodi_id" name="prodi_id" class="form-control">
                        <option value="">Semua program studi</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}" @selected((string) request('prodi_id') === (string) $prodi->id)>{{ $prodi->jenjang }} {{ $prodi->nama_prodi }}</option>
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
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
                <button type="submit" class="btn-primary icon-button"><x-layout-icon name="search" /> Terapkan Filter</button>
                <a href="{{ route('admin.khs') }}" class="btn-outline">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>KHS per Mahasiswa dan Semester</h2></div>
    <div class="page-card-body">
        <div class="table-wrap" style="overflow-x:auto;">
            <table>
                <thead><tr><th>No</th><th>Mahasiswa</th><th>Program Studi</th><th>Angkatan</th><th>Periode</th><th>Semester Angka</th><th>Mata Kuliah</th><th>SKS</th><th>IPS</th><th style="width:120px;">Aksi</th></tr></thead>
                <tbody>
                    @forelse($summaries as $item)
                        <tr>
                            <td>{{ $summaries->firstItem() + $loop->index }}</td>
                            <td><strong>{{ $item->mahasiswa->nama }}</strong><br><small style="color:#64748b;">{{ $item->mahasiswa->nim }}</small></td>
                            <td>{{ $item->mahasiswa->prodi?->jenjang }} {{ $item->mahasiswa->prodi?->nama_prodi ?? '-' }}</td>
                            <td>{{ $item->mahasiswa->angkatan ?? $item->mahasiswa->kelas?->angkatan ?? '-' }}</td>
                            <td><strong>{{ $item->tahun_akademik }}</strong><br><small>{{ $item->semester_akademik }}</small></td>
                            <td>{{ $item->semester_angka === '-' ? '-' : 'Semester '.$item->semester_angka }}</td>
                            <td>{{ $item->jumlah_mata_kuliah }}</td>
                            <td>{{ $item->total_sks }}</td>
                            <td><strong>{{ number_format($item->ips, 2) }}</strong></td>
                            <td>
                                <a class="btn-primary" style="display:inline-block;padding:7px 11px;white-space:nowrap;" href="{{ route('admin.khs.show', [
                                    'mahasiswa' => $item->mahasiswa_id,
                                    'tahun_akademik' => $item->tahun_akademik,
                                    'semester_akademik' => $item->semester_akademik,
                                    'return_url' => request()->fullUrl(),
                                ]) }}">Detail KHS</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" style="text-align:center;padding:36px;color:#64748b;">Belum ada data untuk filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($summaries->hasPages())
            <div style="margin-top:20px;">{{ $summaries->appends(request()->query())->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>
@endsection
