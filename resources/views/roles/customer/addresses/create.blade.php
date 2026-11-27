<!DOCTYPE html>
<html lang="id">
<head>
@php $asset = fn (string $path) => asset($path).(file_exists(public_path($path)) ? '?v='.filemtime(public_path($path)) : ''); @endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
@include('layouts.component._page_shell')
<title>Tambah Alamat · Berkah Kreatif</title>
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}?v={{ filemtime(public_path('vendor/leaflet/leaflet.css')) }}">
<script src="{{ asset('vendor/leaflet/leaflet.js') }}?v={{ filemtime(public_path('vendor/leaflet/leaflet.js')) }}"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
:root {
    --blue-dark:  #54051f;
    --blue-mid:   #85092d;
    --blue-light: #b6435e;
    --blue-sky:   #f8e9ed;
    --orange:     #c2410c;
    --green:      #087f5b;
    --surface:    #faf4f3;
    --card:       #ffffff;
    --ink:        #321d24;
    --ink-mid:    #6f5960;
    --ink-lt:     #806975;
    --border:     #f0dfe1;
    --radius:     16px;
    --radius-sm:  10px;
    --red:        #c62f2f;
}
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--surface);
    color: var(--ink);
    min-height: 100vh;
    min-height: 100dvh;
    padding-bottom: calc(96px + env(safe-area-inset-bottom, 0px));
    overflow-x: clip;
}

/* ── HEADER ──────────────────────────── */
.page-header {
    background: linear-gradient(135deg, var(--blue-dark) 0%, var(--blue-mid) 100%);
    padding: max(env(safe-area-inset-top,0px), 16px) 20px 24px;
    position: sticky; top: 0; z-index: 100;
}
.header-row { display:flex; align-items:center; gap:12px; max-width:520px; margin:0 auto; }
.btn-back {
    width:38px; height:38px; border-radius:50%;
    background:rgba(255,255,255,.15); border:1.5px solid rgba(255,255,255,.25);
    display:flex; align-items:center; justify-content:center;
    color:white; text-decoration:none; flex-shrink:0;
}
.btn-back svg { width:18px; height:18px; }
.header-title { flex:1; font-weight:800; font-size:1.3rem; color:white; margin:0; }
.header-sub { font-size:var(--bk-fs-2xs); font-weight:800; color:rgba(255,255,255,.65); margin-top:2px; letter-spacing:.5px; }

/* ── BODY ────────────────────────────── */
.page-body { max-width:520px; margin:0 auto; padding:16px; }

/* ── SECTION CARD ────────────────────── */
.section-card {
    background:white;
    border-radius:var(--radius);
    border:1.5px solid var(--border);
    margin-bottom:14px;
    overflow:hidden;
    box-shadow:0 2px 12px rgba(84,5,31,.05);

}
.section-head {
    display:flex; align-items:center; gap:10px;
    padding:13px 16px;
    border-bottom:1.5px solid var(--border);
    background:linear-gradient(90deg, rgba(0,119,182,.04) 0%, transparent 100%);
}
.section-num {
    width:28px; height:28px; border-radius:50%;
    background:var(--blue-mid); color:white;
    font-weight:800; font-size:var(--bk-fs-sm);
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.section-title { font-weight:800; font-size:var(--bk-fs-md); color:var(--blue-dark); }
.section-body { padding:14px 16px; display:flex; flex-direction:column; gap:12px; }

/* ── FORM ELEMENTS ───────────────────── */
.field-label {
    font-size:var(--bk-fs-2xs); font-weight:900; color:var(--ink-mid);
    letter-spacing:.5px; text-transform:uppercase; margin-bottom:5px;
    display:flex; align-items:center; gap:6px;
}
.field-label .required { color:var(--red); }
.field-input {
    width:100%; padding:12px 14px;
    border:1.5px solid var(--border); border-radius:var(--radius-sm);
    font-family:'Plus Jakarta Sans',sans-serif; font-size:var(--bk-fs-md); font-weight:600;
    color:var(--ink); background:white; outline:none;
    transition:border-color .2s, box-shadow .2s;
    appearance:none;
}
.field-input:focus {
    border-color:var(--blue-mid);
    box-shadow:0 0 0 3px rgba(0,119,182,.12);
}
.field-input.is-error { border-color:var(--red); box-shadow:0 0 0 3px rgba(239,68,68,.1); }
.field-input::placeholder { color:var(--ink-lt); font-weight:600; }
textarea.field-input { resize:none; min-height:80px; line-height:1.5; }
.field-error { font-size:var(--bk-fs-2xs); font-weight:800; color:var(--red); margin-top:4px; }

/* ── ZONE PILLS ──────────────────────── */
.zone-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.zone-pill {
    border:2px solid var(--border); border-radius:var(--radius-sm);
    padding:10px 6px; text-align:center; cursor:pointer;
    background:white; position:relative;
    transition:border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
}
.zone-pill input[type=radio] { position:absolute; opacity:0; }
.zone-name { font-weight:800; font-size:var(--bk-fs-md); color:var(--ink); display:block; }
.zone-desc { font-size:var(--bk-fs-2xs); font-weight:800; color:var(--ink-lt); display:block; margin-top:2px; }
.zone-price { font-size:var(--bk-fs-2xs); font-weight:900; color:var(--green); display:block; margin-top:2px; }
.zone-pill:has(input:checked), .zone-pill.is-checked {
    border-color:var(--blue-mid);
    background:rgba(0,119,182,.06);
    box-shadow:0 0 0 3px rgba(0,119,182,.12);
}
.zone-pill:has(input:checked) .zone-name, .zone-pill.is-checked .zone-name { color:var(--blue-mid); }

/* ── MAP ─────────────────────────────── */
#addr-map {
    height:180px; border-radius:var(--radius-sm);
    border:1.5px solid var(--border); z-index:10;
    margin-bottom:4px;
}
.map-hint {
    font-size:var(--bk-fs-2xs); font-weight:700; color:var(--ink-lt);
    display:flex; align-items:center; gap:5px;
}
.map-hint svg { width:12px; height:12px; }

