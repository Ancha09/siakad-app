<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }} - {{ $dosen->nama }}</title>
    @include('admin.reports.pdf-style')
    <style>
        .identity { width:100%; margin-bottom:12px; border-collapse:collapse; }
        .identity td { padding:3px 4px; vertical-align:top; }
        .identity .label { width:115px; color:#64748b; }
        .context { margin:5px 0 12px; color:#475569; line-height:1.7; }
        .comment { white-space:pre-wrap; line-height:1.55; }
    </style>
</head>
<body>
    @include('admin.reports.pdf-header')

    <table class="identity">
        <tr><td class="label">Nama Dosen</td><td>: {{ $dosen->nama }}</td></tr>
        <tr><td class="label">NIDN/NIP/Kode</td><td>: {{ $dosen->nidn ?: '-' }}</td></tr>
        <tr><td class="label">Program Studi</td><td>: {{ $dosen->prodi?->nama_prodi ?? '-' }}</td></tr>
    </table>

    <table class="summary">
        <tr>
            <td><span>Jumlah Responden</span><strong>{{ number_format($jumlahResponden) }}</strong></td>
            <td><span>Rata-rata Keseluruhan</span><strong>{{ $rataRata === null ? '-' : number_format($rataRata, 2).' / 5' }}</strong></td>
            <td><span>Status</span><strong>{{ $jumlahResponden > 0 ? 'Sudah dinilai' : 'Belum dinilai' }}</strong></td>
        </tr>
    </table>

    @if($jumlahResponden === 0)
        <h2>Hasil Evaluasi</h2>
        <p class="muted center">Belum ada evaluasi untuk dosen dan filter yang dipilih.</p>
    @else
        <h2>Konteks Evaluasi</h2>
        <div class="context">
            <strong>Mata Kuliah:</strong>
            {{ $mataKuliahs->map(fn ($item) => $item->kode_mk.' - '.$item->nama_mk)->implode(', ') ?: '-' }}<br>
            <strong>Kelas:</strong>
            {{ $kelases->pluck('nama_kelas')->implode(', ') ?: '-' }}<br>
            <strong>Semester / Tahun Akademik:</strong>
            {{ $periode->implode(', ') ?: '-' }}
        </div>

        <h2>Rata-rata per Pertanyaan</h2>
        <table class="data">
            <thead><tr><th style="width:28px;">No</th><th>Pertanyaan</th><th style="width:75px;">Rata-rata</th></tr></thead>
            <tbody>
                @foreach($pertanyaan as $column => $label)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $label }}</td>
                        <td class="center">{{ $rataPertanyaan[$column] === null ? '-' : number_format($rataPertanyaan[$column], 2).' / 5' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Komentar Mahasiswa (Anonim)</h2>
        <table class="data">
            <thead><tr><th style="width:28px;">No</th><th>Konteks</th><th style="width:55px;">Skor</th><th>Komentar</th><th style="width:68px;">Tanggal</th></tr></thead>
            <tbody>
                @forelse($komentar as $item)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>
                            {{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-' }}<br>
                            <span class="muted">{{ $item->krs?->kelas_efektif?->nama_kelas ?? '-' }} | {{ $item->krs?->semester_akademik ?? '-' }} {{ $item->krs?->tahun_akademik ?? '' }}</span>
                        </td>
                        <td class="center">{{ number_format($item->rata_rata, 2) }}</td>
                        <td class="comment">{{ $item->komentar }}</td>
                        <td>{{ $item->submitted_at?->format('d-m-Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="center muted">Belum ada komentar tertulis.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
