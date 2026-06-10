<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Manajemen Pesanan – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · MANAJEMEN PESANAN
   Design System: every color/radius/shadow/spacing derives from
   the canonical tokens emitted by _tokens.blade.php. No undefined
   literals; spacing uses the 4px --space-* scale.
═══════════════════════════════════════════════════════════ */
:root {
    --ink:        var(--brand-900);
    --ink-mid:    color-mix(in srgb, var(--brand-900) 68%, var(--surface-raised));
    --ink-lt:     color-mix(in srgb, var(--brand-900) 42%, var(--surface-raised));
    --card:       var(--surface-raised);
    --bg:         var(--surface-50);
    --border:     var(--surface-200);
    --line-soft:  var(--surface-100);
    --brand-tint:   color-mix(in srgb, var(--brand-500) 12%, var(--surface-raised));
    --accent-tint:  color-mix(in srgb, var(--accent-500) 12%, var(--surface-raised));
    --success-tint: color-mix(in srgb, var(--success-500) 14%, var(--surface-raised));
    --warning-tint: color-mix(in srgb, var(--warning-500) 16%, var(--surface-raised));
    --danger-tint:  color-mix(in srgb, var(--danger-500) 12%, var(--surface-raised));
}
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    font-size: 16px;
    min-height: 100vh;
    padding-bottom: calc(var(--space-20) + env(safe-area-inset-bottom, 0px));
    overflow-x: hidden;
}

/* ── Entrance animation (pure CSS · transform + opacity only) ── */
@keyframes riseIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
.reveal { opacity: 0; animation: riseIn .45s cubic-bezier(.22,.61,.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.09s}.d3{animation-delay:.14s}
.d4{animation-delay:.19s}.d5{animation-delay:.24s}.d6{animation-delay:.30s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ── Header ── */
.topbar {
    background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-500) 100%);
    padding: max(env(safe-area-inset-top, 0px), var(--space-4)) var(--space-5) var(--space-5);
    position: sticky; top: 0; z-index: 100;
}
.topbar__inner {
    display: flex; align-items: center; justify-content: space-between;
    gap: var(--space-3); max-width: 520px; margin: 0 auto;
}
.topbar__left { display: flex; align-items: center; gap: var(--space-3); min-width: 0; }
.topbar__back {
    width: var(--space-11); height: var(--space-11); border-radius: var(--radius-pill);
    background: color-mix(in srgb, var(--surface-raised) 14%, transparent);
    border: 1px solid color-mix(in srgb, var(--surface-raised) 22%, transparent);
    display: flex; align-items: center; justify-content: center;
    color: var(--surface-raised); text-decoration: none; flex-shrink: 0;
    transition: transform .12s ease;
}
.topbar__back:active { transform: scale(.92); }
.topbar__title { font-weight: 800; font-size: 1.1rem; color: var(--surface-raised); line-height: 1.15; }
.topbar__subtitle { font-size: .7rem; font-weight: 600; color: color-mix(in srgb, var(--surface-raised) 65%, transparent); margin-top: 2px; }
.topbar__action {
    display: inline-flex; align-items: center; gap: var(--space-2);
    min-height: var(--space-11); padding: 0 var(--space-4);
    background: var(--accent-500); color: var(--surface-raised);
    text-decoration: none; font-weight: 700; font-size: .8rem;
    border-radius: var(--radius-pill); flex-shrink: 0;
    box-shadow: 0 4px 14px color-mix(in srgb, var(--accent-500) 36%, transparent);
    transition: transform .12s ease;
}
.topbar__action:active { transform: scale(.95); }

/* ── Layout container ── */
.container { max-width: 520px; margin: 0 auto; padding: var(--space-4); }

/* ── Alerts ── */
.alert {
    display: flex; align-items: center; gap: var(--space-2);
    padding: var(--space-3) var(--space-4); border-radius: var(--radius-btn);
    font-size: .85rem; font-weight: 700; margin-bottom: var(--space-3);
}
.alert--success { background: var(--success-tint); color: var(--success-500); border: 1px solid color-mix(in srgb, var(--success-500) 28%, transparent); }
.alert--error   { background: var(--danger-tint);  color: var(--danger-500);  border: 1px solid color-mix(in srgb, var(--danger-500) 24%, transparent); }
.alert svg { flex-shrink: 0; }

