@php
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
    $cartCount = count(session('rental_cart', []));
    $navActive = request()->routeIs('rental.orders.*') ? 'pesanan' : (request()->routeIs('customer.notifications*') ? 'notif' : '');
    $unreadNotif = 0;
    if ($isCustomer) { try { $unreadNotif = auth()->user()->unreadNotifications()->count(); } catch (\Exception $e) {} }
@endphp
<a class="bk-skip" href="#main-content">Langsung ke isi</a>
<div class="bk-header-space">
    <header class="bk-header {{ request()->routeIs('welcome') ? 'bk-header--catalog' : '' }}" data-bk-header>
        <div class="bk-header-inner">
            <a class="bk-logo" href="{{ route('welcome') }}" aria-label="Berkah Kreatif, buka katalog"><img src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt=""></a>
            <a class="bk-wordmark" href="{{ route('welcome') }}"><strong>Berkah Kreatif</strong><span>Papan bunga untuk setiap cerita</span></a>
            <nav class="bk-desktop-nav" aria-label="Navigasi utama">
                @if($isCustomer)<a href="{{ route('customer.dashboard') }}" @if(request()->routeIs('customer.dashboard')) aria-current="page" @endif>Beranda</a>@endif
                <a href="{{ route('welcome') }}" @if(request()->routeIs('welcome', 'catalog.show')) aria-current="page" @endif>Katalog</a>
                @if($isCustomer)<a href="{{ route('rental.orders.index') }}" @if(request()->routeIs('rental.orders.*')) aria-current="page" @endif>Pesanan</a><a class="bk-nav-help" href="{{ route('customer.help') }}" @if(request()->routeIs('customer.help')) aria-current="page" @endif>Bantuan</a>@else{{-- Tamu: tujuan di sini harus sama dengan isi busur di ponsel
                (katalog, keranjang, daftar, masuk). Tanpa “Daftar”, tamu di
                layar lebar kehilangan satu tujuan yang di ponsel tersedia. --}}
                <a href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>Masuk</a><a href="{{ route('register') }}" @if(request()->routeIs('register')) aria-current="page" @endif>Daftar</a>@endif
            </nav>
            <nav class="bk-desktop-actions" aria-label="Pintasan akun dan keranjang">
                <a href="{{ route('cart.index') }}" class="bk-desktop-action" aria-label="Keranjang{{ $cartCount ? ', '.$cartCount.' unit' : '' }}" @if(request()->routeIs('cart.*', 'rental.checkout*')) aria-current="page" @endif>@include('layouts.component._icon', ['name' => 'shopping-bag', 'size' => 20]) @if($cartCount)<span class="bk-action-badge" aria-hidden="true">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>@endif</a>
                @if($isCustomer)
                <a href="{{ route('customer.notifications') }}" class="bk-desktop-action" aria-label="Notifikasi{{ $unreadNotif ? ', '.$unreadNotif.' belum dibaca' : '' }}" @if(request()->routeIs('customer.notifications*')) aria-current="page" @endif>@include('layouts.component._icon', ['name' => 'bell', 'size' => 20]) @if($unreadNotif)<span class="bk-action-badge" aria-hidden="true">{{ $unreadNotif > 99 ? '99+' : $unreadNotif }}</span>@endif</a>
                <a href="{{ route('customer.profile') }}" class="bk-desktop-action" aria-label="Profil" @if(request()->routeIs('customer.profile', 'customer.addresses.*')) aria-current="page" @endif>@include('layouts.component._icon', ['name' => 'user', 'size' => 20])</a>
                @endif
                <span class="bk-theme-host" data-bk-theme-host></span>
            </nav>
            @if(request()->routeIs('welcome'))
            <a class="bk-header-filter bk-filter-fallback" href="#catalog-filter" aria-label="Cari dan filter katalog">@include('layouts.component._icon', ['name' => 'search', 'size' => 21]) <span>Cari &amp; filter</span></a>
            <button type="button" class="bk-header-filter" data-open-dialog="bk-filter" hidden aria-haspopup="dialog" aria-expanded="false" aria-label="Cari dan filter katalog">@include('layouts.component._icon', ['name' => 'search', 'size' => 21]) <span>Cari &amp; filter</span></button>
            @endif
            {{-- Satu menu radial untuk semua peran. Tamu tidak lagi memakai
                 dialog "Jelajahi": tujuan mereka (katalog, keranjang, daftar,
                 masuk) kini berada di busur yang sama. --}}
            @include('layouts.component.customer._radial_customer', ['inHeader' => true])
            <button type="button" class="bk-expand" data-bk-expand hidden aria-label="Kembali ke atas"><img src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt=""></button>
        </div>
    </header>
</div>
@if(!$isCustomer)
<nav class="bk-guest-tabs" aria-label="Navigasi cepat"><a href="{{ route('welcome') }}" @if(request()->routeIs('welcome','catalog.show')) aria-current="page" @endif><span aria-hidden="true" class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span>Katalog</a><a href="{{ route('cart.index') }}" @if(request()->routeIs('cart.*')) aria-current="page" @endif><span aria-hidden="true" class="bk-i">@include('layouts.component._icon', ['name' => 'cart', 'size' => 24])</span>Keranjang @if($cartCount)({{ $cartCount }})@endif</a><a href="{{ route('login') }}"><span aria-hidden="true" class="bk-i">@include('layouts.component._icon', ['name' => 'account', 'size' => 24])</span>Masuk</a></nav>
@endif
