<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Detail Tugas #{{ strtoupper($order->order_code) }} – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · DRIVER · DETAIL TUGAS
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

/* ═══════════════════════════════════════════════ HERO HEADER */
.detail-hero {
    background: linear-gradient(145deg, var(--brand-900) 0%, var(--brand-500) 100%);
    padding: max(env(safe-area-inset-top, 0px), var(--space-5)) var(--space-5) var(--space-7);
    position: relative; overflow: hidden;
}
.detail-hero::after {
    content: ''; position: absolute; width: 160px; height: 160px;
    border-radius: var(--radius-pill); background: rgba(255,255,255,.05);
    top: -50px; right: -40px;
}
.detail-hero__inner { max-width: 520px; margin: 0 auto; position: relative; z-index: 2; }
.detail-hero__nav { display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-4); }
.detail-hero__back {
    width: var(--space-11); height: var(--space-11); border-radius: var(--radius-pill);
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22);
    display: flex; align-items: center; justify-content: center;
    color: var(--surface-raised); text-decoration: none; flex-shrink: 0;
    transition: transform .12s ease;
}
.detail-hero__back:active { transform: scale(.94); }
.detail-hero__code { font-size: .72rem; font-weight: 800; color: rgba(255,255,255,.7); letter-spacing: .8px; }
.detail-hero__status {
    display: inline-flex; align-items: center; gap: var(--space-2);
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22);
    border-radius: var(--radius-pill); padding: var(--space-1) var(--space-3);
    font-size: .68rem; font-weight: 800; color: var(--surface-raised);
    text-transform: uppercase; letter-spacing: .3px; margin-bottom: var(--space-2);
}
.detail-hero__status-dot { width: 7px; height: 7px; border-radius: var(--radius-pill); }
.detail-hero__name { font-weight: 800; font-size: 1.4rem; color: var(--surface-raised); line-height: 1.2; }
.detail-hero__service { font-size: .85rem; font-weight: 600; color: rgba(255,255,255,.75); margin-top: var(--space-1); }

/* ═══════════════════════════════════════════════ CONTENT */
.detail-content { max-width: 520px; margin: calc(var(--space-4) * -1) auto 0; padding: 0 var(--space-4); position: relative; z-index: 10; }

/* ── Alert (designed states) ── */
.detail-alert {
    padding: var(--space-3) var(--space-4); border-radius: var(--radius-btn);
    font-size: .85rem; font-weight: 700; margin-bottom: var(--space-3);
    display: flex; align-items: center; gap: var(--space-2);
}
.detail-alert svg { flex-shrink: 0; }
.detail-alert--success { background: color-mix(in srgb, var(--success-500) 12%, var(--surface-raised)); color: var(--success-500); border: 1px solid color-mix(in srgb, var(--success-500) 24%, transparent); }
.detail-alert--error { background: color-mix(in srgb, var(--danger-500) 10%, var(--surface-raised)); color: var(--danger-500); border: 1px solid color-mix(in srgb, var(--danger-500) 20%, transparent); }

/* ── Section card ── */
.section-card {
    background: var(--surface-raised); border: 1px solid var(--surface-200);
    border-radius: var(--radius-card); margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card); overflow: hidden;
}
.section-card__head {
    display: flex; align-items: center; gap: var(--space-3);
    padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--surface-100);
}
.section-card__icon {
    width: var(--space-9); height: var(--space-9); border-radius: var(--radius-btn);
    background: var(--brand-100); color: var(--brand-500);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.section-card__title { font-weight: 800; font-size: .9rem; color: var(--ink); }

/* ── Data row ── */
.data-row {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--surface-100); gap: var(--space-3);
}
.data-row:last-child { border-bottom: none; }
.data-row__label { font-size: .78rem; font-weight: 600; color: var(--ink-muted); }
.data-row__value { font-size: .85rem; font-weight: 700; color: var(--ink); text-align: right; max-width: 60%; word-break: break-word; }
.data-row__value--accent { color: var(--success-500); }
.data-row__value--primary { color: var(--brand-500); }

/* ── Contact bar ── */
.contact-bar { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-2); padding: var(--space-3) var(--space-4); background: var(--surface-100); }
.contact-bar__btn {
    display: flex; align-items: center; justify-content: center; gap: var(--space-2);
    min-height: var(--space-11); padding: var(--space-2); border-radius: var(--radius-btn);
    font-family: var(--font); font-weight: 800; font-size: .78rem; text-decoration: none;
    border: none; cursor: pointer; transition: transform .12s ease;
}
.contact-bar__btn:active { transform: scale(.96); }
.contact-bar__btn--wa { background: color-mix(in srgb, var(--success-500) 14%, var(--surface-raised)); color: var(--success-500); }
.contact-bar__btn--call { background: var(--brand-100); color: var(--brand-500); }

