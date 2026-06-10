<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Laporan Kendala – Admin – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · LAPORAN KENDALA
   Design System — token-only styling, Lucide icons, pure-CSS motion.
═══════════════════════════════════════════════════════════ */
:root {
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
.d4{animation-delay:.22s}.d5{animation-delay:.28s}
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

/* ═══════════════════════════════════════════════ STATS */
.stats-row { display: flex; gap: var(--space-2); margin-bottom: var(--space-4); }
.stat-pill {
    flex: 1; padding: var(--space-3); border-radius: var(--radius-card);
    background: var(--surface-raised); border: 1px solid var(--surface-200);
    box-shadow: var(--shadow-card); text-align: center;
}
.stat-pill__value { font-size: 1.25rem; font-weight: 800; }
.stat-pill__label { font-size: .6rem; font-weight: 800; color: var(--ink-muted); text-transform: uppercase; letter-spacing: .3px; margin-top: 2px; }

/* ═══════════════════════════════════════════════ FILTERS */
.filter-row {
    display: flex; gap: var(--space-2); margin-bottom: var(--space-4); overflow-x: auto;
    scrollbar-width: none; padding-bottom: var(--space-1);
}
.filter-row::-webkit-scrollbar { display: none; }
.filter-chip {
    display: inline-flex; align-items: center; min-height: var(--space-10);
    padding: 0 var(--space-4); border-radius: var(--radius-pill); white-space: nowrap;
    font-size: .78rem; font-weight: 800; text-decoration: none;
    border: 1px solid var(--surface-200); background: var(--surface-raised); color: var(--ink-muted);
    transition: transform .12s ease;
}
.filter-chip:active { transform: scale(.96); }
.filter-chip.active { background: var(--brand-500); color: var(--surface-raised); border-color: var(--brand-500); }

/* ═══════════════════════════════════════════════ REPORT CARD */
.report-card {
    background: var(--surface-raised); border-radius: var(--radius-card);
    border: 1px solid var(--surface-200); box-shadow: var(--shadow-card);
    margin-bottom: var(--space-3); overflow: hidden;
}
.report-card__header {
    display: flex; align-items: center; justify-content: space-between; gap: var(--space-2);
    padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--surface-100);
}
.report-card__user { display: flex; align-items: center; gap: var(--space-3); min-width: 0; }
.report-card__avatar {
    width: var(--space-8); height: var(--space-8); border-radius: var(--radius-btn);
    background: var(--brand-100); display: flex; align-items: center; justify-content: center;
    font-size: .78rem; font-weight: 800; color: var(--brand-500); flex-shrink: 0;
}
.report-card__name { font-size: .85rem; font-weight: 700; color: var(--ink); }
.report-card__role { font-size: .62rem; font-weight: 600; color: var(--ink-muted); }
.report-card__badge {
    font-size: .58rem; font-weight: 800; padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-pill); text-transform: uppercase; flex-shrink: 0;
}
.report-card__body { padding: var(--space-3) var(--space-4); }
.report-card__category {
    display: inline-flex; align-items: center; gap: var(--space-1);
    font-size: .68rem; font-weight: 800; color: var(--brand-500);
    margin-bottom: var(--space-2);
}
.report-card__desc { font-size: .85rem; font-weight: 600; color: var(--ink); line-height: 1.5; margin-bottom: var(--space-2); }
.report-card__img {
    width: 100%; max-height: 180px; object-fit: cover;
    border-radius: var(--radius-btn); border: 1px solid var(--surface-200); margin-bottom: var(--space-2);
}
.report-card__time { font-size: .65rem; font-weight: 600; color: var(--ink-muted); }
.report-card__footer {
    padding: var(--space-3) var(--space-4); border-top: 1px solid var(--surface-100);
    display: flex; align-items: center; gap: var(--space-2);
}
.status-select {
    flex: 1; min-height: var(--space-11); padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-btn); border: 1px solid var(--surface-200);
    font-family: var(--font); font-size: .8rem; font-weight: 600; color: var(--ink);
    background: var(--surface-raised); outline: none; transition: border-color .15s ease;
}
.status-select:focus { border-color: var(--brand-500); }
.status-btn {
    min-height: var(--space-11); padding: 0 var(--space-4); border-radius: var(--radius-btn);
    background: var(--brand-500); color: var(--surface-raised); border: none;
    font-family: var(--font); font-size: .78rem; font-weight: 800; cursor: pointer;
    display: inline-flex; align-items: center; gap: var(--space-1);
    transition: transform .12s ease;
}
.status-btn:active { transform: scale(.97); }