/* ── TOGGLE ──────────────────────────── */
.toggle-wrap {
    display:flex; align-items:center; justify-content:space-between;
    padding:2px 0;
}
.toggle-label { font-size:var(--bk-fs-sm); font-weight:800; color:var(--ink); }
.toggle-sub   { font-size:var(--bk-fs-2xs); font-weight:700; color:var(--ink-lt); margin-top:2px; }
.toggle-switch {
    width:48px; height:26px; border-radius:99px;
    background:var(--border); cursor:pointer;
    position:relative; transition:background .2s; flex-shrink:0;
    border:none;
}
.toggle-switch::after {
    content:''; position:absolute;
    width:20px; height:20px; border-radius:50%;
    background:white; top:3px; left:3px;
    transition:transform .2s;
    box-shadow:0 2px 6px rgba(0,0,0,.15);
}
.toggle-switch.on { background:var(--green); }
.toggle-switch.on::after { transform:translateX(22px); }

/* ── SUBMIT BUTTON (inline — ikut scroll, anti-overlap) ── */
.bottom-cta {
    max-width:520px;
    margin:0 auto;
    padding:4px 16px 8px;
}
.cta-inner { max-width:520px; margin:0 auto; }
.btn-submit {
    width:100%; padding:14px;
    background:linear-gradient(135deg, var(--blue-dark) 0%, var(--blue-mid) 100%);
    color:white; font-family:'Plus Jakarta Sans',sans-serif; font-weight:900;
    font-size:var(--bk-fs-md); border:none; border-radius:var(--radius-sm);
    cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;
    box-shadow:0 6px 20px rgba(0,119,182,.35);
    transition:transform .15s;
}
.btn-submit:active { transform:scale(.97); }
.btn-submit svg { width:18px; height:18px; }

/* ── ALERT ERROR ─────────────────────── */
.alert-error {
    background:#fff1f1; border:1.5px solid #fecaca;
    border-radius:var(--radius-sm); padding:12px 14px;
    font-size:var(--bk-fs-xs); font-weight:700; color:var(--red);
    margin-bottom:14px;
}
.map-hint[hidden]{display:none}

/* Hormati pengaturan "kurangi gerak": transisi warna/tombol menjadi instan. */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
    }
}

/* Tema gelap: halaman ini memakai variabel lokal (--surface/--card/--ink/--border)
   dan beberapa latar putih lepas, jadi tidak ikut membaik saat pengguna memilih
   tema gelap di header. Pemetaan di bawah hanya berlaku di halaman ini (tidak
   mengubah halaman lain yang juga memakai --ink untuk palet terangnya). */
