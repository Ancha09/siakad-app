@extends('layouts.dosen')

@section('title', 'Persetujuan KRS')

@section('content')
<div class="inner-page">
    <div class="khs-summary">
        <div class="khs-stat">
            <div class="khs-stat-val" style="color:var(--gold)">{{ $menunggu }}</div>
            <div class="khs-stat-label">Mata Kuliah Menunggu</div>
        </div>
        <div class="khs-stat">
            <div class="khs-stat-val" style="color:var(--green)">{{ $disetujui }}</div>
            <div class="khs-stat-label">Mata Kuliah Disetujui</div>
        </div>
        <div class="khs-stat">
            <div class="khs-stat-val" style="color:#dc2626">{{ $ditolak }}</div>
            <div class="khs-stat-label">Mata Kuliah Ditolak</div>
        </div>
    </div>

    @if(session('success'))
        <div class="info-alert" style="margin-bottom:20px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="info-alert" style="margin-bottom:20px;color:#991b1b;">{{ session('error') }}</div>
    @endif

    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head">
            <h3 style="margin:0;">Filter Pengajuan</h3>
        </div>
        <div class="page-card-body">
            <form method="GET" action="{{ route('dosen.krs') }}">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;">
                    <div>
                        <label for="search">Nama / NIM</label>
                        <input id="search" class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Cari mahasiswa wali">
                    </div>
                    <div>
                        <label for="tahun_akademik">Tahun Akademik</label>
                        <select id="tahun_akademik" class="form-control" name="tahun_akademik">
                            <option value="">Semua tahun</option>
                            @foreach($tahunAkademiks as $tahun)
                                <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="semester_akademik">Semester Akademik</label>
                        <select id="semester_akademik" class="form-control" name="semester_akademik">
                            <option value="">Semua semester</option>
                            <option value="Ganjil" @selected(request('semester_akademik') === 'Ganjil')>Ganjil</option>
                            <option value="Genap" @selected(request('semester_akademik') === 'Genap')>Genap</option>
                        </select>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="submit" class="btn-primary">Terapkan</button>
                        <a href="{{ route('dosen.krs') }}" class="btn-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="page-card">
        <div class="page-card-head">
            <h2 style="margin:0;">Persetujuan KRS Mahasiswa Wali</h2>
            <span class="badge badge-blue">{{ $summaries->total() }} pengajuan</span>
        </div>

        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Mahasiswa</th>
                            <th>Program Studi</th>
                            <th>Semester / Angkatan</th>
                            <th>Periode KRS</th>
                            <th>Jumlah MK</th>
                            <th>Total SKS</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summaries as $summary)
                            @php
                                $pending = (int) $summary->jumlah_menunggu;
                                $approved = (int) $summary->jumlah_disetujui;
                                $rejected = (int) $summary->jumlah_ditolak;
                                $status = $pending > 0
                                    ? 'Menunggu'
                                    : ($rejected > 0 ? 'Perlu Revisi' : ($approved > 0 ? 'Disetujui' : 'Diproses'));
                            @endphp
                            <tr>
                                <td>{{ $summaries->firstItem() + $loop->index }}</td>
                                <td>
                                    <strong>{{ $summary->mahasiswa?->nama ?? '-' }}</strong><br>
                                    <small style="color:#64748b;">{{ $summary->mahasiswa?->nim ?? '-' }}</small>
                                </td>
                                <td>{{ $summary->mahasiswa?->prodi?->nama_prodi ?? '-' }}</td>
                                <td>
                                    Semester {{ $summary->mahasiswa?->semester ?? '-' }}<br>
                                    <small style="color:#64748b;">Angkatan {{ $summary->mahasiswa?->angkatan ?? '-' }}</small>
                                </td>
                                <td>
                                    <strong>{{ $summary->tahun_akademik }}</strong><br>
                                    <small style="color:#64748b;">{{ $summary->semester_akademik }}</small>
                                </td>
                                <td>{{ (int) $summary->jumlah_mata_kuliah }} mata kuliah</td>
                                <td><span class="badge badge-blue">Total {{ (int) $summary->total_sks }} SKS</span></td>
                                <td>
                                    @if($status === 'Menunggu')
                                        <span class="badge badge-gold">Menunggu</span>
                                    @elseif($status === 'Disetujui')
                                        <span class="badge badge-green">Disetujui</span>
                                    @elseif($status === 'Perlu Revisi')
                                        <span class="badge" style="background:#fee2e2;color:#b91c1c;">Perlu Revisi</span>
                                    @else
                                        <span class="badge badge-gray">{{ $status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a
                                        class="btn-primary"
                                        style="display:inline-block;padding:7px 11px;font-size:12px;white-space:nowrap;"
                                        href="{{ route('dosen.krs.show', [
                                            'mahasiswa' => $summary->mahasiswa_id,
                                            'tahun_akademik' => $summary->tahun_akademik,
                                            'semester_akademik' => $summary->semester_akademik,
                                            'return_url' => request()->fullUrl(),
                                        ]) }}"
                                    >
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:42px;color:#64748b;">
                                    <strong>Belum ada pengajuan KRS dari mahasiswa wali.</strong>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px;">
                {{ $summaries->appends(request()->query())->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
