@props(['items', 'unread', 'role'])
<aside class="notif-panel" id="notifPanel" aria-label="Pemberitahuan pengumuman">
    <div class="notif-panel-head">
        <h3>Pemberitahuan <span class="badge badge-blue">{{ $unread }} baru</span></h3>
        <button type="button" class="close-btn" onclick="toggleNotif()" aria-label="Tutup pemberitahuan">&times;</button>
    </div>
    <div class="notif-list">
        @forelse($items as $item)
            <a class="notif-item {{ $item->sudah_dibaca ? '' : 'unread' }}" href="{{ route($role.'.pemberitahuan') }}#pengumuman-{{ $item->id }}">
                <div class="notif-icon"><x-layout-icon name="bell" /></div>
                <div>
                    <p>{{ $item->judul }}</p>
                    <span>{{ $item->terbit_pada->timezone('Asia/Jakarta')->format('d M Y, H:i') }} @if($item->penting) &middot; Penting @endif</span>
                    @unless($item->sudah_dibaca)<span>Belum dibaca</span>@endunless
                </div>
            </a>
        @empty
            <p class="announcement-empty">Belum ada pemberitahuan yang terbit.</p>
        @endforelse
    </div>
    <a class="announcement-all" href="{{ route($role.'.pemberitahuan') }}">Lihat semua pemberitahuan &rarr;</a>
</aside>
