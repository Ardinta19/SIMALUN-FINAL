{{--
|--------------------------------------------------------------------------
| Berkah Kreatif — Tema terang/gelap (head)
|--------------------------------------------------------------------------
| Wajib di-@include di dalam <head> SETELAH stylesheet halaman, agar:
|   1. `data-theme` sudah terpasang sebelum paint pertama (tanpa kedipan),
|   2. CSS tema & UI kit dimuat setelah CSS halaman sehingga override
|      permukaan gelap menang pada selektor yang setara.
|
| Nilai awal: localStorage('bk-theme') → 'light'/'dark', atau preferensi
| sistem bila belum pernah memilih.
|--------------------------------------------------------------------------
--}}
@php
    $themeAsset = function (string $path) {
        $full = public_path($path);
        return asset($path) . (file_exists($full) ? '?v=' . filemtime($full) : '');
    };
@endphp
@once
<script>
(function () {
    var root = document.documentElement;
    var stored = null;
    try { stored = window.localStorage.getItem('bk-theme'); } catch (e) {}
    var dark = false;
    if (stored === 'dark') {
        dark = true;
    } else if (stored !== 'light') {
        try { dark = window.matchMedia('(prefers-color-scheme: dark)').matches; } catch (e) {}
    }
    root.setAttribute('data-theme', dark ? 'dark' : 'light');
    root.style.colorScheme = dark ? 'dark' : 'light';
})();
</script>
<link rel="stylesheet" href="{{ $themeAsset('css/berkah-theme.css') }}">
<link rel="stylesheet" href="{{ $themeAsset('css/berkah-ui-kit.css') }}">
@endonce
