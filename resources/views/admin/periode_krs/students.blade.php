@extends('layouts.admin')

@section('title', 'Akses KRS Mahasiswa')

@section('content')

@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div style="background:#fee2e2;color:#991b1b;padding:14px 16px;border-radius:8px;margin-bottom:20px;">
        {{ $errors->first() }}
    </div>
@endif

<div class="toolbar">
    <div>
        <h2 style="margin:0;">Akses KRS per Mahasiswa</h2>
        <p style="margin:5px 0 0;color:#64748b;font-size:13px;">
            {{ $periodeKrs->tahun_akademik }} · {{ $periodeKrs->semester }} ·
            {{ $periodeKrs->tanggal_mulai->format('d/m/Y H:i') }}–{{ $periodeKrs->tanggal_selesai->format('d/m/Y H:i') }}
        </p>
        <p style="margin:5px 0 0;color:#334155;font-size:13px;font-weight:600;">
            Mode akses: {{ [
                'closed' => 'Ditutup untuk semua',
                'all' => 'Dibuka untuk semua',
                'selected' => 'Hanya mahasiswa tertentu',
                'all_except' => 'Semua kecuali mahasiswa tertentu',
            ][$periodeKrs->access_mode ?? 'selected'] }}
        </p>
    </div>
    <a href="{{ route('admin.periode-krs') }}" class="btn-outline">Kembali ke Periode KRS</a>
</div>

@if($periodeKrs->status !== 'Dibuka' || $periodeKrs->tanggal_mulai->isFuture() || $periodeKrs->tanggal_selesai->isPast())
    <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:14px 16px;border-radius:10px;margin-bottom:20px;">
        Periode global belum aktif saat ini. Izin mahasiswa dapat disiapkan, tetapi KRS baru dapat diisi ketika periode global berstatus Dibuka dan berada dalam rentang tanggal.
    </div>
