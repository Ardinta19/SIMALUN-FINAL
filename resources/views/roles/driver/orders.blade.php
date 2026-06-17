<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Daftar Tugas – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · DRIVER · DAFTAR TUGAS
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

/* ═══════════════════════════════════════════════ HEADER */
.appbar {
    max-width: 520px; margin: 0 auto;
    display: flex; align-items: center; gap: var(--space-3);
    padding: max(env(safe-area-inset-top, 0px), var(--space-3)) var(--space-4) var(--space-4);
}
.icon-btn {
    width: var(--space-11); height: var(--space-11); border-radius: var(--radius-pill);
    background: var(--brand-100); border: 1px solid var(--surface-200);
    display: flex; align-items: center; justify-content: center;
    color: var(--brand-900); text-decoration: none; flex-shrink: 0;
    transition: transform .12s ease, background .15s ease;
}
.icon-btn:active { transform: scale(.94); background: var(--surface-100); }
.appbar__titles { min-width: 0; }
.appbar__title { font-size: 1.15rem; font-weight: 800; color: var(--brand-900); line-height: 1.1; }
.appbar__subtitle { font-size: .72rem; font-weight: 600; color: var(--ink-muted); margin-top: 2px; }

/* ═══════════════════════════════════════════════ SUMMARY ROW */
.filter-summary { display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-4); }
.filter-summary__count { font-size: .82rem; font-weight: 800; color: var(--ink); }
.filter-summary__badge {
    background: var(--brand-100); color: var(--brand-500);
    font-size: .68rem; font-weight: 800; padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-pill);
}

/* ═══════════════════════════════════════════════ TASK CARD */
.task-card {
    background: var(--surface-raised); border: 1px solid var(--surface-200);
    border-radius: var(--radius-card); margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card); overflow: hidden;
    transition: transform .12s ease;
}
.task-card:active { transform: scale(.995); }
.task-card__strip { height: 3px; width: 100%; }
.task-card__body { padding: var(--space-3) var(--space-4); }
.task-card__top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: var(--space-2); margin-bottom: var(--space-3);
}
.task-card__code { font-size: .75rem; font-weight: 800; color: var(--brand-500); letter-spacing: .3px; }
.task-card__customer { font-weight: 800; font-size: .95rem; color: var(--ink); margin-top: 2px; }
.task-card__badge {
    font-size: .62rem; font-weight: 800; padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-pill); text-transform: uppercase; white-space: nowrap;
    flex-shrink: 0; letter-spacing: .3px;
}
.task-card__badge--pickup { background: color-mix(in srgb, var(--warning-500) 16%, var(--surface-raised)); color: var(--warning-500); }
.task-card__badge--delivery { background: color-mix(in srgb, var(--success-500) 16%, var(--surface-raised)); color: var(--success-500); }

.task-card__address {
    font-size: .82rem; font-weight: 600; color: var(--ink); line-height: 1.5;
    margin-bottom: var(--space-3); display: flex; gap: var(--space-2); align-items: flex-start;
}
.task-card__address svg { flex-shrink: 0; margin-top: 2px; color: var(--surface-400); }

.task-card__meta { display: flex; gap: var(--space-2); align-items: center; margin-bottom: var(--space-3); flex-wrap: wrap; }
.task-card__chip {
    display: inline-flex; align-items: center; gap: var(--space-1);
    background: var(--surface-100); color: var(--ink); border-radius: var(--radius-pill);
    padding: var(--space-1) var(--space-3); font-size: .7rem; font-weight: 700;
}

.task-card__actions { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-2); }
.task-card__btn {
    min-height: var(--space-11); padding: var(--space-2); border-radius: var(--radius-btn);
    border: none; font-family: var(--font); font-weight: 800; font-size: .8rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    gap: var(--space-1); text-decoration: none; transition: transform .12s ease;
}
.task-card__btn:active { transform: scale(.96); }
.task-card__btn--primary { background: var(--brand-500); color: var(--surface-raised); }
.task-card__btn--outline { background: var(--surface-raised); color: var(--ink); border: 1.5px solid var(--surface-200); }
.task-card__btn--disabled { background: var(--surface-100); color: var(--surface-400); cursor: default; }

/* ── Quick action footer ── */
.task-card__quick {
    background: var(--surface-100); border-top: 1px solid var(--surface-200);
    padding: var(--space-3) var(--space-4); display: flex; align-items: center;
    justify-content: space-between; gap: var(--space-3);
}
.task-card__quick-label { font-size: .74rem; font-weight: 600; color: var(--ink); flex: 1; }
.task-card__quick-btn {
    min-height: var(--space-10); padding: 0 var(--space-4); border: none;
    border-radius: var(--radius-btn); font-family: var(--font); font-weight: 800;
    font-size: .74rem; cursor: pointer; white-space: nowrap; text-decoration: none;
    display: inline-flex; align-items: center; gap: var(--space-1);
    transition: transform .12s ease;
}
.task-card__quick-btn:active { transform: scale(.96); }
.task-card__quick-btn--confirm { background: var(--success-500); color: var(--surface-raised); }
.task-card__quick-btn--upload { background: var(--accent-500); color: var(--surface-raised); }

