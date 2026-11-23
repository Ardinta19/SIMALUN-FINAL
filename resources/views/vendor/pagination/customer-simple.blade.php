@if ($paginator->hasPages())
<style>
    /* Palet merek, sepasang dengan aturan tema gelap .pg-item--* di
       berkah-theme.css. Sebelumnya halaman ini masih biru (#0072ad/#005f92). */
    .pg { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 12px 0; }
    .pg-item { display: inline-block; padding: 8px 12px; border-radius: 10px; font-size: var(--bk-fs-xs); font-weight: 800; text-decoration: none; min-width: 36px; text-align: center; }
    .pg-item--wide { padding: 8px 14px; }
    .pg-item--muted { color: #6f5a63; background: #f6ecec; border: 1.5px solid #eadbd6; cursor: default; }
    .pg-item--step { color: #85092d; background: #f7edee; border: 1.5px solid #eadbd6; }
    .pg-item--page { color: #301722; background: #fffaf6; border: 1.5px solid #eadbd6; }
    .pg-item--current { color: #fff; background: #85092d; font-weight: 900; }
    .pg-dots { padding: 8px 10px; color: #6f5a63; font-weight: 800; }
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
