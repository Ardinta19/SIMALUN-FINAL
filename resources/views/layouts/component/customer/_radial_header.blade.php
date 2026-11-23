{{--
    Menu pelanggan di bagian atas untuk halaman yang memakai header sendiri.
    Keranjang tetap berada di tab bawah; menu hanya memuat tujuan sekunder.
--}}
@php
    $cartCount = count(session('rental_cart', []));
    $unreadNotif = 0;
    try { $unreadNotif = auth()->user()?->unreadNotifications()->count() ?? 0; } catch (\Exception $e) {}
    $navActive = match (true) {
        request()->routeIs('rental.orders.*') => 'pesanan',
        request()->routeIs('customer.notifications*') => 'notif',
        request()->routeIs('cart.*', 'rental.checkout*') => 'keranjang',
        default => '',
    };
@endphp
<div class="bk-top-menu {{ !empty($inline) ? 'bk-top-menu--inline' : '' }}" data-bk-top-menu>
    @include('layouts.component.customer._radial_customer', ['inHeader' => true, 'navActive' => $navActive, 'cartCount' => $cartCount, 'unreadNotif' => $unreadNotif])
</div>
<style>
.bk-top-menu{position:fixed;top:calc(10px + env(safe-area-inset-top, 0px));right:12px;z-index:1100;display:none;pointer-events:none}
.bk-top-menu>*{pointer-events:auto}
.bk-top-menu .bk-header-radial{display:block}
.bk-top-menu .customer-radial__panel{z-index:1200}
@media(max-width:700px){.bk-top-menu{display:block}}
.bk-top-menu--inline{position:static;display:flex;margin-left:auto;right:auto;top:auto}
.bk-top-menu--inline .bk-header-radial{display:none}
@media(max-width:700px){.bk-top-menu--inline .bk-header-radial{display:block}}
@media(max-width:700px){.bk-top-menu--inline .customer-radial__panel{right:0}}
</style>
