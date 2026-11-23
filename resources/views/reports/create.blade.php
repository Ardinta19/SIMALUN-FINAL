<!DOCTYPE html>
<html lang="id">
<head>
@php $asset = fn (string $path) => asset($path).(file_exists(public_path($path)) ? '?v='.filemtime(public_path($path)) : ''); @endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Lapor Kendala – Berkah Kreatif</title>
@include('layouts.component.customer._head_meta')
<style>
:root {
    --blue-dark: #54051f;
    --blue: #85092d;
    --blue-lt: #f8e9ed;
    --green: #047857;
    --green-lt: #ecfdf5;
    --orange: #b45309;
    --orange-lt: #fef3c7;
    --red: #dc2626;
    --red-lt: #fef2f2;
    --surface: #faf4f3;
    --card: #ffffff;
    --ink: #1a2332;
    --ink-mid: #4a5568;
    --ink-lt: #5f7082;
    --border: #f0dfe1;
    --radius: 16px;
    --radius-sm: 10px;
    --font: 'Plus Jakarta Sans', -apple-system, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
body {
    font-family: var(--font);
    background: var(--surface);
    color: var(--ink);
    min-height: 100vh;
    padding-bottom: calc(80px + env(safe-area-inset-bottom, 0px));
    overflow-x: hidden;
}
.wrap { max-width: 520px; margin: 0 auto; padding: 16px; }

/* HEADER */
.page-header {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 20px; padding-top: max(env(safe-area-inset-top, 0px), 8px);
}
.btn-back {
    width: 38px; height: 38px; border-radius: 10px;
    background: var(--card); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    color: var(--ink); text-decoration: none; flex-shrink: 0;
}
.page-title { font-size: 1.1rem; font-weight: 800; color: var(--blue-dark); }

/* FORM CARD */
.form-card {
    background: var(--card); border-radius: var(--radius);
    border: 1px solid var(--border); padding: 20px;
    margin-bottom: 16px;
}
.form-card__title {
    font-size: 0.9rem; font-weight: 800; color: var(--ink);
    margin-bottom: 4px;
}
.form-card__desc {
    font-size: 0.75rem; font-weight: 600; color: var(--ink-lt);
    margin-bottom: 18px; line-height: 1.5;
}

/* FORM ELEMENTS */
.field { margin-bottom: 16px; }
.field__label {
    display: block; font-size: 0.75rem; font-weight: 700;
    color: var(--ink-mid); margin-bottom: 6px;
}
.field__required { color: var(--red); }

.category-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.category-pill {
    display: flex; align-items: center; gap: 6px;
    padding: 10px 14px; border-radius: var(--radius-sm);
    border: 1.5px solid var(--border); background: var(--card);
    cursor: pointer; transition: all 0.15s;
    font-size: 0.78rem; font-weight: 700; color: var(--ink-mid);
}
.category-pill:has(input:checked), .category-pill.is-checked {
    border-color: var(--blue); background: var(--blue-lt); color: var(--blue);
}
.category-pill input { position: absolute; opacity: 0; }
.category-pill:focus-within { outline: 3px solid #bc5b75; outline-offset: 2px; }
.category-pill__icon { font-size: 1rem; }

.field__textarea {
    width: 100%; min-height: 120px; padding: 12px 14px;
    border-radius: var(--radius-sm); border: 1.5px solid var(--border);
    font-family: var(--font); font-size: 0.82rem; font-weight: 600;
    color: var(--ink); resize: vertical; outline: none;
    transition: border-color 0.2s; line-height: 1.6;
}
.field__textarea:focus { border-color: var(--blue); }
.field__textarea::placeholder { color: var(--ink-lt); font-weight: 500; }

.field__file-wrap {
    position: relative; overflow: hidden;
    border: 1.5px dashed var(--border); border-radius: var(--radius-sm);
    padding: 16px; text-align: center; cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.field__file-wrap:hover { border-color: var(--blue); background: var(--blue-lt); }
.field__file-wrap input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer;
}
.field__file-icon { font-size: 1.5rem; margin-bottom: 6px; }
.field__file-text { font-size: 0.72rem; font-weight: 700; color: var(--ink-lt); }
.field__file-hint { font-size: 0.62rem; font-weight: 600; color: var(--ink-lt); margin-top: 4px; }

.submit-btn {
    width: 100%; padding: 14px; border-radius: var(--radius-sm);
    background: var(--blue-dark); color: white; border: none;
    font-family: var(--font); font-size: 0.85rem; font-weight: 700;
    cursor: pointer; display: flex; align-items: center;
    justify-content: center; gap: 8px; transition: transform 0.12s;
}
.submit-btn:active { transform: scale(0.98); }

/* WA ALTERNATIVE */
.wa-alt {
    display: flex; align-items: center; gap: 12px;
    background: var(--green-lt); border: 1px solid #a7f3d0;
    border-radius: var(--radius-sm); padding: 12px 14px;
    margin-bottom: 16px; text-decoration: none;
}
.wa-alt__icon { font-size: 1.3rem; flex-shrink: 0; }
.wa-alt__text { font-size: 0.75rem; font-weight: 700; color: #065f46; line-height: 1.4; }
.wa-alt__text strong { display: block; font-size: 0.8rem; color: #047857; }

/* HISTORY */
.history-card {
    background: var(--card); border-radius: var(--radius);
    border: 1px solid var(--border); overflow: hidden;
}
.history-card__header {
    padding: 14px 16px; border-bottom: 1px solid var(--border);
    font-size: 0.82rem; font-weight: 800; color: var(--ink);
}
.history-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; border-bottom: 1px solid #f8fafc;
}
.history-item:last-child { border-bottom: none; }
.history-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.history-body { flex: 1; min-width: 0; }
.history-desc {
    font-size: 0.75rem; font-weight: 700; color: var(--ink);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.history-meta { font-size: 0.62rem; font-weight: 600; color: var(--ink-lt); margin-top: 2px; }
.history-badge {
    font-size: 0.6rem; font-weight: 800; padding: 3px 8px;
    border-radius: 99px; flex-shrink: 0; text-transform: uppercase;
}

/* TOAST */
.toast {
    position: fixed; top: 16px; left: 50%; transform: translateX(-50%);
    background: var(--green); color: white; padding: 10px 20px;
    border-radius: 10px; font-size: 0.78rem; font-weight: 700;
    z-index: 9999; box-shadow: 0 4px 20px rgba(5,150,105,0.3);
    animation: toastIn 0.3s ease, toastOut 0.3s ease 2.5s forwards;
}
@keyframes toastIn { from { opacity:0; transform:translateX(-50%) translateY(-20px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
@keyframes toastOut { to { opacity:0; transform:translateX(-50%) translateY(-20px); } }

/* ERROR */
.field__error { font-size: 0.68rem; font-weight: 700; color: var(--red); margin-top: 4px; }
</style>
<link rel="stylesheet" href="{{ $asset('css/page-transitions.css') }}"><script src="{{ $asset('js/page-transitions.js') }}" defer></script>
@if(auth()->user()->role === 'customer')<link rel="stylesheet" href="{{ $asset('css/berkah-experience.css') }}">@endif
@include('layouts.component._theme_head')
</head>
<body @if(auth()->user()->role === 'customer') class="bk-experience bk-customer bk-standalone-report" @endif>
@if(auth()->user()->role === 'customer')@include('catalog.customer-navigation')@endif

@if(session('success'))
<div class="toast">{{ session('success') }}</div>
@endif

<main class="wrap" id="main-content" tabindex="-1">

    {{-- Header --}}
    <header class="page-header">
        @php
            $backRoute = match(auth()->user()->role) {
                'admin' => 'admin.dashboard',
                'driver' => 'driver.dashboard',
                default => 'customer.dashboard',
            };
        @endphp
        <a href="{{ route($backRoute) }}" class="btn-back" aria-label="Kembali ke beranda">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <h1 class="page-title">Lapor Kendala</h1>
    </header>

    {{-- Form --}}
    <div class="form-card">
        <div class="form-card__title">Ada kendala atau masukan?</div>
        <div class="form-card__desc">Ceritakan ke kami agar bisa segera ditangani. Tim kami akan meninjau laporan yang masuk.</div>

        <form method="POST" action="{{ route(auth()->user()->role === 'driver' ? 'driver.report.store' : 'customer.report.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- Kategori --}}
            <div class="field">
                <label class="field__label">Kategori <span class="field__required">*</span></label>
                <div class="category-pills">
                    <label class="category-pill">
                        <input type="radio" name="category" value="bug" {{ old('category', 'bug') === 'bug' ? 'checked' : '' }}>
                        <span class="category-pill__icon">🐛</span> Bug / Error
                    </label>
                    <label class="category-pill">
                        <input type="radio" name="category" value="saran" {{ old('category') === 'saran' ? 'checked' : '' }}>
                        <span class="category-pill__icon">💡</span> Saran
                    </label>
                    <label class="category-pill">
                        <input type="radio" name="category" value="komplain" {{ old('category') === 'komplain' ? 'checked' : '' }}>
                        <span class="category-pill__icon">😤</span> Komplain
                    </label>
                </div>
                @error('category') <div class="field__error">{{ $message }}</div> @enderror
            </div>

            {{-- Deskripsi --}}
            <div class="field">
                <label class="field__label" for="description">Jelaskan kendala kamu <span class="field__required">*</span></label>
                <textarea class="field__textarea" id="description" name="description" placeholder="Ceritakan apa yang terjadi, kapan, dan di halaman mana..." required>{{ old('description') }}</textarea>
                @error('description') <div class="field__error">{{ $message }}</div> @enderror
            </div>

            {{-- Screenshot --}}
            <div class="field">
                <label class="field__label" for="file-input">Screenshot (opsional)</label>
                <div class="field__file-wrap" id="dropzone">
                    <input type="file" name="screenshot" accept="image/*" id="file-input">
                    <div class="field__file-icon">📷</div>
                    <div class="field__file-text" id="file-name">Klik atau seret gambar ke sini</div>
                    <div class="field__file-hint">Format: JPG, PNG, WEBP — maks 5 MB</div>
                </div>
                @error('screenshot') <div class="field__error">{{ $message }}</div> @enderror
            </div>

            {{-- Submit --}}
            <button type="submit" class="submit-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Kirim Laporan
            </button>
        </form>
    </div>

    @if($settings?->whatsapp_number)
    <a href="https://wa.me/{{ $settings->whatsapp_number }}?text={{ rawurlencode('Halo Berkah Kreatif, saya ingin melaporkan kendala.') }}" target="_blank" rel="noopener noreferrer" class="wa-alt">
        <span class="wa-alt__icon">💬</span>
        <span class="wa-alt__text"><strong>Hubungi via WhatsApp</strong> Chat langsung dengan admin.</span>
    </a>
    @endif

    {{-- Riwayat Laporan --}}
    @if(isset($myReports) && $myReports->count() > 0)
    <div class="history-card">
        <div class="history-card__header">Riwayat Laporan Kamu</div>
        @foreach($myReports as $report)
        <div class="history-item">
            <div class="history-dot" style="background: {{ $report->status_color }};"></div>
            <div class="history-body">
                <div class="history-desc">{{ $report->category_label }} — {{ Str::limit($report->description, 40) }}</div>
                <div class="history-meta">{{ $report->created_at->diffForHumans() }}</div>
            </div>
            <span class="history-badge" style="background: {{ $report->status_color }}20; color: {{ $report->status_color }};">{{ $report->status_label }}</span>
        </div>
        @endforeach
    </div>
    @endif

</main>

{{-- Navbar berdasarkan role --}}
@if(auth()->user()->role === 'admin')
    @include('layouts.component.admin._navbar_admin', ['active' => ''])
@elseif(auth()->user()->role === 'driver')
    @include('layouts.component.driver._navbar_driver', ['active' => ''])
@else
    @include('layouts.component.customer._navbar_customer', ['active' => ''])
@endif

<script>
// File input preview
var _fileInput = document.getElementById('file-input');
if (_fileInput) _fileInput.addEventListener('change', function() {
    const name = (this.files && this.files[0]) ? this.files[0].name : null;
    if (name) {
        document.getElementById('file-name').textContent = name;
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
@if(auth()->user()->role === 'customer')<script src="{{ $asset('js/berkah-experience.js') }}" defer></script>@endif
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body>
</html>
