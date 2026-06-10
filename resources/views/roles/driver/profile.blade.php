<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Profil Kurir – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · DRIVER · PROFIL
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

/* ── Profile Card ── */
.profile-card {
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-card);
    padding: var(--space-5);
    text-align: center;
    margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card);
}
.profile-card__avatar-wrap {
    width: 80px; height: 80px;
    border-radius: var(--radius-pill);
    border: 3px solid var(--brand-500);
    margin: 0 auto var(--space-3);
    display: flex; align-items: center; justify-content: center;
    position: relative;
}
.profile-card__avatar {
    width: 100%; height: 100%;
    object-fit: cover;
    border-radius: var(--radius-pill);
}
.profile-card__status {
    position: absolute;
    bottom: -2px; right: -4px;
    background: var(--success-500);
    color: var(--surface-raised);
    font-size: .52rem;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: var(--radius-pill);
    border: 2px solid var(--surface-raised);
    text-transform: uppercase;
    letter-spacing: .3px;
}
.profile-card__name { font-weight: 800; font-size: 1.2rem; color: var(--ink); margin-bottom: 2px; }
.profile-card__id { font-size: .76rem; font-weight: 600; color: var(--ink-muted); }

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
    text-align: center;
    box-shadow: var(--shadow-card);
}
.stat-card__icon {
    width: var(--space-8); height: var(--space-8);
    border-radius: var(--radius-btn);
    background: var(--brand-100);
    color: var(--brand-500);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto var(--space-2);
}
.stat-card__icon--star { background: color-mix(in srgb, var(--warning-500) 16%, transparent); color: var(--warning-500); }
.stat-card__num { font-weight: 800; font-size: 1.35rem; color: var(--brand-500); line-height: 1; }
.stat-card__num--amber { color: var(--warning-500); }
.stat-card__label {
    font-size: .64rem; font-weight: 700; color: var(--ink-muted);
    text-transform: uppercase; letter-spacing: .3px; margin-top: var(--space-1);
}
.stat-card__sub { font-size: .68rem; font-weight: 700; color: var(--surface-400); margin-top: 2px; }

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
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.menu-card__icon--blue { background: var(--brand-100); color: var(--brand-500); }
.menu-card__icon--green { background: color-mix(in srgb, var(--success-500) 14%, transparent); color: var(--success-500); }
.menu-card__icon--amber { background: color-mix(in srgb, var(--warning-500) 16%, transparent); color: var(--warning-500); }
.menu-card__body { flex: 1; min-width: 0; }
.menu-card__label { font-weight: 700; font-size: .9rem; color: var(--ink); }
.menu-card__sub { font-size: .72rem; font-weight: 500; color: var(--ink-muted); margin-top: 1px; }
.menu-card__arrow { color: var(--ink-muted); display: flex; flex-shrink: 0; }

