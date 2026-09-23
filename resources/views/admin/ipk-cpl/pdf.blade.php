<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan IPK CPL Teknik Pertambangan</title>
    @include('admin.reports.pdf-style')
</head>
<body>
    @php
        $reportTitle = 'Laporan IPK CPL Teknik Pertambangan';
        $deskripsiFilter = $filterDescription;
    @endphp
    @include('admin.reports.pdf-header')

    <h2>{{ $chartTitle }}</h2>
    <div style="padding:8px;border:1px solid #dbe6f1;text-align:center;page-break-inside:avoid;">
        @if($chartImage)
            <img src="{{ $chartImage }}" alt="{{ $chartTitle }}" style="width:100%;height:auto;">
        @else
            <div style="padding:90px 0;color:#64748b;">Grafik tidak dapat dirender pada server ini.</div>
        @endif
    </div>

    <h2>Ringkasan CPL</h2>
    <table class="data">
        <thead><tr><th>No</th><th>Kode CPL</th><th>Nama CPL</th><th>Mata Kuliah</th><th>SKS Dihitung</th><th>Total SKS</th><th>IPK CPL</th></tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $row->kode_cpl }}</strong></td>
                    <td>{{ $row->nama_cpl }}</td>
                    <td>{{ $row->jumlah_mata_kuliah }}</td>
                    <td>{{ $row->sks_dihitung }}</td>
                    <td>{{ $row->total_sks }}</td>
                    <td>{{ $row->ipk_cpl === null ? '-' : number_format($row->ipk_cpl, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center muted">Belum ada data untuk filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
