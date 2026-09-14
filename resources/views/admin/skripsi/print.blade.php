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
        @foreach($summary as $status => $count)
            <td><span>{{ $status }}</span><strong>{{ $count }}</strong></td>
        @endforeach
    </tr></table>

    <h2>Pembimbing Resmi</h2>
    <table class="data"><thead><tr><th>NIM</th><th>Mahasiswa</th><th>Program Studi</th><th>Judul Skripsi</th><th>Dosen Pembimbing</th><th>Disetujui</th></tr></thead><tbody>
        @forelse($accepted as $item)<tr><td>{{ $item->mahasiswa?->nim ?? '-' }}</td><td>{{ $item->mahasiswa?->nama ?? '-' }}</td><td>{{ $item->mahasiswa?->prodi?->nama_prodi ?? '-' }}</td><td>{{ $item->judul }}</td><td>{{ $item->dosen?->nama ?? '-' }}</td><td>{{ $item->diputuskan_pada?->format('d-m-Y H:i') ?? '-' }}</td></tr>@empty<tr><td colspan="6" class="center muted">Belum ada pembimbing resmi pada filter ini.</td></tr>@endforelse
    </tbody></table>

    <h2>Riwayat Pengajuan</h2>
    <table class="data"><thead><tr><th>NIM</th><th>Mahasiswa</th><th>Prodi</th><th>Judul</th><th>Dosen Tujuan</th><th>Status</th><th>Pengajuan</th><th>Catatan</th></tr></thead><tbody>
        @forelse($submissions as $item)<tr><td>{{ $item->mahasiswa?->nim ?? '-' }}</td><td>{{ $item->mahasiswa?->nama ?? '-' }}</td><td>{{ $item->mahasiswa?->prodi?->nama_prodi ?? '-' }}</td><td>{{ $item->judul }}</td><td>{{ $item->dosen?->nama ?? '-' }}</td><td>{{ $item->status }}</td><td>{{ $item->created_at?->format('d-m-Y H:i') ?? '-' }}</td><td>{{ $item->alasan_keputusan ?: '-' }}</td></tr>@empty<tr><td colspan="8" class="center muted">Belum ada pengajuan pada filter ini.</td></tr>@endforelse
    </tbody></table>
</body>
</html>
