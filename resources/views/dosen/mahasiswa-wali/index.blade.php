@extends('layouts.dosen')

@section('title', 'Mahasiswa Wali')
@section('page-title', 'Mahasiswa Wali')
@section('page-subtitle', 'Daftar mahasiswa yang berada di bawah perwalian Anda')

@section('content')
<div class="inner-page">
    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head">
            <h2 style="margin:0;"><span class="icon-inline"><x-layout-icon name="users" /> Mahasiswa Wali</span></h2>
            <span class="badge" style="background:#fff;color:#0a1f5c;">{{ $mahasiswas->total() }} mahasiswa</span>
        </div>
        <div class="page-card-body">
            <form method="GET" action="{{ route('dosen.mahasiswa-wali') }}">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end;">
                    <div>
                        <label for="search">Nama / NIM</label>
                        <input id="search" class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau NIM">
                    </div>
                    <div>
                        <label for="prodi_id">Program Studi</label>
                        <select id="prodi_id" class="form-control" name="prodi_id">
                            <option value="">Semua program studi</option>
                            @foreach($prodis as $prodi)
                                <option value="{{ $prodi->id }}" @selected((string) request('prodi_id') === (string) $prodi->id)>{{ $prodi->nama_prodi }}</option>
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
                        <label for="semester">Semester</label>
                        <select id="semester" class="form-control" name="semester">
                            <option value="">Semua semester</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester }}" @selected((string) request('semester') === (string) $semester)>Semester {{ $semester }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="submit" class="btn-primary"><span class="icon-inline"><x-layout-icon name="search" /> Cari</span></button>
                        <a href="{{ route('dosen.mahasiswa-wali') }}" class="btn-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="page-card">
        <div class="page-card-head">
            <h2 style="margin:0;">Daftar Mahasiswa Wali</h2>
        </div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Mahasiswa</th>
                            <th>Program Studi</th>
                            <th>Angkatan</th>
                            <th>Semester</th>
                            <th>Status KRS Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mahasiswas as $mahasiswa)
                            @php($krsTerakhir = $mahasiswa->krs->first())
                            <tr>
                                <td>{{ $mahasiswas->firstItem() + $loop->index }}</td>
                                <td>
                                    <strong>{{ $mahasiswa->nama }}</strong><br>
                                    <small style="color:#64748b;">{{ $mahasiswa->nim }}</small>
                                </td>
                                <td>{{ $mahasiswa->prodi?->nama_prodi ?? '-' }}</td>
                                <td>{{ $mahasiswa->angkatan ?? '-' }}</td>
                                <td>{{ $mahasiswa->semester ? 'Semester '.$mahasiswa->semester : '-' }}</td>
                                <td>
                                    @if($krsTerakhir)
                                        <span @class([
                                            'badge',
                                            'badge-green' => $krsTerakhir->status === 'Disetujui',
                                            'badge-gold' => in_array($krsTerakhir->status, ['Menunggu', 'Diambil'], true),
                                            'badge-gray' => ! in_array($krsTerakhir->status, ['Disetujui', 'Menunggu', 'Diambil'], true),
                                        ])>{{ $krsTerakhir->status }}</span>
                                        <br><small style="color:#64748b;">{{ $krsTerakhir->tahun_akademik }} {{ $krsTerakhir->semester_akademik }}</small>
                                    @else
                                        <span class="badge badge-gray">Belum ada KRS</span>
                                    @endif
                                </td>
                                <td>
                                    <a class="btn-primary" style="padding:7px 11px;font-size:12px;white-space:nowrap;" href="{{ route('dosen.mahasiswa-wali.show', [
                                        'mahasiswa' => $mahasiswa,
                                        'return_url' => request()->fullUrl(),
                                    ]) }}">Lihat Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:42px;color:#64748b;">
                                    <span class="empty-state-icon"><x-layout-icon name="users" /></span>
                                    <strong style="display:block;margin-top:8px;">Belum ada mahasiswa wali yang sesuai.</strong>
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
</div>
@endsection
