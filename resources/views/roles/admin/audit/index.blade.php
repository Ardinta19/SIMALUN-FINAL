<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Audit Trail – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · AUDIT TRAIL
   Design System — token-only styling, Lucide icons, pure-CSS motion.
   All colors/radii/shadows/spacing reference canonical tokens emitted
   by layouts.component._tokens (mirrors tailwind.config.js). No page-
   local literal palette — only canonical-token aliases below.
═══════════════════════════════════════════════════════════ */
:root {
    /* Page-local aliases — only reference canonical tokens (no literals) */
    --ink:        var(--brand-900);
    --ink-mid:    var(--surface-400);
    --ink-lt:     var(--surface-400);
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
.d4{animation-delay:.22s}.d5{animation-delay:.28s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ═══════════════════════════════════════════════ HEADER */
.appbar {
    max-width: 520px; margin: 0 auto;
    display: flex; align-items: center; gap: var(--space-3);
    padding: max(env(safe-area-inset-top, 0px), var(--space-3)) var(--space-4) var(--space-4);
}
.appbar__title { font-size: 1.15rem; font-weight: 800; color: var(--brand-900); line-height: 1.1; }

/* ═══════════════════════════════════════════════ ACTION TAGS (prefix quick-filter) */
.action-tags { display: flex; flex-wrap: wrap; gap: var(--space-2); margin-bottom: var(--space-4); }
.action-tag {
    display: inline-flex; align-items: center; gap: var(--space-1);
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    color: var(--ink-mid);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-pill);
    font-size: .72rem; font-weight: 800;
    text-decoration: none;
    transition: transform .12s ease, background .15s ease, color .15s ease;
}
.action-tag:active { transform: scale(.96); }
.action-tag--active { background: var(--brand-500); color: var(--surface-raised); border-color: var(--brand-500); }
.action-tag__count { opacity: .7; font-weight: 700; }

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

/* ── Filter form fields ── */
.filter-grid { display: grid; grid-template-columns: 1fr; gap: var(--space-3); }
.field-block { display: flex; flex-direction: column; gap: var(--space-1); min-width: 0; }
.field-label {
    font-size: .68rem; font-weight: 800; color: var(--ink-mid);
    text-transform: uppercase; letter-spacing: .4px; padding-left: 2px;
}
.field {
    width: 100%; min-width: 0; min-height: var(--space-11);
    padding: var(--space-2) var(--space-3); border-radius: var(--radius-btn);
    border: 1px solid var(--surface-200); font-family: var(--font);
    font-size: .9rem; font-weight: 600; color: var(--ink);
    background: var(--surface-raised); outline: none;
    transition: border-color .15s ease;
}
.field:focus { border-color: var(--brand-500); }

/* ── Date range (labeled, self-explanatory, no mobile overflow) ── */
.date-range { display: flex; flex-wrap: wrap; align-items: flex-end; gap: var(--space-2); }
.date-field { flex: 1 1 130px; min-width: 0; display: flex; flex-direction: column; gap: var(--space-1); }
.date-field .field { width: 100%; min-width: 0; }
.date-sep { align-self: flex-end; padding-bottom: var(--space-3); font-size: 1rem; font-weight: 800; color: var(--ink-mid); flex: 0 0 auto; }

.filter-actions { display: flex; gap: var(--space-2); margin-top: var(--space-4); }
.btn {
    min-height: var(--space-11); padding: 0 var(--space-5); border-radius: var(--radius-btn);
    font-family: var(--font); font-size: .82rem; font-weight: 800;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center; gap: var(--space-1);
    transition: transform .12s ease;
}
.btn:active { transform: scale(.97); }
.btn--primary { background: var(--brand-500); color: var(--surface-raised); border-color: var(--brand-500); }
.btn--ghost { background: var(--brand-100); color: var(--brand-900); border-color: var(--surface-200); }

/* ═══════════════════════════════════════════════ LOG LIST */
.log-row {
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: var(--space-3) var(--space-4);
    margin-bottom: var(--space-2);
}
.log-row__head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: var(--space-2); margin-bottom: var(--space-1);
}
.log-row__action {
    font-size: .76rem; font-weight: 800; color: var(--brand-900);
    font-family: ui-monospace, 'Courier New', monospace;
    background: var(--brand-100);
    padding: 3px var(--space-2); border-radius: var(--radius-btn);
    word-break: break-word;
}
.log-row__time { font-size: .68rem; color: var(--ink-lt); font-weight: 700; flex-shrink: 0; white-space: nowrap; }
.log-row__summary { font-size: .88rem; color: var(--ink); margin-bottom: var(--space-2); line-height: 1.45; }
.log-row__meta { font-size: .72rem; color: var(--ink-lt); font-weight: 600; display: flex; gap: var(--space-3); flex-wrap: wrap; }
.log-row__meta b { color: var(--ink-mid); font-weight: 800; }

