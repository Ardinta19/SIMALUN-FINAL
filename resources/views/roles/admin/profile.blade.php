<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Profil Admin – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · PROFIL
   Design System — token-only styling, Lucide icons, pure-CSS motion.
   All colors/radii/shadows/spacing reference canonical tokens
   emitted by layouts.component._tokens (mirrors tailwind.config.js).
═══════════════════════════════════════════════════════════ */
:root {
    /* Page-local aliases — only reference canonical tokens (no literals) */
    --ink:        var(--brand-900);
    --ink-muted:  var(--surface-400);
    --line:       var(--surface-200);
    --line-soft:  var(--surface-100);
    --font:       'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
body {
    font-family: var(--font);
    font-size: 16px;
    background: var(--surface-50);
    color: var(--ink);
    min-height: 100vh;
    padding-bottom: calc(var(--space-20) + env(safe-area-inset-bottom, 0px));
    overflow-x: hidden;
}

/* ── Entrance animation (pure CSS — transform/opacity only) ── */
@keyframes riseIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.reveal { opacity: 0; animation: riseIn .5s cubic-bezier(.22,.61,.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}
.d4{animation-delay:.22s}.d5{animation-delay:.28s}.d6{animation-delay:.34s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ═══════════════════════════════════════════════ HEADER */
.profile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: max(env(safe-area-inset-top, 0px), var(--space-4)) var(--space-5) var(--space-3);
    max-width: 520px;
    margin: 0 auto;
}
.profile-header__left { display: flex; align-items: center; gap: var(--space-3); min-width: 0; }
.profile-header__back {
    width: var(--space-9); height: var(--space-9);
    border-radius: var(--radius-pill);
    background: var(--brand-100);
    border: 1px solid var(--surface-200);
    display: flex; align-items: center; justify-content: center;
    color: var(--brand-900);
    text-decoration: none;
    flex-shrink: 0;
    transition: transform .12s ease, background .15s ease;
}
.profile-header__back:active { transform: scale(.94); background: var(--surface-100); }
.profile-header__avatar {
    width: var(--space-9); height: var(--space-9);
    border-radius: var(--radius-pill);
    object-fit: cover;
    border: 2px solid var(--surface-200);
}
.profile-header__title { font-weight: 800; font-size: 1.05rem; color: var(--ink); }
.profile-header__notif {
    width: var(--space-10); height: var(--space-10);
    border-radius: var(--radius-pill);
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    display: flex; align-items: center; justify-content: center;
    color: var(--ink-muted);
    text-decoration: none;
    position: relative;
    box-shadow: var(--shadow-card);
    flex-shrink: 0;
}
.profile-header__badge {
    position: absolute;
    top: -2px; right: -2px;
    min-width: 16px; height: 16px;
    background: var(--accent-500);
    color: var(--surface-raised);
    border-radius: var(--radius-pill);
    font-size: .55rem;
    font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px;
    border: 2px solid var(--surface-50);
}

/* ═══════════════════════════════════════════════ BODY */
.page-body { max-width: 520px; margin: 0 auto; padding: var(--space-2) var(--space-4) var(--space-4); }

/* ── Alert ── */
.page-alert {
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-btn);
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: var(--space-3);
}
.page-alert--success {
    background: color-mix(in srgb, var(--success-500) 12%, transparent);
    color: var(--success-500);
    border: 1px solid color-mix(in srgb, var(--success-500) 28%, transparent);
}

/* ── Profile Card ── */
.profile-card {
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-card);
    padding: var(--space-5);
    margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card);
}
.profile-card__name { font-weight: 800; font-size: 1.25rem; color: var(--ink); line-height: 1.2; }
.profile-card__role { font-size: .8rem; font-weight: 600; color: var(--ink-muted); margin-top: var(--space-1); }
.profile-card__status {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin-top: var(--space-3);
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-pill);
    background: color-mix(in srgb, var(--success-500) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--success-500) 28%, transparent);
    font-size: .72rem;
    font-weight: 700;
    color: var(--success-500);
}
.profile-card__status-dot { width: 7px; height: 7px; border-radius: var(--radius-pill); background: var(--success-500); }