/* ── Action card ── */
.action-card {
    background: var(--surface-raised); border: 1px solid var(--surface-200);
    border-radius: var(--radius-card); padding: var(--space-4); margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card);
}
.action-card__title { font-weight: 800; font-size: .95rem; color: var(--ink); margin-bottom: var(--space-1); }
.action-card__hint { font-size: .76rem; font-weight: 500; color: var(--ink-muted); margin-bottom: var(--space-4); line-height: 1.5; }
.action-card__field { margin-bottom: var(--space-3); }
.action-card__label { font-size: .72rem; font-weight: 800; color: var(--ink); margin-bottom: var(--space-2); display: block; }
.action-card__input {
    width: 100%; min-height: var(--space-11); padding: var(--space-3) var(--space-3);
    border: 1.5px solid var(--surface-200); border-radius: var(--radius-btn);
    font-family: var(--font); font-weight: 600; font-size: .9rem;
    background: var(--surface-50); color: var(--ink); outline: none;
    transition: border-color .15s ease, background .15s ease;
}
.action-card__input:focus { border-color: var(--brand-500); background: var(--surface-raised); }
.action-card__submit {
    width: 100%; min-height: var(--space-12); padding: var(--space-3); border-radius: var(--radius-btn);
    border: none; font-family: var(--font); font-weight: 800; font-size: .9rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: var(--space-2);
    color: var(--surface-raised); transition: transform .12s ease;
}
.action-card__submit:active { transform: scale(.97); }
.action-card__submit--success { background: var(--success-500); }
.action-card__submit--accent { background: var(--accent-500); }

/* ── Proof image ── */
.proof-img { width: 100%; border-radius: var(--radius-btn); max-height: 260px; object-fit: cover; display: block; }

/* ── History timeline ── */
.timeline { padding: var(--space-3) var(--space-4); }
.timeline__item { display: flex; gap: var(--space-3); padding: var(--space-3) 0; border-bottom: 1px solid var(--surface-100); }
.timeline__item:last-child { border-bottom: none; }
.timeline__dot { width: 10px; height: 10px; border-radius: var(--radius-pill); background: var(--brand-500); margin-top: var(--space-1); flex-shrink: 0; }
.timeline__text { font-size: .82rem; font-weight: 800; color: var(--ink); }
.timeline__meta { font-size: .72rem; font-weight: 500; color: var(--ink-muted); margin-top: 2px; }
</style>
</head>
<body>

{{-- ══════════════ HERO HEADER ══════════════ --}}
<div class="detail-hero">
    <div class="detail-hero__inner">
        <div class="detail-hero__nav">
            <a href="{{ route('driver.orders') }}" class="detail-hero__back" aria-label="Kembali ke daftar tugas">
                @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
            </a>
            <div class="detail-hero__code">#{{ strtoupper($order->order_code) }}</div>
        </div>
        <div class="detail-hero__status">
            <span class="detail-hero__status-dot" style="background:{{ $order->status_color }}"></span>
            {{ $order->status_label }}
        </div>
        <div class="detail-hero__name">{{ $order->customer->name ?? 'Customer' }}</div>
        <div class="detail-hero__service">{{ $order->service->name ?? 'Layanan' }} &middot; {{ ucfirst($order->pickup_time ?? '-') }}</div>
    </div>
</div>