/* ── Performance Chart ── */
.perf-card {
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-card);
    padding: var(--space-4);
    margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card);
}
.perf-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-4);
}
.perf-card__title { font-weight: 700; font-size: .88rem; color: var(--ink); }
.perf-card__badge {
    font-size: .64rem;
    font-weight: 800;
    padding: 3px 9px;
    border-radius: var(--radius-pill);
    background: color-mix(in srgb, var(--success-500) 12%, transparent);
    color: var(--success-500);
}
.perf-card__chart {
    display: flex;
    align-items: flex-end;
    gap: var(--space-2);
    height: 80px;
    padding-top: 4px;
}
.perf-card__bar {
    flex: 1;
    border-radius: 4px 4px 0 0;
    background: var(--brand-100);
    position: relative;
    min-height: 8px;
    transition: height .3s;
}
.perf-card__bar--today { background: var(--brand-500); }
.perf-card__labels { display: flex; gap: var(--space-2); margin-top: var(--space-2); }
.perf-card__day {
    flex: 1;
    text-align: center;
    font-size: .58rem;
    font-weight: 700;
    color: var(--ink-muted);
    text-transform: uppercase;
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
</style>
</head>
<body>

@php
    $driver = auth()->user();
    $totalAntar = \App\Models\Order::where('driver_id', $driver->id)->where('status','selesai')->count();
    $antarBulanIni = \App\Models\Order::where('driver_id', $driver->id)->where('status','selesai')->whereMonth('updated_at', now()->month)->count();
    $unreadNotif = $driver->unreadNotifications->count();
@endphp

<header class="profile-header reveal d1">
    <div class="profile-header__left">
        <a href="{{ route('driver.dashboard') }}" class="profile-header__back" aria-label="Kembali">
            @include('layouts.component._icon', ['name' => 'back', 'size' => 16, 'label' => 'Kembali'])
        </a>
        <img src="{{ $driver->avatar ? asset('storage/'.$driver->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($driver->name).'&background=0d6fb8&color=fff&size=72' }}"
             alt="{{ $driver->name }}" class="profile-header__avatar">
        <span class="profile-header__title">Profil Kurir</span>
    </div>
    <a href="{{ route('driver.notifications') }}" class="profile-header__notif" aria-label="Notifikasi">
        @include('layouts.component._icon', ['name' => 'notif', 'size' => 20, 'label' => 'Notifikasi'])
        @if($unreadNotif > 0)
            <span class="profile-header__badge">{{ $unreadNotif }}</span>
        @endif
    </a>
</header>

<div class="page-body">

    {{-- Profile Card --}}
    <div class="profile-card reveal d2">
        <div class="profile-card__avatar-wrap">
            <img src="{{ $driver->avatar ? asset('storage/'.$driver->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($driver->name).'&background=0d6fb8&color=fff&size=176' }}"
                 alt="{{ $driver->name }}" class="profile-card__avatar">
            <span class="profile-card__status">Aktif</span>
        </div>
        <div class="profile-card__name">{{ $driver->name }}</div>
        <div class="profile-card__id">ID Kurir: LK-{{ str_pad($driver->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid reveal d3">
        <div class="stat-card">
            <div class="stat-card__icon">
                @include('layouts.component._icon', ['name' => 'truck', 'size' => 16])
            </div>
            <div class="stat-card__num">{{ number_format($totalAntar) }}</div>
            <div class="stat-card__label">Total Antaran</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--star">
                @include('layouts.component._icon', ['name' => 'star', 'size' => 16])
            </div>
            <div class="stat-card__num stat-card__num--amber">{{ $antarBulanIni }}</div>
            <div class="stat-card__label">Bulan Ini</div>
            <div class="stat-card__sub">Antaran</div>
        </div>
    </div>

    {{-- Menu --}}
    <div class="menu-card reveal d4">
        <a href="{{ route('driver.orders') }}" class="menu-card__item">
            <div class="menu-card__icon menu-card__icon--blue">
                @include('layouts.component._icon', ['name' => 'tugas', 'size' => 20])
            </div>
            <div class="menu-card__body">
                <div class="menu-card__label">Riwayat Tugas</div>
                <div class="menu-card__sub">Lihat semua tugas jemput dan antar</div>
            </div>
            <span class="menu-card__arrow">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
        </a>
        <a href="{{ route('driver.report') }}" class="menu-card__item">
            <div class="menu-card__icon menu-card__icon--green">
                @include('layouts.component._icon', ['name' => 'report', 'size' => 20])
            </div>
            <div class="menu-card__body">
                <div class="menu-card__label">Lapor Kendala</div>
                <div class="menu-card__sub">Laporkan masalah atau kendala di lapangan</div>
            </div>
            <span class="menu-card__arrow">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
        </a>
        <a href="{{ route('driver.help') }}" class="menu-card__item">
            <div class="menu-card__icon menu-card__icon--amber">
                @include('layouts.component._icon', ['name' => 'bantuan', 'size' => 20])
            </div>
            <div class="menu-card__body">
                <div class="menu-card__label">Bantuan</div>
                <div class="menu-card__sub">Pusat bantuan & panduan kurir</div>
            </div>
            <span class="menu-card__arrow">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
        </a>
    </div>

    {{-- Performance Chart --}}
    <div class="perf-card reveal d5">
        <div class="perf-card__header">
            <span class="perf-card__title">Performa Minggu Ini</span>
            @if($antarBulanIni > 0)
                <span class="perf-card__badge">+{{ $antarBulanIni }} bulan ini</span>
            @endif
        </div>
        @php
            $days = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];
            $weekData = [];
            for($i=6; $i>=0; $i--) {
                $date = now()->subDays($i);
                $count = \App\Models\Order::where('driver_id', $driver->id)
                    ->where('status','selesai')
                    ->whereDate('updated_at', $date)
                    ->count();
                $weekData[] = $count;
            }
            $maxVal = max(max($weekData), 1);
        @endphp
        <div class="perf-card__chart">
            @foreach($weekData as $idx => $val)
                <div class="perf-card__bar {{ $idx === 6 ? 'perf-card__bar--today' : '' }}" style="height:{{ max(($val / $maxVal) * 100, 8) }}%;"></div>
            @endforeach
        </div>
        <div class="perf-card__labels">
            @foreach($days as $d)
                <span class="perf-card__day">{{ $d }}</span>
            @endforeach
        </div>
    </div>

    {{-- Logout --}}
    <form method="POST" action="{{ route('logout') }}" id="form-logout" class="reveal d6">
        @csrf
        <button type="button" class="btn-logout" id="btn-logout">
            @include('layouts.component._icon', ['name' => 'logout', 'size' => 16])
            Keluar Akun
        </button>
    </form>

</div>

@include('layouts.component.driver._navbar_driver', ['active' => 'profil'])
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
