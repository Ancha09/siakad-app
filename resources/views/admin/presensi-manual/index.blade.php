@extends('layouts.admin')

@section('title', 'Input Absensi Lama')

@section('content')
@if(session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
<div class="page-card">
    <div class="page-card-head">
        <div><h2>Absensi Lama / Manual</h2><small>Entri ini ikut dibaca rekap presensi mahasiswa dan laporan akademik.</small></div>
        <a href="{{ route('admin.presensi-manual.create') }}" class="btn-primary">+ Input Absensi</a>
    </div>
    <div class="page-card-body">
    @include('admin.partials.legacy-filters', ['attendance' => true, 'resetRoute' => 'admin.presensi-manual.index'])
    <div class="table-wrap"><table>
        <thead><tr><th>Mahasiswa</th><th>Mata Kuliah</th><th>Periode</th><th>Tanggal/Pertemuan</th><th>Status</th><th>Dosen</th><th>Keterangan</th><th>Aksi</th></tr></thead>
        <tbody>@forelse($presensis as $item)<tr>
            <td><strong>{{ $item->krs?->mahasiswa?->nama ?? '-' }}</strong><br><small>{{ $item->krs?->mahasiswa?->nim ?? '-' }}</small></td>
            <td>{{ $item->krs?->mata_kuliah_efektif?->kode_mk ?? '-' }}<br><small>{{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-' }}</small></td>
            <td>{{ $item->krs?->semester_akademik }} · {{ $item->krs?->tahun_akademik }}</td>
            <td>{{ $item->tanggal?->format('d-m-Y') }}<br><small>{{ $item->pertemuan ? 'Pertemuan '.$item->pertemuan : 'Tanpa nomor pertemuan' }}</small></td>
            <td><span class="badge badge-blue">{{ $item->status === 'Alpha' ? 'Alpa' : $item->status }}</span></td>
            <td>{{ $item->dosen_efektif?->nama ?? '-' }}</td><td>{{ $item->keterangan ?? '-' }}</td>
            <td><a href="{{ route('admin.presensi-manual.edit', $item) }}" class="btn-outline">Edit</a>
                <form method="POST" action="{{ route('admin.presensi-manual.destroy', $item) }}" onsubmit="return confirm('Hapus permanen absensi manual ini? Rekap kehadiran akan berubah. Pastikan sudah memiliki backup.');" style="display:inline">
                    @csrf @method('DELETE') <button type="submit" class="btn-outline">Hapus</button>
                </form>
            </td>
        </tr>@empty<tr><td colspan="8" style="text-align:center;padding:30px">Belum ada absensi manual.</td></tr>@endforelse</tbody>
    </table></div><div style="margin-top:20px">{{ $presensis->links() }}</div></div>
</div>
@endsection
