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
        <details class="customer-radial" data-customer-radial>
            <summary class="customer-nav__item {{ in_array($navActive, ['keranjang', 'pesanan', 'notif'], true) || request()->routeIs('customer.help') ? 'is-active' : '' }}" aria-label="Menu: keranjang, pesanan, notifikasi, dan bantuan{{ $unreadNotif ? ', '.$unreadNotif.' notifikasi belum dibaca' : '' }}">
                <span class="customer-nav__icon">@include('layouts.component._icon', ['name' => 'menu', 'size' => 24]) @if($unreadNotif)<span class="customer-nav__badge" aria-hidden="true">{{ $unreadNotif > 99 ? '99+' : $unreadNotif }}</span>@endif</span><span class="customer-nav__label">Menu</span>
            </summary>
            <div class="customer-radial__panel" aria-label="Menu pelanggan">
                <div class="customer-radial__heading"><strong>Menu pelanggan</strong><span>Pilih tujuan</span></div>
                <div class="customer-radial__links">
                    <a href="{{ route('cart.index') }}" @if($navActive === 'keranjang') aria-current="page" @endif aria-label="Keranjang{{ $cartCount ? ', '.$cartCount.' unit' : '' }}"><span class="customer-radial__glyph">@include('layouts.component._icon', ['name' => 'shopping-bag', 'size' => 20])</span><span>Keranjang @if($cartCount)<small>{{ $cartCount }} unit</small>@endif</span></a>
                    <a href="{{ route('rental.orders.index') }}" @if($navActive === 'pesanan') aria-current="page" @endif><span class="customer-radial__glyph">@include('layouts.component._icon', ['name' => 'clipboard-list', 'size' => 20])</span><span>Pesanan</span></a>
                    <a href="{{ route('customer.notifications') }}" @if($navActive === 'notif') aria-current="page" @endif aria-label="Notifikasi{{ $unreadNotif ? ', '.$unreadNotif.' belum dibaca' : '' }}"><span class="customer-radial__glyph">@include('layouts.component._icon', ['name' => 'bell', 'size' => 20])</span><span>Notifikasi @if($unreadNotif)<small>{{ $unreadNotif }} baru</small>@endif</span></a>
                    <a href="{{ route('customer.help') }}" @if(request()->routeIs('customer.help')) aria-current="page" @endif><span class="customer-radial__glyph">@include('layouts.component._icon', ['name' => 'help-circle', 'size' => 20])</span><span>Bantuan / FAQ</span></a>
                </div>
            </div>
        </details>
        <a href="{{ route('customer.profile') }}" class="customer-nav__item {{ $navActive === 'profil' ? 'is-active' : '' }}" @if($navActive === 'profil') aria-current="page" @endif>
            <span class="customer-nav__icon">@include('layouts.component._icon', ['name' => 'user', 'size' => 24])</span><span class="customer-nav__label">Profil</span>
        </a>
    </div>
