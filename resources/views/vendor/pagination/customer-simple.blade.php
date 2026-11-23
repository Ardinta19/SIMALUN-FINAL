@if ($paginator->hasPages())
<style>
    .pg { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 12px 0; }
    .pg-item { display: inline-block; padding: 8px 12px; border-radius: 10px; font-size: .82rem; font-weight: 800; text-decoration: none; min-width: 36px; text-align: center; }
    .pg-item--wide { padding: 8px 14px; }
    .pg-item--muted { color: #5f7082; background: #f1f5f9; border: 1.5px solid #ddeeff; cursor: default; }
    .pg-item--step { color: #005f92; background: #e0f4ff; border: 1.5px solid #bae6fd; }
    .pg-item--page { color: #1a2332; background: #fff; border: 1.5px solid #ddeeff; }
    .pg-item--current { color: #fff; background: #0072ad; font-weight: 900; }
    .pg-dots { padding: 8px 10px; color: #5f7082; font-weight: 800; }
</style>
<nav role="navigation" aria-label="Halaman" class="pg">
    @if ($paginator->onFirstPage())
        <span class="pg-item pg-item--wide pg-item--muted">Sebelumnya</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="pg-item pg-item--wide pg-item--step">Sebelumnya</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="pg-dots">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="pg-item pg-item--current">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="pg-item pg-item--page">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="pg-item pg-item--wide pg-item--step">Berikutnya</a>
    @else
        <span class="pg-item pg-item--wide pg-item--muted">Berikutnya</span>
    @endif
</nav>
@endif
