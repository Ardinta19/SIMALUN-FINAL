<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Lacak Tugas – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · DRIVER · LACAK TUGAS (TRACKING)
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
.wrap { max-width: 520px; margin: 0 auto; padding: 0 var(--space-4); }

/* ── Entrance animation (pure CSS — transform/opacity only) ── */
@keyframes riseIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.reveal { opacity: 0; animation: riseIn .5s cubic-bezier(.22,.61,.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}
.d4{animation-delay:.22s}.d5{animation-delay:.28s}.d6{animation-delay:.34s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ═══════════════════════════════════════════════ FIXED BACK BUTTON */
.back-fab {
    position: fixed;
    top: max(env(safe-area-inset-top, 0px), var(--space-3));
    left: var(--space-4);
    z-index: 50;
    width: var(--space-11); height: var(--space-11);
    border-radius: var(--radius-pill);
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    box-shadow: var(--shadow-card);
    display: flex; align-items: center; justify-content: center;
    color: var(--brand-900); text-decoration: none;
    transition: transform .12s ease;
}
.back-fab:active { transform: scale(.94); }

/* ═══════════════════════════════════════════════ HEADER */
.appbar {
    max-width: 520px; margin: 0 auto;
    padding: max(env(safe-area-inset-top, 0px), var(--space-3)) var(--space-4) var(--space-4);
    padding-left: calc(var(--space-11) + var(--space-4) + var(--space-3));
    min-height: calc(var(--space-11) + var(--space-3));
    display: flex; flex-direction: column; justify-content: center;
}
.appbar__title { font-size: 1.15rem; font-weight: 800; color: var(--brand-900); line-height: 1.15; }
.appbar__subtitle { font-size: .72rem; font-weight: 700; color: var(--ink-muted); margin-top: 2px; }

/* ═══════════════════════════════════════════════ OVERVIEW CARD */
.overview-card {
    background: var(--surface-raised); border-radius: var(--radius-card);
    border: 1px solid var(--surface-200); box-shadow: var(--shadow-card);
    padding: var(--space-4); margin-bottom: var(--space-3);
}
.overview-card__title { font-size: .9rem; font-weight: 800; color: var(--ink); margin-bottom: var(--space-3); }
.overview-card__grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-2); }
.overview-card__stat {
    background: var(--surface-100); border-radius: var(--radius-btn);
    padding: var(--space-3); text-align: center;
}
.overview-card__stat-num { font-size: 1.35rem; font-weight: 800; color: var(--brand-500); line-height: 1; }
.overview-card__stat-label { font-size: .68rem; font-weight: 700; color: var(--ink-muted); margin-top: var(--space-1); }

/* ═══════════════════════════════════════════════ ACTIVE TASK CARD */
.active-task {
    background: var(--surface-raised); border-radius: var(--radius-card);
    border: 1px solid var(--surface-200); box-shadow: var(--shadow-card);
    overflow: hidden; margin-bottom: var(--space-3);
}
.active-task__header {
    display: flex; align-items: center; justify-content: space-between; gap: var(--space-2);
    padding: var(--space-3) var(--space-4);
    border-bottom: 1px solid var(--surface-100);
}
.active-task__title {
    display: inline-flex; align-items: center; gap: var(--space-2);
    font-family: ui-monospace, 'Courier New', monospace;
    font-size: .95rem; font-weight: 800; color: var(--brand-900); letter-spacing: .5px;
}
.active-task__title svg { color: var(--accent-500); }
.active-task__badge {
    font-size: .64rem; font-weight: 800; padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-pill); text-transform: uppercase; letter-spacing: .4px;
    white-space: nowrap; flex-shrink: 0;
}
.active-task__badge--pickup   { background: color-mix(in srgb, var(--warning-500) 16%, var(--surface-raised)); color: var(--warning-500); }
.active-task__badge--delivery { background: color-mix(in srgb, var(--success-500) 16%, var(--surface-raised)); color: var(--success-500); }