/* ── Stats row ── */
.stats { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-2); margin-bottom: var(--space-4); }
.stat {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius-card); padding: var(--space-3) var(--space-2);
    text-align: center; box-shadow: var(--shadow-card);
}
.stat__num { font-weight: 800; font-size: 1.4rem; line-height: 1; color: var(--brand-500); }
.stat__num--accent  { color: var(--accent-500); }
.stat__num--success { color: var(--success-500); }
.stat__label { font-size: .62rem; font-weight: 800; color: var(--ink-lt); text-transform: uppercase; letter-spacing: .4px; margin-top: var(--space-2); }

/* ── Filter chips ── */
.filters {
    display: flex; align-items: center; gap: var(--space-2);
    margin-bottom: var(--space-4); overflow-x: auto;
    padding-bottom: var(--space-1); -webkit-overflow-scrolling: touch;
}
.filters::-webkit-scrollbar { display: none; }
.filters { scrollbar-width: none; }
.chip {
    display: inline-flex; align-items: center; min-height: var(--space-11);
    padding: 0 var(--space-4); border-radius: var(--radius-pill);
    font-size: .78rem; font-weight: 700; white-space: nowrap; text-decoration: none;
    border: 1px solid var(--border); background: var(--card); color: var(--ink-mid);
    transition: background .15s ease, color .15s ease;
}
.chip--active { background: var(--brand-500); color: var(--surface-raised); border-color: var(--brand-500); }

/* ── Order card ── */
.order-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius-card); margin-bottom: var(--space-3);
    overflow: hidden; box-shadow: var(--shadow-card);
}
.order-card__head {
    display: flex; align-items: center; justify-content: space-between;
    padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--line-soft);
}
.order-card__code { font-size: .8rem; font-weight: 800; color: var(--brand-500); font-variant-numeric: tabular-nums; }
.order-card__badge {
    font-size: .62rem; font-weight: 800; padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-pill); text-transform: uppercase; letter-spacing: .3px;
}
.order-card__badge--menunggu { background: var(--warning-tint); color: var(--warning-500); }
.order-card__badge--proses   { background: var(--brand-tint);   color: var(--brand-500); }
.order-card__badge--selesai  { background: var(--success-tint); color: var(--success-500); }
.order-card__badge--batal    { background: var(--danger-tint);  color: var(--danger-500); }

.order-card__body { padding: var(--space-3) var(--space-4); }
.order-card__customer { display: flex; align-items: center; gap: var(--space-2); font-weight: 800; font-size: .95rem; color: var(--ink); margin-bottom: var(--space-1); }
.order-card__customer svg { color: var(--ink-lt); flex-shrink: 0; }
.order-card__phone { display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap; font-size: .78rem; font-weight: 600; color: var(--ink-lt); margin-bottom: var(--space-2); }
.order-card__meta { display: flex; gap: var(--space-2); flex-wrap: wrap; margin-bottom: var(--space-2); }
.order-card__chip {
    display: inline-flex; align-items: center; gap: var(--space-1);
    background: var(--line-soft); color: var(--ink-mid); border-radius: var(--radius-pill);
    padding: var(--space-1) var(--space-3); font-size: .72rem; font-weight: 700;
}
.order-card__chip svg { color: var(--ink-lt); }
.order-card__total { font-weight: 800; font-size: .95rem; color: var(--success-500); }

/* ── Action panels ── */
.order-card__panel { padding: var(--space-3) var(--space-4); border-top: 1px solid var(--line-soft); background: var(--line-soft); }
.order-card__panel-label { display: flex; align-items: center; gap: var(--space-1); font-size: .72rem; font-weight: 800; color: var(--ink-mid); margin-bottom: var(--space-2); }
.order-card__panel-form { display: flex; align-items: center; gap: var(--space-2); }
.order-card__select {
    flex: 1; min-height: var(--space-11); padding: 0 var(--space-3);
    border: 1px solid var(--border); border-radius: var(--radius-btn);
    font-family: 'Plus Jakarta Sans', sans-serif; font-size: .8rem; font-weight: 600;
    color: var(--ink); background: var(--card); outline: none;
}
.order-card__select:focus { border-color: var(--brand-500); }
.order-card__panel-btn {
    min-height: var(--space-11); padding: 0 var(--space-4); border: none;
    border-radius: var(--radius-btn); font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800; font-size: .78rem; cursor: pointer; white-space: nowrap;
    color: var(--surface-raised); transition: transform .12s ease;
}
.order-card__panel-btn:active { transform: scale(.95); }
.order-card__panel-btn--primary { background: var(--brand-500); }
.order-card__panel-btn--accent  { background: var(--accent-500); width: 100%; }
.order-card__panel-btn--success { background: var(--success-500); }
.order-card__panel-btn--block   { width: 100%; }
.order-card__panel-btn:disabled {
    background: var(--border); color: var(--ink-lt);
    cursor: not-allowed; transform: none;
}

