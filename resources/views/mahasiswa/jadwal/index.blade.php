@extends('layouts.mahasiswa')

@section('title', 'Jadwal Kuliah')

@section('content')
<div class="inner-page">
    <div class="info-alert"><span class="icon-inline"><x-layout-icon name="calendar" /></span><div><strong>Jadwal Kuliah</strong><br>Mata kuliah berdasarkan KRS yang telah disetujui.</div></div>

    <div class="page-card">
        <div class="page-card-head">
            <h2 class="icon-heading"><x-layout-icon name="calendar" /> Jadwal Kuliah Saya</h2>
            <span class="badge" style="background:#fff;color:#0a1f5c;">{{ $krsItems->count() }} mata kuliah</span>
        </div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>No</th><th>Kode MK</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen Pengampu</th><th>Hari / Jam</th><th>Ruangan</th><th>Periode</th><th>Status KRS</th></tr></thead>
                    <tbody>
                        @forelse($krsItems as $item)
                            @php($jadwal = $item->jadwal)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="badge badge-blue">{{ $item->mata_kuliah_efektif?->kode_mk ?? '-' }}</span></td>
                                <td><strong>{{ $item->mata_kuliah_efektif?->nama_mk ?? '-' }}</strong></td>
                                <td>{{ $item->mata_kuliah_efektif?->sks ?? '-' }}</td>
                                <td>{{ $item->dosen_efektif?->nama ?? '-' }}</td>
                                <td><strong>{{ $jadwal?->hari ?? '-' }}</strong><br><small style="color:#64748b;">{{ $jadwal?->jam_mulai ?? '-' }} - {{ $jadwal?->jam_selesai ?? '-' }}</small></td>
                                <td>{{ $jadwal?->ruangan?->nama_ruangan ?? '-' }}</td>
                                <td>{{ $item->tahun_akademik ?? '-' }}<br><small style="color:#64748b;">{{ $item->semester_akademik ?? '-' }}</small></td>
                                <td><span class="badge badge-green">{{ $item->status ?? '-' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" style="text-align:center;padding:40px;color:#64748b;"><span class="empty-state-icon"><x-layout-icon name="calendar" /></span><strong style="display:block;margin-top:8px;">Belum ada mata kuliah yang diambil.</strong></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