<div class="detail-content">

    @if(session('success'))
        <div class="detail-alert detail-alert--success reveal d1" role="status">
            @include('layouts.component._icon', ['name' => 'success', 'size' => 16])
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="detail-alert detail-alert--error reveal d1" role="alert">
            @include('layouts.component._icon', ['name' => 'error', 'size' => 16])
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="detail-alert detail-alert--error reveal d1" role="alert">
            @include('layouts.component._icon', ['name' => 'error', 'size' => 16])
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Lokasi Customer --}}
    <div class="section-card reveal d2">
        <div class="section-card__head">
            <div class="section-card__icon">
                @include('layouts.component._icon', ['name' => 'location', 'size' => 16])
            </div>
            <div class="section-card__title">Lokasi Customer</div>
        </div>
        <div class="data-row">
            <span class="data-row__label">Alamat</span>
            <span class="data-row__value">{{ $order->customerAddress?->full_address ?? $order->address ?? '-' }}</span>
        </div>
        @if($order->address_note)
        <div class="data-row">
            <span class="data-row__label">Patokan</span>
            <span class="data-row__value">{{ $order->address_note }}</span>
        </div>
        @endif
        <div class="data-row">
            <span class="data-row__label">Zona</span>
            <span class="data-row__value">Zona {{ $order->zone ?? 'A' }}</span>
        </div>
        @php
            // Pesan WA disesuaikan dengan tahap order: kurir lagi jemput vs lagi antar.
            $waContext = in_array($order->status, ['dijemput', 'menunggu']) ? 'pickup' : 'delivery';
            $waUrl = \App\Support\WhatsApp::link(
                $order->customer?->phone,
                \App\Support\WhatsApp::customerMessage($order, $waContext),
            );
        @endphp
        @if($order->customer?->phone && $waUrl)
        <div class="contact-bar">
            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="contact-bar__btn contact-bar__btn--wa">
                @include('layouts.component._icon', ['name' => 'whatsapp', 'size' => 16])
                Chat WA
            </a>
            <a href="tel:{{ $order->customer->phone }}" class="contact-bar__btn contact-bar__btn--call">
                @include('layouts.component._icon', ['name' => 'call', 'size' => 16])
                Telepon
            </a>
        </div>
        @endif
    </div>

    {{-- Detail Pesanan --}}
    <div class="section-card reveal d3">
        <div class="section-card__head">
            <div class="section-card__icon">
                @include('layouts.component._icon', ['name' => 'laundry', 'size' => 16])
            </div>
            <div class="section-card__title">Detail Pesanan</div>
        </div>
        <div class="data-row">
            <span class="data-row__label">Layanan</span>
            <span class="data-row__value">{{ $order->service->name ?? '-' }}</span>
        </div>
        <div class="data-row">
            <span class="data-row__label">Estimasi Berat</span>
            <span class="data-row__value">{{ $order->weight_estimate }} kg</span>
        </div>
        @if($order->weight_actual)
        <div class="data-row">
            <span class="data-row__label">Berat Aktual</span>
            <span class="data-row__value data-row__value--primary">{{ $order->weight_actual }} kg</span>
        </div>
        @endif
        <div class="data-row">
            <span class="data-row__label">Jadwal Jemput</span>
            <span class="data-row__value">{{ $order->pickup_date?->format('d/m/Y') ?? '-' }}, {{ ucfirst($order->pickup_time ?? '-') }}</span>
        </div>
        <div class="data-row">
            <span class="data-row__label">Total</span>
            <span class="data-row__value data-row__value--accent">Rp {{ number_format($order->calculated_total, 0, ',', '.') }}</span>
        </div>
        <div class="data-row">
            <span class="data-row__label">Pembayaran</span>
            <span class="data-row__value">{{ $order->is_paid ? 'Lunas' : 'COD' }}</span>
        </div>
        @if($order->notes)
        <div class="data-row">
            <span class="data-row__label">Catatan</span>
            <span class="data-row__value">{{ $order->notes }}</span>
        </div>
        @endif
    </div>

    {{-- Aksi: Konfirmasi Jemput --}}
    @if($order->status === 'dijemput')
    <div class="action-card reveal d4">
        <div class="action-card__title">Konfirmasi Penjemputan</div>
        <div class="action-card__hint">Masukkan berat aktual setelah pakaian diterima dari customer.</div>
        <form method="POST" action="{{ route('driver.orders.action', $order) }}" id="form-confirm-pickup">
            @csrf
            <input type="hidden" name="status" value="dicuci">
            <div class="action-card__field">
                <label class="action-card__label" for="weight_actual">Berat Aktual (kg)</label>
                <input type="number" step="0.1" min="0.1" max="50" name="weight_actual" id="weight_actual"
                       class="action-card__input" placeholder="Contoh: 4.5"
                       value="{{ old('weight_actual', $order->weight_estimate) }}" required data-fc-stepper>
            </div>
            <button type="button" class="action-card__submit action-card__submit--success" id="btn-confirm-pickup">
                @include('layouts.component._icon', ['name' => 'check', 'size' => 16])
                Konfirmasi Jemput
            </button>
        </form>
    </div>
    @endif

    {{-- Aksi: Selesaikan Pesanan --}}
    @if($order->status === 'dikirim')
    <div class="action-card reveal d4">
        <div class="action-card__title">Konfirmasi Pengiriman</div>
        <div class="action-card__hint">Upload foto bukti setelah pakaian diserahkan ke customer.</div>
        <form method="POST" action="{{ route('driver.orders.action', $order) }}" enctype="multipart/form-data" id="form-confirm-delivery">
            @csrf
            <input type="hidden" name="status" value="selesai">
            <div class="action-card__field">
                <label class="action-card__label" for="proof_image">Foto Bukti Pengiriman</label>
                <input type="file" name="proof_image" id="proof_image" accept="image/*" capture="environment"
                       class="action-card__input" required>
            </div>
            <div class="action-card__field">
                <label class="action-card__label" for="payment_channel">Metode Pembayaran (COD)</label>
                <select name="payment_channel" id="payment_channel" class="action-card__input" required data-fc-segmented data-fc-title="Metode Pembayaran">
                    <option value="cash">Cash (Tunai)</option>
                    <option value="transfer">Transfer Bank</option>
                    <option value="qris">QRIS</option>
                </select>
            </div>
            <button type="button" class="action-card__submit action-card__submit--accent" id="btn-confirm-delivery">
                @include('layouts.component._icon', ['name' => 'camera', 'size' => 16])
                Selesaikan Pesanan
            </button>
        </form>
    </div>
    @endif

    {{-- Bukti Foto --}}
    @if($order->status === 'selesai' && $order->proof_image)
    <div class="section-card reveal d4">
        <div class="section-card__head">
            <div class="section-card__icon">
                @include('layouts.component._icon', ['name' => 'camera', 'size' => 16])
            </div>
            <div class="section-card__title">Bukti Pengiriman</div>
        </div>
        <div style="padding: var(--space-3) var(--space-4)">
            <img src="{{ asset('storage/' . $order->proof_image) }}" class="proof-img" alt="Bukti pengiriman">
        </div>
    </div>
    @endif

    {{-- Riwayat Status --}}
    @if(isset($histori) && $histori->count() > 0)
    <div class="section-card reveal d5">
        <div class="section-card__head">
            <div class="section-card__icon">
                @include('layouts.component._icon', ['name' => 'time', 'size' => 16])
            </div>
            <div class="section-card__title">Riwayat Status</div>
        </div>
        <div class="timeline">
            @foreach($histori as $h)
            <div class="timeline__item">
                <div class="timeline__dot"></div>
                <div>
                    <div class="timeline__text">{{ ucfirst(str_replace('_',' ',$h->status_code)) }}</div>
                    <div class="timeline__meta">{{ $h->status_note ?? '-' }} @if($h->updated_at)&middot; {{ $h->updated_at->format('d M, H:i') }}@endif</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

