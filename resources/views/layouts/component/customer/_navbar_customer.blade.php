{{--
    Partial: _navbar_customer.blade.php
    Usage: @include('layouts.component.customer._navbar_customer', ['active' => 'beranda'])
    Active options: beranda | pesanan | pesan | notif | profil
--}}

@php $navActive = $active ?? 'beranda'; @endphp

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
           aria-label="Notifikasi">
            <span class="customer-nav__icon" style="position:relative">
                @php
                    $unreadNotif = 0;
                    try { $unreadNotif = auth()->user()?->unreadNotifications()->count() ?? 0; } catch (\Exception $e) {}
                @endphp
                @if($unreadNotif > 0)
                    <span class="customer-nav__badge">{{ $unreadNotif > 9 ? '9+' : $unreadNotif }}</span>
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
/* ═══════════════════════════════════════════════
   CUSTOMER BOTTOM NAVBAR — Global Component
   Tambahkan ke file CSS utama atau inline di layout
═══════════════════════════════════════════════ */
.customer-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 999;
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid #f0dfe1;
    box-shadow: 0 -4px 24px rgba(0, 47, 92, 0.08);
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
.customer-nav__inner {
    max-width: 520px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    height: 64px;
    padding: 0 8px;
}
.customer-nav__item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    text-decoration: none;
    color: #756975;
    position: relative;
    padding: 6px 0;
    transition: color 0.2s ease;
    -webkit-tap-highlight-color: transparent;
}
.customer-nav__item.is-active {
    color: #85092d;
}
.customer-nav__item.is-active .customer-nav__icon svg {
    stroke-width: 2.6;
}
.customer-nav__icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.customer-nav__badge {
    position: absolute;
    top: -6px;
    right: -8px;
    background: #85092d;
    color: white;
    font-size: 0.55rem;
    font-weight: 900;
    min-width: 16px;
    height: 16px;
    border-radius: 99px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
    border: 2px solid white;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.customer-nav__label {
    font-size: 0.6rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
/* FAB Button */
.customer-nav__fab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    gap: 3px;
    -webkit-tap-highlight-color: transparent;
}
.customer-nav__fab-btn {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #85092d 0%, #b63050 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 20px rgba(133,9,45,0.3);
    margin-top: -22px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.customer-nav__fab-btn svg {
    stroke-width: 2.5;
}
.customer-nav__fab:active .customer-nav__fab-btn {
    transform: scale(0.94);
    box-shadow: 0 3px 10px rgba(133,9,45,0.2);
}
.customer-nav__fab-label {
    font-size: 0.6rem;
    font-weight: 800;
    color: #85092d;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    margin-top: 2px;
}
/* Active indicator dot */
.customer-nav__item.is-active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    width: 4px;
    height: 4px;
    background: #85092d;
    border-radius: 50%;
}
.customer-nav a:focus-visible { outline: 3px solid #bc5b75; outline-offset: 2px; border-radius: 8px; }
.customer-nav__fab.is-active .customer-nav__fab-label { text-decoration: underline; text-underline-offset: 3px; }
/* Body padding so content isn't hidden behind nav */
body {
    padding-bottom: calc(84px + env(safe-area-inset-bottom, 0px));
}
</style>