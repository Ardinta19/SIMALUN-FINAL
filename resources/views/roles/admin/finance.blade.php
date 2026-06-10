<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Laporan Keuangan – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · LAPORAN KEUANGAN
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
.icon-btn {
    width: var(--space-11); height: var(--space-11); border-radius: var(--radius-pill);
    background: var(--brand-100); border: 1px solid var(--surface-200);
    display: flex; align-items: center; justify-content: center;
    color: var(--brand-900); text-decoration: none; flex-shrink: 0;
    transition: transform .12s ease, background .15s ease;
}
.icon-btn:active { transform: scale(.94); background: var(--surface-100); }
.appbar__title { font-size: 1.15rem; font-weight: 800; color: var(--brand-900); line-height: 1.1; }
.appbar__actions { display: flex; gap: var(--space-2); flex-shrink: 0; }
.pill-btn {
    display: inline-flex; align-items: center; gap: var(--space-1);
    min-height: var(--space-11); padding: 0 var(--space-3);
    border-radius: var(--radius-btn);
    font-family: var(--font); font-size: .78rem; font-weight: 800;
    text-decoration: none; border: 1px solid var(--surface-200);
    background: var(--surface-raised); color: var(--ink); cursor: pointer;
    transition: transform .12s ease;
}
.pill-btn:active { transform: scale(.96); }
.pill-btn--income { background: var(--success-500); color: var(--surface-raised); border-color: var(--success-500); }
.pill-btn--brand { background: var(--brand-500); color: var(--surface-raised); border-color: var(--brand-500); }

/* ═══════════════════════════════════════════════ SUMMARY */
.summary-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-2); margin-bottom: var(--space-4); }
.summary-card {
    background: var(--surface-raised); border-radius: var(--radius-card);
    border: 1px solid var(--surface-200); padding: var(--space-3) var(--space-2);
    text-align: center; box-shadow: var(--shadow-card);
}
.summary-card__label { font-size: .62rem; font-weight: 800; color: var(--ink-muted); text-transform: uppercase; letter-spacing: .5px; }
.summary-card__value { font-size: 1.15rem; font-weight: 800; margin-top: var(--space-1); }
.summary-card__value.income  { color: var(--success-500); }
.summary-card__value.expense { color: var(--danger-500); }
.summary-card__value.balance { color: var(--brand-500); }

/* ═══════════════════════════════════════════════ CARD WRAPPER */
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

/* ── Chart ── */
.chart-bars { display: flex; align-items: flex-end; gap: var(--space-2); height: 88px; }
.chart-bar { flex: 1; display: flex; flex-direction: column; align-items: center; gap: var(--space-1); }
.chart-bar__fill {
    width: 100%; border-radius: var(--radius-btn) var(--radius-btn) 0 0;
    background: linear-gradient(180deg, var(--brand-500) 0%, var(--brand-100) 140%);
    min-height: 4px;
}
.chart-bar__label { font-size: .58rem; font-weight: 700; color: var(--ink-muted); }

/* ── Filters ── */
.filter-tabs { display: flex; gap: var(--space-1); background: var(--surface-100); border-radius: var(--radius-btn); padding: var(--space-1); margin-bottom: var(--space-3); }
.filter-tab {
    flex: 1; text-align: center; min-height: var(--space-10);
    display: flex; align-items: center; justify-content: center;
    border-radius: var(--radius-btn); font-size: .76rem; font-weight: 800;
    color: var(--ink-muted); text-decoration: none; transition: transform .12s ease;
}
.filter-tab:active { transform: scale(.97); }
.filter-tab.active { background: var(--surface-raised); color: var(--brand-500); box-shadow: var(--shadow-card); }
.filter-row { display: flex; flex-wrap: wrap; gap: var(--space-2); margin-bottom: var(--space-3); }
.filter-row:last-child { margin-bottom: 0; }
.field {
    flex: 1; min-width: 120px; min-height: var(--space-11);
    padding: var(--space-2) var(--space-3); border-radius: var(--radius-btn);
    border: 1px solid var(--surface-200); font-family: var(--font);
    font-size: .85rem; font-weight: 600; color: var(--ink);
    background: var(--surface-raised); outline: none;
    transition: border-color .15s ease;
}
.field:focus { border-color: var(--brand-500); }
.btn-apply {
    min-height: var(--space-11); padding: 0 var(--space-5); border-radius: var(--radius-btn);
    background: var(--brand-500); color: var(--surface-raised); font-family: var(--font);
    font-size: .82rem; font-weight: 800; border: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: var(--space-1);
    transition: transform .12s ease;
}
.btn-apply:active { transform: scale(.97); }

