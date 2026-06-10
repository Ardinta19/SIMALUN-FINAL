<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Voucher – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · VOUCHER PROMO (LIST)
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
    display: flex; align-items: center; justify-content: space-between;
    gap: var(--space-3);
    padding: max(env(safe-area-inset-top, 0px), var(--space-3)) var(--space-4) var(--space-4);
}
.appbar__left { display: flex; align-items: center; gap: var(--space-3); min-width: 0; }
.appbar__title { font-size: 1.15rem; font-weight: 800; color: var(--brand-900); line-height: 1.1; }
.pill-btn {
    display: inline-flex; align-items: center; gap: var(--space-1);
    min-height: var(--space-11); padding: 0 var(--space-4);
    border-radius: var(--radius-btn);
    font-family: var(--font); font-size: .82rem; font-weight: 800;
    text-decoration: none; border: 1px solid var(--brand-500);
    background: var(--brand-500); color: var(--surface-raised); cursor: pointer;
    flex-shrink: 0; transition: transform .12s ease;
}
.pill-btn:active { transform: scale(.96); }

/* ═══════════════════════════════════════════════ FLASH / TOAST */
.toast {
    display: flex; align-items: center; gap: var(--space-2);
    margin: 0 0 var(--space-4); padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-btn); font-size: .85rem; font-weight: 700;
    background: color-mix(in srgb, var(--success-500) 14%, var(--surface-raised));
    color: var(--success-500); border: 1px solid color-mix(in srgb, var(--success-500) 30%, transparent);
}
.toast svg { flex-shrink: 0; }
.toast--error {
    background: color-mix(in srgb, var(--danger-500) 12%, var(--surface-raised));
    color: var(--danger-500); border-color: color-mix(in srgb, var(--danger-500) 28%, transparent);
}

/* ═══════════════════════════════════════════════ VOUCHER CARD */
.v-card {
    background: var(--surface-raised); border-radius: var(--radius-card);
    border: 1px solid var(--surface-200); box-shadow: var(--shadow-card);
    padding: var(--space-4); margin-bottom: var(--space-3);
}
.v-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: var(--space-2); margin-bottom: var(--space-2);
}
.v-code {
    display: inline-flex; align-items: center; gap: var(--space-2);
    font-family: ui-monospace, 'Courier New', monospace;
    font-size: 1.05rem; font-weight: 800; color: var(--brand-900); letter-spacing: .5px;
}
.v-code svg { color: var(--accent-500); }
.v-status {
    font-size: .66rem; font-weight: 800; padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-pill); text-transform: uppercase; letter-spacing: .4px;
    white-space: nowrap; flex-shrink: 0;
}
.v-status--on  { background: color-mix(in srgb, var(--success-500) 16%, var(--surface-raised)); color: var(--success-500); }
.v-status--off { background: var(--surface-100); color: var(--ink-muted); }

.v-desc { font-size: .85rem; color: var(--ink-muted); font-weight: 600; margin-bottom: var(--space-3); line-height: 1.45; }

.v-meta {
    display: grid; grid-template-columns: repeat(2, 1fr);
    gap: var(--space-2) var(--space-4); margin-bottom: var(--space-3);
}
.v-meta__item span:first-child {
    display: block; font-size: .66rem; font-weight: 700;
    color: var(--ink-muted); text-transform: uppercase; letter-spacing: .3px;
}
.v-meta__item span:last-child { font-size: .85rem; font-weight: 800; color: var(--ink); }

.v-actions {
    display: flex; gap: var(--space-2);
    border-top: 1px dashed var(--surface-200); padding-top: var(--space-3);
}
.v-actions form { flex: 1; }
.v-btn {
    width: 100%; min-height: var(--space-10);
    display: inline-flex; align-items: center; justify-content: center; gap: var(--space-1);
    border: 1px solid var(--surface-200); background: var(--brand-100); color: var(--brand-900);
    border-radius: var(--radius-btn); font-family: var(--font);
    font-size: .8rem; font-weight: 800; cursor: pointer; transition: transform .12s ease;
}
.v-btn:active { transform: scale(.97); }
.v-btn--danger {
    background: color-mix(in srgb, var(--danger-500) 10%, var(--surface-raised));
    color: var(--danger-500); border-color: color-mix(in srgb, var(--danger-500) 24%, transparent);
}

