/*
 * Berkah Kreatif — tombol "Kembali" pintar.
 *
 * Cara kerja (dua lapis):
 *  1. Server sudah memilih URL cadangan yang masuk akal (halaman induk) dan
 *     menuliskannya di href. Tanpa JavaScript, tombol tetap bekerja.
 *  2. Saat JavaScript aktif DAN pengunjung benar-benar datang dari halaman lain
 *     di situs ini, tombol memakai history.back(). Hasilnya: filter, posisi
 *     gulir, dan tab yang sedang dibuka kembali seperti semula — bukan halaman
 *     induk yang generik.
 *
 * Bila halaman dibuka dari tautan luar, dari bookmark, setelah muat ulang,
 * atau di tab baru, history.back() akan melempar pengunjung keluar dari situs.
 * Semua kondisi itu sengaja dilewatkan sehingga href cadangan yang dipakai.
 *
 * Pemakaian: <a href="{{ $fallback }}" data-smart-back>Kembali</a>
 * (href tetap wajib supaya tetap berfungsi tanpa JS dan bisa dibuka di tab baru.)
 */
(function () {
    'use strict';

    if (window.bkSmartBackReady) return;
    window.bkSmartBackReady = true;

    var SITE = window.location;

    function parseSameSite(url) {
        if (!url) return null;
        try {
            var parsed = new URL(url, SITE.href);
        } catch (error) {
            return null;
        }
        return parsed.origin === SITE.origin ? parsed : null;
    }

    /** Apakah pengunjung sampai di halaman ini lewat navigasi di dalam situs? */
    function cameFromSameSite(anchor) {
        var referrer = parseSameSite(document.referrer);
        if (!referrer) return false;

        // Muat ulang halaman yang sama: jangan kembali ke diri sendiri.
        if (referrer.pathname === SITE.pathname && referrer.search === SITE.search) return false;

        // Jangan pernah kembali ke halaman keluar/masuk atau halaman form POST
        // yang bisa memicu kirim ulang data.
        var skipPaths = ['/login', '/logout', '/register'];
        if (skipPaths.indexOf(referrer.pathname) !== -1) return false;

        // Target khusus (mis. buka di tab baru) tidak boleh diubah perilakunya.
        if (anchor.target && anchor.target !== '_self') return false;

        // Beberapa browser menandai halaman ini sebagai hasil muat ulang meski
        // referrer masih terisi. Dalam kasus itu riwayat tidak bisa dipercaya.
        try {
            var entries = performance.getEntriesByType ? performance.getEntriesByType('navigation') : [];
            var nav = entries && entries[0];
            if (nav && nav.type === 'reload') return false;
        } catch (error) {
            /* performance API tidak tersedia — lanjutkan dengan pemeriksaan lain. */
        }

        return window.history.length > 1;
    }

    document.addEventListener('click', function (event) {
        // Biarkan perilaku bawaan untuk klik tengah, Ctrl/Cmd, Shift, Alt.
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        var anchor = event.target && event.target.closest ? event.target.closest('[data-smart-back]') : null;
        if (!anchor) return;
        if (anchor.hasAttribute('download')) return;

        if (!cameFromSameSite(anchor)) return;

        event.preventDefault();
        window.history.back();
    }, false);
})();
