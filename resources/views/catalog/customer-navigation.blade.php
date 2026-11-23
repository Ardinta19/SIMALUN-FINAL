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
                @if($isCustomer)<a href="{{ route('rental.orders.index') }}" @if(request()->routeIs('rental.orders.*')) aria-current="page" @endif>Pesanan</a><a class="bk-nav-help" href="{{ route('customer.help') }}" @if(request()->routeIs('customer.help')) aria-current="page" @endif>Bantuan</a>@else<a href="{{ route('login') }}">Masuk</a>@endif
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
            <button type="button" class="bk-header-filter" data-open-dialog="bk-filter" hidden aria-label="Cari dan filter katalog">@include('layouts.component._icon', ['name' => 'search', 'size' => 21]) <span>Cari &amp; filter</span></button>
            @endif
            @if($isCustomer)
                @include('layouts.component.customer._radial_customer', ['inHeader' => true])
            @else
                <button type="button" class="bk-menu-trigger" data-open-dialog="bk-menu" hidden aria-label="Buka menu navigasi"><span class="bk-i" aria-hidden="true">@include('layouts.component._icon', ['name' => 'menu', 'size' => 24])</span></button>
            @endif
            <button type="button" class="bk-expand" data-bk-expand hidden aria-label="Kembali ke atas"><img src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt=""></button>
        </div>
    </header>
</div>
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
<nav class="bk-guest-tabs" aria-label="Navigasi cepat"><a href="{{ route('welcome') }}" @if(request()->routeIs('welcome','catalog.show')) aria-current="page" @endif><span aria-hidden="true" class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span>Katalog</a><a href="{{ route('cart.index') }}" @if(request()->routeIs('cart.*')) aria-current="page" @endif><span aria-hidden="true">▢</span>Keranjang @if($cartCount)({{ $cartCount }})@endif</a><a href="{{ route('login') }}"><span aria-hidden="true">○</span>Masuk</a></nav>
@endif
