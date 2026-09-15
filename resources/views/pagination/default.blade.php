@if ($paginator->hasPages())
    <nav class="app-pagination" role="navigation" aria-label="Navigasi halaman">
        <p class="app-pagination__summary">
            Menampilkan {{ $paginator->firstItem() ?? 0 }}&ndash;{{ $paginator->lastItem() ?? 0 }}
            dari {{ $paginator->total() }} data
        </p>

        <div class="app-pagination__links">
            @if ($paginator->onFirstPage())
                <span class="app-pagination__link is-disabled" aria-disabled="true">
                    &larr; Sebelumnya
                </span>
            @else
                <a class="app-pagination__link"
                   href="{{ $paginator->previousPageUrl() }}"
                   rel="prev">
                    &larr; Sebelumnya
                </a>
            @endif

            <div class="app-pagination__pages" aria-label="Nomor halaman">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="app-pagination__link is-disabled" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="app-pagination__link is-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="app-pagination__link" href="{{ $url }}" aria-label="Ke halaman {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a class="app-pagination__link"
                   href="{{ $paginator->nextPageUrl() }}"
                   rel="next">
                    Berikutnya &rarr;
                </a>
            @else
                <span class="app-pagination__link is-disabled" aria-disabled="true">
                    Berikutnya &rarr;
                </span>
            @endif
        </div>
    </nav>
@endif
