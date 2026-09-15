<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transkrip Akademik {{ $mahasiswa->nim }}</title>
    <style>
        @page { margin: 32px 38px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 19px; margin: 0 0 4px; text-align: center; }
        .subtitle { margin: 0 0 22px; text-align: center; }
        .identity { margin-bottom: 18px; width: 100%; }
        .identity td { padding: 3px 0; vertical-align: top; }
        .identity .label { width: 120px; }
        .data { border-collapse: collapse; width: 100%; }
        .data th, .data td { border: 1px solid #9ca3af; padding: 6px; }
        .data th { background: #e5e7eb; text-align: center; }
        .center { text-align: center; }
        .summary { margin-left: auto; margin-top: 16px; width: 245px; }
        .summary td { padding: 4px; }
        .summary strong { font-size: 13px; }
        .footer { color: #4b5563; font-size: 9px; margin-top: 28px; text-align: right; }
    </style>
</head>
<body data-template="transkrip-pdf">
    <h1>TRANSKRIP AKADEMIK</h1>
    <p class="subtitle">Sekolah Tinggi Teknologi Mandala Indonesia</p>

    <table class="identity">
        <tr><td class="label">NIM</td><td>: {{ $mahasiswa->nim }}</td></tr>
        <tr><td class="label">Nama</td><td>: {{ $mahasiswa->nama }}</td></tr>
        <tr><td class="label">Program Studi</td><td>: {{ $mahasiswa->prodi?->nama_prodi ?? '-' }}</td></tr>
        <tr><td class="label">Kelas</td><td>: {{ $mahasiswa->kelas?->nama_kelas ?? '-' }}</td></tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>Tahun/Semester</th>
                <th>Kode</th>
                <th>Mata Kuliah</th>
                <th>SKS</th>
                <th>Nilai</th>
                <th>Bobot</th>
            </tr>
        </thead>
        <tbody>
            @forelse($khs as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->tahun_akademik }} / {{ $item->semester_akademik }}</td>
                    <td>{{ $item->krs?->jadwal?->mataKuliah?->kode_mk ?? '-' }}</td>
                    <td>{{ $item->krs?->jadwal?->mataKuliah?->nama_mk ?? '-' }}</td>
                    <td class="center">{{ $item->krs?->jadwal?->mataKuliah?->sks ?? 0 }}</td>
                    <td class="center">{{ $item->nilai_huruf ?? '-' }}</td>
                    <td class="center">{{ $item->bobot === null ? '-' : number_format($item->bobot, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">Belum ada data hasil studi.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><td>Total SKS</td><td>: <strong>{{ $totalSks }}</strong></td></tr>
        <tr><td>IPK</td><td>: <strong>{{ $ipk === null ? '-' : number_format($ipk, 2) }}</strong></td></tr>
    </table>

    <p class="footer">Dicetak pada {{ now()->format('d-m-Y H:i') }}</p>
</body>
</html>
