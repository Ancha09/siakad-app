<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan IPK CPL Teknik Pertambangan</title>
    @include('admin.reports.pdf-style')
    <style>
        .cpl-meta { margin:5px 0 8px; padding:7px 9px; border:1px solid #dbeafe; background:#f8fafc; }
        .cpl-meta p { margin:0 0 4px; }
        .cpl-meta p:last-child { margin-bottom:0; }
    </style>
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
            <td><span>Mapping Terimport</span><strong>{{ $mappingSummary['jumlah_mapping'] }}</strong></td>
            <td><span>Cocok Master</span><strong>{{ $mappingSummary['cocok_master'] }}</strong></td>
            <td><span>Belum Cocok</span><strong>{{ $mappingSummary['belum_cocok_master'] }}</strong></td>
            <td><span>CPL Memiliki Nilai</span><strong>{{ $summary['cpl_bernilai'] }}</strong></td>
        </tr>
    </table>

    <h2>Ringkasan CPL</h2>
    <table class="data">
        <thead><tr><th>CPL</th><th>Deskripsi</th><th>IPK CPL</th><th>Total SKS</th><th>SKS Dihitung</th><th>Kelengkapan</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td><strong>{{ $row->kode_cpl }}</strong></td>
                    <td>{{ $row->cpl->deskripsi ?: '-' }}</td>
                    <td>{{ $row->ipk_cpl === null ? '-' : number_format($row->ipk_cpl, 2) }}</td>
                    <td>{{ $row->total_sks }}</td>
                    <td>{{ $row->sks_dihitung }}</td>
                    <td>{{ number_format($row->kelengkapan_persen, 2) }}%</td>
                    <td>{{ $row->status }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center muted">Belum ada data untuk filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>

    @foreach($rows as $row)
        <h2>{{ $row->kode_cpl }} - {{ $row->nama_cpl }}</h2>
        <div class="cpl-meta">
            <p><strong>Deskripsi:</strong> {{ $row->cpl->deskripsi ?: '-' }}</p>
            <p><strong>Turunan Visi-Misi:</strong> {!! nl2br(e($row->cpl->turunan_visi_misi ?: '-')) !!}</p>
            <p><strong>CPL KKNI:</strong> {{ $row->cpl->cpl_kkni ?: '-' }}</p>
        </div>
        <table class="data">
            <thead><tr><th>Kode MK</th><th>Mata Kuliah</th><th>Semester</th><th>SKS</th><th>Mahasiswa</th><th>Rata-rata Mutu</th><th>Mutu x SKS</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($row->courses as $course)
                    <tr>
                        <td>{{ $course->kode_mata_kuliah }}</td>
                        <td>{{ $course->nama_mata_kuliah }}</td>
                        <td>{{ $course->semester ?? '-' }}</td>
                        <td>{{ $course->sks }}</td>
                        <td>{{ $course->jumlah_mahasiswa }}</td>
                        <td>{{ $course->rata_bobot === null ? '-' : number_format($course->rata_bobot, 2) }}</td>
                        <td>{{ $course->mutu_sks === null ? '-' : number_format($course->mutu_sks, 2) }}</td>
                        <td>{{ $course->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="center muted">Tidak ada mata kuliah untuk filter yang dipilih.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    @if($unmatchedMappings->isNotEmpty())
        <h2>Mapping Belum Cocok Master</h2>
        <table class="data">
            <thead><tr><th>CPL</th><th>Kode Excel</th><th>Mata Kuliah Excel</th><th>Semester</th><th>SKS</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($unmatchedMappings as $mapping)
                    <tr>
                        <td>{{ $mapping->cpl?->kode_cpl ?? '-' }}</td>
                        <td>{{ $mapping->kode_sumber }}</td>
                        <td>{{ $mapping->nama_sumber }}</td>
                        <td>{{ $mapping->semester ?? '-' }}</td>
                        <td>{{ $mapping->sks ?? '-' }}</td>
                        <td>Belum cocok master</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