/* ═══════════════════════════════════════════════ EMPTY STATE (designed) */
.empty-state { text-align: center; padding: var(--space-12) var(--space-5); }
.empty-state__ico {
    width: var(--space-16); height: var(--space-16); margin: 0 auto var(--space-4);
    border-radius: var(--radius-pill); background: var(--brand-100);
    display: flex; align-items: center; justify-content: center; color: var(--brand-500);
}
.empty-state__title { font-size: 1rem; font-weight: 800; color: var(--ink); margin-bottom: var(--space-2); }
.empty-state__text { color: var(--ink-muted); font-size: .88rem; font-weight: 600; line-height: 1.5; margin-bottom: var(--space-5); }

/* ═══════════════════════════════════════════════ PAGINATION */
.pager { margin-top: var(--space-4); }
.pager nav { display: flex; justify-content: center; }
</style>
</head>
<body>
<header class="appbar reveal d1">
    <div class="appbar__left">
        <x-back-button fallback="admin.dashboard" style="hero" :smart="false" />
        <h1 class="appbar__title">Voucher Promo</h1>
    </div>
    <a href="{{ route('admin.vouchers.create') }}" class="pill-btn" aria-label="{{ __('ui.buttons.create') }}">
        @include('layouts.component._icon', ['name' => 'plus', 'size' => 16])
        {{ __('ui.buttons.create') }}
    </a>
</header>

<main class="wrap">
    @if(session('success'))
    <div class="toast reveal" role="status">
        @include('layouts.component._icon', ['name' => 'success', 'size' => 20])
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="toast toast--error reveal" role="alert">
        @include('layouts.component._icon', ['name' => 'error', 'size' => 20])
        {{ session('error') }}
    </div>
    @endif

    @forelse($vouchers as $i => $v)
    <article class="v-card reveal d{{ min($i + 2, 6) }}">
        <div class="v-card__head">
            <span class="v-code">
                @include('layouts.component._icon', ['name' => 'voucher', 'size' => 20])
                {{ $v->code }}
            </span>
            <span class="v-status {{ $v->is_active ? 'v-status--on' : 'v-status--off' }}">
                {{ $v->is_active ? __('ui.states.active') : __('ui.states.inactive') }}
            </span>
        </div>

        <div class="v-desc">{{ $v->description }}</div>

        <div class="v-meta">
            <div class="v-meta__item">
                <span>Potongan</span>
                <span>{{ $v->display_value }}</span>
            </div>
            <div class="v-meta__item">
                <span>Min. Order</span>
                <span>{{ $v->min_order > 0 ? 'Rp '.number_format($v->min_order, 0, ',', '.') : 'Tanpa minimum' }}</span>
            </div>
            <div class="v-meta__item">
                <span>Pemakaian</span>
                <span>{{ $v->used_count }}{{ $v->usage_limit ? ' / '.$v->usage_limit : '' }}</span>
            </div>
            <div class="v-meta__item">
                <span>Berlaku sampai</span>
                <span>{{ $v->valid_until ? $v->valid_until->translatedFormat('d M Y') : 'Tanpa batas' }}</span>
            </div>
        </div>

        <div class="v-actions">
            <form method="POST" action="{{ route('admin.vouchers.toggle', $v) }}">
                @csrf @method('PATCH')
                <button type="submit" class="v-btn">
                    @include('layouts.component._icon', ['name' => $v->is_active ? 'close' : 'check', 'size' => 16])
                    {{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
            @if($v->used_count === 0)
                <form method="POST" action="{{ route('admin.vouchers.destroy', $v) }}"
                      onsubmit="return confirm('Hapus voucher {{ $v->code }}?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="v-btn v-btn--danger">
                        @include('layouts.component._icon', ['name' => 'delete', 'size' => 16])
                        {{ __('ui.buttons.delete') }}
                    </button>
                </form>
            @endif
        </div>
    </article>
    @empty
    <div class="empty-state reveal d2">
        <div class="empty-state__ico" aria-hidden="true">
            @include('layouts.component._icon', ['name' => 'voucher', 'size' => 32])
        </div>
        <div class="empty-state__title">Belum ada voucher</div>
        <p class="empty-state__text">Buat voucher promo pertama untuk customer.<br>Mereka tinggal masukin kodenya saat pesan.</p>
        <a href="{{ route('admin.vouchers.create') }}" class="pill-btn" style="display:inline-flex">
            @include('layouts.component._icon', ['name' => 'plus', 'size' => 16])
            {{ __('ui.buttons.create') }}
        </a>
    </div>
    @endforelse

    @if($vouchers->hasPages())
    <div class="pager reveal">{{ $vouchers->links() }}</div>
    @endif
</main>

@include('layouts.component.admin._navbar_admin', ['active' => 'beranda'])
@include('layouts.component._form_controls')
</body>
</html>
