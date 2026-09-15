<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }} STTMI</title>
    @include('admin.reports.pdf-style')
</head>
<body>
    @include('admin.reports.pdf-header')

    <table class="summary"><tr>
        <td><span>Total Catatan</span><strong>{{ $summary['total'] }}</strong></td>
        <td><span>Hadir</span><strong>{{ $summary['hadir'] }}</strong></td>
        <td><span>Izin</span><strong>{{ $summary['izin'] }}</strong></td>
        <td><span>Sakit</span><strong>{{ $summary['sakit'] }}</strong></td>
        <td><span>Alpha</span><strong>{{ $summary['alpha'] }}</strong></td>
    </tr></table>

    <h2>Detail Presensi</h2>
    <table class="data">
        <thead><tr><th>NIM</th><th>Mahasiswa</th><th>Prodi</th><th>Kelas</th><th>Mata Kuliah</th><th>Dosen</th><th>Pertemuan</th><th>Tanggal</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($presensis as $item)
                <tr>
                    <td>{{ $item->krs?->mahasiswa?->nim ?? '-' }}</td>
                    <td>{{ $item->krs?->mahasiswa?->nama ?? '-' }}</td>
                    <td>{{ $item->krs?->prodi_efektif?->nama_prodi ?? '-' }}</td>
                    <td>{{ $item->krs?->kelas_efektif?->nama_kelas ?? '-' }}</td>
                    <td>{{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-' }}</td>
                    <td>{{ $item->dosen_efektif?->nama ?? '-' }}</td>
                    <td>{{ $item->pertemuan ?? '-' }}</td>
                    <td>{{ $item->tanggal ? \Illuminate\Support\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td>
                    <td>{{ $item->status }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="center muted">Tidak ada data presensi sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