html[data-theme="dark"] {
    --surface: #140f12;
    --card: #1d1619;
    --ink: #f6eef0;
    --ink-mid: #cbb9bf;
    --ink-lt: #ab989f;
    --border: #3a2b31;
    --blue-sky: #2a1c22;
}
html[data-theme="dark"] body { background: var(--surface); color: var(--ink); }
html[data-theme="dark"] .section-card,
html[data-theme="dark"] .field-input,
html[data-theme="dark"] .zone-pill,
html[data-theme="dark"] textarea.field-input {
    background: var(--card); color: var(--ink); border-color: var(--border);
}
html[data-theme="dark"] .alert-error { background: #3a1418; color: #ffb4b4; }
html[data-theme="dark"] .alert-success { background: #0f2b22; color: #8ff0cc; }
</style>
<link rel="stylesheet" href="{{ $asset('css/page-transitions.css') }}"><script src="{{ $asset('js/page-transitions.js') }}" defer></script>
<link rel="stylesheet" href="{{ $asset('css/customer-forms.css') }}">
<link rel="stylesheet" href="{{ $asset('css/berkah-experience.css') }}">
@include('layouts.component._theme_head')
</head>
<body class="bk-experience bk-customer bk-standalone">
@include('layouts.component._skip_link')
@php
$oldText = function (string $key, $fallback = '') {
    $value = old($key, $fallback);
    return is_scalar($value) ? (string) $value : '';
};
@endphp
@include('catalog.customer-navigation')

<header class="page-header">
    <div class="header-row">
        <a data-smart-back href="{{ route('customer.addresses.index') }}" class="btn-back" aria-label="Kembali ke daftar alamat">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 5l-7 7 7 7"/>
            </svg>
        </a>
        <div>
            <h1 class="header-title">Tambah alamat</h1>
            <div class="header-sub">Alamat penyerahan</div>
        </div>
    </div>
</header>

<main class="page-body" id="main-content" tabindex="-1">

    @if($errors->any())
    <div class="alert-error" role="alert">
        <span class="bk-i" aria-hidden="true">@include('layouts.component._icon', ['name' => 'alert-triangle', 'size' => 20])</span> Periksa kembali isian berikut:<br>
        @foreach($errors->all() as $e) • {{ $e }}<br> @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('customer.addresses.store') }}" id="addr-form">
        @csrf

        {{-- SECTION 1: Info Penerima --}}
        <div class="section-card js-card">
            <div class="section-head">
                <div class="section-num">1</div>
                <div class="section-title">Info penerima</div>
            </div>
            <div class="section-body">
                <div>
                    <label class="field-label" for="address-label">Label alamat <span class="required">*</span></label>
                    <input placeholder="Contoh: Rumah" type="text" name="label" id="address-label" class="field-input {{ $errors->has('label') ? 'is-error' : '' }}"
                        value="{{ $oldText('label', 'Rumah') }}" required autocomplete="off" @error('label') aria-invalid="true" @enderror>
                    @error('label') <div class="field-error" role="alert">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="field-label" for="address-recipient_name">Nama penerima <span class="required">*</span></label>
                    <input placeholder="Contoh: Ibu Sari Wulandari" type="text" name="recipient_name" id="address-recipient_name" class="field-input {{ $errors->has('recipient_name') ? 'is-error' : '' }}"
                        value="{{ $oldText('recipient_name', auth()->user()->name) }}" required autocomplete="name" @error('recipient_name') aria-invalid="true" @enderror>
                    @error('recipient_name') <div class="field-error" role="alert">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="field-label" for="address-phone">No. HP / WhatsApp</label>
                    <input autocomplete="tel" type="tel" inputmode="tel" name="phone" id="address-phone" class="field-input"
                        placeholder="Contoh: 081234567890"
                        value="{{ $oldText('phone', auth()->user()->phone) }}">
                </div>
            </div>
        </div>

        {{-- SECTION 2: Lokasi --}}
        <div class="section-card js-card">
            <div class="section-head">
                <div class="section-num">2</div>
                <div class="section-title">Lokasi alamat</div>
            </div>
            <div class="section-body">
                <div>
                    <div class="field-label">Titik lokasi di peta (opsional)</div>
                    <div id="addr-map" hidden></div><p class="field-hint" id="map-fallback">Peta bersifat opsional. Jika tidak tampil, isi alamat lengkap secara manual.</p>
                    <div class="map-hint" hidden>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Tap atau geser pin untuk menentukan titik akurat
                    </div>
                    <input type="hidden" name="latitude" id="map-lat" value="{{ $oldText('latitude') }}">
                    <input type="hidden" name="longitude" id="map-lng" value="{{ $oldText('longitude') }}">
                </div>

                <div>
                    <label class="field-label" for="address-full_address">Alamat lengkap <span class="required">*</span></label>
                    <textarea placeholder="Contoh: Jl. Merdeka No. 12, RT 03, Palembang" name="full_address" id="address-full_address" class="field-input {{ $errors->has('full_address') ? 'is-error' : '' }}"
                        required autocomplete="street-address" rows="4" @error('full_address') aria-invalid="true" @enderror>{{ $oldText('full_address') }}</textarea>
                    @error('full_address') <div class="field-error" role="alert">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="field-label" for="address-notes">Catatan tambahan</label>
                    <input type="text" name="notes" id="address-notes" class="field-input"
                        placeholder="Contoh: Pagar hijau, titip di pos keamanan."
                        value="{{ $oldText('notes') }}" autocomplete="off">
                </div>
            </div>
        </div>

        {{-- SECTION 3: Pengaturan --}}
        <div class="section-card js-card">
            <div class="section-head">
                <div class="section-num">3</div>
                <div class="section-title">Pengaturan</div>
            </div>
            <div class="section-body">
                <div>
                    <div class="toggle-wrap">
                        <div>
                            <div class="toggle-label">Jadikan alamat utama</div>
                            <div class="toggle-sub">Ditampilkan sebagai alamat utama dan dapat dipilih saat checkout</div>
                        </div>
                        <label><input type="checkbox" name="is_primary" value="1" @checked($oldText('is_primary'))> Jadikan utama</label>
                    </div>
                </div>
            </div>
        </div>

    </form>
