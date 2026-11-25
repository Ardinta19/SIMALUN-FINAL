<!DOCTYPE html>
<html lang="id">
<head>
@php $asset = fn (string $path) => asset($path).(file_exists(public_path($path)) ? '?v='.filemtime(public_path($path)) : ''); @endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
@include('layouts.component._page_shell')
<title>Edit Alamat · Berkah Kreatif</title>
@include('layouts.component.customer._head_meta')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}?v={{ filemtime(public_path('vendor/leaflet/leaflet.css')) }}">
<script src="{{ asset('vendor/leaflet/leaflet.js') }}?v={{ filemtime(public_path('vendor/leaflet/leaflet.js')) }}"></script>
<style>
:root {
    --blue-dark:#54051f; --blue-mid:#85092d; --blue-light:#b6435e;
    --orange:#c2410c; --green:#087f5b; --red:#c62f2f;
    --surface:#faf4f3; --card:#ffffff; --ink:#321d24; --ink-mid:#6f5960;
    --ink-lt:#806975; --border:#f0dfe1; --radius:16px; --radius-sm:10px;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--surface);color:var(--ink);min-height:100vh;min-height:100dvh;padding-bottom:calc(96px + env(safe-area-inset-bottom,0px));overflow-x:clip;}

.page-header{background:linear-gradient(135deg,var(--blue-dark) 0%,var(--blue-mid) 100%);padding:max(env(safe-area-inset-top,0px),16px) 20px 24px;position:sticky;top:0;z-index:100;}
.header-row{display:flex;align-items:center;gap:12px;max-width:520px;margin:0 auto;}
.btn-back{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;color:white;text-decoration:none;flex-shrink:0;}
.btn-back svg{width:18px;height:18px;}
.header-title{flex:1;font-weight:800;font-size:1.3rem;color:white;margin:0;}
.header-sub{font-size:var(--bk-fs-2xs);font-weight:800;color:rgba(255,255,255,.65);margin-top:2px;letter-spacing:.5px;}

.page-body{max-width:520px;margin:0 auto;padding:16px;}

.section-card{background:white;border-radius:var(--radius);border:1.5px solid var(--border);margin-bottom:14px;overflow:hidden;box-shadow:0 2px 12px rgba(84,5,31,.05);}
.section-head{display:flex;align-items:center;gap:10px;padding:13px 16px;border-bottom:1.5px solid var(--border);background:linear-gradient(90deg,rgba(0,119,182,.04) 0%,transparent 100%);}
.section-num{width:28px;height:28px;border-radius:50%;background:var(--blue-mid);color:white;font-weight:800;font-size:var(--bk-fs-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.section-title{font-weight:800;font-size:var(--bk-fs-md);color:var(--blue-dark);}
.section-body{padding:14px 16px;display:flex;flex-direction:column;gap:12px;}

.field-label{font-size:var(--bk-fs-2xs);font-weight:900;color:var(--ink-mid);letter-spacing:.5px;text-transform:uppercase;margin-bottom:5px;display:flex;align-items:center;gap:6px;}
.field-label .required{color:var(--red);}
.field-input{width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:'Plus Jakarta Sans',sans-serif;font-size:var(--bk-fs-md);font-weight:600;color:var(--ink);background:white;outline:none;transition:border-color .2s,box-shadow .2s;appearance:none;}
.field-input:focus{border-color:var(--blue-mid);box-shadow:0 0 0 3px rgba(0,119,182,.12);}
.field-input.is-error{border-color:var(--red);box-shadow:0 0 0 3px rgba(239,68,68,.1);}
.field-input::placeholder{color:var(--ink-lt);font-weight:600;}
textarea.field-input{resize:none;min-height:80px;line-height:1.5;}
.field-error{font-size:var(--bk-fs-2xs);font-weight:800;color:var(--red);margin-top:4px;}

.zone-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
.zone-pill{border:2px solid var(--border);border-radius:var(--radius-sm);padding:10px 6px;text-align:center;cursor:pointer;background:white;position:relative;transition:border-color .2s ease,background-color .2s ease,box-shadow .2s ease;}
.zone-pill input[type=radio]{position:absolute;opacity:0;}
.zone-name{font-weight:800;font-size:var(--bk-fs-md);color:var(--ink);display:block;}
.zone-desc{font-size:var(--bk-fs-2xs);font-weight:800;color:var(--ink-lt);display:block;margin-top:2px;}
.zone-price{font-size:var(--bk-fs-2xs);font-weight:900;color:var(--green);display:block;margin-top:2px;}
.zone-pill:has(input:checked), .zone-pill.is-checked{border-color:var(--blue-mid);background:rgba(0,119,182,.06);box-shadow:0 0 0 3px rgba(0,119,182,.12);}
.zone-pill:has(input:checked) .zone-name, .zone-pill.is-checked .zone-name{color:var(--blue-mid);}

#addr-map{height:180px;border-radius:var(--radius-sm);border:1.5px solid var(--border);z-index:10;margin-bottom:4px;}
.map-hint{font-size:var(--bk-fs-2xs);font-weight:700;color:var(--ink-lt);display:flex;align-items:center;gap:5px;}

.toggle-wrap{display:flex;align-items:center;justify-content:space-between;padding:2px 0;}
.toggle-label{font-size:var(--bk-fs-sm);font-weight:800;color:var(--ink);}
.toggle-sub{font-size:var(--bk-fs-2xs);font-weight:700;color:var(--ink-lt);margin-top:2px;}
.toggle-switch{width:48px;height:26px;border-radius:99px;background:var(--border);cursor:pointer;position:relative;transition:background .2s;flex-shrink:0;border:none;}
.toggle-switch::after{content:'';position:absolute;width:20px;height:20px;border-radius:50%;background:white;top:3px;left:3px;transition:transform .2s;box-shadow:0 2px 6px rgba(0,0,0,.15);}
.toggle-switch.on{background:var(--green);}
.toggle-switch.on::after{transform:translateX(22px);}

.bottom-cta{max-width:520px;margin:0 auto;padding:4px 16px 8px;}
.cta-inner{max-width:520px;margin:0 auto;}
.btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,var(--blue-dark) 0%,var(--blue-mid) 100%);color:white;font-family:'Plus Jakarta Sans',sans-serif;font-weight:900;font-size:var(--bk-fs-md);border:none;border-radius:var(--radius-sm);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 6px 20px rgba(0,119,182,.35);transition:transform .15s;}
.btn-submit:active{transform:scale(.97);}
.btn-submit svg{width:18px;height:18px;}

.alert-error{background:#fff1f1;border:1.5px solid #fecaca;border-radius:var(--radius-sm);padding:12px 14px;font-size:var(--bk-fs-xs);font-weight:700;color:var(--red);margin-bottom:14px;}
.map-hint[hidden]{display:none}
</style>
<link rel="stylesheet" href="{{ $asset('css/page-transitions.css') }}"><script src="{{ $asset('js/page-transitions.js') }}" defer></script>
<link rel="stylesheet" href="{{ $asset('css/customer-forms.css') }}">
<link rel="stylesheet" href="{{ $asset('css/berkah-experience.css') }}">
@include('layouts.component._theme_head')
</head>
<body class="bk-experience bk-customer bk-standalone">
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
            <h1 class="header-title">Edit Alamat</h1>
            <div class="header-sub">{{ strtoupper($address->label ?? 'ALAMAT') }}</div>
        </div>
    </div>
</header>

<main class="page-body" id="main-content" tabindex="-1">

    @if($errors->any())
    <div class="alert-error">
        <span class="bk-i" aria-hidden="true">@include('layouts.component._icon', ['name' => 'alert-triangle', 'size' => 20])</span> Periksa kembali isian:<br>
        @foreach($errors->all() as $e) • {{ $e }}<br> @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('customer.addresses.update', $address) }}" id="addr-form">
        @csrf @method('PUT')

        {{-- SECTION 1: Info Penerima --}}
        <div class="section-card js-card">
            <div class="section-head">
                <div class="section-num">1</div>
                <div class="section-title">Info Penerima</div>
            </div>
            <div class="section-body">
                <div>
                    <label class="field-label" for="address-label">Label Alamat <span class="required">*</span></label>
                    <input placeholder="Contoh: Rumah" type="text" name="label" id="address-label" class="field-input {{ $errors->has('label') ? 'is-error' : '' }}"
                        placeholder="Rumah, Kantor, Kos, dll."
                        value="{{ $oldText('label', $address->label) }}" required>
                    @error('label') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field-label" for="address-recipient_name">Nama Penerima <span class="required">*</span></label>
                    <input placeholder="Contoh: Ibu Sari Wulandari" type="text" name="recipient_name" id="address-recipient_name" class="field-input {{ $errors->has('recipient_name') ? 'is-error' : '' }}"
                        value="{{ $oldText('recipient_name', $address->recipient_name) }}" required>
                    @error('recipient_name') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field-label" for="address-phone">No. HP / WhatsApp</label>
                    <input placeholder="Contoh: 0812 3456 7890" autocomplete="tel" type="tel" name="phone" id="address-phone" class="field-input"
                        value="{{ $oldText('phone', $address->phone) }}" placeholder="08xxxxxxxxxx">
                </div>
            </div>
        </div>

        {{-- SECTION 2: Lokasi --}}
        <div class="section-card js-card">
            <div class="section-head">
                <div class="section-num">2</div>
                <div class="section-title">Lokasi Alamat</div>
            </div>
            <div class="section-body">
                <div>
                    <div class="field-label">Titik Lokasi di Peta (Opsional)</div>
                    <div id="addr-map" hidden></div><p class="field-hint" id="map-fallback">Peta bersifat opsional. Jika tidak tampil, isi alamat lengkap secara manual.</p>
                    <div class="map-hint" hidden>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Tap atau geser pin untuk memperbarui titik
                    </div>
                    <input type="hidden" name="latitude" id="map-lat" value="{{ $oldText('latitude', $address->latitude) }}">
                    <input type="hidden" name="longitude" id="map-lng" value="{{ $oldText('longitude', $address->longitude) }}">
                </div>
                <div>
                    <label class="field-label" for="address-full_address">Alamat Lengkap <span class="required">*</span></label>
                    <textarea placeholder="Contoh: Jl. Merdeka No. 12, RT 03, Palembang" name="full_address" id="address-full_address" class="field-input {{ $errors->has('full_address') ? 'is-error' : '' }}"
                        required>{{ $oldText('full_address', $address->full_address) }}</textarea>
                    @error('full_address') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field-label" for="address-notes">Catatan Tambahan</label>
                    <input type="text" name="notes" id="address-notes" class="field-input"
                        placeholder="Warna pagar, patokan, dll."
                        value="{{ $oldText('notes', $address->notes) }}">
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
                            <div class="toggle-label">Jadikan Alamat Utama</div>
                            <div class="toggle-sub">Ditampilkan sebagai alamat utama dan dapat dipilih saat checkout</div>
                        </div>
                        @if($address->is_primary)<span>Alamat utama saat ini</span>@else<label><input type="checkbox" name="is_primary" value="1" @checked($oldText('is_primary'))> Jadikan utama</label>@endif
                    </div>
                </div>
            </div>
        </div>

    </form>
