<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Template Kartu Bimbingan Skripsi STTMI</title>
    <style>
        @page { margin:24px 30px; }
        body { color:#172033; font:10px DejaVu Sans,sans-serif; }
        .header { width:100%; padding-bottom:10px; border-bottom:3px solid #3359d8; }
        .header td { border:0; vertical-align:middle; }
        .logo { width:58px; height:58px; object-fit:contain; }
        h1 { margin:0; color:#0f2a55; font-size:18px; }
        .subtitle { margin-top:4px; color:#64748b; }
        .identity { width:100%; margin:17px 0; border-collapse:collapse; }
        .identity td { padding:5px 7px; border:0; border-bottom:1px solid #cbd5e1; }
        .identity td:first-child { width:120px; color:#64748b; font-weight:bold; }
        .data { width:100%; border-collapse:collapse; }
        .data th,.data td { padding:7px 6px; border:1px solid #94a3b8; vertical-align:top; }
        .data th { color:#fff; background:#3359d8; font-size:8px; text-align:center; text-transform:uppercase; }
        .data td { height:38px; }
        .signatures { width:100%; margin-top:22px; }
        .signatures td { width:50%; border:0; text-align:center; vertical-align:top; }
        .space { height:65px; }
        .footer { position:fixed; bottom:-12px; left:0; right:0; color:#94a3b8; font-size:7px; text-align:center; }
    </style>
</head>
<body>
    <table class="header"><tr>
        <td style="width:72px;">@if(file_exists(public_path('images/logo_sttmi.jpeg')))<img class="logo" src="{{ public_path('images/logo_sttmi.jpeg') }}" alt="Logo">@endif</td>
        <td><h1>KARTU BIMBINGAN SKRIPSI</h1><div class="subtitle">Sekolah Tinggi Teknologi Mineral Indonesia (STTMI)</div></td>
    </tr></table>

    <table class="identity">
        <tr><td>Nama Mahasiswa</td><td>{{ $mahasiswa?->nama ?? '' }}</td></tr>
        <tr><td>NIM</td><td>{{ $mahasiswa?->nim ?? '' }}</td></tr>
        <tr><td>Program Studi</td><td>{{ $mahasiswa?->prodi?->nama_prodi ?? '' }}</td></tr>
        <tr><td>Judul Skripsi</td><td></td></tr>
        <tr><td>Dosen Pembimbing</td><td></td></tr>
    </table>

    <table class="data">
        <thead><tr><th style="width:25px;">No</th><th style="width:65px;">Tanggal</th><th>Materi / Catatan Bimbingan</th><th style="width:105px;">Tindak Lanjut</th><th style="width:70px;">Paraf</th></tr></thead>
        <tbody>@for($i = 1; $i <= 12; $i++)<tr><td style="text-align:center;">{{ $i }}</td><td></td><td></td><td></td><td></td></tr>@endfor</tbody>
    </table>

    <table class="signatures"><tr>
        <td><div>Mahasiswa,</div><div class="space"></div><strong>( {{ $mahasiswa?->nama ?? '................................' }} )</strong></td>
        <td><div>Dosen Pembimbing,</div><div class="space"></div><strong>( ................................ )</strong></td>
    </tr></table>
    <div class="footer">Template resmi kartu bimbingan skripsi · SIAKAD STTMI</div>
</body>
</html>
