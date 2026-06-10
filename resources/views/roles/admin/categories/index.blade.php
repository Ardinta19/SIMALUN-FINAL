<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Kategori Layanan – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · KATEGORI & LAYANAN
   Design System — token-only styling, Lucide icons, pure-CSS motion.
   All colors/radii/shadows/spacing reference canonical tokens
   emitted by layouts.component._tokens (mirrors tailwind.config.js).
═══════════════════════════════════════════════════════════ */
:root {
    /* Page-local aliases — only reference canonical tokens (no literals) */
    --ink:       var(--brand-900);
    --ink-mid:   var(--surface-400);
    --ink-lt:    var(--surface-400);
    --line:      var(--surface-200);
    --line-soft: var(--surface-100);
    --font:      'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
body {
    font-family: var(--font);
    font-size: 16px;
    background: var(--surface-50);
    color: var(--ink);
    min-height: 100vh;
    padding-bottom: calc(var(--space-24) + env(safe-area-inset-bottom, 0px));
    overflow-x: hidden;
}

/* ── Entrance animation (pure CSS — transform/opacity only) ── */
@keyframes riseIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.reveal { opacity: 0; animation: riseIn .5s cubic-bezier(.22,.61,.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}
.d4{animation-delay:.22s}.d5{animation-delay:.28s}.d6{animation-delay:.34s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ═══════════════════════════════════════════════ HEADER (hero band) */
.page-header {
    background: linear-gradient(135deg, var(--brand-500) 0%, var(--brand-900) 140%);
    color: var(--surface-raised);
    padding: max(env(safe-area-inset-top, 0px), var(--space-4)) var(--space-4) var(--space-5);
    display: flex;
    align-items: center;
    gap: var(--space-3);
}
.page-header__title {
    font-size: 1.15rem;
    font-weight: 800;
    flex: 1;
    line-height: 1.1;
}
.page-header__action {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    background: rgba(255, 255, 255, 0.18);
    border: none;
    color: var(--surface-raised);
    min-height: var(--space-10);
    padding: 0 var(--space-3);
    border-radius: var(--radius-btn);
    font-size: .8rem;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: background .15s ease, transform .12s ease;
}
.page-header__action:hover { background: rgba(255, 255, 255, 0.26); }
.page-header__action:active { transform: scale(.96); }

.container { max-width: 520px; margin: 0 auto; padding: var(--space-4); }

/* ── Flash ── */
.flash {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-3);
    border-radius: var(--radius-btn);
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: var(--space-3);
}
.flash svg { flex-shrink: 0; }
.flash--ok  { background: color-mix(in srgb, var(--success-500) 14%, var(--surface-raised)); color: var(--success-500); }
.flash--err { background: color-mix(in srgb, var(--danger-500) 14%, var(--surface-raised)); color: var(--danger-500); }

/* ── Category card ── */
.cat-card {
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: var(--space-4);
    margin-bottom: var(--space-4);
}
.cat-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-2);
    margin-bottom: var(--space-2);
}
.cat-name {
    font-size: 1rem;
    font-weight: 800;
    color: var(--brand-900);
}
.cat-status {
    font-size: .68rem;
    font-weight: 800;
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-pill);
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.cat-status--on  { background: color-mix(in srgb, var(--success-500) 14%, var(--surface-raised)); color: var(--success-500); }
.cat-status--off { background: var(--surface-100); color: var(--ink-lt); }

.cat-desc {
    font-size: .82rem;
    color: var(--ink-mid);
    margin-bottom: var(--space-3);
}
.cat-meta {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-2) var(--space-4);
    font-size: .74rem;
    margin-bottom: var(--space-3);
}
.cat-meta__item span:first-child {
    color: var(--ink-lt);
    font-weight: 700;
    display: block;
    font-size: .68rem;
}
.cat-meta__item span:last-child {
    color: var(--ink);
    font-weight: 800;
}
.cat-actions {
    display: flex;
    gap: var(--space-2);
    border-top: 1px dashed var(--surface-200);
    padding-top: var(--space-3);
    margin-bottom: var(--space-3);
}
.cat-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-1);
    border: none;
    background: var(--brand-100);
    color: var(--brand-900);
    min-height: var(--space-10);
    padding: 0 var(--space-2);
    border-radius: var(--radius-btn);
    font-size: .78rem;
    font-weight: 800;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    font-family: inherit;
    transition: transform .12s ease, opacity .15s ease;
}
.cat-btn--danger { background: color-mix(in srgb, var(--danger-500) 12%, var(--surface-raised)); color: var(--danger-500); }
.cat-btn:active { transform: scale(.97); }
.cat-btn:hover { opacity: .85; }