</main>

<div class="bottom-cta">
    <div class="cta-inner">
        <button type="submit" form="addr-form" class="btn-submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
            </svg>
            Simpan Perubahan
        </button>
    </div>
</div>

@include('layouts.component.customer._navbar_customer', ['active' => 'profil'])

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof L === 'undefined') return;
    document.getElementById('addr-map').hidden = false;
    const latitude = document.getElementById('map-lat');
    const longitude = document.getElementById('map-lng');
    const lat = Number(latitude.value), lng = Number(longitude.value);
    const hasCoordinates = latitude.value.trim() !== '' && longitude.value.trim() !== '' && Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
    const point = hasCoordinates ? [lat, lng] : [-1.6101, 103.6131];
    const map = L.map('addr-map').setView(point, hasCoordinates ? 15 : 13);
    document.querySelector('.map-hint').hidden = false;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
    let marker = hasCoordinates ? L.marker(point, { draggable: true }).addTo(map) : null;
    function save(p) { latitude.value = p.lat.toFixed(6); longitude.value = p.lng.toFixed(6); }
    if (marker) marker.on('dragend', function () { save(marker.getLatLng()); });
    map.on('click', function (e) {
        if (!marker) {
            marker = L.marker(e.latlng, { draggable: true }).addTo(map);
            marker.on('dragend', function () { save(marker.getLatLng()); });
        } else marker.setLatLng(e.latlng);
        save(e.latlng);
    });
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