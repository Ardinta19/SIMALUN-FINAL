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
{{-- Huruf merek dihosting sendiri (public/fonts + public/css/berkah-fonts.css):
     tidak ada permintaan ke fonts.googleapis.com, jadi halaman tetap benar saat
     luring maupun saat CDN pihak ketiga diblokir. Preload hanya berkas latin
     karena itu yang dipakai seluruh teks Indonesia. --}}
@once
<link rel="preload" as="font" type="font/woff2" crossorigin
      href="{{ $themeAsset('fonts/plus-jakarta-sans-latin-wght-normal.woff2') }}">
<link rel="stylesheet" href="{{ $themeAsset('css/berkah-fonts.css') }}">
@endonce
{{-- Warna bilah peramban: satu nilai merek untuk seluruh aplikasi. Skrip di
     bawah menukarnya ke #140f12 saat tema gelap dan memulihkannya kembali. --}}
<meta name="theme-color" content="#54051f">
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

    /* Bilah peramban ikut tema sejak lukisan pertama. Tanpa ini halaman gelap
       memakai bilah merah merek, dan di halaman yang tidak memuat
       berkah-ui-kit.js (mis. masuk & daftar) warnanya tidak pernah menyesuaikan. */
    var bar = document.querySelector('meta[name="theme-color"]');

    if (bar) {
        bar.setAttribute('content', dark ? '#140f12' : '#54051f');
    }
})();
</script>
<link rel="stylesheet" href="{{ $themeAsset('css/berkah-theme.css') }}">
<link rel="stylesheet" href="{{ $themeAsset('css/berkah-ui-kit.css') }}">
@endonce
