<section class="news-section">
    <div class="announcement-heading">
        <div class="news-section-title">Pengumuman untuk {{ ucfirst(auth()->user()->role) }}</div>
        <a href="{{ route(auth()->user()->role.'.pemberitahuan') }}">Lihat semua &rarr;</a>
    </div>
    <div class="news-list">
        @forelse($notificationItems as $item)
            <a class="news-item announcement-link" href="{{ route(auth()->user()->role.'.pemberitahuan') }}#pengumuman-{{ $item->id }}">
                <div class="news-dot" style="background:{{ $item->penting ? 'var(--red)' : 'var(--blue)' }}"></div>
                <div class="news-body">
                    <p>{{ $item->judul }} @unless($item->sudah_dibaca)<span class="badge badge-blue">Baru</span>@endunless</p>
                    <span>{{ \Illuminate\Support\Str::limit($item->isi, 160) }}</span>
                    <span>{{ $item->terbit_pada->timezone('Asia/Jakarta')->format('d M Y, H:i') }} @if($item->penting) &middot; Penting @endif</span>
                </div>
            </a>
        @empty
            <p class="announcement-empty">Belum ada pengumuman untuk Anda. Pengumuman terbaru dari admin akan tampil di sini.</p>
        @endforelse
    </div>
</section>