/* ── Services section ── */
.svc-section {
    background: var(--surface-50);
    border: 1px solid var(--surface-100);
    border-radius: var(--radius-btn);
    padding: var(--space-3);
}
.svc-section__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: var(--space-2);
    margin-bottom: var(--space-3);
}
.svc-section__title {
    display: flex; align-items: center; gap: var(--space-1);
    font-size: .8rem;
    font-weight: 800;
    color: var(--ink-mid);
    text-transform: uppercase;
    letter-spacing: .5px;
}
.svc-section__add {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    background: var(--brand-500);
    color: var(--surface-raised);
    border: none;
    min-height: var(--space-9);
    padding: 0 var(--space-3);
    border-radius: var(--radius-btn);
    font-size: .72rem;
    font-weight: 800;
    cursor: pointer;
    font-family: inherit;
    transition: transform .12s ease;
}
.svc-section__add:active { transform: scale(.96); }
.svc-row {
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-btn);
    padding: var(--space-3);
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-2);
}
.svc-row:last-child { margin-bottom: 0; }
.svc-row__main {
    flex: 1;
    min-width: 0;
}
.svc-row__name {
    font-size: .88rem;
    font-weight: 800;
    color: var(--ink);
    display: flex;
    align-items: center;
    gap: var(--space-1);
    flex-wrap: wrap;
}
.svc-row__name .svc-pill {
    font-size: .58rem;
    font-weight: 800;
    padding: 2px var(--space-2);
    border-radius: var(--radius-pill);
    text-transform: uppercase;
}
.svc-row__name .svc-pill--off { background: var(--surface-100); color: var(--ink-lt); }
.svc-row__meta {
    font-size: .72rem;
    color: var(--ink-lt);
    margin-top: 2px;
    font-weight: 700;
}
.svc-row__actions {
    display: flex;
    gap: var(--space-1);
}
.svc-mini {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: var(--brand-100);
    color: var(--brand-900);
    min-height: var(--space-9);
    padding: 0 var(--space-2);
    border-radius: var(--radius-btn);
    font-size: .7rem;
    font-weight: 800;
    cursor: pointer;
    font-family: inherit;
    transition: transform .12s ease;
}
.svc-mini:active { transform: scale(.95); }
.svc-mini--danger { background: color-mix(in srgb, var(--danger-500) 12%, var(--surface-raised)); color: var(--danger-500); }
.svc-empty {
    text-align: center;
    color: var(--ink-lt);
    font-size: .78rem;
    padding: var(--space-3) var(--space-2);
    font-style: italic;
}

/* ── Empty state (designed) ── */
.empty-state {
    text-align: center;
    padding: var(--space-12) var(--space-5);
}
.empty-state__ico {
    width: var(--space-16); height: var(--space-16); margin: 0 auto var(--space-4);
    border-radius: var(--radius-pill); background: var(--brand-100);
    display: flex; align-items: center; justify-content: center; color: var(--brand-500);
}
.empty-state__text { color: var(--ink-mid); font-size: .9rem; font-weight: 700; line-height: 1.5; }

/* ═══════════════════════════════════════════════ MODAL */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0, 47, 92, 0.55);
    -webkit-backdrop-filter: blur(2px);
    backdrop-filter: blur(2px);
    display: none;
    align-items: flex-end;
    justify-content: center;
    z-index: 1000;
    padding: var(--space-4);
}
.modal-overlay.is-open { display: flex; }
.modal-card {
    background: var(--surface-raised);
    border-radius: var(--radius-card) var(--radius-card) var(--radius-btn) var(--radius-btn);
    width: 100%;
    max-width: 480px;
    padding: var(--space-5);
    max-height: 90vh;
    overflow-y: auto;
    animation: riseIn .3s cubic-bezier(.22,.61,.36,1);
}
@media (prefers-reduced-motion: reduce){ .modal-card{animation:none} }
.modal-card__head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: var(--space-4);
}
.modal-card__title {
    font-size: 1rem; font-weight: 800; color: var(--brand-900);
}
.modal-card__close {
    background: none; border: none; color: var(--ink-lt);
    line-height: 1; cursor: pointer; padding: var(--space-1);
    display: inline-flex; align-items: center; justify-content: center;
}

