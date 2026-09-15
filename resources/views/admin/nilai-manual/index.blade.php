@extends('layouts.admin')

@section('title', 'Input Nilai Lama')

@section('content')
@if(session('success')) <div class="alert-success">{{ session('success') }}</div> @endif

<div class="page-card">
    <div class="page-card-head">
        <div><h2>Nilai Lama / Manual</h2><small>Nilai historis tersimpan di KHS dan ikut dihitung pada transkrip/IPK.</small></div>
        <a href="{{ route('admin.nilai-manual.create') }}" class="btn-primary">+ Input Nilai</a>
    </div>
    <div class="page-card-body">
        @include('admin.partials.legacy-filters', ['attendance' => false, 'resetRoute' => 'admin.nilai-manual.index'])
        <div class="table-wrap">
            <table>
                <thead><tr><th>Mahasiswa</th><th>Periode</th><th>Mata Kuliah</th><th>Dosen</th><th>Nilai</th><th>SKS/Bobot</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($nilai as $item)
                    <tr>
                        <td><strong>{{ $item->krs?->mahasiswa?->nama ?? '-' }}</strong><br><small>{{ $item->krs?->mahasiswa?->nim ?? '-' }}</small></td>
                        <td>{{ $item->semester_akademik }} · {{ $item->tahun_akademik }}<br><small>Semester {{ $item->krs?->semester ?? '-' }}</small></td>
                        <td>{{ $item->krs?->mata_kuliah_efektif?->kode_mk ?? '-' }}<br><small>{{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-' }}</small></td>
                        <td>{{ $item->dosen_efektif?->nama ?? '-' }}</td>
                        <td>{{ number_format((float) $item->nilai_angka, 2) }} / <strong>{{ $item->nilai_huruf }}</strong></td>
                        <td>{{ $item->sks_efektif }} SKS / {{ number_format((float) $item->bobot, 2) }}</td>
                        <td><a href="{{ route('admin.nilai-manual.edit', $item) }}" class="btn-outline">Edit</a>
                            <form method="POST" action="{{ route('admin.nilai-manual.destroy', $item) }}" onsubmit="return confirm('Hapus permanen nilai manual ini? Nilai akan hilang dari KHS/transkrip. Pastikan sudah memiliki backup.');" style="display:inline">
                                @csrf @method('DELETE') <button type="submit" class="btn-outline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:30px">Belum ada nilai manual.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:20px">{{ $nilai->links() }}</div>
    </div>
</div>
@endsection
