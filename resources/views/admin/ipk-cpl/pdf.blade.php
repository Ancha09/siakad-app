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

    <table class="summary">
        <tr>
            <td><span>CPL Ditampilkan</span><strong>{{ $rows->count() }}</strong></td>
            <td><span>Mapping Aktif</span><strong>{{ $mappingSummary['jumlah_mapping'] }}</strong></td>
            <td><span>Mapping CPL 9 ke CPL 3</span><strong>{{ $mappingSummary['dialihkan_cpl9'] }}</strong></td>
            <td><span>Mapping Aman</span><strong>{{ $mappingSummary['aman'] }}</strong></td>
            <td><span>Manual Override</span><strong>{{ $mappingSummary['manual_override'] }}</strong></td>
            <td><span>Mapping Bermasalah</span><strong>{{ $mappingSummary['bermasalah'] }}</strong></td>
<td><span>Sumber Unik Bermasalah</span><strong>{{ $mappingSummary['belum_cocok_master'] }}</strong></td>
            <td><span>CPL Memiliki Nilai</span><strong>{{ $summary['cpl_bernilai'] }}</strong></td>
        </tr>
    </table>

    <p class="muted" style="font-size:9px;">
        Konfigurasi laporan: CPL aktif {{ implode(', ', $reportConfiguration['active_cpls']) }}.
        {{ $reportConfiguration['hidden_cpl'] }} disembunyikan sementara dan
        {{ $reportConfiguration['redirected_mapping_count'] }} mapping uniknya dialihkan ke
        {{ $reportConfiguration['redirect_target_cpl'] }} sesuai arahan prodi. Pilihan Teknik Geologi disembunyikan sementara.
    </p>

    <h2>Ringkasan CPL</h2>
    <table class="data">
        <thead><tr><th>No</th><th>Kode CPL</th><th>Nama CPL</th><th>Mata Kuliah</th><th>MK Bernilai</th><th>SKS Dihitung</th><th>Total SKS</th><th>IPK CPL</th></tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $row->kode_cpl }}</strong></td>
                    <td>{{ $row->nama_cpl }}</td>
                    <td>{{ $row->jumlah_mata_kuliah }}</td>
                    <td>{{ $row->mata_kuliah_bernilai }}</td>
                    <td>{{ $row->sks_dihitung }}</td>
                    <td>{{ $row->total_sks }}</td>
                    <td>{{ $row->ipk_cpl === null ? '-' : number_format($row->ipk_cpl, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center muted">Belum ada data untuk filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($manualOverrides->isNotEmpty())
        <p class="muted" style="font-size:9px;">Catatan ekuivalensi mapping berdasarkan keputusan admin/prodi untuk akreditasi (nilai mahasiswa tidak diubah):</p>
        @foreach($manualOverrides as $override)
            <p class="muted" style="font-size:8px;margin:3px 0;">{{ $override['cpl'] }}: {{ $override['source'] }} &rarr; {{ $override['target'] }}. {{ $override['reason'] }} Mapping diperbarui: {{ $override['updated_at'] }}.</p>
        @endforeach
    @endif
</body>
</html>