/* ── Stats ── */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-2);
    margin-bottom: var(--space-3);
}
.stat-card {
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-card);
    padding: var(--space-3);
    display: flex;
    align-items: center;
    gap: var(--space-3);
    box-shadow: var(--shadow-card);
}
.stat-card__icon {
    width: var(--space-10); height: var(--space-10);
    border-radius: var(--radius-btn);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.stat-card__icon--blue { background: var(--brand-100); color: var(--brand-500); }
.stat-card__icon--amber { background: color-mix(in srgb, var(--warning-500) 16%, transparent); color: var(--warning-500); }
.stat-card__num { font-weight: 800; font-size: 1.25rem; color: var(--ink); line-height: 1; }
.stat-card__label {
    font-size: .62rem; font-weight: 700; color: var(--ink-muted);
    text-transform: uppercase; letter-spacing: .3px; margin-top: var(--space-1);
}

/* ── Section Label ── */
.section-label {
    font-size: .68rem;
    font-weight: 800;
    color: var(--ink-muted);
    text-transform: uppercase;
    letter-spacing: .6px;
    margin: var(--space-5) 0 var(--space-3);
    padding-left: var(--space-1);
}

/* ── Menu ── */
.menu-card {
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-card);
    overflow: hidden;
    margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card);
}
.menu-card__item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border-bottom: 1px solid var(--line-soft);
    text-decoration: none;
    color: var(--ink);
    transition: background .12s;
}
.menu-card__item:last-child { border-bottom: none; }
.menu-card__item:active { background: var(--line-soft); }
.menu-card__icon {
    width: var(--space-9); height: var(--space-9);
    border-radius: var(--radius-btn);
    background: var(--brand-100);
    color: var(--brand-500);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.menu-card__body { flex: 1; min-width: 0; }
.menu-card__label { font-weight: 700; font-size: .9rem; color: var(--ink); }
.menu-card__sub { font-size: .72rem; font-weight: 500; color: var(--ink-muted); margin-top: 1px; }
.menu-card__arrow { color: var(--ink-muted); display: flex; flex-shrink: 0; }

/* ── Banner ── */
.ops-banner {
    background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-500) 100%);
    border-radius: var(--radius-card);
    padding: var(--space-4) var(--space-5);
    margin-bottom: var(--space-3);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-card);
}
.ops-banner::after {
    content: '';
    position: absolute;
    width: 100px; height: 100px;
    border-radius: var(--radius-pill);
    background: rgba(255,255,255,.05);
    top: -30px; right: -20px;
}
.ops-banner__title {
    font-weight: 800; font-size: 1rem; color: var(--surface-raised);
    line-height: 1.3; margin-bottom: var(--space-1); position: relative; z-index: 1;
}
.ops-banner__sub {
    font-size: .78rem; font-weight: 500; color: rgba(255,255,255,.72);
    position: relative; z-index: 1;
}

/* ── Logout ── */
.btn-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    width: 100%;
    padding: var(--space-3);
    border-radius: var(--radius-pill);
    border: 1.5px solid color-mix(in srgb, var(--danger-500) 25%, transparent);
    background: var(--surface-raised);
    color: var(--danger-500);
    font-family: var(--font);
    font-weight: 700;
    font-size: .9rem;
    cursor: pointer;
    transition: background .15s, transform .12s ease;
}
.btn-logout:active { background: color-mix(in srgb, var(--danger-500) 8%, transparent); transform: scale(.98); }

.version-text {
    text-align: center;
    margin-top: var(--space-3);
    font-size: .68rem;
    font-weight: 600;
    color: var(--ink-muted);
    letter-spacing: .3px;
}
</style>
</head>
<body>

@php
    $adminUnread = auth()->user()->unreadNotifications->count();
@endphp

<header class="profile-header reveal d1">
    <div class="profile-header__left">
        <a href="{{ \App\Support\BackUrl::resolve(request(), 'admin.dashboard') }}" class="profile-header__back" aria-label="Kembali">
            @include('layouts.component._icon', ['name' => 'back', 'size' => 16, 'label' => 'Kembali'])
        </a>
        <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=0d6fb8&color=fff&size=72' }}"
             alt="{{ $user->name }}" class="profile-header__avatar">
        <span class="profile-header__title">Profil Admin</span>
    </div>
    <a href="{{ route('admin.notifications') }}" class="profile-header__notif" aria-label="Notifikasi">
        @include('layouts.component._icon', ['name' => 'notif', 'size' => 20, 'label' => 'Notifikasi'])
        @if($adminUnread > 0)
            <span class="profile-header__badge">{{ $adminUnread }}</span>
        @endif
    </a>
</header>