@include('layouts.component.driver._navbar_driver', ['active' => 'tugas'])
@include('layouts.component._confirm_modal')
@include('layouts.component._form_loading')
@include('layouts.component._form_controls')

@if(in_array($order->status, ['dijemput', 'dikirim'], true))
    @include('layouts.component._driver_geo', ['locationUrl' => route('driver.orders.location', $order)])
@endif

<script>
(function() {
    var btnPickup = document.getElementById('btn-confirm-pickup');
    if (btnPickup) {
        btnPickup.addEventListener('click', function() {
            var weightInput = document.getElementById('weight_actual');
            if (!weightInput || !weightInput.value || parseFloat(weightInput.value) < 0.1) {
                weightInput.focus();
                return;
            }
            showConfirmModal({
                title: 'Konfirmasi Penjemputan?',
                message: 'Pakaian akan ditandai sudah dijemput dengan berat ' + weightInput.value + ' kg. Pastikan sudah diterima dari customer.',
                confirmText: 'Ya, Konfirmasi',
                cancelText: 'Batal',
                type: 'success',
                onConfirm: function() {
                    document.getElementById('form-confirm-pickup').submit();
                }
            });
        });
    }

    var btnDelivery = document.getElementById('btn-confirm-delivery');
    if (btnDelivery) {
        btnDelivery.addEventListener('click', function() {
            var fileInput = document.getElementById('proof_image');
            if (!fileInput || !fileInput.files.length) {
                fileInput.focus();
                return;
            }
            showConfirmModal({
                title: 'Selesaikan Pesanan?',
                message: 'Pesanan akan ditandai selesai dan pembayaran COD dicatat. Pastikan cucian sudah diserahkan ke customer.',
                confirmText: 'Ya, Selesaikan',
                cancelText: 'Batal',
                type: 'warning',
                onConfirm: function() {
                    document.getElementById('form-confirm-delivery').submit();
                }
            });
        });
    }
})();
</script>

</body>
</html>
