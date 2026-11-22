{{--
    Partial: _navbar_customer.blade.php
    Usage: @include('layouts.component.customer._navbar_customer', ['active' => 'beranda'])
    Active options: beranda | pesanan | pesan | notif | profil
--}}

@php
    $navActive = $active ?? 'beranda';
    $unreadNotif = 0;
    try { $unreadNotif = auth()->user()?->unreadNotifications()->count() ?? 0; } catch (\Exception $e) {}
@endphp

<nav class="customer-nav" id="customer-nav" aria-label="Navigasi pelanggan">
    <div class="customer-nav__inner">

        {{-- Beranda --}}
        <a href="{{ route('customer.dashboard') }}"
           class="customer-nav__item {{ $navActive === 'beranda' ? 'is-active' : '' }}"
           @if($navActive === 'beranda') aria-current="page" @endif
           aria-label="Beranda">
            <span class="customer-nav__icon">
                @include('layouts.component._icon', ['name' => 'beranda', 'size' => 24])
            </span>
            <span class="customer-nav__label">Beranda</span>
        </a>

        {{-- Pesanan --}}
        <a href="{{ route('rental.orders.index') }}"
           class="customer-nav__item {{ $navActive === 'pesanan' ? 'is-active' : '' }}"
           @if($navActive === 'pesanan') aria-current="page" @endif
           aria-label="Pesanan">
            <span class="customer-nav__icon">
                @include('layouts.component._icon', ['name' => 'pesanan', 'size' => 24])
            </span>
            <span class="customer-nav__label">Pesanan</span>
        </a>

        {{-- FAB: Pesan Baru --}}
        <a href="{{ route('welcome') }}"
           class="customer-nav__fab {{ $navActive === 'pesan' ? 'is-active' : '' }}"
           @if($navActive === 'pesan') aria-current="page" @endif
           aria-label="Buat Pesanan Baru">
            <span class="customer-nav__fab-btn">
                @include('layouts.component._icon', ['name' => 'add', 'size' => 24])
            </span>
            <span class="customer-nav__fab-label">Pesan</span>
        </a>

        {{-- Notifikasi --}}
        <a href="{{ route('customer.notifications') }}"
           class="customer-nav__item {{ $navActive === 'notif' ? 'is-active' : '' }}"
           @if($navActive === 'notif') aria-current="page" @endif
           aria-label="Notifikasi{{ $unreadNotif > 0 ? ' ('.$unreadNotif.' belum dibaca)' : '' }}">
            <span class="customer-nav__icon" style="position:relative">
                @if($unreadNotif > 0)
                    <span class="customer-nav__badge" aria-hidden="true">{{ $unreadNotif > 9 ? '9+' : $unreadNotif }}</span>
                @endif
                @include('layouts.component._icon', ['name' => 'notif', 'size' => 24])
            </span>
            <span class="customer-nav__label">Notif</span>
        </a>

        {{-- Profil --}}
        <a href="{{ route('customer.profile') }}"
           class="customer-nav__item {{ $navActive === 'profil' ? 'is-active' : '' }}"
           @if($navActive === 'profil') aria-current="page" @endif
           aria-label="Profil">
            <span class="customer-nav__icon">
                @include('layouts.component._icon', ['name' => 'profil', 'size' => 24])
            </span>
            <span class="customer-nav__label">Profil</span>
        </a>

    </div>
</nav>

<style>
/* Tata letak navbar berdiri sendiri: tidak mengikuti line-height atau reset halaman. */
.customer-nav, .customer-nav * { box-sizing: border-box; }
.customer-nav {
    position: fixed;
    inset: auto 0 0;
    z-index: 999;
    margin: 0;
    padding: 0 0 env(safe-area-inset-bottom, 0px);
    background: rgba(255,255,255,.97);
    border-top: 1px solid #f0dfe1;
    box-shadow: 0 -4px 24px rgba(84,5,31,.08);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    line-height: 1;
}
.customer-nav__inner {
    max-width: 520px;
    height: 64px;
    margin: 0 auto;
    padding: 0 8px;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
}
.customer-nav__item, .customer-nav__fab {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    min-width: 0;
    height: 64px;
    margin: 0;
    padding: 10px 0 0;
    gap: 5px;
    color: #756975;
    text-decoration: none;
    line-height: 1;
    -webkit-tap-highlight-color: transparent;
}
.customer-nav__item.is-active { color: #85092d; }
.customer-nav__item.is-active .customer-nav__icon svg { stroke-width: 2.6; }
.customer-nav__icon { position: relative; display: flex; width: 24px; height: 24px; align-items: center; justify-content: center; flex: none; }
.customer-nav__icon svg, .customer-nav__fab-btn svg { display: block; width: 24px; height: 24px; flex: none; }
.customer-nav__badge {
    position: absolute;
    top: -6px; right: -8px;
    display: flex; align-items: center; justify-content: center;
    min-width: 16px; height: 16px; padding: 0 3px;
    border: 2px solid white; border-radius: 99px;
    background: #85092d; color: white;
    font-size: 9px; font-weight: 900; line-height: 1;
}
.customer-nav__label, .customer-nav__fab-label {
    display: block;
    margin: 0;
    padding: 0;
    font-size: 10px;
    font-weight: 800;
    line-height: 12px;
    letter-spacing: .4px;
    text-transform: uppercase;
    white-space: nowrap;
}
.customer-nav__fab { padding: 0; gap: 0; color: #85092d; }
.customer-nav__fab-btn {
    position: absolute; top: -16px;
    display: flex; align-items: center; justify-content: center;
    width: 52px; height: 52px; border-radius: 50%;
    background: linear-gradient(135deg, #85092d 0%, #b63050 100%);
    color: #fff; box-shadow: 0 6px 20px rgba(133,9,45,.3);
    transition: transform .15s ease, box-shadow .15s ease;
}
.customer-nav__fab-btn svg { stroke-width: 2.5; }
.customer-nav__fab:active .customer-nav__fab-btn { transform: scale(.94); box-shadow: 0 3px 10px rgba(133,9,45,.2); }
.customer-nav__fab-label { position: absolute; top: 39px; color: #85092d; }
.customer-nav__item.is-active::after {
    content: ''; position: absolute; bottom: 3px;
    width: 4px; height: 4px; background: #85092d; border-radius: 50%;
}
.customer-nav a:focus-visible { outline: 3px solid #bc5b75; outline-offset: -3px; border-radius: 8px; }
.customer-nav__fab.is-active .customer-nav__fab-label { text-decoration: underline; text-underline-offset: 3px; }
@media (max-width: 320px) { .customer-nav__label, .customer-nav__fab-label { font-size: 9px; letter-spacing: 0; } }
@media (prefers-reduced-motion: reduce) { .customer-nav__fab-btn { transition: none; } }
/* Jaga konten terakhir tetap dapat digulir melewati navbar dan area aman perangkat. */
body { padding-bottom: calc(96px + env(safe-area-inset-bottom, 0px)); }
</style>