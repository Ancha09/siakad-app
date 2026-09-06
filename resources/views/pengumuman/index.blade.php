@extends('layouts.'.$role)
@section('title', 'Pemberitahuan')
@section('page-title', 'Pemberitahuan')
@section('page-subtitle', 'Pengumuman terbaru dari admin akademik')
@section('content')
<div class="inner-page">
    <div class="announcement-heading"><h2>Pengumuman {{ $role === 'admin' ? 'kampus' : 'untuk '.ucfirst($role) }}</h2></div>
    @if(session('success'))<div class="alert-success" role="status">{{ session('success') }}</div>@endif
    @forelse($pengumumans as $item)
        <article class="page-card announcement-detail" id="pengumuman-{{ $item->id }}">
            <div class="page-card-body">
                <div class="announcement-meta">
                    <span class="badge badge-blue">{{ ucfirst($item->penerima) }}</span>
                    @if($item->penting)<span class="badge badge-red">Penting</span>@endif
                    <time>{{ $item->terbit_pada->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</time>
                </div>
                <h2>{{ $item->judul }}</h2>
                <div class="announcement-text">{{ $item->isi }}</div>
                @if($item->tautan)<a class="announcement-all" href="{{ $item->tautan }}" target="_blank" rel="noopener noreferrer">Buka tautan informasi &nearr;</a>@endif
                @if($item->berakhir_pada)<p class="announcement-muted">Berlaku sampai {{ $item->berakhir_pada->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</p>@endif
                @if($item->sudah_dibaca)
                    <span class="badge badge-green">Sudah dibaca</span>
                @else
                    <form method="POST" action="{{ route($role.'.pemberitahuan.baca', $item->id) }}">@csrf<button class="announcement-button secondary">Tandai sudah dibaca</button></form>
                @endif
            </div>
        </article>
    @empty
        <div class="page-card"><p class="announcement-empty">Belum ada pengumuman yang terbit untuk Anda.</p></div>
    @endforelse
    <x-announcement-pagination :paginator="$pengumumans" />
</div>
@endsection
