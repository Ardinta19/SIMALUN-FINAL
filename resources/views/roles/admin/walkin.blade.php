<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Order Walk-in – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · ORDER WALK-IN
   Design System: all color/radius/shadow/spacing values derive
   from the canonical tokens emitted by _tokens.blade.php.
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
.d1{animation-delay:.04s}.d2{animation-delay:.10s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ── Header ── */
.topbar {
    background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-500) 100%);
    padding: max(env(safe-area-inset-top, 0px), var(--space-4)) var(--space-5) var(--space-5);
    position: sticky; top: 0; z-index: 100;
}
.topbar__inner { display: flex; align-items: center; gap: var(--space-3); max-width: 520px; margin: 0 auto; }
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

/* ── Layout container ── */
.container { max-width: 520px; margin: 0 auto; padding: var(--space-4); }

/* ── Alerts ── */
.alert {
    display: flex; align-items: flex-start; gap: var(--space-2);
    padding: var(--space-3) var(--space-4); border-radius: var(--radius-btn);
    font-size: .85rem; font-weight: 700; margin-bottom: var(--space-3);
}
.alert--success { background: var(--success-tint); color: var(--success-500); border: 1px solid color-mix(in srgb, var(--success-500) 28%, transparent); }
.alert--error   { background: var(--danger-tint);  color: var(--danger-500);  border: 1px solid color-mix(in srgb, var(--danger-500) 24%, transparent); }
.alert svg { flex-shrink: 0; margin-top: 1px; }
.alert__body { min-width: 0; }
.alert__title { font-weight: 800; }
.alert__list { margin: var(--space-1) 0 0; padding-left: var(--space-4); font-weight: 600; }