.active-task__body { padding: var(--space-4); }
.active-task__customer { font-size: .95rem; font-weight: 800; color: var(--ink); margin-bottom: var(--space-1); }
.active-task__address { font-size: .82rem; font-weight: 600; color: var(--ink-muted); line-height: 1.5; margin-bottom: var(--space-3); }
.active-task__meta { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.active-task__chip {
    display: inline-flex; align-items: center; gap: var(--space-1);
    background: var(--surface-100); color: var(--ink-muted);
    border-radius: var(--radius-pill); padding: var(--space-1) var(--space-3);
    font-size: .7rem; font-weight: 800;
}
.active-task__actions {
    display: flex; gap: var(--space-2);
    padding: var(--space-3) var(--space-4);
    background: var(--surface-100);
}
.active-task__btn {
    flex: 1; min-height: var(--space-10);
    display: inline-flex; align-items: center; justify-content: center; gap: var(--space-1);
    border-radius: var(--radius-btn); border: 1px solid transparent;
    font-family: var(--font); font-size: .78rem; font-weight: 800;
    cursor: pointer; text-decoration: none; transition: transform .12s ease;
}
.active-task__btn:active { transform: scale(.97); }
.active-task__btn--primary { background: var(--brand-500); color: var(--surface-raised); border-color: var(--brand-500); }
.active-task__btn--wa {
    background: color-mix(in srgb, var(--success-500) 14%, var(--surface-raised));
    color: var(--success-500); border-color: color-mix(in srgb, var(--success-500) 28%, transparent);
}

/* ═══════════════════════════════════════════════ EMPTY STATE (designed) */
.empty-state { text-align: center; padding: var(--space-12) var(--space-5); }
.empty-state__ico {
    width: var(--space-16); height: var(--space-16); margin: 0 auto var(--space-4);
    border-radius: var(--radius-pill); background: var(--brand-100);
    display: flex; align-items: center; justify-content: center; color: var(--brand-500);
}
.empty-state__title { font-size: 1rem; font-weight: 800; color: var(--ink); margin-bottom: var(--space-2); }
.empty-state__text { color: var(--ink-muted); font-size: .88rem; font-weight: 600; line-height: 1.5; }
</style>
</head>
<body>

@php
    $driver = auth()->user();
    $activeOrders = \App\Models\Order::where('driver_id', $driver->id)
        ->whereIn('status', ['dijemput', 'dikirim'])
        ->with(['customer', 'customerAddress'])
        ->latest()
        ->get();
    $selesaiHariIni = \App\Models\Order::where('driver_id', $driver->id)
        ->where('status', 'selesai')
        ->whereDate('updated_at', today())
        ->count();
@endphp

<a href="{{ route('driver.dashboard') }}" class="back-fab" aria-label="Kembali">
    @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
</a>

<header class="appbar reveal d1">
    <div class="appbar__title">Lacak Tugas</div>
    <div class="appbar__subtitle">Status penjemputan &amp; pengiriman</div>
</header>

<main class="wrap">

    {{-- Ringkasan --}}
    <div class="overview-card reveal d2">
        <div class="overview-card__title">Ringkasan Hari Ini</div>
        <div class="overview-card__grid">
            <div class="overview-card__stat">
                <div class="overview-card__stat-num">{{ $activeOrders->where('status', 'dijemput')->count() }}</div>
                <div class="overview-card__stat-label">Pickup Aktif</div>
            </div>
            <div class="overview-card__stat">
                <div class="overview-card__stat-num">{{ $activeOrders->where('status', 'dikirim')->count() }}</div>
                <div class="overview-card__stat-label">Delivery Aktif</div>
            </div>
            <div class="overview-card__stat">
                <div class="overview-card__stat-num">{{ $selesaiHariIni }}</div>
                <div class="overview-card__stat-label">Selesai Hari Ini</div>
            </div>
            <div class="overview-card__stat">
                <div class="overview-card__stat-num">{{ $activeOrders->count() }}</div>
                <div class="overview-card__stat-label">Total Aktif</div>
            </div>
        </div>
    </div>

    {{-- Daftar tugas aktif --}}
    @forelse($activeOrders as $i => $order)
    <article class="active-task reveal d{{ min($i + 3, 6) }}">
        <div class="active-task__header">
            <span class="active-task__title">
                @include('layouts.component._icon', ['name' => 'tugas', 'size' => 16])
                #{{ strtoupper($order->order_code) }}
            </span>
            <span class="active-task__badge {{ $order->status === 'dijemput' ? 'active-task__badge--pickup' : 'active-task__badge--delivery' }}">
                {{ $order->status === 'dijemput' ? 'Jemput' : 'Antar' }}
            </span>
        </div>
        <div class="active-task__body">
            <div class="active-task__customer">{{ $order->customer->name ?? 'Customer' }}</div>
            <div class="active-task__address">{{ $order->customerAddress?->full_address ?? $order->address ?? '-' }}</div>
            <div class="active-task__meta">
                <span class="active-task__chip">
                    @include('layouts.component._icon', ['name' => 'laundry', 'size' => 16])
                    {{ $order->weight_estimate }} kg
                </span>
                @if($order->pickup_date)
                <span class="active-task__chip">
                    @include('layouts.component._icon', ['name' => 'date', 'size' => 16])
                    {{ $order->pickup_date->format('d/m') }}
                </span>
                @endif
                <span class="active-task__chip">
                    @include('layouts.component._icon', ['name' => 'time', 'size' => 16])
                    {{ ucfirst($order->pickup_time ?? '-') }}
                </span>
            </div>
        </div>
        <div class="active-task__actions">
            @if($order->customer?->phone)
            <a href="{{ \App\Support\WhatsApp::link($order->customer->phone, \App\Support\WhatsApp::customerMessage($order, $order->status === 'dijemput' ? 'pickup' : 'delivery')) }}" target="_blank" rel="noopener" class="active-task__btn active-task__btn--wa">
                @include('layouts.component._icon', ['name' => 'whatsapp', 'size' => 16])
                WA
            </a>
            @endif
            <a href="{{ route('driver.orders.show', $order) }}" class="active-task__btn active-task__btn--primary">
                Detail &amp; Aksi
                @include('layouts.component._icon', ['name' => 'forward', 'size' => 16])
            </a>
        </div>
    </article>
    @empty
    <div class="empty-state reveal d2">
        <div class="empty-state__ico" aria-hidden="true">
            @include('layouts.component._icon', ['name' => 'selesai', 'size' => 32])
        </div>
        <div class="empty-state__title">Tidak Ada Tugas Aktif</div>
        <p class="empty-state__text">Semua tugas sudah diselesaikan.<br>Kamu akan mendapat notifikasi saat ada penugasan baru.</p>
    </div>
    @endforelse

</main>

@include('layouts.component.driver._navbar_driver', ['active' => 'tugas'])

@php
    // Kirim lokasi GPS untuk tugas aktif paling baru (jemput/antar).
    $geoOrder = $activeOrders->first();
@endphp
@if($geoOrder)
    @include('layouts.component._driver_geo', ['locationUrl' => route('driver.orders.location', $geoOrder)])
@endif

</body>
</html>