.log-diff {
    margin-top: var(--space-2); padding: var(--space-2) var(--space-3);
    background: var(--surface-50); border-radius: var(--radius-btn);
    font-size: .72rem; font-family: ui-monospace, 'Courier New', monospace; overflow-x: auto;
}
.log-diff__row { display: flex; gap: var(--space-2); margin-bottom: 2px; }
.log-diff__key { color: var(--ink-lt); font-weight: 800; min-width: 110px; }
.log-diff__before { color: var(--danger-500); }
.log-diff__after { color: var(--success-500); font-weight: 800; }
.log-diff__arrow { color: var(--ink-lt); }

/* ── Empty state (designed) ── */
.empty-state { text-align: center; padding: var(--space-12) var(--space-5); }
.empty-state__ico {
    width: var(--space-14); height: var(--space-14); margin: 0 auto var(--space-3);
    border-radius: var(--radius-pill); background: var(--brand-100);
    display: flex; align-items: center; justify-content: center; color: var(--brand-500);
}
.empty-state__text { color: var(--ink-mid); font-size: .9rem; font-weight: 700; line-height: 1.5; }

/* ── Pagination ── */
.pagination-wrap { display: flex; justify-content: center; margin-top: var(--space-4); margin-bottom: var(--space-4); }
.pagination-wrap nav { font-size: .85rem; }
</style>
</head>
<body>

{{-- ══════════════ HEADER ══════════════ --}}
<header class="appbar">
    <x-back-button fallback="admin.dashboard" style="hero" />
    <h1 class="appbar__title">Audit Trail</h1>
</header>

