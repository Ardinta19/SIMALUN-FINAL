{{--
    Partial: _navbar_admin.blade.php
    Usage: @include('layouts.component.admin._navbar_admin')
           Parameter opsional `active` dipertahankan demi kompatibilitas, tetapi
           penanda halaman aktif kini dihitung dari rute sehingga tidak bisa
           salah sorot saat halaman baru ditambahkan.

    Isi: bilah navigasi bawah (semua ukuran layar) berisi tujuan utama admin.
    Halaman shell (layouts.admin) memakai sidebar penuh; partial ini dipakai
    halaman admin mandiri yang belum dimigrasikan ke shell.
--}}
@php
    $pendingCount = 0;
    $unreadCount = 0;
    try {
        $pendingCount = \App\Models\RentalOrderUnit::where('status', 'pending')->count();
        $unreadCount = auth()->user()?->unreadNotifications?->count() ?? 0;
    } catch (\Exception $e) {}

    $navItems = [
        ['key' => 'beranda', 'label' => 'Beranda', 'route' => 'dashboard.admin', 'match' => ['dashboard.admin', 'admin.dashboard'], 'icon' => 'home', 'badge' => null],
        ['key' => 'pesanan', 'label' => 'Pesanan', 'route' => 'admin.rental.orders', 'match' => ['admin.rental.orders', 'admin.rental.orders.show'], 'icon' => 'orders', 'badge' => $pendingCount ?: null],
        ['key' => 'jadwal', 'label' => 'Jadwal', 'route' => 'admin.rental.schedule', 'match' => ['admin.rental.schedule', 'admin.rental.schedule.daily-sheet'], 'icon' => 'calendar', 'badge' => null],
        ['key' => 'kas', 'label' => 'Buku kas', 'route' => 'admin.rental.cashbook.index', 'match' => ['admin.rental.cashbook.*'], 'icon' => 'finance', 'badge' => null],
        ['key' => 'notifikasi', 'label' => 'Notifikasi', 'route' => 'admin.notifications', 'match' => ['admin.notifications', 'admin.notifications.*'], 'icon' => 'bell', 'badge' => $unreadCount ?: null],
        ['key' => 'profil', 'label' => 'Profil', 'route' => 'admin.profile', 'match' => ['admin.profile'], 'icon' => 'user', 'badge' => null],
    ];
@endphp

<nav class="admin-nav" id="admin-nav" aria-label="Navigasi utama admin">
    <div class="admin-nav__inner">
        @foreach($navItems as $item)
            @php $isActive = request()->routeIs(...$item['match']); @endphp
            <a href="{{ route($item['route']) }}"
               class="admin-nav__item {{ $isActive ? 'is-active' : '' }}"
               @if($isActive) aria-current="page" @endif
               aria-label="{{ $item['label'] }}">
                <span class="admin-nav__icon">
                    @if($item['badge'])
                        <span class="admin-nav__badge">{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
                    @endif
                    @include('layouts.component._icon', ['name' => $item['icon'], 'size' => 22])
                </span>
                <span class="admin-nav__label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>

<style>
/* Self-contained (literal values) agar tampil identik di semua halaman admin
   mandiri, dengan padanan tema gelap dan tanpa animasi saat gerak dikurangi. */
.admin-nav {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 999;
    background: rgba(255, 250, 248, .97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid #f0dfe1;
    box-shadow: 0 -4px 24px rgba(60, 10, 25, .08);
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
html[data-theme="dark"] .admin-nav {
    background: rgba(29, 23, 27, .97);
    border-top-color: #3a2c32;
    box-shadow: 0 -4px 24px rgba(0, 0, 0, .5);
}
.admin-nav__inner {
    max-width: 620px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    min-height: 64px;
    padding: 0 6px;
}
.admin-nav__item {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    padding: 7px 0;
    text-decoration: none;
    color: #6f5a63;
    position: relative;
    transition: color .2s ease, transform .2s ease;
    -webkit-tap-highlight-color: transparent;
}
html[data-theme="dark"] .admin-nav__item { color: #c9b4bb; }
.admin-nav__item:active { transform: scale(.96); }
.admin-nav__item.is-active { color: #85092d; }
html[data-theme="dark"] .admin-nav__item.is-active { color: #f0b6c4; }
.admin-nav__item.is-active .admin-nav__icon svg { stroke-width: 2.6; }
.admin-nav__icon { position: relative; display: flex; align-items: center; justify-content: center; }
.admin-nav__icon svg { width: 22px; height: 22px; }
.admin-nav__badge {
    position: absolute;
    top: -6px; right: -9px;
    background: #b63050;
    color: #fff;
    font-size: .55rem;
    font-weight: 900;
    min-width: 16px; height: 16px;
    border-radius: 99px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px;
    border: 2px solid #fffaf8;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
html[data-theme="dark"] .admin-nav__badge { border-color: #1d171b; }
.admin-nav__label {
    font-size: .58rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .35px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
.admin-nav__item.is-active::after {
    content: '';
    position: absolute;
    bottom: 0;
    width: 5px; height: 5px;
    background: currentColor;
    border-radius: 50%;
}
@media (prefers-reduced-motion: reduce) {
    .admin-nav__item { transition: none; }
    .admin-nav__item:active { transform: none; }
}
</style>
