{{--
|--------------------------------------------------------------------------
| Topbar shell admin: tombol menu, remah roti, pintasan, identitas
|--------------------------------------------------------------------------
| Halaman anak dapat mengisi @section('crumbs') dengan markup
| <a>/<span class="adm-crumbs__sep">/</span> miliknya sendiri.
--}}
@php
    $admTopName = trim(auth()->user()->name ?? 'Admin');
    /* Halaman anak boleh menentukan halaman induknya sendiri lewat
       @section('back', route('...')). Tanpa itu, kembali ke dasbor. */
    $admBackHref = trim($__env->yieldContent('back')) ?: route('dashboard.admin');
    $admOnHome = request()->routeIs('dashboard.admin', 'admin.dashboard');
    $admTopInitial = mb_strtoupper(mb_substr(explode(' ', $admTopName)[0] ?: 'A', 0, 1));
@endphp

<header class="adm-topbar">
    <button type="button" class="adm-topbar__menu" data-adm-nav-toggle aria-controls="adm-sidebar" aria-expanded="false" aria-label="Buka menu admin">
        @include('layouts.component._icon', ['name' => 'menu', 'size' => 18])
    </button>

    @unless($admOnHome)
        <a class="adm-iconbtn adm-topbar__back" href="{{ $admBackHref }}" data-smart-back
           aria-label="Kembali" title="Kembali">
            @include('layouts.component._icon', ['name' => 'arrow-left', 'size' => 18])
        </a>
    @endunless

    <nav class="adm-crumbs" aria-label="Remah roti">
        <a class="adm-crumbs__home" href="{{ route('dashboard.admin') }}">Dasbor</a>
        <span class="adm-crumbs__sep" aria-hidden="true">/</span>
        @if (View::hasSection('crumbs'))
            @yield('crumbs')
        @else
            <strong>@yield('title', 'Admin')</strong>
        @endif
    </nav>

    <div class="adm-topbar__tools">
        <span data-bk-theme-host></span>
        <a class="adm-iconbtn adm-iconbtn--optional" href="{{ route('admin.help') }}" aria-label="Panduan admin" title="Panduan admin">
            @include('layouts.component._icon', ['name' => 'help', 'size' => 18])
        </a>
        <a class="adm-iconbtn" href="{{ route('admin.notifications') }}" aria-label="Notifikasi admin" title="Notifikasi">
            @include('layouts.component._icon', ['name' => 'bell', 'size' => 18])
        </a>
        <a class="adm-iconbtn adm-iconbtn--optional" href="{{ route('welcome') }}" aria-label="Lihat katalog publik" title="Lihat katalog publik" target="_blank" rel="noopener">
            @include('layouts.component._icon', ['name' => 'eye', 'size' => 18])
        </a>
        <a class="adm-iconbtn" href="{{ route('admin.profile') }}" aria-label="Profil admin" title="Profil">
            <span class="adm-avatar" aria-hidden="true">{{ $admTopInitial }}</span>
        </a>
    </div>
</header>