</main>

{{-- SUBMIT BUTTON --}}
<div class="bottom-cta">
    <div class="cta-inner">
        <button type="submit" form="addr-form" class="btn-submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            Simpan Alamat
        </button>
    </div>
</div>

{{-- BOTTOM NAVBAR --}}
@include('layouts.component.customer._navbar_customer', ['active' => 'profil'])

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof L === 'undefined') return;
    document.getElementById('addr-map').hidden = false;
    const latitude = document.getElementById('map-lat'), longitude = document.getElementById('map-lng');
    const lat = Number(latitude.value), lng = Number(longitude.value);
    const hasCoordinates = latitude.value.trim() !== '' && longitude.value.trim() !== '' && Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
    const point = hasCoordinates ? [lat, lng] : [-1.6101, 103.6131];
    const map = L.map('addr-map').setView(point, hasCoordinates ? 15 : 13);
    document.querySelector('.map-hint').hidden = false;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
    let marker = hasCoordinates ? L.marker(point, { draggable: true }).addTo(map) : null;
    if (marker) marker.on('dragend', function () { save(marker.getLatLng()); });
    map.on('click', function (e) {
        if (!marker) {
            marker = L.marker(e.latlng, { draggable: true }).addTo(map);
            marker.on('dragend', function () { save(marker.getLatLng()); });
        } else marker.setLatLng(e.latlng);
        save(e.latlng);
    });
    function save(point) {
        document.getElementById('map-lat').value = point.lat.toFixed(6);
        document.getElementById('map-lng').value = point.lng.toFixed(6);
    }
});
</script>

<script>
/* Fallback :has(input:checked) untuk Android/iOS lama — toggle class .is-checked */
(function(){
  function syncChecked(){
    var inputs = document.querySelectorAll('label input[type=radio], label input[type=checkbox]');
    for (var i = 0; i < inputs.length; i++){
      var lbl = inputs[i].closest('label');
      if (!lbl) continue;
      if (inputs[i].checked) lbl.classList.add('is-checked');
      else lbl.classList.remove('is-checked');
    }
  }
  document.addEventListener('change', function(e){
    var t = e.target;
    if (t && (t.type === 'radio' || t.type === 'checkbox')) syncChecked();
  });
  if (document.readyState !== 'loading') syncChecked();
  else document.addEventListener('DOMContentLoaded', syncChecked);
  window.addEventListener('load', syncChecked);
})();
</script>
<script src="{{ $asset('js/berkah-experience.js') }}" defer></script>
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body>
</html>