/* ── Date range (labeled, self-explanatory, no mobile overflow) ── */
.date-range { align-items: flex-end; }
.date-field { flex: 1 1 130px; min-width: 0; display: flex; flex-direction: column; gap: var(--space-1); }
.date-field__label { font-size: .68rem; font-weight: 800; color: var(--ink-muted); text-transform: uppercase; letter-spacing: .4px; padding-left: 2px; }
.date-field .field { width: 100%; min-width: 0; }
.date-sep { align-self: flex-end; padding-bottom: var(--space-3); font-size: 1rem; font-weight: 800; color: var(--ink-muted); flex: 0 0 auto; }
.date-range .btn-apply { flex: 0 0 auto; }

/* ── Transactions ── */
.tx-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: var(--space-4); border-bottom: 1px solid var(--surface-200);
}
.tx-head__title { display: flex; align-items: center; gap: var(--space-2); font-size: .92rem; font-weight: 800; color: var(--ink); }
.tx-head__title svg { color: var(--brand-500); }
.tx-head__count { font-size: .7rem; font-weight: 800; color: var(--ink-muted); }
.tx-item {
    display: flex; align-items: center; gap: var(--space-3);
    padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--surface-100);
}
.tx-item:last-child { border-bottom: none; }
.tx-icon {
    width: var(--space-9); height: var(--space-9); border-radius: var(--radius-btn);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.tx-icon.income  { background: color-mix(in srgb, var(--success-500) 14%, var(--surface-raised)); color: var(--success-500); }
.tx-icon.expense { background: color-mix(in srgb, var(--danger-500) 14%, var(--surface-raised)); color: var(--danger-500); }
.tx-body { flex: 1; min-width: 0; }
.tx-desc { font-size: .85rem; font-weight: 700; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tx-meta { font-size: .68rem; font-weight: 600; color: var(--ink-muted); margin-top: 2px; }
.tx-amount { font-size: .85rem; font-weight: 800; text-align: right; flex-shrink: 0; white-space: nowrap; }
.tx-amount.income  { color: var(--success-500); }
.tx-amount.expense { color: var(--danger-500); }

/* ── Empty state (designed) ── */
.empty-state { text-align: center; padding: var(--space-10) var(--space-5); }
.empty-state__ico {
    width: var(--space-14); height: var(--space-14); margin: 0 auto var(--space-3);
    border-radius: var(--radius-pill); background: var(--brand-100);
    display: flex; align-items: center; justify-content: center; color: var(--brand-500);
}
.empty-state__text { color: var(--ink-muted); font-size: .9rem; font-weight: 700; line-height: 1.5; }

/* ── Expense form ── */
.expense-form { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.btn-save {
    min-height: var(--space-11); padding: 0 var(--space-5); border-radius: var(--radius-btn);
    background: var(--brand-900); color: var(--surface-raised); font-family: var(--font);
    font-size: .82rem; font-weight: 800; border: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: var(--space-1);
    transition: transform .12s ease;
}
.btn-save:active { transform: scale(.97); }

/* ── Pagination ── */
.pagination-wrap { display: flex; justify-content: center; margin-bottom: var(--space-4); }
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
.toast--error { background: var(--danger-500); }
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
@if(session('error'))
<div class="toast toast--error" role="alert">
    @include('layouts.component._icon', ['name' => 'error', 'size' => 20])
    {{ session('error') }}
</div>
@endif

{{-- ══════════════ HEADER ══════════════ --}}
<header class="appbar">
    <div class="appbar__left">
        <a href="{{ \App\Support\BackUrl::resolve(request(), 'admin.dashboard') }}" class="icon-btn" aria-label="Kembali ke dashboard">
            @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
        </a>
        <h1 class="appbar__title">Laporan Keuangan</h1>
    </div>
    <div class="appbar__actions">
        <a href="{{ route('admin.finance.export', request()->query()) }}" class="pill-btn pill-btn--income" aria-label="Export Excel">
            @include('layouts.component._icon', ['name' => 'document', 'size' => 16])
            Excel
        </a>
        <a href="{{ route('admin.finance.export-pdf', request()->query()) }}" class="pill-btn pill-btn--brand" aria-label="Export PDF">
            @include('layouts.component._icon', ['name' => 'document', 'size' => 16])
            PDF
        </a>
    </div>
</header>

<main class="wrap">

    {{-- Summary --}}
    <div class="summary-grid reveal d1">
        <div class="summary-card">
            <div class="summary-card__label">Pemasukan</div>
            <div class="summary-card__value income">{{ number_format($masuk / 1000, 0, ',', '.') }}k</div>
        </div>
        <div class="summary-card">
            <div class="summary-card__label">Pengeluaran</div>
            <div class="summary-card__value expense">{{ number_format($keluar / 1000, 0, ',', '.') }}k</div>
        </div>
        <div class="summary-card">
            <div class="summary-card__label">Saldo</div>
            <div class="summary-card__value balance">{{ number_format($saldo / 1000, 0, ',', '.') }}k</div>
        </div>
    </div>

    {{-- Revenue Chart --}}
    @if(isset($chartData) && $chartData->sum('total') > 0)
    <div class="card reveal d2">
        <div class="card__pad">
            <div class="card__title">
                @include('layouts.component._icon', ['name' => 'chart', 'size' => 20])
                Pemasukan 7 Hari Terakhir
            </div>
            @php $maxChart = $chartData->max('total') ?: 1; @endphp
            <div class="chart-bars">
                @foreach($chartData as $bar)
                <div class="chart-bar">
                    <div class="chart-bar__fill" style="height: {{ max(4, ($bar['total'] / $maxChart) * 78) }}px;"></div>
                    <span class="chart-bar__label">{{ $bar['date'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="card reveal d3">
        <div class="card__pad">
            <div class="card__title">
                @include('layouts.component._icon', ['name' => 'filter', 'size' => 20])
                Filter Laporan
            </div>
            <div class="filter-tabs">
                <a href="{{ route('admin.finance.index', ['range' => 'harian']) }}" class="filter-tab {{ ($filters['range'] ?? '') === 'harian' ? 'active' : '' }}">Hari Ini</a>
                <a href="{{ route('admin.finance.index', ['range' => 'mingguan']) }}" class="filter-tab {{ ($filters['range'] ?? '') === 'mingguan' ? 'active' : '' }}">Minggu</a>
                <a href="{{ route('admin.finance.index', ['range' => 'bulanan']) }}" class="filter-tab {{ ($filters['range'] ?? 'bulanan') === 'bulanan' ? 'active' : '' }}">Bulan</a>
            </div>
            <form method="GET" action="{{ route('admin.finance.index') }}">
                <div class="filter-row date-range">
                    <div class="date-field">
                        <label class="date-field__label" for="fStartDate">Dari Tanggal</label>
                        <input type="date" id="fStartDate" name="start_date" class="field" value="{{ $filters['start_date'] ?? '' }}" aria-label="Dari tanggal" data-fc-date data-fc-title="Dari Tanggal">
                    </div>
                    <span class="date-sep" aria-hidden="true">–</span>
                    <div class="date-field">
                        <label class="date-field__label" for="fEndDate">Sampai Tanggal</label>
                        <input type="date" id="fEndDate" name="end_date" class="field" value="{{ $filters['end_date'] ?? '' }}" aria-label="Sampai tanggal" data-fc-date data-fc-title="Sampai Tanggal">
                    </div>
                    <button type="submit" class="btn-apply">
                        @include('layouts.component._icon', ['name' => 'check', 'size' => 16])
                        Terapkan
                    </button>
                </div>
                <div class="filter-row">
                    <select name="service_id" class="field" aria-label="Filter layanan" data-fc-select data-fc-title="Pilih Layanan">
                        <option value="">Semua Layanan</option>
                        @foreach($daftarLayanan as $s)
                            <option value="{{ $s->id }}" {{ ($filters['service_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <select name="type" class="field" aria-label="Filter tipe transaksi" data-fc-segmented>
                        <option value="">Semua Tipe</option>
                        <option value="income" {{ ($filters['type'] ?? '') === 'income' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="expense" {{ ($filters['type'] ?? '') === 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Transaction List --}}
    <div class="card reveal d4">
        <div class="tx-head">
            <span class="tx-head__title">
                @include('layouts.component._icon', ['name' => 'finance', 'size' => 20])
                Riwayat Transaksi
            </span>
            <span class="tx-head__count">{{ $transaksi->total() }} data</span>
        </div>

        @forelse($transaksi as $t)
        <div class="tx-item">
            <div class="tx-icon {{ $t->entry_type }}">
                @include('layouts.component._icon', ['name' => $t->entry_type === 'income' ? 'add' : 'minus', 'size' => 16])
            </div>
            <div class="tx-body">
                <div class="tx-desc">{{ $t->notes ?? 'Transaksi' }}</div>
                <div class="tx-meta">
                    {{ $t->entry_date->format('d/m/Y') }}
                    @if($t->order)
                        &middot; {{ $t->order->order_code }}
                    @endif
                    @if($t->order?->customer)
                        &middot; {{ Str::limit($t->order->customer->name, 15) }}
                    @endif
                </div>
            </div>
            <div class="tx-amount {{ $t->entry_type }}">
                {{ $t->entry_type === 'income' ? '+' : '-' }} Rp {{ number_format($t->amount, 0, ',', '.') }}
            </div>
        </div>
        @empty
        <div class="empty-state">
            <div class="empty-state__ico" aria-hidden="true">
                @include('layouts.component._icon', ['name' => 'finance', 'size' => 24])
            </div>
            <p class="empty-state__text">Belum ada transaksi di periode ini.<br>Coba ubah filter atau catat pengeluaran baru.</p>
        </div>
        @endforelse
    </div>

    @if($transaksi->hasPages())
    <div class="pagination-wrap">
        {{ $transaksi->links('pagination::simple-tailwind') }}
    </div>
    @endif

    {{-- Quick Expense Entry --}}
    <div class="card reveal d5">
        <div class="card__pad">
            <div class="card__title">
                @include('layouts.component._icon', ['name' => 'minus', 'size' => 20])
                Catat Pengeluaran
            </div>
            <form method="POST" action="{{ route('admin.finance.store') }}" class="expense-form">
                @csrf
                <input type="text" name="description" class="field" placeholder="Keterangan (cth: Beli deterjen)" aria-label="Keterangan pengeluaran" required>
                <input type="number" name="amount" class="field" style="min-width:100px;" placeholder="Jumlah (Rp)" aria-label="Jumlah pengeluaran" required min="1" step="1000" data-fc-stepper>
                <select name="category" class="field" style="min-width:100px;" aria-label="Kategori pengeluaran" data-fc-select data-fc-title="Kategori">
                    <option value="operational">Operasional</option>
                    <option value="salary">Gaji</option>
                    <option value="investment">Investasi</option>
                    <option value="other">Lainnya</option>
                </select>
                <button type="submit" class="btn-save">
                    @include('layouts.component._icon', ['name' => 'check', 'size' => 16])
                    Simpan
                </button>
            </form>
        </div>
    </div>

</main>

{{-- Admin Navbar --}}
@include('layouts.component.admin._navbar_admin', ['active' => 'beranda'])
@include('layouts.component._form_loading')
@include('layouts.component._form_controls')

</body>
</html>
