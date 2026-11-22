@php
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
    $cartCount = count(session('rental_cart', []));
@endphp
<a class="bk-skip" href="#main-content">Langsung ke isi</a>
<div class="bk-header-space">
    <header class="bk-header {{ request()->routeIs('welcome') ? 'bk-header--catalog' : '' }}" data-bk-header>
        <div class="bk-header-inner">
            <a class="bk-logo" href="{{ route('welcome') }}" aria-label="Berkah Kreatif, buka katalog"><img src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt=""></a>
            <a class="bk-wordmark" href="{{ route('welcome') }}"><strong>Berkah Kreatif</strong><span>Papan bunga untuk setiap cerita</span></a>
            <nav class="bk-desktop-nav" aria-label="Navigasi utama">
                <a href="{{ route('welcome') }}">Katalog</a>
                <a href="{{ route('cart.index') }}">Keranjang <span class="bk-count">{{ $cartCount }}</span></a>
                @if($isCustomer)<a href="{{ route('rental.orders.index') }}">Pesanan saya</a><a href="{{ route('customer.dashboard') }}">Beranda</a><a href="{{ route('customer.notifications') }}">Notifikasi</a><a href="{{ route('customer.profile') }}">Profil</a>@else<a href="{{ route('login') }}">Masuk</a>@endif
            </nav>
            <button type="button" class="bk-command-trigger" data-open-dialog="bk-command" hidden aria-label="Buka navigasi cepat">Cari halaman <kbd>Ctrl K</kbd></button>
            @if(request()->routeIs('welcome'))
            <a class="bk-header-filter bk-filter-fallback" href="#catalog-filter" aria-label="Cari dan filter katalog">@include('layouts.component._icon', ['name' => 'search', 'size' => 21]) <span>Cari &amp; filter</span></a>
            <button type="button" class="bk-header-filter" data-open-dialog="bk-filter" hidden aria-label="Cari dan filter katalog">@include('layouts.component._icon', ['name' => 'search', 'size' => 21]) <span>Cari &amp; filter</span></button>
            @endif
            @if($isCustomer)<a class="bk-header-cart" href="{{ route('cart.index') }}" aria-label="Keranjang{{ $cartCount ? ', '.$cartCount.' unit' : '' }}">@include('layouts.component._icon', ['name' => 'shopping-bag', 'size' => 22]) @if($cartCount)<span>{{ $cartCount }}</span>@endif</a>@else<button type="button" class="bk-menu-trigger" data-open-dialog="bk-menu" hidden aria-label="Buka menu navigasi"><span aria-hidden="true">☰</span></button>@endif
            <button type="button" class="bk-expand" data-bk-expand hidden aria-label="Buka navigasi"><img src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt=""></button>
        </div>
    </header>
</div>
<dialog class="bk-dialog bk-command" id="bk-command" aria-labelledby="bk-command-title">
    <div class="bk-dialog-head"><div><span class="bk-eyebrow">NAVIGASI CEPAT</span><h2 id="bk-command-title">Mau ke mana?</h2></div><button type="button" data-close-dialog aria-label="Tutup navigasi">×</button></div>
    <label for="bk-command-search">Cari halaman</label><input id="bk-command-search" type="search" placeholder="Contoh: keranjang" autocomplete="off" data-command-search>
    <nav aria-label="Hasil navigasi cepat" class="bk-command-links">
        <a data-command-item href="{{ route('welcome') }}">Katalog <span>Temukan papan bunga →</span></a>
        <a data-command-item href="{{ route('cart.index') }}">Keranjang <span>Lihat pilihanmu →</span></a>
        @if($isCustomer)<a data-command-item href="{{ route('rental.orders.index') }}">Pesanan saya <span>Pantau pesanan →</span></a><a data-command-item href="{{ route('customer.dashboard') }}">Beranda akun <span>Ringkasan akun →</span></a><a data-command-item href="{{ route('customer.notifications') }}">Notifikasi <span>Lihat kabar terbaru →</span></a><a data-command-item href="{{ route('customer.profile') }}">Profil <span>Kelola profil →</span></a><a data-command-item href="{{ route('customer.help') }}">Bantuan <span>Panduan penyewaan →</span></a>@else<a data-command-item href="{{ route('login') }}">Masuk <span>Masuk ke akun →</span></a>@endif
    </nav><p class="bk-no-results" data-command-empty hidden>Tidak ada halaman yang cocok.</p>
</dialog>
<dialog class="bk-dialog bk-menu" id="bk-menu" aria-labelledby="bk-menu-title">
    <div class="bk-dialog-head"><div><span class="bk-eyebrow">BERKAH KREATIF</span><h2 id="bk-menu-title">Jelajahi</h2></div><button type="button" data-close-dialog aria-label="Tutup menu">×</button></div>
    <p>Semua yang kamu butuhkan untuk menyiapkan momen istimewa.</p>
    <nav class="bk-menu-links" aria-label="Menu navigasi">
    @if($isCustomer)
        <a href="{{ route('cart.index') }}"><span>01</span>Keranjang <b>↗</b></a><a href="{{ route('rental.orders.index') }}"><span>02</span>Pesanan saya <b>↗</b></a><a href="{{ route('customer.notifications') }}"><span>03</span>Notifikasi <b>↗</b></a><a href="{{ route('customer.help') }}"><span>04</span>Bantuan / FAQ <b>↗</b></a>
    @else
        <a href="{{ route('welcome') }}"><span>01</span>Katalog <b>↗</b></a><a href="{{ route('cart.index') }}"><span>02</span>Keranjang <b>↗</b></a><a href="{{ route('login') }}"><span>03</span>Masuk <b>↗</b></a>
    @endif
    </nav>
</dialog>
@if(!$isCustomer)
<nav class="bk-guest-tabs" aria-label="Navigasi cepat"><a href="{{ route('welcome') }}" @if(request()->routeIs('welcome','catalog.show')) aria-current="page" @endif><span aria-hidden="true">✿</span>Katalog</a><a href="{{ route('cart.index') }}" @if(request()->routeIs('cart.*')) aria-current="page" @endif><span aria-hidden="true">▢</span>Keranjang @if($cartCount)({{ $cartCount }})@endif</a><a href="{{ route('login') }}"><span aria-hidden="true">○</span>Masuk</a></nav>
@endif