.order-card__footer { padding: var(--space-3) var(--space-4); border-top: 1px solid var(--line-soft); }
.order-card__detail-btn {
    display: flex; align-items: center; justify-content: center; gap: var(--space-2);
    width: 100%; min-height: var(--space-11); border-radius: var(--radius-btn);
    background: var(--line-soft); color: var(--ink-mid); font-weight: 800; font-size: .82rem;
    text-decoration: none; transition: background .12s ease;
}
.order-card__detail-btn:active { background: var(--border); }

/* ── Empty state ── */
.empty { text-align: center; padding: var(--space-12) var(--space-5); }
.empty__icon {
    width: var(--space-16); height: var(--space-16); border-radius: var(--radius-pill);
    background: var(--brand-tint); color: var(--brand-500);
    display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-4);
}
.empty__title { font-weight: 800; font-size: 1.05rem; color: var(--ink); margin-bottom: var(--space-2); }
.empty__desc { font-size: .85rem; font-weight: 500; color: var(--ink-lt); }

/* ── Pagination ── */
.pagination-wrap { padding: var(--space-3) 0; }
.pagination-wrap nav { display: flex; justify-content: center; }
</style>
</head>
<body>

<header class="topbar">
    <div class="topbar__inner">
        <div class="topbar__left">
            <a href="{{ route('dashboard.admin') }}" class="topbar__back" aria-label="Kembali ke dashboard">
                @include('layouts.component._icon', ['name' => 'back', 'size' => 20])
            </a>
            <div>
                <div class="topbar__title">Manajemen Pesanan</div>
                <div class="topbar__subtitle">Kelola semua order masuk</div>
            </div>
        </div>
        <a href="{{ route('admin.walkin.form') }}" class="topbar__action">
            @include('layouts.component._icon', ['name' => 'plus', 'size' => 16])
            Walk-in
        </a>
    </div>
</header>

