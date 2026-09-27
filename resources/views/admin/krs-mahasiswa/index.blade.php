@extends('layouts.admin')

@section('title', 'KRS Admin')
@section('page-subtitle', 'Pemeriksaan dan kartu KRS mahasiswa')

@section('content')
    @if(session('success'))
        <div class="alert-success" style="margin-bottom:18px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2;color:#991b1b;padding:14px 16px;border-radius:9px;margin-bottom:18px;">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div style="background:#fee2e2;color:#991b1b;padding:14px 16px;border-radius:9px;margin-bottom:18px;">
            <strong>Reset belum dapat diproses.</strong>
            <ul style="margin:7px 0 0 18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head">
            <h2>Filter KRS Mahasiswa</h2>
        </div>
        <div class="page-card-body">
            <form method="GET" action="{{ route('admin.krs-mahasiswa.index') }}"
                  style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label for="periode_krs_id">Periode KRS</label>
                    <select id="periode_krs_id" class="form-control" name="periode_krs_id">
                        <option value="">Semua periode</option>
                        @foreach($periodes as $periodeOption)
                            <option value="{{ $periodeOption->id }}" @selected((string) request('periode_krs_id') === (string) $periodeOption->id)>{{ $periodeOption->tahun_akademik }} - {{ $periodeOption->semester }}</option>
                        @endforeach
                    </select>
                </div>
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
                        @foreach(['Draft', 'Diambil', 'Menunggu', 'Disetujui', 'Ditolak'] as $status)
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
            @if($selectedPeriod)
                <div style="background:#f8fafc;border:1px solid #dbe4f0;padding:16px;border-radius:10px;margin-bottom:18px;">
                    <h3 style="margin:0 0 9px;">Reset Persetujuan KRS: {{ $selectedPeriod->tahun_akademik }} {{ $selectedPeriod->semester }}</h3>
                    <p style="margin:0 0 12px;color:#475569;">Pilih mahasiswa pada halaman ini, atau reset semua mahasiswa pada periode ini. Alasan wajib diisi. Data KRS tetap tersimpan.</p>
                    <form id="bulk-reset-form" method="POST" action="{{ route('admin.periode-krs.reset-persetujuan', $selectedPeriod) }}" onsubmit="return confirm(event.submitter?.value === 'all' ? 'Aksi ini akan mengembalikan status persetujuan KRS SEMUA mahasiswa pada periode ini menjadi menunggu persetujuan. Data KRS tidak akan dihapus.' : 'Aksi ini akan mengembalikan status persetujuan KRS mahasiswa terpilih menjadi menunggu persetujuan. Data KRS tidak akan dihapus.')">
                        @csrf
                        <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                        <label for="bulk-reset-reason" style="display:block;font-weight:700;margin-bottom:5px;">Alasan reset <span style="color:#b91c1c;">*</span></label>
                        <textarea id="bulk-reset-reason" name="alasan" class="form-control" rows="2" minlength="5" maxlength="1000" required placeholder="Jelaskan alasan koreksi persetujuan KRS" style="width:100%;max-width:760px;">{{ old('alasan') }}</textarea>
                        <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:10px;">
                            <button type="submit" name="mode" value="selected" class="btn-primary">Reset Mahasiswa Terpilih</button>
                            <button type="submit" name="mode" value="all" class="btn-outline">Reset Semua pada Periode Ini</button>
                        </div>
                    </form>
                </div>
            @else
                <p style="margin:0 0 16px;color:#64748b;">Pilih Periode KRS pada filter untuk menggunakan fitur reset persetujuan.</p>
            @endif
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        @if($selectedPeriod)<th>Pilih</th>@endif
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
                            @if($selectedPeriod)
                                <td><input type="checkbox" name="mahasiswa_ids[]" value="{{ $item->mahasiswa_id }}" form="bulk-reset-form" aria-label="Pilih {{ $item->mahasiswa?->nama }}" @disabled($item->perlu_revisi || $item->sudah_bernilai)></td>
                            @endif
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
                            <td><strong>{{ $item->status_persetujuan }}</strong>@if($item->perlu_revisi)<br><small style="color:#9a3412;">Perlu diajukan ulang</small>@elseif($item->sudah_bernilai)<br><small style="color:#64748b;">Sudah ada KHS; reset dinonaktifkan</small>@endif</td>
                            <td>
                                <a class="btn-primary" style="display:inline-block;padding:7px 11px;white-space:nowrap;"
                                   href="{{ route('admin.krs-mahasiswa.show', [
                                       'mahasiswa' => $item->mahasiswa_id,
                                       'tahun_akademik' => $item->tahun_akademik,
                                       'semester_akademik' => $item->semester_akademik,
                                       'return_url' => request()->fullUrl(),
                                   ]) }}">Buka</a>
                                @if($selectedPeriod && ! $item->perlu_revisi && ! $item->sudah_bernilai && $item->periodRecords->contains(fn ($record) => $record->status !== 'Draft'))
                                    <details style="margin-top:8px;min-width:180px;">
                                        <summary style="cursor:pointer;color:#b91c1c;font-weight:700;">Reset satu mahasiswa</summary>
                                        <form method="POST" action="{{ route('admin.periode-krs.reset-persetujuan', $selectedPeriod) }}" onsubmit="return confirm('Aksi ini akan mengembalikan status persetujuan KRS mahasiswa terpilih menjadi menunggu persetujuan. Data KRS tidak akan dihapus.')" style="margin-top:8px;">
                                            @csrf
                                            <input type="hidden" name="mode" value="single">
                                            <input type="hidden" name="mahasiswa_id" value="{{ $item->mahasiswa_id }}">
                                            <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                            <textarea name="alasan" class="form-control" rows="3" minlength="5" maxlength="1000" required placeholder="Alasan reset" aria-label="Alasan reset {{ $item->mahasiswa?->nama }}"></textarea>
                                            <button type="submit" class="btn-outline" style="margin-top:7px;">Konfirmasi Reset</button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $selectedPeriod ? 10 : 9 }}" style="text-align:center;padding:36px;color:#64748b;">Belum ada data untuk filter yang dipilih.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px;">{{ $summaries->appends(request()->query())->onEachSide(1)->links() }}</div>
        </div>
    </div>

    <div class="page-card" style="margin-top:20px;">
        <div class="page-card-head"><h2>Riwayat Reset Persetujuan KRS</h2></div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Waktu</th><th>Admin</th><th>Mahasiswa</th><th>Aksi</th><th>Alasan</th></tr></thead>
                    <tbody>
                        @forelse($resetHistory as $reset)
                            <tr>
                                <td>{{ $reset->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $reset->admin?->name ?? '-' }}</td>
                                <td>{{ $reset->mahasiswa?->nama ?? '-' }} ({{ $reset->mahasiswa?->nim ?? '-' }})</td>
                                <td>{{ ucfirst($reset->aksi) }}</td>
                                <td>{{ $reset->alasan }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;padding:20px;color:#64748b;">Belum ada riwayat reset.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