<div class="page-body">

    @if(session('status') === 'profile-updated')
        <div class="page-alert page-alert--success reveal d1">Profil berhasil diperbarui.</div>
    @endif

    {{-- Profile Card --}}
    <div class="profile-card reveal d2">
        <div class="profile-card__name">{{ $user->name }}</div>
        <div class="profile-card__role">Administrator Utama &middot; {{ config('laundry.name', 'Azka Laundry') }}</div>
        <div class="profile-card__status">
            <span class="profile-card__status-dot"></span>
            Sistem Aktif
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid reveal d3">
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--blue">
                @include('layouts.component._icon', ['name' => 'truck', 'size' => 20])
            </div>
            <div>
                <div class="stat-card__num">{{ \App\Models\User::where('role','driver')->where('is_active',true)->count() }}</div>
                <div class="stat-card__label">Kurir Aktif</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--amber">
                @include('layouts.component._icon', ['name' => 'orders', 'size' => 20])
            </div>
            <div>
                <div class="stat-card__num">{{ \App\Models\Order::whereDate('created_at', today())->count() }}</div>
                <div class="stat-card__label">Pesanan Hari Ini</div>
            </div>
        </div>
    </div>

    {{-- Pengaturan Operasional --}}
    <div class="section-label reveal d4">Pengaturan Operasional</div>
    <div class="menu-card reveal d4">
        <a href="{{ route('admin.orders') }}" class="menu-card__item">
            <div class="menu-card__icon">
                @include('layouts.component._icon', ['name' => 'orders', 'size' => 20])
            </div>
            <div class="menu-card__body">
                <div class="menu-card__label">Manajemen Pesanan</div>
                <div class="menu-card__sub">Kelola status, tugaskan kurir</div>
            </div>
            <span class="menu-card__arrow">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
        </a>
        <a href="{{ route('admin.walkin.form') }}" class="menu-card__item">
            <div class="menu-card__icon">
                @include('layouts.component._icon', ['name' => 'add-user', 'size' => 20])
            </div>
            <div class="menu-card__body">
                <div class="menu-card__label">Order Walk-in</div>
                <div class="menu-card__sub">Buat pesanan pelanggan langsung</div>
            </div>
            <span class="menu-card__arrow">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
        </a>
        <a href="{{ route('admin.finance.index') }}" class="menu-card__item">
            <div class="menu-card__icon">
                @include('layouts.component._icon', ['name' => 'finance', 'size' => 20])
            </div>
            <div class="menu-card__body">
                <div class="menu-card__label">Laporan Keuangan</div>
                <div class="menu-card__sub">Rekap pemasukan dan pengeluaran</div>
            </div>
            <span class="menu-card__arrow">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
        </a>
        <a href="{{ route('admin.reports') }}" class="menu-card__item">
            <div class="menu-card__icon">
                @include('layouts.component._icon', ['name' => 'report', 'size' => 20])
            </div>
            <div class="menu-card__body">
                <div class="menu-card__label">Laporan Masalah</div>
                <div class="menu-card__sub">Kelola laporan bug & kendala</div>
            </div>
            <span class="menu-card__arrow">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
        </a>
    </div>

    {{-- Banner --}}
    <div class="ops-banner reveal d5">
        <div class="ops-banner__title">Optimalkan Operasi</div>
        <div class="ops-banner__sub">Cek performa outlet dan kelola semua dari sini</div>
    </div>

    {{-- Logout --}}
    <form method="POST" action="{{ route('logout') }}" id="form-logout" class="reveal d6">
        @csrf
        <button type="button" class="btn-logout" id="btn-logout">
            @include('layouts.component._icon', ['name' => 'logout', 'size' => 16])
            Keluar dari Akun
        </button>
    </form>
    <div class="version-text reveal d6">{{ config('laundry.name', 'Azka Laundry') }} v{{ config('laundry.version', '2.4.0') }} &middot; Admin Panel</div>

</div>

@include('layouts.component.admin._navbar_admin', ['active' => 'profil'])
@include('layouts.component._confirm_modal')

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', function() {
            showConfirmModal({
                title: 'Keluar dari Akun?',
                message: 'Kamu akan keluar dari sesi ini. Yakin ingin melanjutkan?',
                confirmText: 'Ya, Keluar',
                cancelText: 'Batal',
                type: 'danger',
                onConfirm: function() {
                    document.getElementById('form-logout').submit();
                }
            });
        });
    }
});
</script>

</body>
</html>
