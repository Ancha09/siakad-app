@extends('layouts.admin')
@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Akademik')
@section('page-subtitle', 'Ringkasan data kampus dan informasi terbaru')
@section('content')
@php
    $number = fn ($value, $decimals = 0) => $value === null ? '—' : number_format($value, $decimals, ',', '.');
@endphp
<div class="inner-page academic-dashboard">
    <section class="academic-welcome">
        <div><span class="academic-eyebrow">PANEL AKADEMIK</span><h2>Gambaran kampus hari ini</h2><p>Pantau perkuliahan, capaian mahasiswa, dan informasi kampus dari satu halaman.</p></div>
        <form method="GET" class="academic-year"><label for="tahun">Tahun akademik</label><div><select id="tahun" name="tahun">@foreach($years as $year)<option @selected($year === $stats['year'])>{{ $year }}</option>@endforeach</select><button>Tampilkan</button></div></form>
    </section>
    <div class="academic-stats">
        <a class="academic-stat" href="{{ route('admin.dosen') }}"><span class="academic-stat-icon"><x-layout-icon name="graduation" /></span><p>Dosen aktif mengajar</p><strong>{{ $number($stats['activeLecturers']) }}</strong><small>Dari {{ $number($stats['totalLecturers']) }} dosen; memiliki jadwal {{ $stats['year'] }}.</small></a>
        <a class="academic-stat" href="{{ route('admin.mahasiswa') }}"><span class="academic-stat-icon gold"><x-layout-icon name="users" /></span><p>Total mahasiswa</p><strong>{{ $number($stats['totalStudents']) }}</strong><small>Seluruh mahasiswa yang tercatat saat ini.</small></a>
        <div class="academic-stat"><span class="academic-stat-icon violet"><x-layout-icon name="chart" /></span><p>Rata-rata IPK kampus</p><strong>{{ $number($stats['ipk'], 2) }}</strong><small>{{ $number($stats['gradedStudents']) }} mahasiswa memiliki nilai sampai {{ $stats['year'] }}.</small></div>
    </div>
    <div class="academic-grid">
        <section class="academic-panel" style="grid-column:1 / -1;">
            <div class="announcement-heading"><div><h3>Perubahan IPK</h3><p class="announcement-muted">Tahun akademik sebelumnya dan tahun terpilih</p></div>
            @if($stats['delta'] !== null)<span class="badge {{ $stats['delta'] < 0 ? 'badge-red' : 'badge-green' }}">{{ $stats['delta'] > 0 ? 'Naik' : ($stats['delta'] < 0 ? 'Turun' : 'Tetap') }} {{ $number(abs($stats['delta']), 2) }}</span>@endif</div>
            @foreach([[$stats['previousYear'], $stats['previousIpk'], $stats['previousGradedStudents']], [$stats['year'], $stats['ipk'], $stats['gradedStudents']]] as [$year, $ipk, $count])
                <div class="academic-comparison">
                    <div><span>{{ $year }}</span><strong>{{ $number($ipk, 2) }}</strong></div>
                    <meter class="academic-track" min="0" max="4" value="{{ $ipk ?? 0 }}" aria-label="Rata-rata IPK tahun {{ $year }}">
                        {{ $number($ipk, 2) }}
                    </meter>
                    <small>{{ $count }} mahasiswa bernilai</small>
                </div>
            @endforeach
            @if($stats['delta'] === null)
                <p class="academic-note">Belum cukup data nilai pada kedua tahun untuk menyimpulkan kenaikan atau penurunan IPK.</p>
            @else
                <p class="academic-note">Rata-rata IPK {{ $stats['delta'] < 0 ? 'menurun' : ($stats['delta'] > 0 ? 'meningkat' : 'tetap') }} {{ $number(abs($stats['delta']), 2) }} poin dibanding {{ $stats['previousYear'] }}.</p>
            @endif
            <details class="academic-method"><summary>Cara menghitung IPK</summary><p>IPK setiap mahasiswa = jumlah (bobot nilai × SKS) ÷ jumlah SKS dari seluruh KHS bernilai pada KRS yang disetujui, sampai tahun terpilih. Semua pengambilan mata kuliah dihitung sesuai perhitungan KHS saat ini. Rata-rata kampus adalah rata-rata IPK mahasiswa tersebut; jumlah mahasiswa pada kedua tahun dapat berbeda. Nilai kosong tidak dianggap nol.</p></details>
        </section>
    </div>
    <section class="academic-panel">
        <div class="announcement-heading"><div><h3>Ringkasan program studi</h3><p class="announcement-muted">Jumlah dosen dan mahasiswa saat ini, serta IPK kumulatif sampai {{ $stats['year'] }}</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Program studi</th><th>Dosen</th><th>Mahasiswa</th><th>Mahasiswa bernilai</th><th>Rata-rata IPK</th></tr></thead><tbody>
        @forelse($stats['prodis'] as $prodi)
            <tr><td><strong>{{ $prodi->nama_prodi }}</strong></td><td>{{ $prodi->dosens_count }}</td><td>{{ $prodi->mahasiswas_count }}</td><td>{{ $prodi->jumlah_bernilai }}</td><td><span class="badge badge-blue">{{ $number($prodi->ipk, 2) }}</span></td></tr>
        @empty<tr><td colspan="5" class="announcement-empty">Belum ada data program studi.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="academic-panel">
        <div class="announcement-heading"><div><h3>Pengumuman kampus</h3><p class="announcement-muted">Kelola informasi untuk setiap kelompok penerima.</p></div><div class="action-buttons"><a class="announcement-button" href="{{ route('admin.pengumuman.create', ['penerima' => 'dosen']) }}">+ Untuk dosen</a><a class="announcement-button secondary" href="{{ route('admin.pengumuman.create', ['penerima' => 'mahasiswa']) }}">+ Untuk mahasiswa</a></div></div>
        @forelse($announcements as $item)
            <a class="academic-announcement" href="{{ route('admin.pengumuman.edit', $item) }}"><div><strong>{{ $item->judul }}</strong><p class="announcement-muted">{{ ucfirst($item->penerima) }} &middot; {{ $item->terbit_pada?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? 'Waktu terbit belum ditentukan' }}</p></div><span class="badge badge-blue">{{ $item->label_status }}</span></a>
        @empty<p class="announcement-empty">Belum ada pengumuman. Buat informasi pertama untuk dosen atau mahasiswa.</p>@endforelse
        <a class="announcement-all" href="{{ route('admin.pengumuman.index') }}">Kelola semua pengumuman &rarr;</a>
    </section>
</div>
@endsection
