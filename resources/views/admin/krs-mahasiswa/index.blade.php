@extends('layouts.admin')

@section('title', 'KRS Admin')
@section('page-subtitle', 'Pemeriksaan dan kartu KRS mahasiswa')

@section('content')
    @if(session('success'))
        <div class="alert-success" style="margin-bottom:18px;">{{ session('success') }}</div>
    @endif

    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head">
            <h2>Filter KRS Mahasiswa</h2>
        </div>
        <div class="page-card-body">
            <form method="GET" action="{{ route('admin.krs-mahasiswa.index') }}"
                  style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label for="search">Nama atau NIM</label>
                    <input id="search" class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Ketik huruf awal nama atau NIM" list="krs-student-suggestions" autocomplete="off">
                    <datalist id="krs-student-suggestions">
                        @unless(request()->filled('search'))
                            @foreach($studentSuggestions as $studentSuggestion)
                                <option value="{{ $studentSuggestion->nama }}">{{ $studentSuggestion->nim }}</option>
                                <option value="{{ $studentSuggestion->nim }}">{{ $studentSuggestion->nama }}</option>
                            @endforeach
                        @endunless
                    </datalist>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="angkatan">Angkatan</label>
                    <select id="angkatan" class="form-control" name="angkatan">
                        <option value="">Semua angkatan</option>
                        @foreach($angkatans as $angkatan)
                            <option value="{{ $angkatan }}" @selected((string) request('angkatan') === (string) $angkatan)>{{ $angkatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="prodi_id">Program Studi</label>
                    <select id="prodi_id" class="form-control" name="prodi_id">
                        <option value="">Semua program studi</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}" @selected((string) request('prodi_id') === (string) $prodi->id)>{{ $prodi->jenjang }} {{ $prodi->nama_prodi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="kelas_id">Kelas</label>
                    <select id="kelas_id" class="form-control" name="kelas_id">
                        <option value="">Semua kelas</option>
                        @foreach($kelases as $kelas)
                            <option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="semester">Semester Studi</label>
                    <select id="semester" class="form-control" name="semester">
                        <option value="">Semua semester</option>
                        @foreach(range(1, 14) as $semester)
                            <option value="{{ $semester }}" @selected((string) request('semester') === (string) $semester)>Semester {{ $semester }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="semester_akademik">Semester Akademik</label>
                    <select id="semester_akademik" class="form-control" name="semester_akademik">
                        <option value="">Semua</option>
                        <option value="Ganjil" @selected(request('semester_akademik') === 'Ganjil')>Ganjil</option>
                        <option value="Genap" @selected(request('semester_akademik') === 'Genap')>Genap</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="tahun_akademik">Tahun Akademik</label>
                    <select id="tahun_akademik" class="form-control" name="tahun_akademik">
                        <option value="">Semua tahun</option>
                        @foreach($tahunAkademiks as $tahun)
                            <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="status">Status KRS</label>
                    <select id="status" class="form-control" name="status">
                        <option value="">Semua status</option>
                        @foreach(['Diambil', 'Menunggu', 'Disetujui', 'Ditolak'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button type="submit" class="btn-primary">Terapkan Filter</button>
                    <a href="{{ route('admin.krs-mahasiswa.index') }}" class="btn-outline">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="page-card">
        <div class="page-card-head" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <h2>KRS per Mahasiswa dan Periode</h2>
            <a href="{{ route('admin.krs') }}" class="btn-outline">Kelola Baris KRS</a>
        </div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Mahasiswa</th>
                        <th>Program Studi / Kelas</th>
                        <th>Periode</th>
                        <th>Semester</th>
                        <th>Mata Kuliah</th>
                        <th>Total SKS</th>
                        <th>Status Persetujuan</th>
                        <th style="width:110px;">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($summaries as $item)
                        <tr>
                            <td>{{ $summaries->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->mahasiswa?->nama ?? '-' }}</strong><br>
                                <small style="color:#64748b;">{{ $item->mahasiswa?->nim ?? '-' }} · Angkatan {{ $item->mahasiswa?->angkatan ?? $item->mahasiswa?->kelas?->angkatan ?? '-' }}</small>
                            </td>
                            <td>
                                {{ $item->mahasiswa?->prodi?->jenjang }} {{ $item->mahasiswa?->prodi?->nama_prodi ?? '-' }}<br>
                                <small style="color:#64748b;">{{ $item->mahasiswa?->kelas?->nama_kelas ?? 'Belum ada kelas' }}</small>
                            </td>
                            <td><strong>{{ $item->tahun_akademik }}</strong><br><small>{{ $item->semester_akademik }}</small></td>
                            <td>{{ $item->semester_studi ? 'Semester '.$item->semester_studi : '-' }}</td>
                            <td>{{ $item->jumlah_mata_kuliah }} mata kuliah</td>
                            <td>{{ $item->total_sks }} SKS</td>
                            <td><strong>{{ $item->status_persetujuan }}</strong></td>
                            <td>
                                <a class="btn-primary" style="display:inline-block;padding:7px 11px;white-space:nowrap;"
                                   href="{{ route('admin.krs-mahasiswa.show', [
                                       'mahasiswa' => $item->mahasiswa_id,
                                       'tahun_akademik' => $item->tahun_akademik,
                                       'semester_akademik' => $item->semester_akademik,
                                       'return_url' => request()->fullUrl(),
                                   ]) }}">Buka</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="text-align:center;padding:36px;color:#64748b;">Belum ada data untuk filter yang dipilih.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px;">{{ $summaries->appends(request()->query())->onEachSide(1)->links() }}</div>
        </div>
    </div>
@endsection
