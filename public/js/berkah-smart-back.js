/*
 * Berkah Kreatif — tombol "Kembali" pintar.
 *
 * Cara kerja (dua lapis):
 *  1. Server sudah memilih URL cadangan yang masuk akal (halaman induk) dan
 *     menuliskannya di href. Tanpa JavaScript, tombol tetap bekerja.
 *  2. Saat JavaScript aktif DAN pengunjung benar-benar datang dari halaman lain
 *     di situs ini — DAN halaman itu sama dengan halaman yang tertulis di
 *     href — tombol memakai history.back(). Hasilnya: filter, posisi gulir,
 *     dan tab yang sedang dibuka kembali seperti semula.
 *
 * Pemeriksaan tujuan itu wajib. Dulu `history.back()` dipakai untuk setiap
 * referrer dalam situs, sehingga tombol berlabel "Kembali ke keranjang" di
 * halaman checkout bisa membawa pengunjung ke halaman papan bunga (asal
 * checkout dibuka lewat "Pesan langsung") — labelnya berbohong. Bila tujuan
 * riwayat berbeda dari label, href-lah yang dipakai.
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

    function samePath(a, b) {
        var normalize = function (path) {
            return (path || '/').replace(/\/+$/, '') || '/';
        };
        return normalize(a) === normalize(b);
    }

    /**
     * Boleh memakai history.back()?
     *
     * Syarat: pengunjung datang dari halaman lain di situs ini, halaman itu
     * BUKAN halaman formulir yang berbahaya diulang, dan halaman itu memang
     * halaman yang ditulis pada href tombol. Tanpa syarat terakhir, label
     * tombol dan tujuan sebenarnya bisa berbeda.
     */
    function cameFromSameSite(anchor) {
        var referrer = parseSameSite(document.referrer);
        if (!referrer) return false;

        var href = parseSameSite(anchor.getAttribute('href'));
        if (!href) return false;

        // Inti perbaikan: tujuan riwayat harus sama dengan yang tertulis di
        // tombol. Kalau berbeda, ikuti href supaya label tidak berbohong.
        if (!samePath(referrer.pathname, href.pathname)) return false;

        // Muat ulang halaman yang sama: jangan kembali ke diri sendiri.
        if (referrer.pathname === SITE.pathname && referrer.search === SITE.search) return false;

        // Jangan pernah kembali ke halaman keluar/masuk, halaman checkout, atau
        // halaman form POST lain yang bisa memicu kirim ulang data.
        var skipPaths = ['/login', '/logout', '/register', '/checkout'];
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

    /* Klik ganda pada tombol Kembali tidak boleh memanggil history.back()
       dua kali — pengunjung akan melompat dua halaman ke belakang. */
    var leaving = false;

    document.addEventListener('click', function (event) {
        if (leaving) {
            event.preventDefault();
            return;
        }
        // Biarkan perilaku bawaan untuk klik tengah, Ctrl/Cmd, Shift, Alt.
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        var anchor = event.target && event.target.closest ? event.target.closest('[data-smart-back]') : null;
        if (!anchor) return;
        if (anchor.hasAttribute('download')) return;

        if (!cameFromSameSite(anchor)) return;

        leaving = true;
        event.preventDefault();
        window.history.back();
    }, false);
})();