/* ── Form card ── */
.form-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius-card); overflow: hidden; box-shadow: var(--shadow-card);
}
.form-card__head { padding: var(--space-4); border-bottom: 1px solid var(--line-soft); display: flex; align-items: center; gap: var(--space-3); }
.form-card__icon {
    width: var(--space-9); height: var(--space-9); border-radius: var(--radius-btn);
    background: var(--accent-tint); color: var(--accent-500);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.form-card__title { font-weight: 800; font-size: .95rem; color: var(--ink); }
.form-card__body { padding: var(--space-4); }

/* ── Form fields ── */
.form-group { margin-bottom: var(--space-4); }
.form-label { font-size: .76rem; font-weight: 800; color: var(--ink-mid); margin-bottom: var(--space-2); display: block; }
.form-label__req { color: var(--danger-500); }
.form-input {
    width: 100%; min-height: var(--space-11); padding: var(--space-3);
    border: 1.5px solid var(--border); border-radius: var(--radius-btn);
    font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 600; font-size: 16px;
    color: var(--ink); background: var(--card); outline: none; transition: border-color .2s ease;
}
.form-input:focus { border-color: var(--brand-500); }
.form-input::placeholder { color: var(--ink-lt); font-weight: 500; }

/* ── Custom field trigger (replaces native <select>) ── */
.field-trigger {
    width: 100%; min-height: var(--space-11);
    padding: var(--space-2) var(--space-3);
    border: 1.5px solid var(--border); border-radius: var(--radius-btn);
    background: var(--card); cursor: pointer;
    display: flex; align-items: center; gap: var(--space-2);
    text-align: left; font-family: 'Plus Jakarta Sans', sans-serif;
    transition: border-color .2s ease, background .12s ease;
}
.field-trigger:active { background: var(--surface-50); }
.field-trigger:focus-visible { outline: none; border-color: var(--brand-500); }
.field-trigger.is-active { border-color: var(--brand-500); }
.field-trigger__body { flex: 1; min-width: 0; }
.field-trigger__value {
    font-size: 16px; font-weight: 700; color: var(--ink);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.field-trigger__value.is-placeholder { font-weight: 500; color: var(--ink-lt); }
.field-trigger__sub {
    font-size: .72rem; font-weight: 600; color: var(--ink-lt); margin-top: 1px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.field-trigger__chev { color: var(--ink-lt); flex-shrink: 0; display: flex; }

/* ── Segmented control (replaces native <select> for slot proses) ── */
.segmented {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-1);
    padding: var(--space-1);
    background: var(--surface-100); border-radius: var(--radius-btn);
}
.segmented__btn {
    min-height: var(--space-11); border: none; border-radius: var(--space-2);
    background: transparent; color: var(--ink-mid); cursor: pointer;
    font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: .88rem;
    display: flex; align-items: center; justify-content: center; gap: var(--space-1);
    transition: background .15s ease, color .15s ease, box-shadow .15s ease;
}
.segmented__btn:active { transform: none; }
.segmented__btn.is-selected {
    background: var(--brand-500); color: var(--surface-raised);
    box-shadow: 0 2px 8px color-mix(in srgb, var(--brand-500) 32%, transparent);
}
@media (prefers-reduced-motion: reduce){ .segmented__btn { transition: none; } }

/* ── Stepper (number input with − / + controls) ── */
.stepper {
    display: grid; grid-template-columns: var(--space-11) 1fr var(--space-11);
    align-items: stretch;
    border: 1.5px solid var(--border); border-radius: var(--radius-btn);
    overflow: hidden; background: var(--card);
}
.stepper:focus-within { border-color: var(--brand-500); }
.stepper__btn {
    min-width: var(--space-11); min-height: var(--space-11);
    border: none; background: var(--surface-100); color: var(--brand-500);
    font-size: 1.4rem; font-weight: 800; line-height: 1; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    -webkit-user-select: none; user-select: none;
    transition: background .12s ease;
}
.stepper__btn:active { background: color-mix(in srgb, var(--brand-500) 18%, var(--surface-raised)); }
.stepper__btn:disabled { opacity: .4; cursor: not-allowed; }
.stepper__input {
    border: none; outline: none; background: transparent; min-width: 0; width: 100%;
    text-align: center; font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800; font-size: 16px; color: var(--ink);
    -moz-appearance: textfield; appearance: textfield;
}
.stepper__input::-webkit-outer-spin-button,
.stepper__input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

/* ── Item lines ── */
.items-section { border: 1.5px solid var(--border); border-radius: var(--radius-btn); padding: var(--space-3); margin-bottom: var(--space-4); }
.items-section__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-3); }
.items-section__title { display: flex; align-items: center; gap: var(--space-1); font-size: .78rem; font-weight: 800; color: var(--ink-mid); }
.items-section__hint { font-size: .7rem; font-weight: 600; color: var(--ink-lt); }
.items-wrap { display: grid; gap: var(--space-2); }
.item-row {
    display: grid;
    grid-template-columns: 1fr var(--space-11);
    grid-template-areas:
        "service service"
        "qty     remove";
    gap: var(--space-2); align-items: center;
    padding: var(--space-2);
    border: 1.5px solid var(--line-soft); border-radius: var(--radius-btn);
    background: var(--surface-50);
}
.item-row [data-item-service-trigger] { grid-area: service; }
.item-row .stepper { grid-area: qty; }
.item-row .item-remove { grid-area: remove; }
.item-remove {
    width: var(--space-11); height: var(--space-11);
    border: 1.5px solid color-mix(in srgb, var(--danger-500) 24%, transparent);
    background: var(--danger-tint); color: var(--danger-500); border-radius: var(--radius-btn);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.item-remove:disabled { opacity: .4; cursor: not-allowed; }
.btn-add-item {
    margin-top: var(--space-2); min-height: var(--space-11); padding: 0 var(--space-3);
    border: 1.5px dashed color-mix(in srgb, var(--brand-500) 40%, transparent);
    background: var(--brand-tint); color: var(--brand-500); border-radius: var(--radius-btn);
    font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: .8rem;
    cursor: pointer; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: var(--space-1);
    transition: background .12s ease;
}
.btn-add-item:active { background: color-mix(in srgb, var(--brand-500) 22%, var(--surface-raised)); }

/* ── Submit ── */
.form-submit {
    width: 100%; min-height: var(--space-12); border: none; border-radius: var(--radius-btn);
    background: var(--accent-500); color: var(--surface-raised);
    font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: .95rem;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: var(--space-2);
    box-shadow: 0 4px 14px color-mix(in srgb, var(--accent-500) 34%, transparent);
    transition: transform .12s ease;
}
.form-submit:active { transform: scale(.97); }
@media (prefers-reduced-motion: reduce){ .form-submit:active, .topbar__back:active { transform: none; } }
</style>
</head>
<body>

<header class="topbar">
    <div class="topbar__inner">
        <a href="{{ route('admin.orders') }}" class="topbar__back" aria-label="Kembali ke daftar pesanan">
            @include('layouts.component._icon', ['name' => 'back', 'size' => 20])
        </a>
        <div>
            <div class="topbar__title">Order Walk-in</div>
            <div class="topbar__subtitle">Tambah pesanan pelanggan langsung</div>
        </div>
    </div>
</header>

<main class="container">

    @if(session('status'))
        <div class="alert alert--success" role="status">
            @include('layouts.component._icon', ['name' => 'success', 'size' => 20])
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert--error" role="alert">
            @include('layouts.component._icon', ['name' => 'warning', 'size' => 20])
            <div class="alert__body">
                <div class="alert__title">Periksa kembali isian Anda</div>
                <ul class="alert__list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="form-card reveal d1">
        <div class="form-card__head">
            <div class="form-card__icon">
                @include('layouts.component._icon', ['name' => 'add-user', 'size' => 20])
            </div>
            <div class="form-card__title">Data Pelanggan & Order</div>
        </div>
        <div class="form-card__body">
            <form method="POST" action="{{ route('admin.orders.walk-in.store') }}" id="walkin-form">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="walkin-customer-name">Nama Pelanggan <span class="form-label__req">*</span></label>
                    <input id="walkin-customer-name" name="customer_name" type="text" placeholder="Nama lengkap" required class="form-input" value="{{ old('customer_name') }}">
                </div>

                <div class="form-group">
                    <label class="form-label" for="walkin-customer-phone">No. HP (opsional)</label>
                    <input id="walkin-customer-phone" name="customer_phone" type="tel" inputmode="numeric" placeholder="08xxxxxxxxxx" class="form-input" value="{{ old('customer_phone') }}">
                </div>

                {{-- Layanan: custom bottom-sheet trigger backing a hidden service_id input --}}
                <div class="form-group">
                    <label class="form-label">Layanan <span class="form-label__req">*</span></label>
                    <input type="hidden" name="service_id" id="walkin-service-main" value="{{ old('service_id') }}" required>
                    <button type="button" class="field-trigger" id="walkin-service-trigger" aria-haspopup="dialog">
                        <div class="field-trigger__body">
                            <div class="field-trigger__value is-placeholder" id="walkin-service-label">Pilih layanan</div>
                            <div class="field-trigger__sub" id="walkin-service-sub" hidden></div>
                        </div>
                        <span class="field-trigger__chev">
                            @include('layouts.component._icon', ['name' => 'chevron-down', 'size' => 20])
                        </span>
                    </button>
                </div>

                {{-- Berat (kg): number input wrapped in a stepper --}}
                <div class="form-group">
                    <label class="form-label" for="walkin-weight">Berat (kg) <span class="form-label__req">*</span></label>
                    <div class="stepper" data-stepper data-step="0.5" data-min="0.5" data-max="50" data-decimals="1">
                        <button type="button" class="stepper__btn" data-stepper-dec aria-label="Kurangi berat">−</button>
                        <input id="walkin-weight" name="weight_estimate" type="number" min="0.5" max="50" step="0.1" inputmode="decimal" placeholder="Contoh: 3.5" required class="stepper__input" value="{{ old('weight_estimate') }}">
                        <button type="button" class="stepper__btn" data-stepper-inc aria-label="Tambah berat">+</button>
                    </div>
                </div>

                {{-- Slot Proses: segmented control backing a hidden pickup_time input --}}
                <div class="form-group">
                    <label class="form-label">Slot Proses <span class="form-label__req">*</span></label>
                    <input type="hidden" name="pickup_time" id="walkin-pickup-time" value="{{ old('pickup_time', 'pagi') }}" required>
                    <div class="segmented" role="group" aria-label="Slot proses">
                        <button type="button" class="segmented__btn" data-slot="pagi">Pagi</button>
                        <button type="button" class="segmented__btn" data-slot="siang">Siang</button>
                        <button type="button" class="segmented__btn" data-slot="sore">Sore</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="walkin-notes">Catatan (opsional)</label>
                    <input id="walkin-notes" name="notes" type="text" placeholder="Catatan khusus" class="form-input" value="{{ old('notes') }}">
                </div>

                {{-- Item Lines --}}
                <div class="items-section">
                    <div class="items-section__head">
                        <span class="items-section__title">
                            @include('layouts.component._icon', ['name' => 'laundry', 'size' => 16])
                            Item Satuan (opsional)
                        </span>
                        <span class="items-section__hint">Tambah sesuai kebutuhan</span>
                    </div>

                    <div id="walkin-items-wrap" class="items-wrap"></div>

                    <button type="button" id="walkin-add-item" class="btn-add-item">
                        @include('layouts.component._icon', ['name' => 'plus', 'size' => 16])
                        Tambah Item Satuan
                    </button>

                    <template id="walkin-item-template">
                        <div class="item-row" data-item-row>
                            <button type="button" class="field-trigger" data-item-service-trigger aria-haspopup="dialog">
                                <div class="field-trigger__body">
                                    <div class="field-trigger__value is-placeholder" data-item-service-label>Pilih item</div>
                                    <div class="field-trigger__sub" data-item-service-sub hidden></div>
                                </div>
                                <span class="field-trigger__chev">
                                    @include('layouts.component._icon', ['name' => 'chevron-down', 'size' => 20])
                                </span>
                            </button>
                            <input type="hidden" data-item-service value="">
                            <div class="stepper" data-stepper data-step="1" data-min="1" data-max="999" data-decimals="0">
                                <button type="button" class="stepper__btn" data-stepper-dec aria-label="Kurangi jumlah">−</button>
                                <input type="number" min="1" max="999" step="1" inputmode="numeric" placeholder="Qty" class="stepper__input" data-item-qty value="1">
                                <button type="button" class="stepper__btn" data-stepper-inc aria-label="Tambah jumlah">+</button>
                            </div>
                            <button type="button" class="item-remove" data-item-remove aria-label="Hapus item">
                                @include('layouts.component._icon', ['name' => 'close', 'size' => 16])
                            </button>
                        </div>
                    </template>
                </div>

                <button type="submit" class="form-submit">
                    @include('layouts.component._icon', ['name' => 'plus', 'size' => 20])
                    Buat Order Walk-in
                </button>
            </form>
        </div>
    </div>

</main>

@include('layouts.component.admin._navbar_admin', ['active' => 'pesanan'])
@include('layouts.component._form_loading')
@include('layouts.component._bottom_sheet_select')

@php
    // Build picker data from the SAME source the native select used, so the
    // submitted service_id value space stays identical (no-regression).
    $mainServiceOptions = [];
    if(($kgCategories ?? collect())->count() > 0) {
        foreach($kgCategories as $cat) {
            foreach($cat->services as $layanan) {
                $mainServiceOptions[] = [
                    'value' => (string) $layanan->id,
                    'label' => $layanan->name,
                    'sub'   => $cat->name.' · Rp '.number_format($layanan->effective_unit_price, 0, ',', '.').'/kg',
                    'category' => (string) $layanan->service_category_id,
                ];
            }
        }
    } else {
        foreach(($daftarLayanan ?? collect()) as $layanan) {
            if(($layanan->pricing_model ?? 'per_kg') === 'per_kg') {
                $mainServiceOptions[] = [
                    'value' => (string) $layanan->id,
                    'label' => $layanan->name,
                    'sub'   => 'Rp '.number_format($layanan->effective_unit_price, 0, ',', '.').'/kg',
                    'category' => (string) $layanan->service_category_id,
                ];
            }
        }
    }

    $itemServiceOptions = [];
    if(($itemCategories ?? collect())->count() > 0) {
        foreach($itemCategories as $cat) {
            foreach($cat->services as $layananItem) {
                $itemServiceOptions[] = [
                    'value' => (string) $layananItem->id,
                    'label' => $layananItem->name,
                    'sub'   => $cat->name.' · Rp '.number_format($layananItem->effective_unit_price, 0, ',', '.').'/item',
                    'category' => (string) $layananItem->service_category_id,
                ];
            }
        }
    } else {
        foreach(($daftarLayananItem ?? collect()) as $layananItem) {
            $itemServiceOptions[] = [
                'value' => (string) $layananItem->id,
                'label' => $layananItem->name,
                'sub'   => 'Rp '.number_format($layananItem->effective_unit_price, 0, ',', '.').'/item',
                'category' => (string) $layananItem->service_category_id,
            ];
        }
    }
@endphp

<script>
(function () {
    var MAIN_OPTIONS = @json($mainServiceOptions, JSON_UNESCAPED_UNICODE);
    var ITEM_OPTIONS = @json($itemServiceOptions, JSON_UNESCAPED_UNICODE);
    var OLD_SERVICE_ID = @json((string) old('service_id', ''));

    /* ── Main service (custom bottom-sheet trigger) ───────────────── */
    var serviceInput   = document.getElementById('walkin-service-main');
    var serviceTrigger = document.getElementById('walkin-service-trigger');
    var serviceLabel   = document.getElementById('walkin-service-label');
    var serviceSub     = document.getElementById('walkin-service-sub');
    var mainCategoryId = '';

    function findOption(list, value) {
        for (var i = 0; i < list.length; i++) {
            if (String(list[i].value) === String(value)) return list[i];
        }
        return null;
    }

    function applyMainService(value) {
        var opt = findOption(MAIN_OPTIONS, value);
        if (opt) {
            serviceInput.value = opt.value;
            serviceLabel.textContent = opt.label;
            serviceLabel.classList.remove('is-placeholder');
            mainCategoryId = opt.category || '';
            if (opt.sub) { serviceSub.textContent = opt.sub; serviceSub.hidden = false; }
            else { serviceSub.hidden = true; }
            serviceTrigger.classList.add('is-active');
        } else {
            serviceInput.value = '';
            serviceLabel.textContent = 'Pilih layanan';
            serviceLabel.classList.add('is-placeholder');
            serviceSub.hidden = true;
            mainCategoryId = '';
            serviceTrigger.classList.remove('is-active');
        }
        syncItemCategories();
    }

    serviceTrigger.addEventListener('click', function () {
        window.showBottomSelect({
            title: 'Pilih Layanan',
            options: MAIN_OPTIONS,
            onSelect: function (value) { applyMainService(value); }
        });
    });

    /* ── Slot proses (segmented control) ──────────────────────────── */
    var slotInput = document.getElementById('walkin-pickup-time');
    var slotBtns = document.querySelectorAll('.segmented__btn[data-slot]');

    function applySlot(value) {
        var matched = false;
        slotBtns.forEach(function (btn) {
            var on = btn.getAttribute('data-slot') === value;
            btn.classList.toggle('is-selected', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            if (on) matched = true;
        });
        if (matched) slotInput.value = value;
    }
    slotBtns.forEach(function (btn) {
        btn.addEventListener('click', function () { applySlot(btn.getAttribute('data-slot')); });
    });
    // Default: old() value if present, else first option.
    (function () {
        var initial = slotInput.value || (slotBtns[0] && slotBtns[0].getAttribute('data-slot'));
        applySlot(initial);
    })();

    /* ── Stepper (generic; handles weight + qty) ──────────────────── */
    function clamp(n, min, max) {
        if (typeof min === 'number' && n < min) n = min;
        if (typeof max === 'number' && n > max) n = max;
        return n;
    }
    function bindStepper(root) {
        var input = root.querySelector('.stepper__input');
        var dec = root.querySelector('[data-stepper-dec]');
        var inc = root.querySelector('[data-stepper-inc]');
        if (!input || !dec || !inc) return;
        var step = parseFloat(root.getAttribute('data-step')) || 1;
        var min = root.hasAttribute('data-min') ? parseFloat(root.getAttribute('data-min')) : null;
        var max = root.hasAttribute('data-max') ? parseFloat(root.getAttribute('data-max')) : null;
        var decimals = parseInt(root.getAttribute('data-decimals') || '0', 10);

        function fmt(n) { return decimals > 0 ? n.toFixed(decimals) : String(Math.round(n)); }
        function bump(dir) {
            var current = parseFloat(input.value);
            if (isNaN(current)) current = (min !== null ? min : 0);
            var next = clamp(current + dir * step, min, max);
            next = Math.round(next * 1e6) / 1e6; // kill float drift
            input.value = fmt(next);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        dec.addEventListener('click', function () { bump(-1); });
        inc.addEventListener('click', function () { bump(1); });
    }
    document.querySelectorAll('[data-stepper]').forEach(bindStepper);

    /* ── Item lines (dynamic rows) ────────────────────────────────── */
    var itemsWrap = document.getElementById('walkin-items-wrap');
    var addItemBtn = document.getElementById('walkin-add-item');
    var itemTemplate = document.getElementById('walkin-item-template');

    function visibleItemOptions() {
        if (!mainCategoryId) return ITEM_OPTIONS;
        return ITEM_OPTIONS.filter(function (o) { return String(o.category) === String(mainCategoryId); });
    }

    function setItemRowService(row, value) {
        var input = row.querySelector('[data-item-service]');
        var label = row.querySelector('[data-item-service-label]');
        var sub = row.querySelector('[data-item-service-sub]');
        var opt = findOption(ITEM_OPTIONS, value);
        if (opt) {
            input.value = opt.value;
            input.setAttribute('data-category', opt.category || '');
            label.textContent = opt.label;
            label.classList.remove('is-placeholder');
            if (opt.sub) { sub.textContent = opt.sub; sub.hidden = false; } else { sub.hidden = true; }
        } else {
            input.value = '';
            input.removeAttribute('data-category');
            label.textContent = 'Pilih item';
            label.classList.add('is-placeholder');
            sub.hidden = true;
        }
    }

    // When the main category changes, clear item selections that no longer match.
    function syncItemCategories() {
        if (!itemsWrap) return;
        itemsWrap.querySelectorAll('[data-item-row]').forEach(function (row) {
            var input = row.querySelector('[data-item-service]');
            if (!input || !input.value) return;
            var cat = input.getAttribute('data-category') || '';
            if (mainCategoryId && String(cat) !== String(mainCategoryId)) {
                setItemRowService(row, '');
            }
        });
    }

    function reindexItemNames() {
        var rows = itemsWrap.querySelectorAll('[data-item-row]');
        rows.forEach(function (row, i) {
            var service = row.querySelector('[data-item-service]');
            var qty = row.querySelector('[data-item-qty]');
            if (service) service.name = 'item_lines[' + i + '][service_id]';
            if (qty) qty.name = 'item_lines[' + i + '][qty]';
        });
        var removeButtons = itemsWrap.querySelectorAll('[data-item-remove]');
        removeButtons.forEach(function (btn) { btn.disabled = rows.length <= 1; });
    }

    function addItemRow() {
        var node = itemTemplate.content.cloneNode(true);
        var row = node.querySelector('[data-item-row]');
        var trigger = node.querySelector('[data-item-service-trigger]');
        var removeBtn = node.querySelector('[data-item-remove]');

        trigger.addEventListener('click', function () {
            window.showBottomSelect({
                title: 'Pilih Item Satuan',
                options: visibleItemOptions(),
                onSelect: function (value) { setItemRowService(row, value); }
            });
        });
        removeBtn.addEventListener('click', function () {
            row.remove();
            reindexItemNames();
        });

        node.querySelectorAll('[data-stepper]').forEach(bindStepper);

        itemsWrap.appendChild(node);
        reindexItemNames();
    }

    addItemBtn.addEventListener('click', addItemRow);

    // Initialise: restore old() main service, then seed first item row.
    applyMainService(OLD_SERVICE_ID);
    addItemRow();
})();
</script>

</body>
</html>