<main class="container">

    @if(session('success'))
        <div class="alert alert--success" role="status">
            @include('layouts.component._icon', ['name' => 'success', 'size' => 20])
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert--error" role="alert">
            @include('layouts.component._icon', ['name' => 'error', 'size' => 20])
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Stats --}}
    <section class="stats reveal d1" aria-label="Ringkasan pesanan">
        <div class="stat">
            <div class="stat__num">{{ $jumlahSemua ?? 0 }}</div>
            <div class="stat__label">Total</div>
        </div>
        <div class="stat">
            <div class="stat__num stat__num--accent">{{ $jumlahAktif ?? 0 }}</div>
            <div class="stat__label">Aktif</div>
        </div>
        <div class="stat">
            <div class="stat__num stat__num--success">{{ $jumlahSelesai ?? 0 }}</div>
            <div class="stat__label">Selesai</div>
        </div>
    </section>

    {{-- Filter --}}
    @php $currentStatus = request('status', ''); @endphp
    <nav class="filters reveal d2" aria-label="Filter status pesanan">
        <a href="{{ route('admin.orders') }}" class="chip {{ !$currentStatus ? 'chip--active' : '' }}">Semua</a>
        <a href="{{ route('admin.orders', ['status' => 'menunggu']) }}" class="chip {{ $currentStatus === 'menunggu' ? 'chip--active' : '' }}">Menunggu</a>
        <a href="{{ route('admin.orders', ['status' => 'dijemput']) }}" class="chip {{ $currentStatus === 'dijemput' ? 'chip--active' : '' }}">Dijemput</a>
        <a href="{{ route('admin.orders', ['status' => 'dicuci']) }}" class="chip {{ $currentStatus === 'dicuci' ? 'chip--active' : '' }}">Dicuci</a>
        <a href="{{ route('admin.orders', ['status' => 'disetrika']) }}" class="chip {{ $currentStatus === 'disetrika' ? 'chip--active' : '' }}">Setrika</a>
        <a href="{{ route('admin.orders', ['status' => 'siap']) }}" class="chip {{ $currentStatus === 'siap' ? 'chip--active' : '' }}">Siap</a>
        <a href="{{ route('admin.orders', ['status' => 'dikirim']) }}" class="chip {{ $currentStatus === 'dikirim' ? 'chip--active' : '' }}">Dikirim</a>
        <a href="{{ route('admin.orders', ['status' => 'selesai']) }}" class="chip {{ $currentStatus === 'selesai' ? 'chip--active' : '' }}">Selesai</a>
    </nav>

    {{-- Order Cards --}}
    @forelse($pesanan as $o)
    @php
        $badgeClass = match(true) {
            $o->status === 'selesai' => 'order-card__badge--selesai',
            $o->status === 'dibatalkan' => 'order-card__badge--batal',
            $o->status === 'menunggu' => 'order-card__badge--menunggu',
            default => 'order-card__badge--proses',
        };
    @endphp
    <article class="order-card reveal d3">
        <div class="order-card__head">
            <span class="order-card__code">#{{ strtoupper($o->order_code) }}</span>
            <span class="order-card__badge {{ $badgeClass }}">{{ $o->status_label }}</span>
        </div>
        <div class="order-card__body">
            <div class="order-card__customer">
                @include('layouts.component._icon', ['name' => 'user', 'size' => 16])
                {{ $o->customer->name ?? '-' }}
            </div>
            <div class="order-card__phone">
                @if($o->customer?->phone)
                    @include('layouts.component._icon', ['name' => 'call', 'size' => 16])
                @endif
                {{ $o->customer->phone ?? '' }}
                @if($o->customer?->phone)
                    <x-wa-button
                        :phone="$o->customer->phone"
                        :message="\App\Support\WhatsApp::customerMessage($o, 'confirm')"
                        label="WA"
                    />
                @endif
            </div>
            <div class="order-card__meta">
                <span class="order-card__chip">
                    @include('layouts.component._icon', ['name' => 'laundry', 'size' => 16])
                    {{ $o->service->name ?? '-' }}
                </span>
                <span class="order-card__chip">{{ $o->weight_estimate }} kg</span>
                @if($o->pickup_date)
                <span class="order-card__chip">
                    @include('layouts.component._icon', ['name' => 'date', 'size' => 16])
                    {{ $o->pickup_date->format('d/m') }}
                </span>
                @endif
            </div>
            <div class="order-card__total">Rp {{ number_format($o->calculated_total, 0, ',', '.') }}</div>
        </div>

        {{-- Panel: Tugaskan Driver Pickup --}}
        @if($o->status === 'menunggu')
        @php $driverPickup = ($daftarDriver ?? collect())->first(); @endphp
        <div class="order-card__panel">
            <div class="order-card__panel-label">
                @include('layouts.component._icon', ['name' => 'pickup', 'size' => 16])
                Tugaskan Kurir Penjemputan
            </div>
            <form method="POST" action="{{ route('admin.orders.assign-driver', $o) }}" class="order-card__panel-form">
                @csrf
                <input type="hidden" name="assignment_type" value="pickup">
                @if($driverPickup)
                <input type="hidden" name="driver_id" value="{{ $driverPickup->id }}">
                <button type="submit" class="order-card__panel-btn order-card__panel-btn--primary order-card__panel-btn--block" data-driver-name="{{ $driverPickup->name }}">
                    Tugaskan Kurir &middot; {{ $driverPickup->name }}
                </button>
                @else
                <button type="button" class="order-card__panel-btn order-card__panel-btn--block" disabled>Belum ada kurir</button>
                @endif
            </form>
        </div>
        @endif

        {{-- Panel: Update Status Workshop --}}
        @if(in_array($o->status, ['dicuci', 'disetrika']))
        <div class="order-card__panel">
            @php $nextStatus = $o->status === 'dicuci' ? 'disetrika' : 'siap'; @endphp
            <div class="order-card__panel-label">
                @include('layouts.component._icon', ['name' => 'refresh', 'size' => 16])
                Update ke: {{ $nextStatus === 'disetrika' ? 'Setrika' : 'Siap Kirim' }}
            </div>
            <form method="POST" action="{{ route('admin.orders.update-status', $o) }}" class="order-card__panel-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $nextStatus }}">
                <button type="submit" class="order-card__panel-btn order-card__panel-btn--accent">
                    Update Status
                </button>
            </form>
        </div>
        @endif

        {{-- Panel: Tugaskan Driver Antar --}}
        @if($o->status === 'siap')
        @php $driverDelivery = ($daftarDriver ?? collect())->first(); @endphp
        <div class="order-card__panel">
            <div class="order-card__panel-label">
                @include('layouts.component._icon', ['name' => 'delivery', 'size' => 16])
                Tugaskan Kurir Pengantaran
            </div>
            <form method="POST" action="{{ route('admin.orders.assign-driver', $o) }}" class="order-card__panel-form">
                @csrf
                <input type="hidden" name="assignment_type" value="delivery">
                @if($driverDelivery)
                <input type="hidden" name="driver_id" value="{{ $driverDelivery->id }}">
                <button type="submit" class="order-card__panel-btn order-card__panel-btn--success order-card__panel-btn--block" data-driver-name="{{ $driverDelivery->name }}">
                    Kirim &middot; {{ $driverDelivery->name }}
                </button>
                @else
                <button type="button" class="order-card__panel-btn order-card__panel-btn--block" disabled>Belum ada kurir</button>
                @endif
            </form>
        </div>
        @endif

        <div class="order-card__footer">
            <a href="{{ route('admin.orders.receipt', $o) }}" target="_blank" rel="noopener" class="order-card__detail-btn">
                @include('layouts.component._icon', ['name' => 'receipt', 'size' => 16])
                Lihat Detail / Struk
            </a>
        </div>
    </article>
    @empty
    <div class="empty reveal d3">
        <div class="empty__icon">
            @include('layouts.component._icon', ['name' => 'pesanan', 'size' => 32])
        </div>
        <div class="empty__title">Belum Ada Pesanan</div>
        <div class="empty__desc">Pesanan baru akan muncul di sini.</div>
    </div>
    @endforelse

    @if($pesanan->hasPages())
    <div class="pagination-wrap">
        {{ $pesanan->links() }}
    </div>
    @endif

