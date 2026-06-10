<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Notifikasi Admin – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · NOTIFIKASI
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
.appbar__title { font-size: 1.15rem; font-weight: 800; color: var(--brand-900); line-height: 1.1; }

/* ═══════════════════════════════════════════════ CARD */
.card {
    background: var(--surface-raised); border-radius: var(--radius-card);
    border: 1px solid var(--surface-200); box-shadow: var(--shadow-card);
    margin-bottom: var(--space-4); overflow: hidden;
}
.card__pad { padding: var(--space-4); }
.card__title {
    display: flex; align-items: center; gap: var(--space-2);
    font-size: .92rem; font-weight: 800; color: var(--ink); margin-bottom: var(--space-3);
}
.card__title svg { color: var(--brand-500); }

/* ── Rows ── */
.row {
    display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-3);
    padding: var(--space-3); border: 1px solid var(--surface-200);
    border-radius: var(--radius-btn); margin-bottom: var(--space-2);
}
.row:last-child { margin-bottom: 0; }
.row__title { font-size: .88rem; font-weight: 800; color: var(--ink); }
.row__meta { font-size: .72rem; font-weight: 700; color: var(--ink-muted); margin-top: var(--space-1); line-height: 1.5; }

/* ── Status badge ── */
.badge {
    font-size: .66rem; font-weight: 800; padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-pill); align-self: flex-start; white-space: nowrap;
    background: color-mix(in srgb, var(--warning-500) 16%, var(--surface-raised));
    color: var(--warning-500);
}

/* ── Notification link rows ── */
.notif {
    display: flex; align-items: flex-start; gap: var(--space-3);
    padding: var(--space-3); border: 1px solid var(--surface-200);
    border-radius: var(--radius-btn); margin-bottom: var(--space-2);
    text-decoration: none; color: inherit;
    transition: transform .12s ease, background .15s ease;
}
.notif:last-of-type { margin-bottom: 0; }
.notif:active { transform: scale(.99); background: var(--surface-100); }
.notif.is-unread {
    background: var(--brand-100);
    border-color: color-mix(in srgb, var(--brand-500) 35%, var(--surface-200));
    border-left: 4px solid var(--brand-500);
}
.notif__ico {
    width: var(--space-9); height: var(--space-9); border-radius: var(--radius-btn);
    flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    background: var(--brand-100); color: var(--brand-500);
}
.notif__body { flex: 1; min-width: 0; }
.notif__title { font-size: .88rem; font-weight: 800; color: var(--ink); }
.notif__msg { font-size: .74rem; font-weight: 700; color: var(--ink-muted); margin-top: 2px; line-height: 1.5; }
.notif__time { font-size: .68rem; font-weight: 700; color: var(--ink-muted); text-align: right; flex-shrink: 0; white-space: nowrap; }

/* ── Empty state (designed) ── */
.empty-state { text-align: center; padding: var(--space-10) var(--space-5); }
.empty-state__ico {
    width: var(--space-14); height: var(--space-14); margin: 0 auto var(--space-3);
    border-radius: var(--radius-pill); background: var(--brand-100);
    display: flex; align-items: center; justify-content: center; color: var(--brand-500);
}
.empty-state__text { color: var(--ink-muted); font-size: .9rem; font-weight: 700; line-height: 1.5; }

/* ── Pagination ── */
.pagination-wrap { display: flex; justify-content: center; margin-top: var(--space-3); }
.pagination-wrap nav { font-size: .85rem; }
</style>
</head>
<body>

{{-- ══════════════ HEADER ══════════════ --}}
<header class="appbar">
    <a href="{{ \App\Support\BackUrl::resolve(request(), 'dashboard.admin') }}" class="icon-btn" aria-label="Kembali">
        @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
    </a>
    <h1 class="appbar__title">Notifikasi Admin</h1>
</header>

<main class="wrap">

    {{-- ══════════════ ANTRIAN OPERASIONAL ══════════════ --}}
    <section class="card reveal d1">
        <div class="card__pad">
            <div class="card__title">
                @include('layouts.component._icon', ['name' => 'orders', 'size' => 20])
                Antrian Operasional Terbaru
            </div>
            @forelse($notifikasiOperasional as $order)
                <div class="row">
                    <div>
                        <div class="row__title">{{ $order->order_code }} · {{ $order->service->name ?? 'Layanan' }}</div>
                        <div class="row__meta">{{ $order->customer->name ?? 'Customer' }} · {{ $order->created_at->translatedFormat('d M Y H:i') }}</div>
                    </div>
                    <span class="badge">{{ $order->status_label }}</span>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-state__ico" aria-hidden="true">
                        @include('layouts.component._icon', ['name' => 'orders', 'size' => 24])
                    </div>
                    <p class="empty-state__text">Belum ada aktivitas order.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ══════════════ NOTIFIKASI SISTEM ══════════════ --}}
    <section class="card reveal d2">
        <div class="card__pad">
            <div class="card__title">
                @include('layouts.component._icon', ['name' => 'notifikasi', 'size' => 20])
                Notifikasi Sistem
            </div>
            @forelse($notifications as $notif)
                <a href="{{ isset($notif->data['order_id']) ? route('admin.orders') : '#' }}" class="notif {{ is_null($notif->read_at) ? 'is-unread' : '' }}">
                    <span class="notif__ico" aria-hidden="true">
                        @include('layouts.component._icon', ['name' => 'notifikasi', 'size' => 16])
                    </span>
                    <div class="notif__body">
                        <div class="notif__title">{{ $notif->data['title'] ?? 'Notifikasi' }}</div>
                        <div class="notif__msg">{{ $notif->data['message'] ?? '-' }}</div>
                    </div>
                    <div class="notif__time">{{ $notif->created_at->diffForHumans() }}</div>
                </a>
            @empty
                <div class="empty-state">
                    <div class="empty-state__ico" aria-hidden="true">
                        @include('layouts.component._icon', ['name' => 'notifikasi', 'size' => 24])
                    </div>
                    <p class="empty-state__text">Belum ada notifikasi sistem.<br>Notifikasi baru akan muncul di sini.</p>
                </div>
            @endforelse

            @if($notifications->hasPages())
            <div class="pagination-wrap">
                {{ $notifications->links() }}
            </div>
            @endif
        </div>
    </section>

</main>

@include('layouts.component.admin._navbar_admin', ['active' => ''])

</body>
</html>
