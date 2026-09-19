<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Rencana Studi</title>
    <style>
        @page { margin: 24px 32px 28px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: Arial, sans-serif; font-size: 10px; }
        .template-letterhead { display: block; width: 100%; height: auto; margin: 0 0 8px; }
        .letterhead { width: 100%; border-collapse: collapse; border-bottom: 3px double #111; margin-bottom: 8px; }
        .letterhead td { border: 0; padding: 0 0 8px; vertical-align: middle; }
        .logo { width: 70px; height: 70px; object-fit: contain; }
        .school { text-align: center; line-height: 1.25; }
        .school .sttmi { font-size: 25px; font-weight: bold; letter-spacing: 2px; }
        .school .name { font-size: 14px; font-weight: bold; }
        .school .address { margin-top: 4px; font-size: 9px; }
        .document-meta { width: 100%; border-collapse: collapse; margin: 6px 0 2px; font-size: 8px; }
        .document-meta td { border: 0; padding: 1px 0; }
        h1 { margin: 9px 0 2px; font-size: 16px; text-align: center; text-decoration: underline; }
        .academic-year { margin: 0 0 10px; font-size: 12px; font-weight: bold; text-align: center; }
        .identity { width: 100%; border-collapse: collapse; margin-bottom: 11px; }
        .identity td { border: 0; padding: 2px 4px; vertical-align: top; }
        .identity .label { width: 92px; font-weight: bold; }
        .identity .colon { width: 8px; }
        .courses { width: 100%; border-collapse: collapse; }
        .courses th, .courses td { border: 1px solid #111; padding: 5px 4px; vertical-align: top; }
        .courses th { background: #e5e7eb; text-align: center; font-size: 8px; }
        .center { text-align: center; }
        .course-name { font-weight: bold; }
        .muted { color: #4b5563; font-size: 8px; line-height: 1.35; }
        .total-row td { font-weight: bold; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 17px; page-break-inside: avoid; }
        .signatures td { width: 50%; border: 0; padding: 0 18px; text-align: center; vertical-align: top; }
        .signature-space { height: 58px; }
        .signer { font-weight: bold; text-decoration: underline; }
        .footer { position: fixed; right: 0; bottom: -17px; left: 0; color: #6b7280; font-size: 7px; text-align: center; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    @if($templateLetterhead)
        <img class="template-letterhead" src="{{ $templateLetterhead }}" alt="Kop resmi STTMI dari template KRS">
    @else
        <table class="letterhead">
            <tr>
                <td style="width:78px;">
                    @if(file_exists(public_path('images/logo_sttmi.jpeg')))
                        <img class="logo" src="{{ public_path('images/logo_sttmi.jpeg') }}" alt="Logo STTMI">
                    @endif
                </td>
                <td class="school">
                    <div class="sttmi">STTMI</div>
                    <div class="name">Sekolah Tinggi Teknologi Mineral Indonesia</div>
                    <div class="address">Jalan Cihanjuang No.161, Kabupaten Bandung Barat 40559 &middot; Telp. 082-118652085</div>
                </td>
                <td style="width:78px;"></td>
            </tr>
        </table>
    @endif

    <table class="document-meta">
        <tr><td>No. Formulir</td><td>: Form-1/II/{{ now()->format('Y') }}</td><td style="text-align:right;">Tanggal cetak: {{ now()->format('d / m / Y') }}</td></tr>
    </table>

    <h1>KARTU RENCANA STUDI (KRS)</h1>
    <div class="academic-year">TAHUN AKADEMIK {{ $tahunAkademik }} &middot; {{ strtoupper($semesterAkademik) }}</div>

    <table class="identity">
        <tr>
            <td class="label">Nama</td><td class="colon">:</td><td>{{ strtoupper($mahasiswa->nama) }}</td>
            <td class="label">Semester</td><td class="colon">:</td><td>{{ $semesterStudi ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIM</td><td class="colon">:</td><td>{{ $mahasiswa->nim }}</td>
            <td class="label">Angkatan</td><td class="colon">:</td><td>{{ $mahasiswa->angkatan ?? $mahasiswa->kelas?->angkatan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Program Studi</td><td class="colon">:</td><td>{{ trim(($mahasiswa->prodi?->jenjang ?? '').' '.($mahasiswa->prodi?->nama_prodi ?? '-')) }}</td>
            <td class="label">Kelas</td><td class="colon">:</td><td>{{ $mahasiswa->kelas?->nama_kelas ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Dosen Wali</td><td class="colon">:</td><td colspan="4">{{ $dosenWali?->nama ?? '-' }}</td>
        </tr>
    </table>

    <table class="courses">
        <thead>
        <tr>
            <th style="width:28px;">NO.</th>
            <th style="width:68px;">KODE</th>
            <th>NAMA MATA KULIAH</th>
            <th style="width:48px;">SEMESTER</th>
            <th style="width:34px;">SKS</th>
            <th style="width:185px;">KETERANGAN</th>
        </tr>
        </thead>
        <tbody>
        @foreach($printableRecords as $item)
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td class="center">{{ $item->mata_kuliah_efektif?->kode_mk ?? '-' }}</td>
                <td class="course-name">{{ $item->mata_kuliah_efektif?->nama_mk ?? '-' }}</td>
                <td class="center">{{ $item->mata_kuliah_efektif?->semester ?? $semesterStudi ?? '-' }}</td>
                <td class="center">{{ $item->mata_kuliah_efektif?->sks ?? 0 }}</td>
                <td>
                    <strong>{{ $item->dosen_efektif?->nama ?? 'Dosen belum tersedia' }}</strong>
                    @if($item->jadwal)
                        <div class="muted">
                            {{ $item->jadwal->hari ?? '-' }}, {{ substr((string) $item->jadwal->jam_mulai, 0, 5) }}-{{ substr((string) $item->jadwal->jam_selesai, 0, 5) }}
                            &middot; {{ $item->jadwal->ruangan?->nama_ruangan ?? 'Ruang belum tersedia' }}
                        </div>
                    @endif
                    <div class="muted">Status: {{ $item->status }}</div>
                </td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="4" style="text-align:right;">JUMLAH SKS</td>
            <td class="center">{{ $totalSks }}</td>
            <td>{{ $totalSks }} Satuan Kredit Semester</td>
        </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                Dosen Wali
                <div class="signature-space"></div>
                <div class="signer">{{ $dosenWali?->nama ?? '(....................................)' }}</div>
                @if($dosenWali?->nidn)<div>NIDN: {{ $dosenWali->nidn }}</div>@endif
            </td>
            <td>
                Ketua Program Studi<br>
                {{ trim(($mahasiswa->prodi?->jenjang ?? '').' '.($mahasiswa->prodi?->nama_prodi ?? '')) }}
                <div class="signature-space"></div>
                <div class="signer">{{ $namaKetuaProgramStudi ?: '(....................................)' }}</div>
                @if($nipKetuaProgramStudi)<div>NIP: {{ $nipKetuaProgramStudi }}</div>@endif
            </td>
        </tr>
    </table>

    <div class="footer">Dokumen dibuat otomatis oleh SIAKAD STTMI pada {{ now()->format('d-m-Y H:i') }} WIB</div>
</body>
</html>
