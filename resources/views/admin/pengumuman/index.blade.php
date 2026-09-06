@extends('layouts.admin')
@section('title', 'Kelola Pengumuman')
@section('page-title', 'Pengumuman')
@section('page-subtitle', 'Kelola informasi khusus dosen dan mahasiswa')
@section('content')
<div class="inner-page">
    <div class="announcement-heading">
        <div><h2>Pengumuman kampus</h2><p class="announcement-muted">Pilih penerima, lalu buat pengumuman untuk kelompok tersebut.</p></div>
        <a class="announcement-button" href="{{ route('admin.pengumuman.create', compact('penerima')) }}">+ Buat untuk {{ ucfirst($penerima) }}</a>
    </div>
    <nav class="announcement-tabs" aria-label="Penerima pengumuman">
        @foreach(['mahasiswa', 'dosen'] as $target)
            <a class="{{ $target === $penerima ? 'active' : '' }}" href="{{ route('admin.pengumuman.index', ['penerima' => $target]) }}" @if($target === $penerima) aria-current="page" @endif>Pengumuman {{ ucfirst($target) }}</a>
        @endforeach
    </nav>
    @if(session('success'))<div class="alert-success" role="status">{{ session('success') }}</div>@endif
    <div class="page-card"><div class="table-wrap"><table>
        <thead><tr><th>Pengumuman</th><th>Status</th><th>Jadwal terbit</th><th>Pembaca</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse($pengumumans as $item)
            <tr>
                <td><strong>{{ $item->judul }}</strong>@if($item->penting) <span class="badge badge-red">Penting</span>@endif<p class="announcement-muted">{{ $item->penulis?->name ?? 'Admin' }}</p></td>
                <td><span class="badge {{ $item->label_status === 'Terbit' ? 'badge-green' : 'badge-gray' }}">{{ $item->label_status }}</span></td>
                <td>{{ $item->terbit_pada?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? 'Belum ditentukan' }}</td>
                <td>{{ $item->pembaca_count }}</td>
                <td><div class="action-buttons">
                    <a class="announcement-button secondary" href="{{ route('admin.pengumuman.edit', $item) }}">Edit</a>
                    <form action="{{ route('admin.pengumuman.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus pengumuman ini?')">@csrf @method('DELETE')<button class="announcement-button danger">Hapus</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="5" class="announcement-empty">Belum ada pengumuman untuk {{ $penerima }}.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <x-announcement-pagination :paginator="$pengumumans" />
</div>
@endsection
