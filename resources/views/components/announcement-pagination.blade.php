@props(['paginator'])
@if($paginator->hasPages())
<nav class="announcement-pagination" aria-label="Halaman pengumuman">
    @if($paginator->onFirstPage())<span>Sebelumnya</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Sebelumnya</a>@endif
    <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
    @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &rarr;</a>@else<span>Berikutnya</span>@endif
</nav>
@endif