@endif

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head"><h3 style="margin:0;">Filter Mahasiswa</h3></div>
    <div class="page-card-body">
        <form method="GET" action="{{ route('admin.periode-krs.students', $periodeKrs) }}">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:14px;align-items:end;">
                <div>
                    <label for="search">Nama / NIM</label>
                    <input id="search" class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Ketik huruf awal nama atau NIM" list="access-student-suggestions" autocomplete="off">
                    <datalist id="access-student-suggestions">
                        @unless(request()->filled('search'))
                            @foreach($studentSuggestions as $studentSuggestion)
                                <option value="{{ $studentSuggestion->nama }}">{{ $studentSuggestion->nim }}</option>
                                <option value="{{ $studentSuggestion->nim }}">{{ $studentSuggestion->nama }}</option>
                            @endforeach
                        @endunless
                    </datalist>
                </div>

                <div>
                    <label for="prodi_id">Program Studi</label>
                    <select id="prodi_id" class="form-control" name="prodi_id">
                        <option value="">Semua prodi</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}" @selected((string) request('prodi_id') === (string) $prodi->id)>{{ $prodi->nama_prodi }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="kelas_id">Kelas</label>
                    <select id="kelas_id" class="form-control" name="kelas_id">
                        <option value="">Semua kelas</option>
                        @foreach($kelases as $kelas)
                            <option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="angkatan">Angkatan</label>
                    <select id="angkatan" class="form-control" name="angkatan">
                        <option value="">Semua angkatan</option>
                        @foreach($angkatans as $angkatan)
                            <option value="{{ $angkatan }}" @selected((string) request('angkatan') === (string) $angkatan)>{{ $angkatan }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="semester">Semester Studi</label>
                    <select id="semester" class="form-control" name="semester">
                        <option value="">Semua semester</option>
                        @for($semester = 1; $semester <= 14; $semester++)
                            <option value="{{ $semester }}" @selected((string) request('semester') === (string) $semester)>Semester {{ $semester }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label for="status_akses">Status Akses</label>
                    <select id="status_akses" class="form-control" name="status_akses">
                        <option value="">Semua status</option>
                        <option value="belum_dibuka" @selected(request('status_akses') === 'belum_dibuka')>Belum Dibuka</option>
                        <option value="dibuka" @selected(request('status_akses') === 'dibuka')>Dibuka</option>
                        <option value="ditutup" @selected(request('status_akses') === 'ditutup')>Ditutup</option>
                    </select>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="submit" class="btn-primary">Terapkan</button>
                    <a href="{{ route('admin.periode-krs.students', $periodeKrs) }}" class="btn-outline">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head">
        <h3 style="margin:0;">Daftar Mahasiswa</h3>
    </div>
    <div class="page-card-body">
        <form
            id="bulk-access-form"
            method="POST"
            action="{{ route('admin.periode-krs.students.access-bulk', $periodeKrs) }}"
            style="display:flex;align-items:end;gap:10px;flex-wrap:wrap;margin-bottom:16px;"
            onsubmit="return confirm('Ubah akses KRS semua mahasiswa yang dipilih?')"
        >
            @csrf
            @method('PATCH')
            <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
            <div>
                <label for="bulk_status_akses">Aksi mahasiswa terpilih</label>
                <select id="bulk_status_akses" class="form-control" name="status_akses" required>
                    <option value="1">Buka KRS</option>
                    <option value="0">Tutup KRS</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Terapkan ke Pilihan</button>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:38px;">
                            <input
                                type="checkbox"
                                aria-label="Pilih semua mahasiswa di halaman ini"
                                onclick="document.querySelectorAll('.student-access-checkbox').forEach((item) => item.checked = this.checked)"
                            >
                        </th>
                        <th>No</th>
                        <th>Mahasiswa</th>
                        <th>Prodi / Kelas</th>
                        <th>Semester</th>
                        <th>Status Akses</th>
                        <th style="width:130px;">Aksi Akses</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mahasiswas as $mahasiswa)
                        @php
                            $akses = $mahasiswa->aksesPeriodeKrs->first();
                            $statusAkses = $periodeKrs->allowsMahasiswa($mahasiswa) ? 'dibuka' : 'ditutup';
                        @endphp
                        <tr>
                            <td>
                                <input
                                    class="student-access-checkbox"
                                    form="bulk-access-form"
                                    type="checkbox"
                                    name="mahasiswa_ids[]"
                                    value="{{ $mahasiswa->id }}"
                                    aria-label="Pilih {{ $mahasiswa->nama }}"
                                >
                            </td>
                            <td>{{ $mahasiswas->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $mahasiswa->nama }}</strong><br>
                                <small style="color:#64748b;">{{ $mahasiswa->nim }}</small>
                            </td>
                            <td>
                                {{ $mahasiswa->prodi?->nama_prodi ?? '-' }}<br>
                                <small style="color:#64748b;">{{ $mahasiswa->kelas?->nama_kelas ?? '-' }} · Angkatan {{ $mahasiswa->angkatan ?? $mahasiswa->kelas?->angkatan ?? '-' }}</small>
                            </td>
                            <td>{{ $mahasiswa->semester ?? $mahasiswa->kelas?->semester ?? '-' }}</td>
                            <td>
                                @if($statusAkses === 'dibuka')
                                    <span class="badge badge-green">Dibuka</span>
                                    @if($akses?->tanggal_dibuka)
                                        <br><small style="color:#64748b;">{{ $akses->tanggal_dibuka->format('d/m/Y H:i') }}</small>
                                    @endif
                                @else
                                    <span class="badge badge-gray">Ditutup</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.periode-krs.students.access', [$periodeKrs, $mahasiswa]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                    @if($statusAkses === 'dibuka')
                                        <input type="hidden" name="status_akses" value="0">
                                        <button type="submit" class="btn-delete" style="white-space:nowrap;" onclick="return confirm('Tutup akses KRS mahasiswa ini?')">Tutup KRS</button>
                                    @else
                                        <input type="hidden" name="status_akses" value="1">
                                        <button type="submit" class="btn-primary" style="white-space:nowrap;" onclick="return confirm('Buka akses KRS hanya untuk mahasiswa ini?')">Buka KRS</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:#64748b;">
                                Tidak ada mahasiswa yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $mahasiswas->appends(request()->query())->onEachSide(1)->links() }}
        </div>
    </div>
</div>

@endsection
