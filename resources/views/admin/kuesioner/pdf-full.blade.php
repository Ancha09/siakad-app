<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
    @include('admin.reports.pdf-style')
</head>
<body>
    @include('admin.reports.pdf-header')

    <table class="summary">
        <tr>
            <td><span>Total Dosen</span><strong>{{ number_format($totalDosen) }}</strong></td>
            <td><span>Sudah Dievaluasi</span><strong>{{ number_format($totalDinilai) }}</strong></td>
            <td><span>Belum Dievaluasi</span><strong>{{ number_format($totalBelumDinilai) }}</strong></td>
            <td><span>Total Responden</span><strong>{{ number_format($totalResponden) }}</strong></td>
        </tr>
    </table>

    <h2>Rekap Evaluasi Per Dosen</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width:28px;">No</th>
                <th>Dosen</th>
                <th>NIDN/NIP/Kode</th>
                <th>Program Studi</th>
                <th>Mata Kuliah Terkait</th>
                <th>Periode</th>
                <th style="width:55px;">Responden</th>
                <th style="width:55px;">Skor</th>
                <th style="width:75px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evaluasiDosen as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->dosen->nama }}</td>
                    <td>{{ $item->dosen->nidn ?: '-' }}</td>
                    <td>{{ $item->dosen->prodi?->nama_prodi ?? '-' }}</td>
                    <td>{{ $item->mata_kuliahs->map(fn ($mk) => $mk->kode_mk.' - '.$mk->nama_mk)->implode(', ') ?: '-' }}</td>
                    <td>{{ $item->periode->implode(', ') ?: '-' }}</td>
                    <td class="center">{{ $item->jumlah_responden }}</td>
                    <td class="center">{{ $item->rata_rata === null ? '-' : number_format($item->rata_rata, 2) }}</td>
                    <td>{{ $item->jumlah_responden > 0 ? 'Sudah dinilai' : 'Belum dinilai' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="center muted">Tidak ada data dosen sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
