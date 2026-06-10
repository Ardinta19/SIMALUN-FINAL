<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Buat Voucher – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · BUAT VOUCHER (CREATE FORM)
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
    padding-bottom: calc(var(--space-16) + env(safe-area-inset-bottom, 0px));
    overflow-x: hidden;
}
.wrap { max-width: 520px; margin: 0 auto; padding: 0 var(--space-4); }

@keyframes riseIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.reveal { opacity: 0; animation: riseIn .5s cubic-bezier(.22,.61,.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}.d4{animation-delay:.22s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ── Header ── */
.appbar {
    max-width: 520px; margin: 0 auto;
    display: flex; align-items: center; gap: var(--space-3);
    padding: max(env(safe-area-inset-top, 0px), var(--space-3)) var(--space-4) var(--space-4);
}
.appbar__title { font-size: 1.15rem; font-weight: 800; color: var(--brand-900); line-height: 1.1; }

/* ── Section card ── */
.card {
    background: var(--surface-raised); border-radius: var(--radius-card);
    border: 1px solid var(--surface-200); box-shadow: var(--shadow-card);
    padding: var(--space-5); margin-bottom: var(--space-4);
}
.card__title {
    display: flex; align-items: center; gap: var(--space-2);
    font-size: .82rem; font-weight: 800; color: var(--ink);
    text-transform: uppercase; letter-spacing: .4px;
    margin-bottom: var(--space-4);
}
.card__title svg { color: var(--brand-500); }

/* ── Fields ── */
.field { margin-bottom: var(--space-4); }
.field:last-child { margin-bottom: 0; }
.field__label {
    display: block; font-size: .78rem; font-weight: 800; color: var(--ink);
    margin-bottom: var(--space-2);
}
.field__hint { display: block; font-size: .72rem; color: var(--ink-muted); font-weight: 600; margin-top: var(--space-2); line-height: 1.4; }
.input, .textarea {
    width: 100%; min-height: var(--space-12);
    padding: var(--space-3) var(--space-4);
    border: 1.5px solid var(--surface-200); border-radius: var(--radius-btn);
    font-family: var(--font); font-size: .95rem; font-weight: 600; color: var(--ink);
    background: var(--surface-raised); outline: none;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.input:focus, .textarea:focus {
    border-color: var(--brand-500);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-500) 18%, transparent);
}
.input--mono {
    font-family: ui-monospace, 'Courier New', monospace;
    text-transform: uppercase; letter-spacing: 1px; font-weight: 800;
}

/* ── Affix input (unit prefix/suffix, e.g. Rp / %) ── */
.affix {
    display: flex; align-items: stretch;
    border: 1.5px solid var(--surface-200); border-radius: var(--radius-btn);
    background: var(--surface-raised); overflow: hidden;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.affix:focus-within { border-color: var(--brand-500); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-500) 18%, transparent); }
.affix__unit {
    display: flex; align-items: center; justify-content: center;
    padding: 0 var(--space-4); min-width: var(--space-12);
    background: var(--surface-100); color: var(--ink-muted);
    font-weight: 800; font-size: .92rem; flex-shrink: 0;
}
.affix__input {
    flex: 1; min-width: 0; border: none; outline: none; background: transparent;
    padding: var(--space-3) var(--space-4); min-height: var(--space-12);
    font-family: var(--font); font-size: 1rem; font-weight: 700; color: var(--ink);
    -moz-appearance: textfield; appearance: textfield;
}
.affix__input::-webkit-outer-spin-button,
.affix__input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.affix__input::placeholder { color: var(--ink-muted); font-weight: 500; }

.row-2 { display: grid; grid-template-columns: 1fr; gap: var(--space-3); }
@media (min-width: 460px) { .row-2--num { grid-template-columns: 1fr 1fr; } }
.error { color: var(--danger-500); font-size: .74rem; font-weight: 700; margin-top: var(--space-2); }

/* ── Hideable field (animated) ── */
.field[hidden] { display: none; }

