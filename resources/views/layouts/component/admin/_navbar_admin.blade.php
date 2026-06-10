{{--
    Partial: _navbar_admin.blade.php
    Usage: @include('layouts.component.admin._navbar_admin', ['active' => 'beranda'])
    Active options: beranda | pesanan | profil

    Visual style mirrors the Customer bottom nav (inline 22px line-icons,
    persistent labels, active dot). Active state is exact-match only — when
    `active` matches no key (absent/empty/invalid) NO item is highlighted
    (no default fallback). Colors/radius/shadow/spacing derive from canonical
    tokens (_tokens.blade.php), values identical to the Customer nav.
--}}
@php
    $navActive = isset($active) ? trim((string) $active) : null;

    $pendingCount = 0;
    $unreadCount  = 0;
    try {
        $pendingCount = \App\Models\Order::where('status', 'menunggu')->count();
        $unreadCount  = auth()->user()?->unreadNotifications?->count() ?? 0;
    } catch (\Exception $e) {}
@endphp

<nav class="admin-nav" id="admin-nav" aria-label="Navigasi utama">
    <div class="admin-nav__inner">

        {{-- Beranda --}}
        <a href="{{ route('dashboard.admin') }}"
           class="admin-nav__item {{ $navActive === 'beranda' ? 'is-active' : '' }}"
           @if($navActive === 'beranda') aria-current="page" @endif
           aria-label="Beranda">
            <span class="admin-nav__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </span>
            <span class="admin-nav__label">Beranda</span>
        </a>

        {{-- Pesanan --}}
        <a href="{{ route('admin.orders') }}"
           class="admin-nav__item {{ $navActive === 'pesanan' ? 'is-active' : '' }}"
           @if($navActive === 'pesanan') aria-current="page" @endif
           aria-label="Pesanan">
            <span class="admin-nav__icon">
                @if($pendingCount > 0)
                    <span class="admin-nav__badge">{{ $pendingCount > 9 ? '9+' : $pendingCount }}</span>
                @endif
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="2"/>
                    <line x1="9" y1="12" x2="15" y2="12"/>
                    <line x1="9" y1="16" x2="13" y2="16"/>
                </svg>
            </span>
            <span class="admin-nav__label">Pesanan</span>
        </a>

        {{-- Profil --}}
        <a href="{{ route('admin.profile') }}"
           class="admin-nav__item {{ $navActive === 'profil' ? 'is-active' : '' }}"
           @if($navActive === 'profil') aria-current="page" @endif
           aria-label="Profil">
            <span class="admin-nav__icon">
                @if($unreadCount > 0)
                    <span class="admin-nav__badge admin-nav__badge--success">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </span>
            <span class="admin-nav__label">Profil</span>
        </a>

    </div>
</nav>

<style>
/* Self-contained: literal values (no host-page token dependency) so the nav
   renders IDENTICALLY on every page — matches the Customer nav exactly. */
.admin-nav {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 999;
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid #e8f0fe;
    box-shadow: 0 -4px 24px rgba(0, 47, 92, 0.08);
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
.admin-nav__inner {
    max-width: 520px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    height: 64px;
    padding: 0 8px;
}
.admin-nav__item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    text-decoration: none;
    color: #94a3b8;
    position: relative;
    padding: 6px 0;
    transition: color 0.2s ease;
    -webkit-tap-highlight-color: transparent;
}
.admin-nav__item.is-active { color: #0077b6; }
.admin-nav__item.is-active .admin-nav__icon svg { stroke-width: 2.6; }
.admin-nav__icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.admin-nav__badge {
    position: absolute;
    top: -6px; right: -8px;
    background: #FF6B35;
    color: #fff;
    font-size: 0.55rem;
    font-weight: 900;
    min-width: 16px; height: 16px;
    border-radius: 99px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px;
    border: 2px solid #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.admin-nav__badge--success { background: #00C48C; }
.admin-nav__label {
    font-size: 0.6rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.admin-nav__item.is-active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    width: 4px; height: 4px;
    background: #0077b6;
    border-radius: 50%;
}
@media (prefers-reduced-motion: reduce) { .admin-nav__item { transition: none; } }
</style>
