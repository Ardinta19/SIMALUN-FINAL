@if ($paginator->hasPages())
<nav class="bk-pagination" aria-label="Navigasi halaman">
    @if ($paginator->onFirstPage())<span class="is-disabled" aria-disabled="true">← Sebelumnya</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Sebelumnya</a>@endif
    <div class="bk-pagination__pages" aria-label="Pilih halaman">
        @foreach ($elements as $element)
            @if (is_string($element))<span aria-hidden="true">{{ $element }}</span>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())<span class="is-current" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>@else<a href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach
    </div>
    @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya →</a>@else<span class="is-disabled" aria-disabled="true">Berikutnya →</span>@endif
</nav>
@endif