/* ── Switch toggle ── */
.switch-row {
    display: flex; align-items: center; justify-content: space-between; gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    background: var(--brand-100); border-radius: var(--radius-btn);
    border: 1px solid var(--surface-200); cursor: pointer;
}
.switch-row__text { font-size: .88rem; font-weight: 700; color: var(--ink); }
.switch-row__sub { font-size: .72rem; font-weight: 600; color: var(--ink-muted); margin-top: 2px; }
.switch { position: relative; width: 46px; height: 28px; flex-shrink: 0; }
.switch input { position: absolute; opacity: 0; width: 0; height: 0; }
.switch__track {
    position: absolute; inset: 0; border-radius: var(--radius-pill);
    background: var(--surface-300, #cbd5e1); transition: background .2s ease;
}
.switch__thumb {
    position: absolute; top: 3px; left: 3px; width: 22px; height: 22px;
    border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.25);
    transition: transform .2s cubic-bezier(.34,1.56,.64,1);
}
.switch input:checked ~ .switch__track { background: var(--success-500); }
.switch input:checked ~ .switch__thumb { transform: translateX(18px); }
.switch input:focus-visible ~ .switch__track { box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-500) 30%, transparent); }
@media (prefers-reduced-motion: reduce){ .switch__thumb, .switch__track { transition: none; } }

/* ── Submit ── */
.btn-submit {
    width: 100%; min-height: var(--space-12); margin-top: var(--space-2);
    display: inline-flex; align-items: center; justify-content: center; gap: var(--space-2);
    background: var(--brand-500); color: var(--surface-raised);
    border: none; border-radius: var(--radius-btn);
    font-family: var(--font); font-size: .95rem; font-weight: 800; cursor: pointer;
    box-shadow: 0 6px 16px color-mix(in srgb, var(--brand-500) 30%, transparent);
    transition: transform .12s ease;
}
.btn-submit:active { transform: scale(.98); }

/* Enhanced custom controls fill their field width */
.field .fc-trigger, .field .fc-segmented { width: 100%; }
</style>
</head>
<body>
<header class="appbar reveal d1">
    <x-back-button fallback="admin.vouchers.index" style="hero" :smart="false" />
    <h1 class="appbar__title">Buat Voucher Baru</h1>
</header>

