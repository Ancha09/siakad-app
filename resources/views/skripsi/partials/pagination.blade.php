@if($paginator->hasPages())
    <nav class="sk-pagination" aria-label="Navigasi halaman">
        @if($paginator->previousPageUrl())<a class="sk-link sk-secondary" href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>@endif
        <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }} &middot; {{ $paginator->total() }} data</span>
        @if($paginator->nextPageUrl())<a class="sk-link sk-secondary" href="{{ $paginator->nextPageUrl() }}">Selanjutnya</a>@endif
    </nav>
@endif
