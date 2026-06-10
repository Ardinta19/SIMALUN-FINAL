<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Notifikasi Kurir – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · DRIVER · NOTIFIKASI
   Design System — token-only styling, Lucide icons, pure-CSS motion.
   All colors/radii/shadows/spacing reference canonical tokens
   emitted by layouts.component._tokens (mirrors tailwind.config.js).
═══════════════════════════════════════════════════════════ */
:root {
    /* Page-local aliases — only reference canonical tokens (no literals) */
    --ink:        var(--brand-900);
    --ink-muted:  var(--surface-400);
    --line:       var(--surface-200);
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
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}.d4{animation-delay:.22s}.d5{animation-delay:.28s}
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

/* ═══════════════════════════════════════════════ DATE GROUP */
.date-group { margin-bottom: var(--space-5); }
.date-label {
    display: flex; align-items: center; gap: var(--space-2);
    font-size: .7rem; font-weight: 800; color: var(--ink-muted);
    text-transform: uppercase; letter-spacing: .8px; margin-bottom: var(--space-3);
}
.date-label::after { content: ''; flex: 1; height: 1px; background: var(--line); }

/* ═══════════════════════════════════════════════ NOTIFICATION CARD */
.notif {
    display: flex; align-items: flex-start; gap: var(--space-3);
    background: var(--surface-raised); border: 1px solid var(--surface-200);
    border-radius: var(--radius-card); box-shadow: var(--shadow-card);
    padding: var(--space-3); margin-bottom: var(--space-2);
    text-decoration: none; color: inherit; position: relative; overflow: hidden;
    transition: transform .12s ease, background .15s ease;
}
.notif:active { transform: scale(.99); background: var(--surface-100); }
.notif.is-unread {
    background: var(--brand-100);
    border-color: color-mix(in srgb, var(--brand-500) 35%, var(--surface-200));
    border-left: 4px solid var(--brand-500);
}
.notif__ico {
    width: var(--space-10); height: var(--space-10); border-radius: var(--radius-btn);
    flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    background: var(--brand-100); color: var(--brand-500);
}
.notif__ico--accent  { background: color-mix(in srgb, var(--accent-500) 14%, var(--surface-raised));  color: var(--accent-500); }
.notif__ico--success { background: color-mix(in srgb, var(--success-500) 14%, var(--surface-raised)); color: var(--success-500); }
.notif__ico--warning { background: color-mix(in srgb, var(--warning-500) 16%, var(--surface-raised)); color: var(--warning-500); }
.notif__body { flex: 1; min-width: 0; }
.notif__title { font-size: .88rem; font-weight: 800; color: var(--ink); line-height: 1.3; }
.notif.is-unread .notif__title { color: var(--brand-900); }
.notif__msg {
    font-size: .76rem; font-weight: 700; color: var(--ink-muted); line-height: 1.45; margin-top: 2px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.notif__time { font-size: .68rem; font-weight: 800; color: var(--ink-muted); margin-top: var(--space-1); }
.notif.is-unread .notif__time { color: var(--brand-500); }
.notif__dot {
    width: var(--space-2); height: var(--space-2); border-radius: var(--radius-pill);
    background: var(--accent-500); flex-shrink: 0; margin-top: var(--space-1);
}

/* ── Empty state (designed) ── */
.empty-state { text-align: center; padding: var(--space-16) var(--space-5); }
.empty-state__ico {
    width: var(--space-16); height: var(--space-16); margin: 0 auto var(--space-4);
    border-radius: var(--radius-pill); background: var(--brand-100);
    display: flex; align-items: center; justify-content: center; color: var(--brand-500);
}
.empty-state__title { font-size: 1.05rem; font-weight: 800; color: var(--ink); margin-bottom: var(--space-1); }
.empty-state__text { color: var(--ink-muted); font-size: .85rem; font-weight: 700; line-height: 1.5; }

/* ── Pagination ── */
.pagination-wrap { display: flex; justify-content: center; padding: var(--space-2) 0; }
.pagination-wrap nav { font-size: .85rem; }
</style>
</head>
<body>

{{-- ══════════════ HEADER ══════════════ --}}
<header class="appbar reveal d1">
    <a href="{{ route('driver.dashboard') }}" class="icon-btn" aria-label="Kembali">
        @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
    </a>
    <h1 class="appbar__title">Notifikasi</h1>
</header>

<main class="wrap">

    @php
        // Conceptual type → Lucide glyph + tone class (token-based, no literals).
        $notifIcons = [
            'order_created'     => ['icon' => 'laundry',  'tone' => ''],
            'pickup_assigned'   => ['icon' => 'pickup',   'tone' => 'notif__ico--accent'],
            'washing_started'   => ['icon' => 'refresh',  'tone' => ''],
            'ready_to_deliver'  => ['icon' => 'success',  'tone' => 'notif__ico--success'],
            'delivered'         => ['icon' => 'delivery', 'tone' => 'notif__ico--success'],
            'payment_confirmed' => ['icon' => 'money',    'tone' => 'notif__ico--warning'],
        ];
    @endphp

    @if(isset($notifications) && $notifications->count() > 0)
        @php
            $grouped = $notifications->groupBy(function($n) {
                $d = $n->created_at;
                if ($d->isToday())     return 'Hari Ini';
                if ($d->isYesterday()) return 'Kemarin';
                return $d->translatedFormat('d F Y');
            });
        @endphp

        @foreach($grouped as $dateLabel => $group)
        <div class="date-group reveal {{ 'd'.(min($loop->iteration + 1, 5)) }}">
            <div class="date-label">{{ $dateLabel }}</div>
            @foreach($group as $notif)
            @php
                $type     = $notif->data['type'] ?? 'system';
                $icon     = $notifIcons[$type] ?? ['icon' => 'notif', 'tone' => ''];
                $orderId  = $notif->data['order_id'] ?? null;
                $href     = $orderId ? route('driver.orders.show', ['order' => $orderId, 'back' => route('driver.notifications')]) : '#';
                $isUnread = is_null($notif->read_at);
            @endphp
            <a href="{{ $href }}" class="notif {{ $isUnread ? 'is-unread' : '' }}">
                <span class="notif__ico {{ $icon['tone'] }}" aria-hidden="true">
                    @include('layouts.component._icon', ['name' => $icon['icon'], 'size' => 20])
                </span>
                <div class="notif__body">
                    <div class="notif__title">{{ $notif->data['title'] ?? 'Notifikasi' }}</div>
                    <div class="notif__msg">{{ $notif->data['message'] ?? '-' }}</div>
                    <div class="notif__time">{{ $notif->created_at->diffForHumans() }}</div>
                </div>
                @if($isUnread)<span class="notif__dot"></span>@endif
            </a>
            @endforeach
        </div>
        @endforeach

        <div class="pagination-wrap">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="empty-state reveal d2">
            <div class="empty-state__ico" aria-hidden="true">
                @include('layouts.component._icon', ['name' => 'notif', 'size' => 32])
            </div>
            <div class="empty-state__title">Belum Ada Notifikasi</div>
            <p class="empty-state__text">Info penugasan dan update pesanan akan muncul di sini.</p>
        </div>
    @endif

</main>

@include('layouts.component.driver._navbar_driver', ['active' => ''])

</body>
</html>