<main class="wrap">

    {{-- Quick filter per action (prefix tags) — preserves bug-fixed prefix logic --}}
    @if($actionGroups->isNotEmpty())
    @php
        $prefixTotals = [];
        foreach ($actionGroups as $group) {
            $prefix = explode('.', $group->action)[0];
            $prefixTotals[$prefix] = ($prefixTotals[$prefix] ?? 0) + $group->total;
        }
    @endphp
    <div class="action-tags reveal d1">
        <a href="{{ route('admin.audit.index') }}"
           class="action-tag {{ ! request('action') ? 'action-tag--active' : '' }}">
            Semua <span class="action-tag__count">({{ $logs->total() }})</span>
        </a>
        @foreach($prefixTotals as $prefix => $prefixTotal)
            <a href="{{ route('admin.audit.index', ['action' => $prefix]) }}"
               class="action-tag {{ request('action') === $prefix ? 'action-tag--active' : '' }}">
                {{ $prefix }}<span class="action-tag__count">({{ $prefixTotal }})</span>
            </a>
        @endforeach
    </div>
    @endif

    {{-- Filter form --}}
    <div class="card reveal d2">
        <div class="card__pad">
            <div class="card__title">
                @include('layouts.component._icon', ['name' => 'filter', 'size' => 20])
                Filter Jejak Audit
            </div>
            <form method="GET" action="{{ route('admin.audit.index') }}">
                <div class="filter-grid">
                    <div class="field-block">
                        <label class="field-label" for="actor">Aktor</label>
                        <select name="actor_id" id="actor" class="field" aria-label="Filter aktor"
                                data-fc-select data-fc-title="Pilih Aktor">
                            <option value="">Semua aktor</option>
                            @foreach($actors as $actor)
                            <option value="{{ $actor->id }}" {{ (string) request('actor_id') === (string) $actor->id ? 'selected' : '' }}>
                                {{ $actor->name }} ({{ $actor->role }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-block">
                        <label class="field-label" for="action-filter">Action</label>
                        <input type="text" name="action" id="action-filter" class="field"
                               value="{{ request('action') }}"
                               placeholder="contoh: voucher, order.">
                    </div>

                    <div class="field-block">
                        <label class="field-label">Periode</label>
                        <div class="date-range">
                            <div class="date-field">
                                <label class="field-label" for="date-from">Dari Tanggal</label>
                                <input type="date" name="date_from" id="date-from" class="field"
                                       value="{{ request('date_from') }}" aria-label="Dari tanggal"
                                       data-fc-date data-fc-title="Dari Tanggal">
                            </div>
                            <span class="date-sep" aria-hidden="true">–</span>
                            <div class="date-field">
                                <label class="field-label" for="date-to">Sampai Tanggal</label>
                                <input type="date" name="date_to" id="date-to" class="field"
                                       value="{{ request('date_to') }}" aria-label="Sampai tanggal"
                                       data-fc-date data-fc-title="Sampai Tanggal">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn--primary">
                        @include('layouts.component._icon', ['name' => 'check', 'size' => 16])
                        Terapkan
                    </button>
                    <a href="{{ route('admin.audit.index') }}" class="btn btn--ghost">
                        @include('layouts.component._icon', ['name' => 'refresh', 'size' => 16])
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="reveal d3">
    @forelse($logs as $log)
    <div class="log-row">
        <div class="log-row__head">
            <span class="log-row__action">{{ $log->action }}</span>
            <span class="log-row__time">{{ $log->created_at->format('d/m/Y H:i') }}</span>
        </div>

        @if($log->summary)
        <div class="log-row__summary">{{ $log->summary }}</div>
        @endif

        <div class="log-row__meta">
            <span><b>Aktor:</b> {{ $log->actor?->name ?? 'Sistem' }}</span>
            @if($log->ip)
            <span><b>IP:</b> {{ $log->ip }}</span>
            @endif
            @if($log->auditable_type)
            <span><b>Target:</b> {{ class_basename($log->auditable_type) }}#{{ $log->auditable_id }}</span>
            @endif
        </div>

        @if($log->before || $log->after)
        <div class="log-diff">
            @php
                $beforeArr = is_array($log->before) ? $log->before : [];
                $afterArr  = is_array($log->after) ? $log->after : [];
                $keys = array_unique(array_merge(array_keys($beforeArr), array_keys($afterArr)));
            @endphp
            @foreach($keys as $key)
                @php
                    $b = $beforeArr[$key] ?? null;
                    $a = $afterArr[$key] ?? null;
                    if (is_array($b)) $b = json_encode($b);
                    if (is_array($a)) $a = json_encode($a);
                @endphp
                @if($b !== $a)
                <div class="log-diff__row">
                    <span class="log-diff__key">{{ $key }}:</span>
                    @if($b !== null)
                        <span class="log-diff__before">{{ Str::limit((string) $b, 60) }}</span>
                        <span class="log-diff__arrow">→</span>
                    @endif
                    @if($a !== null)
                        <span class="log-diff__after">{{ Str::limit((string) $a, 60) }}</span>
                    @else
                        <span class="log-diff__after">(dihapus)</span>
                    @endif
                </div>
                @endif
            @endforeach
        </div>
        @endif
    </div>
    @empty
    <div class="card">
        <div class="empty-state">
            <div class="empty-state__ico" aria-hidden="true">
                @include('layouts.component._icon', ['name' => 'audit', 'size' => 32])
            </div>
            <p class="empty-state__text">Belum ada catatan audit yang cocok.<br>Coba ubah atau atur ulang filter di atas.</p>
            @if(request()->hasAny(['actor_id','action','date_from','date_to']))
                <a href="{{ route('admin.audit.index') }}" class="btn btn--ghost" style="margin-top:var(--space-4);">
                    @include('layouts.component._icon', ['name' => 'refresh', 'size' => 16])
                    Reset Filter
                </a>
            @endif
        </div>
    </div>
    @endforelse
    </div>

    @if($logs->hasPages())
    <div class="pagination-wrap">
        {{ $logs->links() }}
    </div>
    @endif

</main>

{{-- Admin Navbar --}}
@include('layouts.component.admin._navbar_admin', ['active' => 'beranda'])
@include('layouts.component._form_controls')

</body>
</html>
