{{-- Navigasi utama pelanggan: tiap ikon membawa tujuan yang jelas, tanpa tindakan tersembunyi. --}}
@php
    $navActive = $active ?? '';
    if (request()->routeIs('cart.*', 'rental.checkout*')) $navActive = 'keranjang';
    elseif (request()->routeIs('welcome', 'catalog.show')) $navActive = 'katalog';
    elseif ($navActive === 'pesan') $navActive = 'katalog';
    $cartCount = count(session('rental_cart', []));
    $unreadNotif = 0;
    try { $unreadNotif = auth()->user()?->unreadNotifications()->count() ?? 0; } catch (\Exception $e) {}
@endphp
<nav class="customer-nav" id="customer-nav" aria-label="Navigasi pelanggan">
    <div class="customer-nav__inner">
        <a href="{{ route('customer.dashboard') }}" class="customer-nav__item {{ $navActive === 'beranda' ? 'is-active' : '' }}" @if($navActive === 'beranda') aria-current="page" @endif>
            <span class="customer-nav__icon">@include('layouts.component._icon', ['name' => 'home', 'size' => 24])</span><span class="customer-nav__label">Beranda</span>
        </a>
        <a href="{{ route('welcome') }}" class="customer-nav__item {{ $navActive === 'katalog' ? 'is-active' : '' }}" @if($navActive === 'katalog') aria-current="page" @endif>
            <span class="customer-nav__icon">@include('layouts.component._icon', ['name' => 'search', 'size' => 24])</span><span class="customer-nav__label">Katalog</span>
        </a>
        <a href="{{ route('cart.index') }}" class="customer-nav__item {{ $navActive === 'keranjang' ? 'is-active' : '' }}" @if($navActive === 'keranjang') aria-current="page" @endif aria-label="Keranjang{{ $cartCount ? ', '.$cartCount.' unit' : '' }}">
            <span class="customer-nav__icon">@include('layouts.component._icon', ['name' => 'shopping-bag', 'size' => 24]) @if($cartCount)<span class="customer-nav__badge" aria-hidden="true">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>@endif</span><span class="customer-nav__label">Keranjang</span>
        </a>
        <a href="{{ route('rental.orders.index') }}" class="customer-nav__item {{ $navActive === 'pesanan' ? 'is-active' : '' }}" @if($navActive === 'pesanan') aria-current="page" @endif>
            <span class="customer-nav__icon">@include('layouts.component._icon', ['name' => 'clipboard-list', 'size' => 24])</span><span class="customer-nav__label">Pesanan</span>
        </a>
        <a href="{{ route('customer.notifications') }}" class="customer-nav__item {{ $navActive === 'notif' ? 'is-active' : '' }}" @if($navActive === 'notif') aria-current="page" @endif aria-label="Notifikasi{{ $unreadNotif ? ', '.$unreadNotif.' belum dibaca' : '' }}">
            <span class="customer-nav__icon">@include('layouts.component._icon', ['name' => 'bell', 'size' => 24]) @if($unreadNotif)<span class="customer-nav__badge" aria-hidden="true">{{ $unreadNotif > 99 ? '99+' : $unreadNotif }}</span>@endif</span><span class="customer-nav__label">Notif</span>
        </a>
        <a href="{{ route('customer.profile') }}" class="customer-nav__item {{ $navActive === 'profil' ? 'is-active' : '' }}" @if($navActive === 'profil') aria-current="page" @endif>
            <span class="customer-nav__icon">@include('layouts.component._icon', ['name' => 'user', 'size' => 24])</span><span class="customer-nav__label">Profil</span>
        </a>
    </div>
</nav>
<style>
.customer-nav,.customer-nav *{box-sizing:border-box}.customer-nav{position:fixed;inset:auto 0 0;z-index:999;margin:0;padding:0 0 env(safe-area-inset-bottom, 0px);background:rgba(255,252,249,.96);border-top:1px solid #f0dfe1;box-shadow:0 -4px 24px rgba(84,5,31,.08);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);font-family:'Plus Jakarta Sans',system-ui,sans-serif;line-height:1}.customer-nav__inner{max-width:620px;height:68px;margin:0 auto;padding:0 4px;display:grid;grid-template-columns:repeat(6,minmax(0,1fr))}.customer-nav__item{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:0;height:68px;padding:4px 0;gap:5px;color:#6b5960;text-decoration:none;line-height:1;-webkit-tap-highlight-color:transparent;transition:color .2s ease,transform .2s ease}.customer-nav__item.is-active{color:#85092d}.customer-nav__item.is-active .customer-nav__icon{background:#f6e6e8}.customer-nav__icon{position:relative;display:flex;align-items:center;justify-content:center;width:38px;height:30px;border-radius:12px;flex:none;transition:background .2s ease}.customer-nav__icon svg{display:block;width:22px;height:22px;flex:none}.customer-nav__label{display:block;margin:0;padding:0;font-size:10px;font-weight:700;line-height:12px;white-space:nowrap}.customer-nav__badge{position:absolute;top:-5px;right:-4px;display:flex;align-items:center;justify-content:center;min-width:17px;height:17px;padding:0 3px;border:2px solid #fff;border-radius:99px;background:#85092d;color:#fff;font-size:9px;font-weight:800;line-height:1}.customer-nav a:focus-visible{outline:3px solid #bc5b75;outline-offset:-3px;border-radius:12px}.customer-nav__item:active{transform:scale(.95)}body{padding-bottom:calc(96px + env(safe-area-inset-bottom, 0px))}@media(max-width:700px){.customer-nav{inset:auto auto calc(10px + env(safe-area-inset-bottom, 0px)) 50%;transform:translateX(-50%);width:min(620px,calc(100% - 24px));padding:0;border:1px solid rgba(139,78,89,.2);border-radius:24px;background:rgba(255,252,249,.94);box-shadow:0 10px 32px rgba(84,5,31,.14)}}@media(max-width:350px){.customer-nav__label{font-size:9px}.customer-nav__inner{padding:0 2px}}@media(prefers-reduced-motion:reduce){.customer-nav__item,.customer-nav__icon{transition:none}.customer-nav__item:active{transform:none}}
</style>
