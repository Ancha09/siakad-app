@extends('layouts.dosen')

@section('title', 'Jadwal Mengajar')
@section('page-title', 'Jadwal Mengajar')
@section('page-subtitle', 'Jadwal dan mata kuliah yang Anda ampu')

@section('content')
<div class="inner-page">
    <div class="page-card">
        <div class="page-card-head">
            <h2 class="icon-heading"><x-layout-icon name="calendar" /> Jadwal Mengajar Dosen</h2>
            <span class="badge" style="background:#fff;color:#0a1f5c;">{{ $jadwals->count() }} jadwal</span>
        </div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>No</th><th>Kode MK</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen Pengampu</th><th>Program Studi</th><th>Semester</th><th>Tahun Akademik</th><th>Hari / Jam</th><th>Ruangan</th><th>Mahasiswa</th></tr></thead>
                    <tbody>
                        @forelse($jadwals as $jadwal)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $jadwal->mataKuliah?->kode_mk ?? '-' }}</td>
                                <td><strong>{{ $jadwal->mataKuliah?->nama_mk ?? '-' }}</strong></td>
                                <td>{{ $jadwal->mataKuliah?->sks ?? '-' }}</td>
                                <td>{{ $jadwal->dosen?->nama ?? '-' }}</td>
                                <td>{{ $jadwal->mataKuliah?->prodi?->nama_prodi ?? '-' }}</td>
                                <td>{{ $jadwal->mataKuliah?->semester ? 'Semester '.$jadwal->mataKuliah->semester : '-' }}<br><small style="color:#64748b;">{{ $jadwal->semester_akademik ?? '-' }}</small></td>
                                <td>{{ $jadwal->tahun_akademik ?? '-' }}</td>
                                <td><strong>{{ $jadwal->hari ?? '-' }}</strong><br><small style="color:#64748b;">{{ $jadwal->jam_mulai ?? '-' }} - {{ $jadwal->jam_selesai ?? '-' }}</small></td>
                                <td>{{ $jadwal->ruangan?->nama_ruangan ?? '-' }}</td>
                                <td><span class="badge badge-green">{{ $jadwal->jumlah_mahasiswa }} Mahasiswa</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="11" style="text-align:center;padding:40px;color:#64748b;">Belum ada mata kuliah yang diampu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