<main class="wrap">
    <form method="POST" action="{{ route('admin.vouchers.store') }}" id="voucher-form">
        @csrf

        {{-- ══════════ IDENTITAS ══════════ --}}
        <section class="card reveal d2">
            <div class="card__title">
                @include('layouts.component._icon', ['name' => 'voucher', 'size' => 18])
                Identitas Voucher
            </div>

            <div class="field">
                <label class="field__label" for="code">Kode Voucher</label>
                <input type="text" name="code" id="code" class="input input--mono"
                       value="{{ old('code') }}" required maxlength="30" placeholder="WELCOME10"
                       autocomplete="off" autocapitalize="characters">
                <span class="field__hint">Disimpan UPPERCASE. Customer memasukkan kode ini saat memesan.</span>
                @error('code')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label class="field__label" for="description">Keterangan</label>
                <input type="text" name="description" id="description" class="input"
                       value="{{ old('description') }}" required maxlength="200"
                       placeholder="Diskon 10% untuk customer baru">
                @error('description')<div class="error">{{ $message }}</div>@enderror
            </div>
        </section>

        {{-- ══════════ DISKON ══════════ --}}
        <section class="card reveal d3">
            <div class="card__title">
                @include('layouts.component._icon', ['name' => 'tag', 'size' => 18])
                Aturan Diskon
            </div>

            <div class="field">
                <label class="field__label" for="type">Jenis Diskon</label>
                <select name="type" id="type" class="input" required data-fc-segmented data-fc-title="Jenis Diskon">
                    <option value="percent" {{ old('type', 'percent') === 'percent' ? 'selected' : '' }}>Persen (%)</option>
                    <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Nominal (Rp)</option>
                </select>
                @error('type')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label class="field__label" for="value">Nilai Diskon</label>
                <div class="affix">
                    <span class="affix__unit" id="value-prefix">Rp</span>
                    <input type="number" name="value" id="value" class="affix__input"
                           value="{{ old('value') }}" required min="1" step="1" inputmode="numeric" placeholder="0">
                    <span class="affix__unit" id="value-suffix">%</span>
                </div>
                <span class="field__hint" id="value-hint">Diskon dalam persen, maksimal 100%.</span>
                @error('value')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field" id="field-max-discount">
                <label class="field__label" for="max_discount">Maks. Diskon (Rp)</label>
                <div class="affix">
                    <span class="affix__unit">Rp</span>
                    <input type="number" name="max_discount" id="max_discount" class="affix__input"
                           value="{{ old('max_discount') }}" min="1" step="1000" inputmode="numeric" placeholder="Opsional">
                </div>
                <span class="field__hint">Batas maksimum potongan untuk diskon persen. Kosongkan jika tanpa batas.</span>
                @error('max_discount')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label class="field__label" for="min_order">Min. Order</label>
                <div class="affix">
                    <span class="affix__unit">Rp</span>
                    <input type="number" name="min_order" id="min_order" class="affix__input"
                           value="{{ old('min_order', 0) }}" min="0" step="1000" inputmode="numeric" placeholder="0">
                </div>
                <span class="field__hint">Minimal belanja agar voucher bisa dipakai. Isi 0 untuk tanpa minimum.</span>
                @error('min_order')<div class="error">{{ $message }}</div>@enderror
            </div>
        </section>

        {{-- ══════════ MASA BERLAKU & BATAS ══════════ --}}
        <section class="card reveal d4">
            <div class="card__title">
                @include('layouts.component._icon', ['name' => 'date', 'size' => 18])
                Masa Berlaku & Batas
            </div>

            <div class="row-2">
                <div class="field">
                    <label class="field__label" for="valid_from">Berlaku Dari</label>
                    <input type="date" name="valid_from" id="valid_from" class="input"
                           value="{{ old('valid_from') }}" data-fc-date data-fc-title="Berlaku Dari">
                    @error('valid_from')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label class="field__label" for="valid_until">Berlaku Sampai</label>
                    <input type="date" name="valid_until" id="valid_until" class="input"
                           value="{{ old('valid_until') }}" data-fc-date data-fc-title="Berlaku Sampai">
                    @error('valid_until')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="field">
                <label class="field__label" for="usage_limit">Batas Pemakaian</label>
                <input type="number" name="usage_limit" id="usage_limit" class="input"
                       value="{{ old('usage_limit') }}" min="1" step="1" inputmode="numeric"
                       placeholder="Opsional, kosongkan untuk tanpa batas">
                <span class="field__hint">Total pemakaian gabungan dari semua customer.</span>
                @error('usage_limit')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label class="switch-row">
                    <span>
                        <span class="switch-row__text">Langsung aktifkan</span>
                        <span class="switch-row__sub">Voucher bisa dipakai segera setelah dibuat</span>
                    </span>
                    <span class="switch">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span class="switch__track"></span>
                        <span class="switch__thumb"></span>
                    </span>
                </label>
            </div>
        </section>

        <button type="submit" class="btn-submit reveal d4">
            @include('layouts.component._icon', ['name' => 'check', 'size' => 20])
            Simpan Voucher
        </button>
    </form>
</main>

@include('layouts.component._form_controls')

<script>
(function () {
    var typeSel    = document.getElementById('type');
    var valuePre   = document.getElementById('value-prefix');
    var valueSuf   = document.getElementById('value-suffix');
    var valueHint  = document.getElementById('value-hint');
    var valueInput = document.getElementById('value');
    var maxField   = document.getElementById('field-max-discount');
    if (!typeSel) return;

    function applyType() {
        var isPercent = (typeSel.value || 'percent') === 'percent';

        // Unit affordance on the value field: % (suffix) for percent, Rp (prefix) for nominal.
        valuePre.style.display = isPercent ? 'none' : 'flex';
        valueSuf.style.display = isPercent ? 'flex' : 'none';

        // Constraints + hint adapt to the discount type.
        if (isPercent) {
            valueInput.max = '100';
            valueInput.step = '1';
            valueHint.textContent = 'Diskon dalam persen, maksimal 100%.';
        } else {
            valueInput.removeAttribute('max');
            valueInput.step = '1000';
            valueHint.textContent = 'Diskon dalam rupiah, contoh 5000.';
        }

        // Max-discount cap is only meaningful for percentage discounts.
        if (maxField) maxField.hidden = !isPercent;
    }

    typeSel.addEventListener('change', applyType);
    document.addEventListener('DOMContentLoaded', applyType);
    applyType();
})();
</script>

</body>
</html>