.form-row { margin-bottom: var(--space-3); }
.form-row__label {
    display: block;
    font-size: .78rem;
    font-weight: 800;
    color: var(--ink-mid);
    margin-bottom: var(--space-2);
}
.form-row__hint {
    display: block;
    font-size: .68rem;
    color: var(--ink-lt);
    margin-top: var(--space-1);
}
.input,
.select {
    width: 100%;
    padding: var(--space-3);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-btn);
    font-family: inherit;
    font-size: .9rem;
    color: var(--ink);
    background: var(--surface-raised);
}
.input:focus,
.select:focus {
    outline: none;
    border-color: var(--brand-500);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-500) 18%, transparent);
}
.toggle-row {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    background: var(--brand-100);
    border-radius: var(--radius-btn);
    margin-bottom: var(--space-3);
    font-size: .85rem;
    font-weight: 700;
}
.toggle-row input { width: 16px; height: 16px; accent-color: var(--brand-500); }
.btn-submit {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    background: var(--brand-500);
    color: var(--surface-raised);
    padding: var(--space-3) var(--space-4);
    border: none;
    border-radius: var(--radius-btn);
    font-size: .9rem;
    font-weight: 800;
    cursor: pointer;
    margin-top: var(--space-1);
    font-family: inherit;
    transition: background .15s ease, transform .12s ease;
}
.btn-submit:hover { background: var(--brand-900); }
.btn-submit:active { transform: scale(.98); }
.context-tag {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    background: var(--brand-100);
    color: var(--brand-900);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-btn);
    font-size: .78rem;
    font-weight: 800;
    margin-bottom: var(--space-3);
}
</style>
</head>
<body>
@include('layouts.component.admin._navbar_admin', ['active' => 'beranda'])

<div class="page-header">
    <x-back-button fallback="admin.dashboard" style="hero" />
    <div class="page-header__title">Kategori &amp; Layanan</div>
    <button type="button" class="page-header__action" data-cat-create>
        @include('layouts.component._icon', ['name' => 'add', 'size' => 16])
        Kategori
    </button>
</div>

