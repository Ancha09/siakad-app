<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Akademik STTMI</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; padding:28px; color:#172033; font:12px Arial, sans-serif; }
        h1 { margin:0 0 5px; color:#0f2a55; font-size:22px; }
        h2 { margin:24px 0 9px; padding-bottom:6px; color:#0f2a55; font-size:15px; border-bottom:2px solid #0f2a55; }
        p { margin:3px 0; }
        .toolbar { display:flex; gap:8px; margin-bottom:20px; }
        .toolbar button { padding:9px 14px; border:0; border-radius:6px; background:#0f2a55; color:#fff; cursor:pointer; }
        .summary { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; margin:18px 0; }
        .summary div { padding:12px; border:1px solid #cbd5e1; border-radius:7px; }
        .summary span { display:block; color:#64748b; font-size:10px; }
        .summary strong { display:block; color:#0f2a55; font-size:19px; margin-top:4px; }
        table { width:100%; border-collapse:collapse; margin-bottom:14px; }
        th, td { padding:7px; border:1px solid #cbd5e1; text-align:left; vertical-align:top; }
        th { background:#eef2f7; color:#0f2a55; font-size:10px; text-transform:uppercase; }
        .muted { color:#64748b; }
        @media print {
            body { padding:0; }
            .toolbar { display:none; }
            h2 { break-after:avoid; }
            tr { break-inside:avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button><button type="button" onclick="window.close()">Tutup</button></div>
    <h1>Laporan Akademik STTMI</h1>
    <p class="muted">Filter: {{ $deskripsiFilter }}</p>
    <p class="muted">Dicetak: {{ now()->format('d-m-Y H:i') }}</p>

    <div class="summary">
        <div><span>Mahasiswa</span><strong>{{ $ringkasan['jumlah_mahasiswa'] }}</strong></div>
        <div><span>Mata Kuliah</span><strong>{{ $ringkasan['jumlah_mata_kuliah'] }}</strong></div>
        <div><span>Rata-rata IP</span><strong>{{ number_format($ringkasan['rata_ip'], 2) }}</strong></div>
        <div><span>Kehadiran</span><strong>{{ number_format($ringkasan['rata_kehadiran'], 1) }}%</strong></div>
        <div><span>Kehadiran &lt;75%</span><strong>{{ $ringkasan['mahasiswa_kehadiran_rendah'] }}</strong></div>
    </div>

    <h2>Rekap Nilai</h2>
    <table><thead><tr><th>Mata Kuliah</th><th>Dosen</th><th>Kelas</th><th>Mahasiswa</th><th>Rata Nilai</th><th>Rata Bobot</th><th>Tertinggi</th><th>Terendah</th></tr></thead><tbody>
        @forelse($rekapNilai as $item)<tr><td>{{ $item->mata_kuliah }}</td><td>{{ $item->dosen }}</td><td>{{ $item->kelas }}</td><td>{{ $item->jumlah_mahasiswa }}</td><td>{{ number_format($item->rata_nilai, 2) }}</td><td>{{ number_format($item->rata_bobot, 2) }}</td><td>{{ number_format($item->tertinggi, 2) }}</td><td>{{ number_format($item->terendah, 2) }}</td></tr>@empty<tr><td colspan="8">Belum ada data.</td></tr>@endforelse
    </tbody></table>

    <h2>Rekap KRS</h2>
    <table><thead><tr><th>Menunggu</th><th>Disetujui</th><th>Ditolak</th><th>Mahasiswa Mengajukan</th><th>SKS Disetujui</th></tr></thead><tbody><tr><td>{{ $rekapKrs['menunggu'] }}</td><td>{{ $rekapKrs['disetujui'] }}</td><td>{{ $rekapKrs['ditolak'] }}</td><td>{{ $rekapKrs['mahasiswa_mengajukan'] }}</td><td>{{ $rekapKrs['sks_disetujui'] }}</td></tr></tbody></table>

    <h2>Kehadiran di Bawah 75%</h2>
    <table><thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Kelas</th><th>Mata Kuliah</th><th>H</th><th>I</th><th>S</th><th>A</th><th>%</th></tr></thead><tbody>
        @forelse($kehadiranRendah as $item)<tr><td>{{ $item->nim }}</td><td>{{ $item->nama }}</td><td>{{ $item->prodi }}</td><td>{{ $item->kelas }}</td><td>{{ $item->mata_kuliah }}</td><td>{{ $item->hadir }}</td><td>{{ $item->izin }}</td><td>{{ $item->sakit }}</td><td>{{ $item->alpha }}</td><td>{{ number_format($item->persentase, 1) }}%</td></tr>@empty<tr><td colspan="10">Tidak ada data.</td></tr>@endforelse
    </tbody></table>

    <h2>Rekap Evaluasi Perkuliahan</h2>
    <p>Responden: {{ $rekapKuesioner['jumlah_responden'] }} | Sudah: {{ $rekapKuesioner['sudah_mengisi'] }} | Belum: {{ $rekapKuesioner['belum_mengisi'] }} | Rata-rata: {{ number_format($rekapKuesioner['rata_rata'], 2) }}/5</p>
    <table><thead><tr><th>Dosen</th><th>Mata Kuliah</th><th>Kelas</th><th>Responden</th><th>Skor</th></tr></thead><tbody>
        @forelse($rekapEvaluasi as $item)<tr><td>{{ $item->dosen }}</td><td>{{ $item->mata_kuliah }}</td><td>{{ $item->kelas }}</td><td>{{ $item->jumlah_responden }}</td><td>{{ number_format($item->rata_rata, 2) }}/5</td></tr>@empty<tr><td colspan="5">Belum ada data.</td></tr>@endforelse
    </tbody></table>

    <h2>Ringkasan Akademik Mahasiswa</h2>
    <table><thead><tr><th>NIM</th><th>Nama</th><th>Program Studi</th><th>Kelas</th><th>SKS Disetujui</th><th>IP Akademik</th><th>Kehadiran</th></tr></thead><tbody>
        @forelse($detailMahasiswa as $item)<tr><td>{{ $item->nim }}</td><td>{{ $item->nama }}</td><td>{{ $item->prodi }}</td><td>{{ $item->kelas }}</td><td>{{ $item->total_sks }}</td><td>{{ $item->ip === null ? '-' : number_format($item->ip, 2) }}</td><td>{{ $item->kehadiran === null ? '-' : number_format($item->kehadiran, 1).'%' }}</td></tr>@empty<tr><td colspan="7">Belum ada data.</td></tr>@endforelse
    </tbody></table>
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
