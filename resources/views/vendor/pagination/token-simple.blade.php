{{--
    Paginasi bersama untuk semua peran (`$paginator->links('vendor.pagination.token-simple')`).

    Dulu setiap butir membawa `style="..."` dengan token `--brand-*` dan
    `--surface-*`. Token itu hanya ada di halaman mandiri (`_tokens`), sedangkan
    panel admin memakai `--adm-*` — di sana tautan halaman kehilangan warna dan
    latar, dan tingginya cuma ~32 px. Sekarang semuanya kelas
    `.bk-page-nav*` (berkah-ui-kit.css) dengan varian `.adm-app` di
    berkah-admin.css, dan tinggi minimumnya 44 px.
--}}
@if ($paginator->hasPages())
<nav class="bk-page-nav" role="navigation" aria-label="Halaman">
    @if ($paginator->onFirstPage())
        <span class="bk-page-nav__link bk-page-nav__edge is-disabled" aria-disabled="true">Sebelumnya</span>
    @else
        <a class="bk-page-nav__link bk-page-nav__edge" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="bk-page-nav__gap" aria-hidden="true">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="bk-page-nav__current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="bk-page-nav__link" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a class="bk-page-nav__link bk-page-nav__edge" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
    @else
        <span class="bk-page-nav__link bk-page-nav__edge is-disabled" aria-disabled="true">Berikutnya</span>
    @endif
</nav>
@endif
