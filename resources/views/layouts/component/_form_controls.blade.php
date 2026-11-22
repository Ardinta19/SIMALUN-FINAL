{{--
|--------------------------------------------------------------------------
| Berkah Kreatif — Form Controls (STANDAR DEFAULT UI/UX)
|--------------------------------------------------------------------------
| Satu-satunya sumber kontrol form kustom untuk seluruh aplikasi:
|   <select>                        → listbox + popover (bottom sheet di HP)
|   <select data-fc-segmented>      → segmented control (2–4 opsi)
|   <input type="date">             → kalender Indonesia (GSAP animated)
|   <input type="number" data-fc-stepper> → stepper
|
| Kontrol native yang sudah di-enhance tetap berada di DOM sehingga nama
| field, nilai, dan format kiriman TIDAK berubah (YYYY-MM-DD untuk tanggal).
|
| ── CARA PAKAI ─────────────────────────────────────────────────────────
| Cukup @include sekali di halaman/layout. Semua <select> dan
| <input type="date"> otomatis di-enhance; tidak perlu atribut apa pun.
| Tambahkan atribut berikut hanya bila perlu:
|   data-fc-native     → biarkan kontrol ini native (mis. butuh native UI)
|   data-fc-plain      → jangan enhance select ini
|   data-fc-title      → judul sheet/kalender
|   data-fc-placeholder→ teks placeholder trigger
|   data-fc-segmented  → render 2–4 opsi sebagai segmented control
|   data-fc-stepper    → input angka memakai tombol − / +
|
| API publik:
|   window.initFormControls(root = document)  → idempoten, untuk markup baru
|   window.fcPick({anchor,title,options,onSelect}) → sheet/popover generik
|
| Aset: public/css/berkah-controls.css, public/js/berkah-controls.js,
|       public/vendor/gsap/gsap.min.js (lokal, tanpa CDN).
|--------------------------------------------------------------------------
--}}
@php
    $fcAsset = function (string $path) {
        $full = public_path($path);
        return asset($path) . (file_exists($full) ? '?v=' . filemtime($full) : '');
    };
@endphp

@once
    {{-- Tandai lebih awal agar normalisasi kontrol tidak berkedip. --}}
    <script>document.documentElement.classList.add('fc-ui');</script>
    <link rel="stylesheet" href="{{ $fcAsset('css/berkah-controls.css') }}">
    <script src="{{ $fcAsset('vendor/gsap/gsap.min.js') }}"></script>
    <script src="{{ $fcAsset('js/berkah-controls.js') }}"></script>
@endonce