</main>

@include('layouts.component.admin._navbar_admin', ['active' => 'pesanan'])
@include('layouts.component._confirm_modal')
@include('layouts.component._bottom_sheet_select')
@include('layouts.component._form_loading')

<script>
(function() {
    // Confirm modal for status update buttons
    document.querySelectorAll('.order-card__panel-btn--accent').forEach(function(btn) {
        var form = btn.closest('form');
        if (!form) return;

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            showConfirmModal({
                title: 'Update Status Pesanan?',
                message: 'Status pesanan akan diperbarui ke tahap selanjutnya. Lanjutkan?',
                confirmText: 'Ya, Update',
                cancelText: 'Batal',
                type: 'info',
                onConfirm: function() { form.submit(); }
            });
        });

        // Change button to type=button to prevent direct submit
        btn.type = 'button';
    });

    // Confirm modal for assign driver buttons (single preselected driver)
    document.querySelectorAll('.order-card__panel-btn--primary, .order-card__panel-btn--success').forEach(function(btn) {
        var form = btn.closest('form');
        if (!form) return;
        if (btn.disabled) return; // no driver available — nothing to confirm
        if (btn.classList.contains('order-card__panel-btn--accent')) return; // skip status update btns

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var driverName = btn.getAttribute('data-driver-name') || 'kurir';
            showConfirmModal({
                title: 'Tugaskan Kurir?',
                message: 'Tugaskan ' + driverName + '? Kurir akan langsung menerima notifikasi tugas baru.',
                confirmText: 'Ya, Tugaskan',
                cancelText: 'Batal',
                type: 'success',
                onConfirm: function() { form.submit(); }
            });
        });

        btn.type = 'button';
    });
})();
</script>

</body>
</html>