/* ── Empty state (designed) ── */
.empty-state { text-align: center; padding: var(--space-12) var(--space-5); }
.empty-state__ico {
    width: var(--space-14); height: var(--space-14); margin: 0 auto var(--space-3);
    border-radius: var(--radius-pill); background: var(--brand-100);
    display: flex; align-items: center; justify-content: center; color: var(--brand-500);
}
.empty-state__text { color: var(--ink-muted); font-size: .9rem; font-weight: 700; line-height: 1.5; }

.pagination-wrap { display: flex; justify-content: center; margin-top: var(--space-3); }
.pagination-wrap nav { font-size: .85rem; }

/* ── Toast (designed, transform/opacity only) ── */
.toast {
    position: fixed; top: var(--space-4); left: 50%; transform: translateX(-50%);
    display: flex; align-items: center; gap: var(--space-2);
    background: var(--success-500); color: var(--surface-raised);
    padding: var(--space-3) var(--space-5); border-radius: var(--radius-btn);
    font-size: .85rem; font-weight: 700; z-index: 9999; box-shadow: var(--shadow-card);
    animation: toastIn .3s ease, toastOut .3s ease 2.5s forwards;
}
@keyframes toastIn { from { opacity:0; transform:translateX(-50%) translateY(-20px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
@keyframes toastOut { to { opacity:0; transform:translateX(-50%) translateY(-20px); } }
@media (prefers-reduced-motion: reduce){ .toast{animation:none} }
</style>
</head>
<body>

@if(session('success'))
<div class="toast" role="status">
    @include('layouts.component._icon', ['name' => 'success', 'size' => 20])
    {{ session('success') }}
</div>
@endif

{{-- ══════════════ HEADER ══════════════ --}}
<header class="appbar">
    <a href="{{ \App\Support\BackUrl::resolve(request(), 'admin.dashboard') }}" class="icon-btn" aria-label="Kembali ke dashboard">
        @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
    </a>
    <h1 class="appbar__title">Laporan Kendala</h1>
</header>

<main class="wrap">

    {{-- Stats --}}
    <div class="stats-row reveal d1">
        <div class="stat-pill">
            <div class="stat-pill__value" style="color: var(--warning-500);">{{ $countOpen }}</div>
            <div class="stat-pill__label">Menunggu</div>
        </div>
        <div class="stat-pill">
            <div class="stat-pill__value" style="color: var(--brand-500);">{{ $countInProgress }}</div>
            <div class="stat-pill__label">Ditangani</div>
        </div>
        <div class="stat-pill">
            <div class="stat-pill__value" style="color: var(--success-500);">{{ $countResolved }}</div>
            <div class="stat-pill__label">Selesai</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-row reveal d2">
        <a href="{{ route('admin.reports') }}" class="filter-chip {{ !$status && !$category ? 'active' : '' }}">Semua</a>
        <a href="{{ route('admin.reports', ['status' => 'open']) }}" class="filter-chip {{ $status === 'open' ? 'active' : '' }}">Menunggu</a>
        <a href="{{ route('admin.reports', ['status' => 'in_progress']) }}" class="filter-chip {{ $status === 'in_progress' ? 'active' : '' }}">Ditangani</a>
        <a href="{{ route('admin.reports', ['category' => 'bug']) }}" class="filter-chip {{ $category === 'bug' ? 'active' : '' }}">Bug</a>
        <a href="{{ route('admin.reports', ['category' => 'komplain']) }}" class="filter-chip {{ $category === 'komplain' ? 'active' : '' }}">Komplain</a>
        <a href="{{ route('admin.reports', ['category' => 'saran']) }}" class="filter-chip {{ $category === 'saran' ? 'active' : '' }}">Saran</a>
    </div>

    {{-- Reports List --}}
    @forelse($reports as $report)
    <div class="report-card reveal d3">
        <div class="report-card__header">
            <div class="report-card__user">
                <div class="report-card__avatar" aria-hidden="true">{{ strtoupper(substr($report->user->name ?? 'U', 0, 2)) }}</div>
                <div>
                    <div class="report-card__name">{{ $report->user->name ?? '-' }}</div>
                    <div class="report-card__role">{{ ucfirst($report->user->role ?? 'user') }}</div>
                </div>
            </div>
            <span class="report-card__badge" style="background: {{ $report->status_color }}20; color: {{ $report->status_color }};">
                {{ $report->status_label }}
            </span>
        </div>
        <div class="report-card__body">
            <div class="report-card__category">
                @php
                    $catIcon = $report->category === 'bug' ? 'alert' : ($report->category === 'saran' ? 'info' : 'chat');
                @endphp
                @include('layouts.component._icon', ['name' => $catIcon, 'size' => 16])
                {{ $report->category_label }}
            </div>
            <div class="report-card__desc">{{ $report->description }}</div>
            @if($report->screenshot_path)
                <img src="{{ asset('storage/' . $report->screenshot_path) }}" class="report-card__img" alt="Screenshot laporan">
            @endif
            <div class="report-card__time">{{ $report->created_at->translatedFormat('d M Y, H:i') }} — {{ $report->created_at->diffForHumans() }}</div>
        </div>
        <div class="report-card__footer">
            <form method="POST" action="{{ route('admin.reports.update-status', $report) }}" style="display:flex; gap:var(--space-2); width:100%;">
                @csrf
                @method('PATCH')
                <select name="status" class="status-select" data-fc-select data-fc-title="Ubah Status">
                    <option value="open" {{ $report->status === 'open' ? 'selected' : '' }}>Menunggu</option>
                    <option value="in_progress" {{ $report->status === 'in_progress' ? 'selected' : '' }}>Ditangani</option>
                    <option value="resolved" {{ $report->status === 'resolved' ? 'selected' : '' }}>Selesai</option>
                    <option value="closed" {{ $report->status === 'closed' ? 'selected' : '' }}>Ditutup</option>
                </select>
                <button type="button" class="status-btn js-status-btn">
                    @include('layouts.component._icon', ['name' => 'refresh', 'size' => 16])
                    Update
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="empty-state reveal d2">
        <div class="empty-state__ico" aria-hidden="true">
            @include('layouts.component._icon', ['name' => 'report', 'size' => 24])
        </div>
        <p class="empty-state__text">Belum ada laporan kendala masuk.<br>Laporan dari pengguna akan tampil di sini.</p>
    </div>
    @endforelse

    @if($reports->hasPages())
    <div class="pagination-wrap">
        {{ $reports->links('pagination::simple-tailwind') }}
    </div>
    @endif

</main>

@include('layouts.component.admin._navbar_admin', ['active' => ''])
@include('layouts.component._confirm_modal')
@include('layouts.component._form_controls')

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-status-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = btn.closest('form');
            var statusLabel = form.querySelector('.status-select option:checked').textContent;
            showConfirmModal({
                title: 'Update Status Laporan?',
                message: 'Status laporan akan diubah menjadi "' + statusLabel + '". Lanjutkan?',
                confirmText: 'Ya, Update',
                cancelText: 'Batal',
                type: 'info',
                onConfirm: function() {
                    form.submit();
                }
            });
        });
    });
});
</script>

</body>
</html>
