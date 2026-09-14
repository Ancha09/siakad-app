<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }} STTMI</title>
    @include('admin.reports.pdf-style')
</head>
<body>
    @include('admin.reports.pdf-header')

    <table class="summary">
        <tr>
            <td><span>Mahasiswa</span><strong>{{ $ringkasan['jumlah_mahasiswa'] }}</strong></td>
            <td><span>Mata Kuliah</span><strong>{{ $ringkasan['jumlah_mata_kuliah'] }}</strong></td>
            <td><span>Rata-rata IP</span><strong>{{ number_format($ringkasan['rata_ip'], 2) }}</strong></td>
            <td><span>Kehadiran</span><strong>{{ number_format($ringkasan['rata_kehadiran'], 1) }}%</strong></td>
            <td><span>Kehadiran &lt;75%</span><strong>{{ $ringkasan['mahasiswa_kehadiran_rendah'] }}</strong></td>
        </tr>
    </table>

    <h2>Rekap Nilai</h2>
    <table class="data"><thead><tr><th>Mata Kuliah</th><th>Dosen</th><th>Kelas</th><th>Mahasiswa</th><th>Rata Nilai</th><th>Rata Bobot</th><th>Tertinggi</th><th>Terendah</th></tr></thead><tbody>
        @forelse($rekapNilai as $item)<tr><td>{{ $item->mata_kuliah }}</td><td>{{ $item->dosen }}</td><td>{{ $item->kelas }}</td><td>{{ $item->jumlah_mahasiswa }}</td><td>{{ number_format($item->rata_nilai, 2) }}</td><td>{{ number_format($item->rata_bobot, 2) }}</td><td>{{ number_format($item->tertinggi, 2) }}</td><td>{{ number_format($item->terendah, 2) }}</td></tr>@empty<tr><td colspan="8" class="center muted">Belum ada data.</td></tr>@endforelse
    </tbody></table>

    <h2>Rekap KRS dan Evaluasi</h2>
    <table class="data"><thead><tr><th>KRS Menunggu</th><th>Disetujui</th><th>Ditolak</th><th>Mahasiswa Mengajukan</th><th>SKS Disetujui</th><th>Responden Evaluasi</th><th>Skor Evaluasi</th></tr></thead><tbody><tr><td>{{ $rekapKrs['menunggu'] }}</td><td>{{ $rekapKrs['disetujui'] }}</td><td>{{ $rekapKrs['ditolak'] }}</td><td>{{ $rekapKrs['mahasiswa_mengajukan'] }}</td><td>{{ $rekapKrs['sks_disetujui'] }}</td><td>{{ $rekapKuesioner['jumlah_responden'] }}</td><td>{{ number_format($rekapKuesioner['rata_rata'], 2) }}/5</td></tr></tbody></table>

    <h2>Kehadiran di Bawah 75%</h2>
    <table class="data"><thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Kelas</th><th>Mata Kuliah</th><th>H</th><th>I</th><th>S</th><th>A</th><th>%</th></tr></thead><tbody>
        @forelse($kehadiranRendah as $item)<tr><td>{{ $item->nim }}</td><td>{{ $item->nama }}</td><td>{{ $item->prodi }}</td><td>{{ $item->kelas }}</td><td>{{ $item->mata_kuliah }}</td><td>{{ $item->hadir }}</td><td>{{ $item->izin }}</td><td>{{ $item->sakit }}</td><td>{{ $item->alpha }}</td><td>{{ number_format($item->persentase, 1) }}%</td></tr>@empty<tr><td colspan="10" class="center muted">Tidak ada data.</td></tr>@endforelse
    </tbody></table>

    <h2>Rekap Evaluasi Perkuliahan</h2>
    <table class="data"><thead><tr><th>Dosen</th><th>Mata Kuliah</th><th>Kelas</th><th>Responden</th><th>Skor</th></tr></thead><tbody>
        @forelse($rekapEvaluasi as $item)<tr><td>{{ $item->dosen }}</td><td>{{ $item->mata_kuliah }}</td><td>{{ $item->kelas }}</td><td>{{ $item->jumlah_responden }}</td><td>{{ number_format($item->rata_rata, 2) }}/5</td></tr>@empty<tr><td colspan="5" class="center muted">Belum ada data.</td></tr>@endforelse
    </tbody></table>

    <h2>Ringkasan Akademik Mahasiswa</h2>
    <table class="data"><thead><tr><th>NIM</th><th>Nama</th><th>Program Studi</th><th>Kelas</th><th>SKS Disetujui</th><th>IP Akademik</th><th>Kehadiran</th></tr></thead><tbody>
        @forelse($detailMahasiswa as $item)<tr><td>{{ $item->nim }}</td><td>{{ $item->nama }}</td><td>{{ $item->prodi }}</td><td>{{ $item->kelas }}</td><td>{{ $item->total_sks }}</td><td>{{ $item->ip === null ? '-' : number_format($item->ip, 2) }}</td><td>{{ $item->kehadiran === null ? '-' : number_format($item->kehadiran, 1).'%' }}</td></tr>@empty<tr><td colspan="7" class="center muted">Belum ada data.</td></tr>@endforelse
    </tbody></table>
</body>
</html>
