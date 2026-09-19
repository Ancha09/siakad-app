@extends('layouts.dosen')

@section('title', 'Mata Kuliah Ampu')
@section('page-title', 'Mata Kuliah Ampu')
@section('page-subtitle', 'Daftar mata kuliah berdasarkan jadwal mengajar Anda')

@section('content')
<div class="inner-page">
    <div class="info-alert">
        <span class="icon-inline"><x-layout-icon name="book" /></span>
        <div><strong>Mata Kuliah yang Anda Ampu</strong><br>Data diambil dari jadwal yang terhubung langsung dengan akun dosen Anda.</div>
    </div>

    <div class="page-card">
        <div class="page-card-head">
            <h2 class="icon-heading"><x-layout-icon name="book" /> Daftar Mata Kuliah yang Diampu</h2>
            <span class="badge" style="background:#fff;color:#0a1f5c;">{{ $jadwals->count() }} jadwal</span>
        </div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>No</th><th>Kode MK</th><th>Mata Kuliah</th><th>Dosen Pengampu</th><th>SKS</th><th>Program Studi</th><th>Semester</th><th>Hari / Jam</th><th>Ruangan</th><th>Tahun Akademik</th><th>Mahasiswa</th></tr></thead>
                    <tbody>
                        @forelse($jadwals as $jadwal)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="badge badge-blue">{{ $jadwal->mataKuliah?->kode_mk ?? '-' }}</span></td>
                                <td><strong>{{ $jadwal->mataKuliah?->nama_mk ?? '-' }}</strong></td>
                                <td>{{ $jadwal->dosen?->nama ?? '-' }}</td>
                                <td>{{ $jadwal->mataKuliah?->sks ?? '-' }}</td>
                                <td>{{ $jadwal->mataKuliah?->prodi?->nama_prodi ?? '-' }}</td>
                                <td>{{ $jadwal->mataKuliah?->semester ? 'Semester '.$jadwal->mataKuliah->semester : '-' }}<br><small style="color:#64748b;">{{ $jadwal->semester_akademik ?? '-' }}</small></td>
                                <td><strong>{{ $jadwal->hari ?? '-' }}</strong><br><small style="color:#64748b;">{{ $jadwal->jam_mulai ?? '-' }} - {{ $jadwal->jam_selesai ?? '-' }}</small></td>
                                <td>{{ $jadwal->ruangan?->nama_ruangan ?? '-' }}</td>
                                <td>{{ $jadwal->tahun_akademik ?? '-' }}</td>
                                <td><span class="badge badge-green">{{ $jadwal->jumlah_mahasiswa }} Mahasiswa</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="11" style="text-align:center;padding:40px;color:#64748b;"><span class="empty-state-icon"><x-layout-icon name="book" /></span><strong style="display:block;margin-top:8px;">Belum ada mata kuliah yang diampu.</strong></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
