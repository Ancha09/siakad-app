<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Perkuliahan - {{ $jadwal->mataKuliah?->nama_mk ?? 'Matkul' }}</title>
    <style>
        @page {
            margin: 20px 25px 25px;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            line-height: 1.35;
            color: #111827;
            margin: 0;
        }
        .letterhead {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px double #111827;
            margin-bottom: 10px;
        }
        .letterhead td {
            border: 0;
            padding: 0 0 6px;
            vertical-align: middle;
        }
        .logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .school {
            text-align: center;
            line-height: 1.25;
        }
        .school .sttmi {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1.5px;
            color: #0a1f5c;
        }
        .school .name {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .school .address {
            margin-top: 3px;
            font-size: 8.5px;
            color: #4b5563;
        }
        .doc-title {
            text-align: center;
            font-size: 13.5px;
            font-weight: bold;
            text-decoration: underline;
            margin: 8px 0 2px;
            color: #0a1f5c;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
        }
        .identity-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9px;
        }
        .identity-table td {
            border: 0;
            padding: 2px 4px;
            vertical-align: top;
        }
        .identity-table .label {
            width: 110px;
            font-weight: bold;
            color: #374151;
        }
        .identity-table .colon {
            width: 8px;
            text-align: center;
        }
        .bap-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8.5px;
        }
        .bap-table th,
        .bap-table td {
            border: 1px solid #374151;
            padding: 4px 5px;
            vertical-align: top;
        }
        .bap-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            text-align: center;
            font-size: 8.5px;
        }
        .text-center {
            text-align: center;
        }
        .thumb-img {
            max-width: 55px;
            max-height: 40px;
            object-fit: cover;
            border-radius: 3px;
            border: 1px solid #cbd5e1;
        }
        .summary-box {
            width: 100%;
            border: 1px dashed #94a3b8;
            background: #f8fafc;
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 14px;
            font-size: 8.5px;
        }
        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signatures td {
            width: 50%;
            border: 0;
            padding: 0 15px;
            text-align: center;
            vertical-align: top;
            font-size: 9px;
        }
        .signature-space {
            height: 52px;
        }
        .signer-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
        }
        .footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5px;
            color: #94a3b8;
            border-top: 0.5px solid #e2e8f0;
            padding-top: 3px;
        }
        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    {{-- ===================== KOP RESMI STTMI ===================== --}}
    <table class="letterhead">
        <tr>
            <td style="width: 65px; text-align: center;">
                @if(file_exists(public_path('images/logo_sttmi.jpeg')))
                    <img class="logo" src="{{ public_path('images/logo_sttmi.jpeg') }}" alt="Logo STTMI">
                @endif
            </td>
            <td class="school">
                <div class="sttmi">STTMI BANDUNG</div>
                <div class="name">SEKOLAH TINGGI TEKNOLOGI MINERAL INDONESIA</div>
                <div class="address">Jalan Cihanjuang No. 161, Kabupaten Bandung Barat 40559 &middot; Telp. 082-118652085 &middot; Email: info@sttmi.ac.id</div>
            </td>
            <td style="width: 65px;"></td>
        </tr>
    </table>

    <div class="doc-title">BERITA ACARA PERKULIAHAN (BAP)</div>
    <div class="doc-subtitle">
        TAHUN AKADEMIK {{ $jadwal->tahun_akademik ?? '-' }} &middot; SEMESTER {{ strtoupper($jadwal->semester_akademik ?? 'GANJIL') }}
    </div>

    {{-- ===================== TABEL IDENTITAS MATA KULIAH ===================== --}}
    <table class="identity-table">
        <tr>
            <td class="label">Mata Kuliah</td>
            <td class="colon">:</td>
            <td><strong>{{ $jadwal->mataKuliah?->nama_mk ?? '-' }}</strong> ({{ $jadwal->mataKuliah?->kode_mk ?? '-' }})</td>

            <td class="label">Program Studi</td>
            <td class="colon">:</td>
            <td>{{ $prodi?->nama_prodi ?? $jadwal->mataKuliah?->prodi?->nama_prodi ?? '-' }} ({{ $prodi?->jenjang ?? 'S1' }})</td>
        </tr>
        <tr>
            <td class="label">Bobot SKS / Smt</td>
            <td class="colon">:</td>
            <td>{{ $jadwal->mataKuliah?->sks ?? 0 }} SKS / Semester {{ $jadwal->mataKuliah?->semester ?? '-' }}</td>

            <td class="label">Kelas / Ruangan</td>
            <td class="colon">:</td>
            <td>Kelas {{ $jadwal->kelas?->nama_kelas ?? $jadwal->kelas ?? '-' }} / Ruang {{ $jadwal->ruangan?->nama_ruangan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Dosen Pengampu</td>
            <td class="colon">:</td>
            <td>{{ $jadwal->dosen?->nama ?? '-' }}</td>

            <td class="label">Jadwal Kuliah</td>
            <td class="colon">:</td>
            <td>{{ $jadwal->hari }}, {{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }} WIB</td>
        </tr>
        <tr>
            <td class="label">NIDN / NIP Dosen</td>
            <td class="colon">:</td>
            <td>{{ $jadwal->dosen?->nidn ?? '-' }}</td>

            <td class="label">Jumlah Peserta</td>
            <td class="colon">:</td>
            <td>{{ $totalPeserta }} Mahasiswa Terdaftar</td>
        </tr>
    </table>

    {{-- ===================== TABEL BERITA ACARA PERKULIAHAN ===================== --}}
    <table class="bap-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 75px;">Hari, Tanggal</th>
                <th style="width: 45px;">Waktu</th>
                <th>Pokok Bahasan / Materi Kuliah</th>
                <th style="width: 110px;">Keterangan</th>
                <th style="width: 60px;">Kehadiran</th>
                <th style="width: 60px;">Bukti Foto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pertemuans as $p)
                <tr>
                    <td class="text-center"><strong>{{ $p->pertemuan }}</strong></td>
                    <td>
                        {{ \Carbon\Carbon::parse($p->tanggal)->translatedFormat('l') }}<br>
                        <span style="color: #4b5563;">{{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}</span>
                    </td>
                    <td class="text-center">
                        {{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }}
                    </td>
                    <td>
                        <strong>{{ $p->materi_kuliah ?? '-' }}</strong>
                    </td>
                    <td>
                        {{ $p->keterangan ?? '-' }}
                    </td>
                    <td class="text-center">
                        <strong>{{ $p->total_hadir }}</strong> / {{ $totalPeserta }}<br>
                        <span style="color: #64748b; font-size: 7.5px;">Hadir</span>
                    </td>
                    <td class="text-center">
                        @if($p->foto && file_exists(public_path('storage/' . $p->foto)))
                            <img class="thumb-img" src="{{ public_path('storage/' . $p->foto) }}" alt="Foto Pertemuan {{ $p->pertemuan }}">
                        @elseif($p->foto && file_exists(storage_path('app/public/' . $p->foto)))
                            <img class="thumb-img" src="{{ storage_path('app/public/' . $p->foto) }}" alt="Foto Pertemuan {{ $p->pertemuan }}">
                        @else
                            <span style="color: #94a3b8; font-size: 8px;">Tersedia di sistem</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #64748b;">
                        Belum ada catatan pelaksanaan perkuliahan (BAP) yang terinput.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===================== RINGKASAN ===================== --}}
    <div class="summary-box">
        <strong>Ringkasan Pelaksanaan:</strong>
        Total pertemuan terlaksana: <strong>{{ $pertemuans->count() }}</strong> dari 16 pertemuan standar ({{ min(100, round(($pertemuans->count() / 16) * 100)) }}%).
        Rata-rata persentase kehadiran mahasiswa di kelas ini: <strong>{{ $krs->count() > 0 ? round($krs->avg('persentase'), 1) : 0 }}%</strong>.
    </div>

    {{-- ===================== TANDA TANGAN ===================== --}}
    <table class="signatures">
        <tr>
            <td>
                Mengetahui,<br>
                Ketua Program Studi {{ $prodi?->nama_prodi ?? $jadwal->mataKuliah?->prodi?->nama_prodi ?? '-' }}
                <div class="signature-space"></div>
                <div class="signer-name">
                    {{ $prodi?->ketua_program_studi_nama ?? 'Dr. Ir. Budi Santoso, M.T.' }}
                </div>
                NIP. {{ $prodi?->ketua_program_studi_nip ?? '-' }}
            </td>
            <td>
                Bandung Barat, {{ now()->translatedFormat('d F Y') }}<br>
                Dosen Pengampu Mata Kuliah,
                <div class="signature-space"></div>
                <div class="signer-name">
                    {{ $jadwal->dosen?->nama ?? '-' }}
                </div>
                NIDN. {{ $jadwal->dosen?->nidn ?? '-' }}
            </td>
        </tr>
    </table>

    <div class="footer">
        Dokumen resmi Berita Acara Perkuliahan (BAP) digenerate secara otomatis melalui SIAKAD STTMI &middot; Dicetak pada {{ now()->format('d-m-Y H:i') }} WIB
    </div>
</body>
</html>