/* ── Empty state (designed) ── */
.empty-state { text-align: center; padding: var(--space-16) var(--space-5); }
.empty-state__ico {
    width: var(--space-16); height: var(--space-16); margin: 0 auto var(--space-4);
    border-radius: var(--radius-pill); background: var(--brand-100);
    display: flex; align-items: center; justify-content: center; color: var(--brand-500);
}
.empty-state__title { font-weight: 800; font-size: 1.05rem; color: var(--ink); margin-bottom: var(--space-2); }
.empty-state__text { color: var(--ink-muted); font-size: .88rem; font-weight: 600; line-height: 1.5; }

/* ── Pagination ── */
.pagination-wrap { display: flex; justify-content: center; padding: var(--space-3) 0; }
.pagination-wrap nav { font-size: .85rem; }
</style>
</head>
<body>

{{-- ══════════════ HEADER ══════════════ --}}
<header class="appbar">
    <a href="{{ route('driver.dashboard') }}" class="icon-btn" aria-label="Kembali ke dashboard">
        @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
    </a>
    <div class="appbar__titles">
        <h1 class="appbar__title">Daftar Tugas</h1>
        <div class="appbar__subtitle">Tugas jemput &amp; antar yang aktif</div>
    </div>
</header>

<main class="wrap">

    @if($pesanan->count() > 0)
    <div class="filter-summary reveal d1">
        <span class="filter-summary__count">{{ $pesanan->total() }} tugas aktif</span>
        <span class="filter-summary__badge">Hari ini</span>
    </div>
    @endif

    @php
        $stripColors = [
            'dijemput' => 'var(--brand-500)',
            'dikirim'  => 'var(--warning-500)',
            'siap'     => 'var(--success-500)',
            'menunggu' => 'var(--surface-400)',
        ];
    @endphp

    @forelse($pesanan as $order)
    @php $strip = $stripColors[$order->status] ?? 'var(--brand-500)'; @endphp
    <div class="task-card reveal d2">
        <div class="task-card__strip" style="background:{{ $strip }}"></div>
        <div class="task-card__body">
            <div class="task-card__top">
                <div>
                    <div class="task-card__code">#{{ strtoupper($order->order_code) }}</div>
                    <div class="task-card__customer">{{ $order->customer->name ?? 'Customer' }}</div>
                </div>
                <span class="task-card__badge {{ $order->status === 'dijemput' ? 'task-card__badge--pickup' : 'task-card__badge--delivery' }}">
                    {{ $order->status === 'dijemput' ? 'Jemput' : 'Antar' }}
                </span>
            </div>

            <div class="task-card__address">
                @include('layouts.component._icon', ['name' => 'location', 'size' => 16])
                <span>{{ Str::limit($order->customerAddress?->full_address ?? $order->address ?? '-', 70) }}</span>
            </div>

            <div class="task-card__meta">
                <span class="task-card__chip">{{ $order->weight_estimate }} kg</span>
                <span class="task-card__chip">{{ ucfirst($order->pickup_time ?? '-') }}</span>
                @if($order->pickup_date)
                <span class="task-card__chip">{{ $order->pickup_date->format('d/m') }}</span>
                @endif
            </div>

            <div class="task-card__actions">
                @if($order->customer?->phone)
                <a href="{{ \App\Support\WhatsApp::link($order->customer->phone, \App\Support\WhatsApp::customerMessage($order, $order->status === 'dijemput' ? 'pickup' : 'delivery')) }}"
                   target="_blank" rel="noopener" class="task-card__btn task-card__btn--outline">
                    @include('layouts.component._icon', ['name' => 'whatsapp', 'size' => 16])
                    Chat WA
                </a>
                @else
                <div class="task-card__btn task-card__btn--disabled">Tidak ada WA</div>
                @endif
                <a href="{{ route('driver.orders.show', $order) }}" class="task-card__btn task-card__btn--primary">
                    Detail
                    @include('layouts.component._icon', ['name' => 'forward', 'size' => 16])
                </a>
            </div>
        </div>

        @if($order->status === 'dijemput')
        <div class="task-card__quick">
            <span class="task-card__quick-label">Pakaian sudah dijemput?</span>
            <a href="{{ route('driver.orders.show', $order) }}" class="task-card__quick-btn task-card__quick-btn--confirm">
                @include('layouts.component._icon', ['name' => 'check', 'size' => 16])
                Konfirmasi
            </a>
        </div>
        @elseif($order->status === 'dikirim')
        <div class="task-card__quick">
            <span class="task-card__quick-label">Upload bukti di halaman detail</span>
            <a href="{{ route('driver.orders.show', $order) }}" class="task-card__quick-btn task-card__quick-btn--upload">
                @include('layouts.component._icon', ['name' => 'camera', 'size' => 16])
                Upload Bukti
            </a>
        </div>
        @endif
    </div>
    @empty
    <div class="empty-state reveal d1">
        <div class="empty-state__ico" aria-hidden="true">
            @include('layouts.component._icon', ['name' => 'success', 'size' => 32])
        </div>
        <div class="empty-state__title">Semua Tugas Selesai</div>
        <p class="empty-state__text">Belum ada tugas baru saat ini.<br>Kamu akan mendapat notifikasi saat ada penugasan.</p>
    </div>
    @endforelse

    @if($pesanan->hasPages())
    <div class="pagination-wrap">
        {{ $pesanan->links('vendor.pagination.token-simple') }}
    </div>
    @endif

</main>

@include('layouts.component.driver._navbar_driver', ['active' => 'tugas'])

</body>
</html>
