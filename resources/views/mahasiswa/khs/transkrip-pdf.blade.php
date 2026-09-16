<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transkrip Akademik {{ $mahasiswa->nim }}</title>
    <style>
        @page { margin: 210px 38px 40px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .letterhead { position: fixed; top: -178px; left: 0; right: 0; }
        .letterhead img { display: block; width: 100%; height: auto; }
        h1 { font-size: 19px; margin: 0 0 18px; text-align: center; }
        .identity { margin-bottom: 18px; width: 100%; }
        .identity td { padding: 3px 0; vertical-align: top; }
        .identity .label { width: 120px; }
        .data { border-collapse: collapse; width: 100%; }
        .data th, .data td { border: 1px solid #9ca3af; padding: 6px; }
        .data th { background: #e5e7eb; text-align: center; }
        .data thead { display: table-header-group; }
        .data tr { page-break-inside: avoid; }
        .center { text-align: center; }
        .summary { margin-left: auto; margin-top: 16px; width: 245px; }
        .summary td { padding: 4px; }
        .summary strong { font-size: 13px; }
        .signature { width: 300px; margin-left: auto; margin-top: 28px; page-break-inside: avoid; }
        .signature td { padding: 0; text-align: center; }
        .signature p { margin: 4px 0; }
        .signature-space { height: 72px; }
        .signature-name { font-weight: bold; }
        .signature .footer { color: #4b5563; font-size: 9px; margin-bottom: 12px; }
    </style>
</head>
<body data-template="transkrip-pdf">
    {{-- The complete local letterhead includes the STTMI logo, address, phone and rules. --}}
    <div class="letterhead">
        <img src="{{ public_path('images/kop-sttmi.jpeg.jpeg') }}" alt="Kop resmi STTMI">
    </div>
    <h1>TRANSKRIP AKADEMIK</h1>

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
                    <td>{{ $item->krs?->mata_kuliah_efektif?->kode_mk ?? '-' }}</td>
                    <td>{{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-' }}</td>
                    <td class="center">{{ $item->sks_efektif }}</td>
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

    <table class="signature">
        <tr><td>
            <p class="footer">Dicetak pada {{ now()->format('d-m-Y H:i') }}</p>
            <p>Ketua STTMI,</p>
            <div class="signature-space"></div>
            <p class="signature-name">Dr. Ir. Awang Suwandhi, M.Sc.</p>
        </td></tr>
    </table>
</body>
</html>