<div class="container">
    @if(session('success'))
        <div class="flash flash--ok">
            @include('layouts.component._icon', ['name' => 'success', 'size' => 18])
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash--err">
            @include('layouts.component._icon', ['name' => 'error', 'size' => 18])
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="flash flash--err">
            @include('layouts.component._icon', ['name' => 'error', 'size' => 18])
            {{ $errors->first() }}
        </div>
    @endif

    @forelse($categories as $cat)
    <div class="cat-card reveal {{ 'd' . min($loop->iteration, 6) }}">
        <div class="cat-card__head">
            <div class="cat-name">{{ $cat->name }}</div>
            <span class="cat-status {{ $cat->is_active ? 'cat-status--on' : 'cat-status--off' }}">
                {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>

        @if($cat->description)
            <div class="cat-desc">{{ $cat->description }}</div>
        @endif

        <div class="cat-meta">
            <div class="cat-meta__item">
                <span>Model harga</span>
                <span>{{ $cat->pricing_model === 'per_kg' ? 'Per Kg' : 'Per Item' }}</span>
            </div>
            <div class="cat-meta__item">
                <span>Jumlah layanan</span>
                <span>{{ $cat->services_count }}</span>
            </div>
        </div>

        <div class="cat-actions">
            <button type="button" class="cat-btn"
                    data-cat-edit
                    data-id="{{ $cat->id }}"
                    data-name="{{ $cat->name }}"
                    data-pricing="{{ $cat->pricing_model }}"
                    data-description="{{ $cat->description }}"
                    data-active="{{ $cat->is_active ? '1' : '0' }}">
                @include('layouts.component._icon', ['name' => 'edit', 'size' => 16])
                Edit
            </button>
            <form method="POST" action="{{ route('admin.categories.toggle', $cat) }}" style="flex: 1;">
                @csrf @method('PATCH')
                <button type="submit" class="cat-btn" style="width:100%;">
                    @include('layouts.component._icon', ['name' => $cat->is_active ? 'eye-off' : 'eye', 'size' => 16])
                    {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
            @if($cat->services_count === 0)
                <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" style="flex: 1;"
                      onsubmit="return confirm('Hapus kategori {{ $cat->name }}?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="cat-btn cat-btn--danger" style="width:100%;">
                        @include('layouts.component._icon', ['name' => 'hapus', 'size' => 16])
                        Hapus
                    </button>
                </form>
            @endif
        </div>

        {{-- Layanan dalam kategori ini --}}
        <div class="svc-section">
            <div class="svc-section__head">
                <div class="svc-section__title">
                    @include('layouts.component._icon', ['name' => 'laundry', 'size' => 16])
                    Layanan ({{ $cat->services_count }})
                </div>
                <button type="button" class="svc-section__add"
                        data-svc-create
                        data-category-id="{{ $cat->id }}"
                        data-category-name="{{ $cat->name }}"
                        data-pricing="{{ $cat->pricing_model }}">
                    @include('layouts.component._icon', ['name' => 'add', 'size' => 14])
                    Tambah Layanan
                </button>
            </div>

            @forelse($cat->services as $svc)
            <div class="svc-row">
                <div class="svc-row__main">
                    <div class="svc-row__name">
                        {{ $svc->name }}
                        @unless($svc->is_active)
                            <span class="svc-pill svc-pill--off">Nonaktif</span>
                        @endunless
                    </div>
                    <div class="svc-row__meta">
                        Rp {{ number_format($svc->effective_unit_price, 0, ',', '.') }}/{{ $svc->unit_type }}
                        &middot; Estimasi {{ $svc->estimated_hours }} jam
                    </div>
                </div>
                <div class="svc-row__actions">
                    <button type="button" class="svc-mini"
                            data-svc-edit
                            data-id="{{ $svc->id }}"
                            data-name="{{ $svc->name }}"
                            data-unit-price="{{ $svc->effective_unit_price }}"
                            data-estimated-hours="{{ $svc->estimated_hours }}"
                            data-description="{{ $svc->description }}"
                            data-active="{{ $svc->is_active ? '1' : '0' }}"
                            data-category-name="{{ $cat->name }}"
                            data-pricing="{{ $cat->pricing_model }}"
                            aria-label="Edit layanan {{ $svc->name }}">
                        @include('layouts.component._icon', ['name' => 'edit', 'size' => 14])
                    </button>
                    <form method="POST" action="{{ route('admin.services.toggle', $svc) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit" class="svc-mini" title="{{ $svc->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                aria-label="{{ $svc->is_active ? 'Nonaktifkan' : 'Aktifkan' }} layanan {{ $svc->name }}">
                            @include('layouts.component._icon', ['name' => $svc->is_active ? 'eye-off' : 'eye', 'size' => 14])
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.services.destroy', $svc) }}" style="display:inline;"
                          onsubmit="return confirm('Hapus layanan {{ $svc->name }}? Tidak bisa dipulihkan.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="svc-mini svc-mini--danger" aria-label="Hapus layanan {{ $svc->name }}">
                            @include('layouts.component._icon', ['name' => 'hapus', 'size' => 14])
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="svc-empty">Belum ada layanan. Klik "Tambah Layanan" untuk mengisi.</div>
            @endforelse
        </div>
    </div>
    @empty
    <div class="empty-state reveal d1">
        <div class="empty-state__ico" aria-hidden="true">
            @include('layouts.component._icon', ['name' => 'package', 'size' => 32])
        </div>
        <p class="empty-state__text">Belum ada kategori.<br>Klik "+ Kategori" untuk tambah kategori pertama.</p>
    </div>
    @endforelse
</div>

{{-- Modal Tambah / Edit Kategori --}}
<div class="modal-overlay" id="catModal">
    <div class="modal-card">
        <div class="modal-card__head">
            <div class="modal-card__title" id="catModalTitle">Buat Kategori</div>
            <button type="button" class="modal-card__close" data-cat-close aria-label="Tutup">
                @include('layouts.component._icon', ['name' => 'close', 'size' => 24])
            </button>
        </div>

        <form method="POST" id="catForm" action="{{ route('admin.categories.store') }}">
            @csrf
            <input type="hidden" name="_method" id="catFormMethod" value="">

            <div class="form-row">
                <label class="form-row__label" for="cat-name">Nama Kategori</label>
                <input type="text" name="name" id="cat-name" class="input"
                       required maxlength="80"
                       placeholder="contoh: Kiloan, Satuan, Karpet">
            </div>

            <div class="form-row">
                <label class="form-row__label" for="cat-pricing">Model Harga</label>
                <select name="pricing_model" id="cat-pricing" class="select" required
                        data-fc-segmented data-fc-title="Model Harga">
                    <option value="per_kg">Per Kg</option>
                    <option value="per_item">Per Item</option>
                </select>
                <span class="form-row__hint">Per kg = layanan utama (single-pick). Per item = item satuan (multiple).</span>
            </div>

            <div class="form-row">
                <label class="form-row__label" for="cat-desc">Keterangan</label>
                <input type="text" name="description" id="cat-desc" class="input"
                       maxlength="200"
                       placeholder="Opsional, untuk catatan internal">
            </div>

            <label class="toggle-row">
                <input type="checkbox" name="is_active" id="cat-active" value="1" checked>
                <span>Kategori aktif</span>
            </label>

            <button type="submit" class="btn-submit" id="catFormSubmit">
                @include('layouts.component._icon', ['name' => 'check', 'size' => 18])
                Simpan Kategori
            </button>
        </form>
    </div>
</div>

{{-- Modal Tambah / Edit Layanan --}}
<div class="modal-overlay" id="svcModal">
    <div class="modal-card">
        <div class="modal-card__head">
            <div class="modal-card__title" id="svcModalTitle">Tambah Layanan</div>
            <button type="button" class="modal-card__close" data-svc-close aria-label="Tutup">
                @include('layouts.component._icon', ['name' => 'close', 'size' => 24])
            </button>
        </div>

        <div class="context-tag" id="svcContextTag">
            @include('layouts.component._icon', ['name' => 'tag', 'size' => 16])
            <span id="svcContextTagText">Kategori: -</span>
        </div>

        <form method="POST" id="svcForm" action="">
            @csrf
            <input type="hidden" name="_method" id="svcFormMethod" value="">

            <div class="form-row">
                <label class="form-row__label" for="svc-name">Nama Layanan</label>
                <input type="text" name="name" id="svc-name" class="input"
                       required maxlength="120"
                       placeholder="contoh: Cuci Karpet 2x3 m">
            </div>

            <div class="form-row">
                <label class="form-row__label" for="svc-price">Harga (Rp)</label>
                <input type="number" name="unit_price" id="svc-price" class="input"
                       required min="1000" max="1000000" step="500"
                       placeholder="contoh: 50000"
                       data-fc-stepper>
                <span class="form-row__hint" id="svcPriceHint">Per kg / per item — ikut model kategori.</span>
            </div>

            <div class="form-row">
                <label class="form-row__label" for="svc-hours">Estimasi Selesai (jam)</label>
                <input type="number" name="estimated_hours" id="svc-hours" class="input"
                       required min="1" max="240"
                       placeholder="contoh: 48"
                       data-fc-stepper>
                <span class="form-row__hint">24 jam = 1 hari, 48 jam = 2 hari, 240 jam = 10 hari.</span>
            </div>

            <div class="form-row">
                <label class="form-row__label" for="svc-desc">Keterangan</label>
                <input type="text" name="description" id="svc-desc" class="input"
                       maxlength="200"
                       placeholder="Opsional">
            </div>

            <label class="toggle-row">
                <input type="checkbox" name="is_active" id="svc-active" value="1" checked>
                <span>Layanan aktif</span>
            </label>

            <button type="submit" class="btn-submit" id="svcFormSubmit">
                @include('layouts.component._icon', ['name' => 'check', 'size' => 18])
                Simpan Layanan
            </button>
        </form>
    </div>
</div>

@include('layouts.component._form_controls')

<script>
(function() {
    var catStoreUrl = @json(route('admin.categories.store'));
    var catUpdateBase = @json(url('admin/categories'));
    var svcUpdateBase = @json(url('admin/services'));

    /* Enhance dynamically-shown controls + reflect programmatic value changes
       onto the progressive-enhancement widgets (segmented / stepper). */
    function syncControls(modalEl) {
        if (typeof window.initFormControls === 'function') {
            window.initFormControls(modalEl);
        }
        modalEl.querySelectorAll('select, input[type="number"]').forEach(function(ctrl) {
            ctrl.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    // ── Modal kategori ──
    var catModal = document.getElementById('catModal');
    var catModalCard = catModal.querySelector('.modal-card');
    var catForm = document.getElementById('catForm');
    var catTitle = document.getElementById('catModalTitle');
    var catSubmit = document.getElementById('catFormSubmit');
    var catMethod = document.getElementById('catFormMethod');

    function openCatCreate() {
        catTitle.textContent = 'Buat Kategori';
        catSubmit.textContent = 'Simpan Kategori';
        catForm.action = catStoreUrl;
        catMethod.value = '';
        document.getElementById('cat-name').value = '';
        document.getElementById('cat-pricing').value = 'per_kg';
        document.getElementById('cat-desc').value = '';
        document.getElementById('cat-active').checked = true;
        catModal.classList.add('is-open');
        syncControls(catModalCard);
    }
    function openCatEdit(btn) {
        catTitle.textContent = 'Edit Kategori';
        catSubmit.textContent = 'Simpan Perubahan';
        catForm.action = catUpdateBase + '/' + btn.dataset.id;
        catMethod.value = 'PATCH';
        document.getElementById('cat-name').value = btn.dataset.name || '';
        document.getElementById('cat-pricing').value = btn.dataset.pricing || 'per_kg';
        document.getElementById('cat-desc').value = btn.dataset.description || '';
        document.getElementById('cat-active').checked = btn.dataset.active === '1';
        catModal.classList.add('is-open');
        syncControls(catModalCard);
    }
    function closeCat() { catModal.classList.remove('is-open'); }

    document.querySelector('[data-cat-create]').addEventListener('click', openCatCreate);
    document.querySelectorAll('[data-cat-edit]').forEach(function(btn) {
        btn.addEventListener('click', function() { openCatEdit(btn); });
    });
    document.querySelector('[data-cat-close]').addEventListener('click', closeCat);
    catModal.addEventListener('click', function(e) { if (e.target === catModal) closeCat(); });

    // ── Modal layanan ──
    var svcModal = document.getElementById('svcModal');
    var svcModalCard = svcModal.querySelector('.modal-card');
    var svcForm = document.getElementById('svcForm');
    var svcTitle = document.getElementById('svcModalTitle');
    var svcSubmit = document.getElementById('svcFormSubmit');
    var svcMethod = document.getElementById('svcFormMethod');
    var svcContextTag = document.getElementById('svcContextTagText');
    var svcPriceHint = document.getElementById('svcPriceHint');

    function setSvcPricingHint(pricing) {
        svcPriceHint.textContent = pricing === 'per_kg'
            ? 'Harga per kilogram (model kiloan).'
            : 'Harga per item (model satuan).';
    }

    function openSvcCreate(btn) {
        svcTitle.textContent = 'Tambah Layanan';
        svcSubmit.textContent = 'Simpan Layanan';
        svcForm.action = catUpdateBase + '/' + btn.dataset.categoryId + '/services';
        svcMethod.value = '';
        svcContextTag.textContent = 'Kategori: ' + btn.dataset.categoryName;
        setSvcPricingHint(btn.dataset.pricing);
        document.getElementById('svc-name').value = '';
        document.getElementById('svc-price').value = '';
        document.getElementById('svc-hours').value = '48';
        document.getElementById('svc-desc').value = '';
        document.getElementById('svc-active').checked = true;
        svcModal.classList.add('is-open');
        syncControls(svcModalCard);
    }
    function openSvcEdit(btn) {
        svcTitle.textContent = 'Edit Layanan';
        svcSubmit.textContent = 'Simpan Perubahan';
        svcForm.action = svcUpdateBase + '/' + btn.dataset.id;
        svcMethod.value = 'PATCH';
        svcContextTag.textContent = 'Kategori: ' + btn.dataset.categoryName;
        setSvcPricingHint(btn.dataset.pricing);
        document.getElementById('svc-name').value = btn.dataset.name || '';
        document.getElementById('svc-price').value = btn.dataset.unitPrice || '';
        document.getElementById('svc-hours').value = btn.dataset.estimatedHours || '48';
        document.getElementById('svc-desc').value = btn.dataset.description || '';
        document.getElementById('svc-active').checked = btn.dataset.active === '1';
        svcModal.classList.add('is-open');
        syncControls(svcModalCard);
    }
    function closeSvc() { svcModal.classList.remove('is-open'); }

    document.querySelectorAll('[data-svc-create]').forEach(function(btn) {
        btn.addEventListener('click', function() { openSvcCreate(btn); });
    });
    document.querySelectorAll('[data-svc-edit]').forEach(function(btn) {
        btn.addEventListener('click', function() { openSvcEdit(btn); });
    });
    document.querySelector('[data-svc-close]').addEventListener('click', closeSvc);
    svcModal.addEventListener('click', function(e) { if (e.target === svcModal) closeSvc(); });
})();
</script>
</body>
</html>
