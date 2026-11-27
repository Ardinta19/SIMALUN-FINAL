/* ══════════════════════════════════════════════════════════════════════════
   Berkah Kreatif — strip berjalan katalog
   ──────────────────────────────────────────────────────────────────────────
   Isi strip (lima langkah pemesanan + Instagram/TikTok/WhatsApp) bergerak ke
   KANAN tanpa henti. Ia berhenti saat kursor masuk, saat disentuh, saat
   diklik, dan saat salah satu tautannya difokuskan papan ketik; dua detik
   setelah interaksi terakhir ia lanjut sendiri.

   Tiga pengaman:
     1. `prefers-reduced-motion` atau `?gerak=mati` (localStorage
        `bk-motion=off`) → tidak ada salinan, tidak ada animasi; strip menjadi
        deretan biasa yang bisa digulir sendiri. Isi tetap terbaca.
     2. Salinan isi disembunyikan dari pembaca layar (`aria-hidden`) dan
        tautannya dicabut dari urutan Tab (`tabindex="-1"`), jadi tidak ada
        langkah yang terbaca dua kali dan tidak ada fokus yang hilang.
     3. Saat strip di luar layar atau tab tidak aktif, animasinya dihentikan
        supaya tidak membakar CPU di latar belakang.

   Tanpa JavaScript berkas ini tidak berperan apa pun: CSS sudah membuat
   strip bisa digulir mendatar.
   ══════════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var SPEED = 38;          // piksel per detik — cukup pelan untuk dibaca
    var RESUME_DELAY = 2000; // dua detik setelah interaksi terakhir
    var MAX_COPIES = 6;      // batas salinan agar tidak menggelembung di layar ultra-lebar

    function reducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function forcedOff() {
        try {
            return window.localStorage.getItem('bk-motion') === 'off';
        } catch (error) {
            return false;
        }
    }

    function forEach(list, fn) {
        if (!list) return;
        for (var i = 0; i < list.length; i += 1) fn(list[i]);
    }

    function cloneGroup(group) {
        var copy = group.cloneNode(true);
        copy.setAttribute('aria-hidden', 'true');
        copy.removeAttribute('data-bk-ribbon-group');
        forEach(copy.querySelectorAll('a'), function (link) {
            link.setAttribute('tabindex', '-1');
        });
        forEach(copy.querySelectorAll('[id]'), function (el) {
            el.removeAttribute('id');
        });
        return copy;
    }

    function init(bar) {
        if (!bar || bar.getAttribute('data-bk-ribbon-ready') === '1') return;
        bar.setAttribute('data-bk-ribbon-ready', '1');

        var viewport = bar.querySelector('[data-bk-ribbon-viewport]');
        var track = bar.querySelector('[data-bk-ribbon-track]');
        var group = bar.querySelector('[data-bk-ribbon-group]');

        if (!viewport || !track || !group) {
            bar.classList.add('is-static');
            return;
        }

        if (reducedMotion() || forcedOff()) {
            bar.classList.add('is-static');
            return;
        }

        var copies = 0;

        function addCopy() {
            track.appendChild(cloneGroup(group));
            copies += 1;
        }

        /* Susun salinan: minimal dua, lalu tambah dua-dua sampai lebih lebar
           dari dua layar. Jumlahnya selalu genap supaya sambungan -100/N %
           jatuh tepat di awal salinan yang sama. */
        function build() {
            while (copies < 2) addCopy();
            while (copies + 2 <= MAX_COPIES && track.scrollWidth < viewport.clientWidth * 2) {
                addCopy();
                addCopy();
            }
            if (copies % 2 !== 0) addCopy();
        }

        function measure() {
            var cycle = track.scrollWidth / copies; // lebar satu salinan
            var seconds = Math.max(12, Math.round(cycle / SPEED));
            track.style.setProperty('--bk-ribbon-shift', (-100 / copies).toFixed(4) + '%');
            track.style.setProperty('--bk-ribbon-duration', seconds + 's');
        }

        build();
        measure();
        bar.classList.add('is-running');

        /* ── Jeda & lanjut ────────────────────────────────────────────────── */
        var timer = null;
        var pointerInside = false;
        var focusInside = false;

        function pause() {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
            bar.classList.add('is-paused');
        }

        function resumeLater() {
            if (timer) window.clearTimeout(timer);
            timer = null;
            if (pointerInside || focusInside) return;
            timer = window.setTimeout(function () {
                timer = null;
                bar.classList.remove('is-paused');
            }, RESUME_DELAY);
        }

        bar.addEventListener('pointerenter', function () {
            pointerInside = true;
            pause();
        });
        bar.addEventListener('pointerover', function () {
            pointerInside = true;
        });
        bar.addEventListener('pointerleave', function () {
            pointerInside = false;
            resumeLater();
        });
        bar.addEventListener('pointerdown', pause);
        bar.addEventListener('pointerup', function (event) {
            /* Di layar sentuh tidak ada "pointerleave": setelah jari diangkat,
               jeda dihitung mundur dari ketukan itu sendiri. */
            if (event.pointerType === 'touch' || event.pointerType === 'pen') {
                pointerInside = false;
                resumeLater();
            }
        });
        bar.addEventListener('click', pause);
        bar.addEventListener('focusin', function () {
            focusInside = true;
            pause();
        });
        bar.addEventListener('focusout', function () {
            focusInside = false;
            resumeLater();
        });

        /* ── Hemat tenaga saat tidak terlihat ─────────────────────────────── */
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                bar.classList.add('is-offscreen');
            } else {
                bar.classList.remove('is-offscreen');
            }
        });

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                for (var i = 0; i < entries.length; i += 1) {
                    bar.classList.toggle('is-offscreen', !entries[i].isIntersecting);
                }
            }, { rootMargin: '120px 0px' });
            observer.observe(bar);
        }

        /* ── Lebar berubah: tambah salinan bila perlu, lalu hitung ulang ──── */
        var resizeTimer = null;
        window.addEventListener('resize', function () {
            if (resizeTimer) window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(function () {
                resizeTimer = null;
                if (track.scrollWidth < viewport.clientWidth * 2 && copies + 2 <= MAX_COPIES) {
                    addCopy();
                    addCopy();
                }
                measure();
            }, 160);
        }, { passive: true });
    }

    function initAll() {
        forEach(document.querySelectorAll('[data-bk-ribbon]'), init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.BkRibbon = { refresh: initAll };
})();
