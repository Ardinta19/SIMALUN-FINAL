@if ($paginator->hasPages())
<nav role="navigation" aria-label="Halaman" style="display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:6px;padding:12px 0;">
    @if ($paginator->onFirstPage())
        <span style="padding:8px 14px;border-radius:var(--radius-btn);font-size:.82rem;font-weight:800;color:var(--surface-400);background:var(--surface-100);border:1.5px solid var(--surface-200);cursor:default;">Sebelumnya</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="padding:8px 14px;border-radius:var(--radius-btn);font-size:.82rem;font-weight:800;color:var(--brand-500);background:var(--brand-100);border:1.5px solid var(--brand-100);text-decoration:none;">Sebelumnya</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span style="padding:8px 10px;font-size:.82rem;font-weight:800;color:var(--surface-400);">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page" style="padding:8px 12px;border-radius:var(--radius-btn);font-size:.82rem;font-weight:900;color:var(--surface-raised);background:var(--brand-500);min-width:36px;text-align:center;display:inline-block;">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" style="padding:8px 12px;border-radius:var(--radius-btn);font-size:.82rem;font-weight:800;color:var(--brand-900);background:var(--surface-raised);border:1.5px solid var(--surface-200);text-decoration:none;min-width:36px;text-align:center;display:inline-block;">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="padding:8px 14px;border-radius:var(--radius-btn);font-size:.82rem;font-weight:800;color:var(--brand-500);background:var(--brand-100);border:1.5px solid var(--brand-100);text-decoration:none;">Berikutnya</a>
    @else
        <span style="padding:8px 14px;border-radius:var(--radius-btn);font-size:.82rem;font-weight:800;color:var(--surface-400);background:var(--surface-100);border:1.5px solid var(--surface-200);cursor:default;">Berikutnya</span>
    @endif
</nav>
@endif