</nav>
<style>
.customer-nav,.customer-nav *{box-sizing:border-box}.customer-nav{position:fixed;inset:auto 0 0;z-index:999;margin:0;padding:0 0 env(safe-area-inset-bottom, 0px);background:rgba(255,252,249,.96);border-top:1px solid #f0dfe1;box-shadow:0 -4px 24px rgba(84,5,31,.08);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);font-family:'Plus Jakarta Sans',system-ui,sans-serif;line-height:1}.customer-nav__inner{max-width:620px;height:68px;margin:0 auto;padding:0 4px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr))}.customer-nav__item{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:0;height:68px;padding:4px 0;gap:5px;color:#6b5960;text-decoration:none;line-height:1;-webkit-tap-highlight-color:transparent;transition:color .2s ease,transform .2s ease}.customer-nav__item.is-active{color:#85092d}.customer-nav__item.is-active .customer-nav__icon{background:#f6e6e8}.customer-nav__icon{position:relative;display:flex;align-items:center;justify-content:center;width:38px;height:30px;border-radius:12px;flex:none;transition:background .2s ease}.customer-nav__icon svg{display:block;width:22px;height:22px;flex:none}.customer-nav__label{display:block;margin:0;padding:0;font-size:10px;font-weight:700;line-height:12px;white-space:nowrap}.customer-nav__badge{position:absolute;top:-5px;right:-4px;display:flex;align-items:center;justify-content:center;min-width:17px;height:17px;padding:0 3px;border:2px solid #fff;border-radius:99px;background:#85092d;color:#fff;font-size:9px;font-weight:800;line-height:1}.customer-nav a:focus-visible{outline:3px solid #bc5b75;outline-offset:-3px;border-radius:12px}.customer-nav__item:active{transform:scale(.95)}body{padding-bottom:calc(96px + env(safe-area-inset-bottom, 0px))}@media(max-width:700px){.customer-nav{inset:auto auto calc(10px + env(safe-area-inset-bottom, 0px)) 50%;transform:translateX(-50%);width:min(620px,calc(100% - 24px));padding:0;border:1px solid rgba(139,78,89,.2);border-radius:24px;background:rgba(255,252,249,.94);box-shadow:0 10px 32px rgba(84,5,31,.14)}}@media(max-width:350px){.customer-nav__label{font-size:9px}.customer-nav__inner{padding:0 2px}}@media(prefers-reduced-motion:reduce){.customer-nav__item,.customer-nav__icon{transition:none}.customer-nav__item:active{transform:none}}

.customer-radial{position:static;min-width:0}.customer-radial>summary{list-style:none;cursor:pointer}.customer-radial>summary::-webkit-details-marker{display:none}.customer-radial[open]>summary .customer-nav__icon{background:#f6e6e8;color:#85092d}.customer-radial__panel{position:absolute;bottom:calc(100% + 12px);left:50%;transform:translateX(-50%);width:min(340px,calc(100vw - 32px));padding:18px;border:1px solid #ead9d6;border-radius:30px;background:rgba(255,252,249,.97);box-shadow:0 15px 42px rgba(65,22,32,.2);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);line-height:1.4}.customer-radial__heading{display:flex;justify-content:space-between;align-items:baseline;gap:8px;margin:0 4px 12px;color:#351d26;font-size:.9rem}.customer-radial__heading span{color:#785e64;font-size:.72rem}.customer-radial__links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.customer-radial__links a{display:flex;align-items:center;gap:10px;min-width:0;min-height:64px;padding:10px;border:1px solid #f0dfdc;border-radius:20px;background:#fff8f5;color:#4a2931;text-decoration:none;font-size:.82rem;font-weight:700;transition:background .2s ease,transform .2s ease}.customer-radial__links a:hover,.customer-radial__links a[aria-current]{background:#f3e3e1;color:#75102e}.customer-radial__links a:active{transform:scale(.98)}.customer-radial__glyph{display:grid;place-items:center;width:32px;height:32px;border-radius:50%;background:#f3e3e1;color:#85092d;flex:none}.customer-radial__links small{display:block;color:#785e64;font-size:.69rem;font-weight:600;margin-top:3px}.customer-nav summary:focus-visible{outline:3px solid #bc5b75;outline-offset:-3px;border-radius:12px}.customer-radial[open] .customer-radial__panel{animation:customer-menu-enter .2s ease-out}@keyframes customer-menu-enter{from{opacity:0;transform:translate(-50%,8px) scale(.98)}to{opacity:1;transform:translate(-50%,0) scale(1)}}@media(max-height:440px){.customer-radial__panel{max-height:calc(100dvh - 90px);overflow:auto}}@media(prefers-reduced-motion:reduce){.customer-radial[open] .customer-radial__panel{animation:none}.customer-radial__links a{transition:none}}
</style>
<script>
(() => {
    const menu = document.querySelector('[data-customer-radial]');
    if (!menu) return;
    document.addEventListener('click', event => { if (menu.open && !menu.contains(event.target)) menu.open = false; });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && menu.open) { menu.open = false; menu.querySelector('summary').focus(); } });
})();
</script>
