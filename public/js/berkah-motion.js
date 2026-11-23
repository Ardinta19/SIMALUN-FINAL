/* ==========================================================================
   BERKAH KREATIF — LAPISAN GERAK (berkah-motion.js)
   --------------------------------------------------------------------------
   Satu mesin gerak untuk seluruh peran (guest, auth, customer, admin).
   Pustaka lokal: GSAP 3.15 + ScrollTrigger (public/vendor/gsap) dan
   Lenis 1.3 (public/vendor/lenis). Tidak ada CDN.

   Pembagian tugas — masing-masing dipakai sesuai fungsinya:

   · Lenis   → gulir halus berbobot untuk roda mouse, trackpad, dan layar
               sentuh (syncTouch dengan lerp lebih ringan agar tidak terasa
               "berat" di ponsel). Otomatis berhenti saat sheet/dialog
               mengunci gulir (kelas .fc-scroll-lock). Halaman yang ingin
               gulir asli cukup menandai <html data-bk-no-smooth>.
   · GSAP + ScrollTrigger
             → kemunculan bertahap elemen ([data-bk-reveal],
               [data-bk-reveal-group]), parallax halus ([data-bk-parallax]),
               dan hitungan angka ([data-bk-count]).
   · Micro-interaction
             → tekanan tombol berpegas ([data-bk-press]), kemiringan kartu
               katalog ([data-bk-tilt]), serta denyut chip status aktif.

   Prinsip keselamatan:
   1. Tanpa JavaScript, tanpa GSAP, atau saat skrip gagal: tidak ada yang
      disembunyikan lewat CSS — elemen tetap terlihat sepenuhnya.
   2. prefers-reduced-motion: reduce mematikan seluruh gerak, termasuk Lenis.
   3. saveData / koneksi lambat: Lenis dan parallax dilewati.
   4. Semua transform dikembalikan ke nilai netral setelah animasi selesai.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.documentElement;
    var reduceQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var finePointer = window.matchMedia ? window.matchMedia('(pointer: fine)') : null;
    var connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection || null;
    var saveData = !!(connection && (connection.saveData || /2g/.test(connection.effectiveType || '')));

    var state = {
        lenis: null,
        triggers: [],
        ticker: null,
        started: false
    };

    /* ── Utilitas ─────────────────────────────────────────────────────────── */

    function prefersReduced() {
        return !!(reduceQuery && reduceQuery.matches);
    }

    function motionOn() {
        return !prefersReduced();
    }

    function hasGsap() {
        return !!(window.gsap && window.ScrollTrigger);
    }

    function each(list, fn) {
        if (!list) return;
        for (var i = 0; i < list.length; i += 1) fn(list[i], i);
    }

    function once(el, key) {
        if (el.dataset[key]) return false;
        el.dataset[key] = '1';
        return true;
    }

    /* ── 1. Lenis: gulir halus ────────────────────────────────────────────────
       Semua perangkat, termasuk layar sentuh. Di sentuh, Lenis memakai
       syncTouch dengan lerp ringan supaya gulir jari tetap mengikuti jari.
       Elemen bertanda [data-lenis-prevent] (sheet, tabel bergulir, sidebar)
       tetap memakai gulir asli. */

    function scrollLocked() {
        return document.documentElement.classList.contains('fc-scroll-lock')
            || document.body.classList.contains('fc-scroll-lock')
            || document.documentElement.classList.contains('lenis-stopped-lock');
    }

    /* Wadah bergulir sendiri (sidebar admin, tabel lebar, badan dialog) harus
       memakai gulir asli. Lenis berhenti mengintersep roda mouse di elemen
       bertanda data-lenis-prevent, jadi tanda itu dipasang sekali di sini. */
    var SCROLL_REGIONS = [
        '.adm-sidebar', '.adm-drawer', '.adm-scroll-x', '.adm-sheet__body',
        '.bk-dialog', '.bk-filter-sheet', '.fc-sheet', '.fc-list', '.fc-cal',
        '.bk-table-wrap', '.table-scroll'
    ].join(',');

    function markScrollRegions() {
        each(document.querySelectorAll(SCROLL_REGIONS), function (el) {
            el.setAttribute('data-lenis-prevent', '');
        });
    }

    function initLenis() {
        if (state.lenis || !window.Lenis || !motionOn() || saveData) return;
        if (document.documentElement.hasAttribute('data-bk-no-smooth')) return;

        try {
            var lenis = new window.Lenis({
                duration: 1.05,
                easing: function (t) {
                    return Math.min(1, 1.001 - Math.pow(2, -10 * t));
                },
                smoothWheel: true,
                wheelMultiplier: 1,
                touchMultiplier: 1.2,
                /* Sentuh: ikuti jari lebih rapat (lerp kecil) daripada roda
                   mouse, supaya tidak terasa "melayang" saat menggulir cepat. */
                syncTouch: true,
                syncTouchLerp: 0.09,
                touchInertiaMultiplier: 28,
                autoRaf: false
            });
            state.lenis = lenis;
            root.classList.add('bk-lenis');
            markScrollRegions();

            /* Lenis default menggulir jendela (bukan wrapper sendiri), jadi
               ScrollTrigger membaca scrollTop yang sama. Cukup beri tahu
               ScrollTrigger setiap kali Lenis memperbarui posisi — tanpa
               scrollerProxy, yang justru bisa salah hitung pada elemen fixed. */
            if (window.gsap && window.ScrollTrigger) {
                lenis.on('scroll', window.ScrollTrigger.update);
                state.ticker = function (time) {
                    lenis.raf(time * 1000);
                };
                window.gsap.ticker.add(state.ticker);
                window.gsap.ticker.lagSmoothing(0);
            } else {
                var raf = function (time) {
                    if (state.lenis !== lenis) return;
                    lenis.raf(time);
                    window.requestAnimationFrame(raf);
                };
                window.requestAnimationFrame(raf);
            }

            /* Kunci/buka gulir mengikuti sheet milik form-controls. */
            var syncLock = function () {
                if (scrollLocked()) lenis.stop();
                else lenis.start();
            };
            var watcher = new MutationObserver(syncLock);
            watcher.observe(root, { attributes: true, attributeFilter: ['class'] });
            watcher.observe(document.body, { attributes: true, attributeFilter: ['class'] });

            /* Tautan jangkar di dalam halaman memakai gulir halus yang sama.
               Tautan "lompat ke isi" tidak disentuh: fokus keyboard harus
               tetap berpindah seperti perilaku asli peramban. */
            document.addEventListener('click', function (event) {
                /* Ctrl/Cmd/Shift/Alt + klik berarti "buka di tab/jendela
                   baru"; klik tengah bukan urusan kita; dan bila skrip lain
                   sudah menangani klik ini, jangan diambil alih. */
                if (event.defaultPrevented || event.button !== 0) return;
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                var link = event.target.closest ? event.target.closest('a[href^="#"]') : null;
                if (!link) return;
                if (link.classList.contains('adm-skip') || link.classList.contains('skip-link')) return;
                if (link.hasAttribute('download')) return;
                if (link.target && link.target !== '_self') return;
                var hash = link.getAttribute('href');
                if (!hash || hash === '#') return;
                var target = null;
                try {
                    target = document.getElementById(decodeURIComponent(hash.slice(1)));
                } catch (error) {
                    target = null;
                }
                if (!target) return;
                event.preventDefault();
                lenis.scrollTo(target, { offset: -84 });
                if (history.replaceState) history.replaceState(null, '', hash);
            });
        } catch (error) {
            /* Gulir asli tetap dipakai bila Lenis gagal dimuat. */
            state.lenis = null;
        }
    }

    /* ── 2. Kemunculan elemen ─────────────────────────────────────────────────
       [data-bk-reveal="up|fade|scale"]  → satu elemen
       [data-bk-reveal-group="list"]     → anak-anaknya berurutan
       Elemen di atas lipatan dianimasikan segera; sisanya menunggu masuk
       layar satu kali saja (once) supaya gulir balik tidak berkedip. */

    function revealTargets() {
        var singles = document.querySelectorAll('[data-bk-reveal]:not([data-bk-done])');
        each(singles, function (el) {
            if (!once(el, 'bkDone')) return;
            var kind = el.getAttribute('data-bk-reveal') || 'up';
            runReveal(el, kind, 0);
        });

        var groups = document.querySelectorAll('[data-bk-reveal-group]');
        each(groups, function (group) {
            if (!once(group, 'bkGroupDone')) return;
            var children = Array.prototype.slice.call(group.children);
            var step = parseFloat(group.getAttribute('data-bk-reveal-stagger')) || 0.07;
            children.forEach(function (child, index) {
                runReveal(child, 'up', Math.min(index * step, 0.42));
            });
        });
    }

    function runReveal(el, kind, delay) {
        var gsap = window.gsap;
        if (!gsap) {
            el.classList.add('is-in');
            return;
        }
        var map = {
            up: { y: 18, opacity: 0 },
            fade: { opacity: 0 },
            scale: { y: 12, scale: 0.985, opacity: 0 },
            hero: { y: 24, opacity: 0 }
        };
        var from = map[kind] || map.up;
        var tween = Object.assign({}, from, {
            duration: kind === 'hero' ? 0.72 : 0.55,
            delay: delay,
            ease: 'power3.out',
            clearProps: 'opacity',
            onStart: function () { el.classList.add('is-in'); },
            scrollTrigger: {
                trigger: el,
                start: 'top 92%',
                once: true
            }
        });
        gsap.from(el, tween);
    }

    /* ── 3. Parallax halus ───────────────────────────────────────────────────
       [data-bk-parallax="0.12"] → bergeser maksimal 12% tinggi elemen. */

    function initParallax() {
        if (!motionOn() || saveData) return;
        each(document.querySelectorAll('[data-bk-parallax]'), function (el) {
            /* refresh() dipanggil setiap konten baru masuk (katalog dinamis,
               pratinjau dashboard). Tanpa penanda ini, tiap panggilan membuat
               satu ScrollTrigger tambahan untuk elemen yang sama. */
            /* Kunci penanda WAJIB berbeda dari atribut pemicunya: `once()`
               menulis dataset dengan nama yang sama, dan `data-bk-parallax`
               sudah berisi kedalaman (mis. "0.08") — memakainya sebagai kunci
               membuat parallax tidak pernah berjalan sama sekali. */
            if (!once(el, 'bkParallaxReady')) return;
            var depth = parseFloat(el.getAttribute('data-bk-parallax')) || 0.1;
            var distance = Math.max(-40, Math.min(40, depth * 160));
            window.gsap.to(el, {
                y: distance,
                ease: 'none',
                scrollTrigger: {
                    trigger: el,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 0.6
                }
            });
        });
    }

    /* ── 4. Micro-interaction: tekanan berpegas ──────────────────────────────
       [data-bk-press] → tombol terasa "masuk" saat ditekan dan memantul
       kembali. Hanya transform, tanpa menggeser tata letak. */

    function initPress() {
        if (!motionOn()) return;
        var gsap = window.gsap;
        if (!gsap) return;

        document.addEventListener('pointerdown', function (event) {
            var el = event.target.closest ? event.target.closest('[data-bk-press]') : null;
            if (!el) return;
            gsap.to(el, { scale: 0.965, duration: 0.12, ease: 'power2.out', overwrite: true });
        }, { passive: true });

        var release = function (event) {
            var el = event.target.closest ? event.target.closest('[data-bk-press]') : null;
            if (!el) return;
            gsap.to(el, { scale: 1, duration: 0.34, ease: 'elastic.out(1, 0.55)', overwrite: true });
        };
        document.addEventListener('pointerup', release, { passive: true });
        document.addEventListener('pointercancel', release, { passive: true });
        document.addEventListener('pointerleave', release, { passive: true });
    }

    /* ── 5. Micro-interaction: kartu katalog sedikit mengikuti kursor ───────
       [data-bk-tilt] → miring maksimal 3°, hanya di penunjuk presisi.
       Sengaja kecil: kartu berisi foto produk, bukan mainan 3D. */

    function initTilt() {
        if (!motionOn() || saveData || !finePointer || !finePointer.matches) return;
        var gsap = window.gsap;
        if (!gsap) return;

        each(document.querySelectorAll('[data-bk-tilt]'), function (card) {
            /* Sama seperti parallax: tanpa penanda, cepat atau lambat satu
               kartu punya beberapa pasang listener pointer dan setiap gerak
               kursor menjalankan beberapa tween sekaligus. */
            if (!once(card, 'bkTiltReady')) return;
            var rotX = gsap.quickTo(card, 'rotationX', { duration: 0.4, ease: 'power2.out' });
            var rotY = gsap.quickTo(card, 'rotationY', { duration: 0.4, ease: 'power2.out' });
            gsap.set(card, { transformStyle: 'preserve-3d' });

            /* Perspektif dipasang saat kursor benar-benar masuk: animasi
               kemunculan kartu menimpa transform lebih dulu. */
            card.addEventListener('pointerenter', function () {
                gsap.set(card, { transformPerspective: 900 });
            });

            card.addEventListener('pointermove', function (event) {
                var box = card.getBoundingClientRect();
                var x = (event.clientX - box.left) / box.width - 0.5;
                var y = (event.clientY - box.top) / box.height - 0.5;
                rotY(x * 5);
                rotX(y * -5);
            });
            card.addEventListener('pointerleave', function () {
                rotY(0);
                rotX(0);
            });
        });
    }

    /* ── 6. Hitungan angka ───────────────────────────────────────────────────
       [data-bk-count="1240"] → angka naik dari 0 saat masuk layar. */

    function initCounters() {
        if (!motionOn()) return;
        each(document.querySelectorAll('[data-bk-count]'), function (el) {
            if (!once(el, 'bkCountDone')) return;
            var target = parseFloat(el.getAttribute('data-bk-count'));
            if (isNaN(target)) return;
            var prefix = el.getAttribute('data-bk-count-prefix') || '';
            var suffix = el.getAttribute('data-bk-count-suffix') || '';
            var value = { n: 0 };
            window.gsap.to(value, {
                n: target,
                duration: 0.9,
                ease: 'power2.out',
                snap: { n: 1 },
                onUpdate: function () {
                    el.textContent = prefix + Math.round(value.n).toLocaleString('id-ID') + suffix;
                },
                scrollTrigger: { trigger: el, start: 'top 94%', once: true }
            });
        });
    }

    /* ── 7. Siklus hidup ─────────────────────────────────────────────────────
       Halaman yang menambah konten baru (katalog dinamis, lembar filter)
       dapat memanggil window.BkMotion.refresh(). */

    function start() {
        if (state.started) {
            refresh();
            return;
        }
        state.started = true;
        root.classList.add('bk-motion');

        if (!motionOn()) {
            /* Semua elemen yang mungkin dianimasikan tetap utuh. */
            each(document.querySelectorAll('[data-bk-reveal], [data-bk-reveal-group] > *'), function (el) {
                el.classList.add('is-in');
            });
            return;
        }

        if (hasGsap()) {
            window.gsap.registerPlugin(window.ScrollTrigger);
            window.gsap.defaults({ ease: 'power3.out' });
            initLenis();
            revealTargets();
            initParallax();
            initTilt();
            initCounters();
            initPress();
        } else {
            /* GSAP gagal dimuat: tampilkan semuanya tanpa gerak. */
            each(document.querySelectorAll('[data-bk-reveal], [data-bk-reveal-group] > *'), function (el) {
                el.classList.add('is-in');
            });
            initLenis();
        }

        document.addEventListener('bk:content-updated', refresh);
    }

    function refresh() {
        if (!motionOn() || !hasGsap()) return;
        if (state.lenis) markScrollRegions();
        revealTargets();
        initParallax();
        initTilt();
        initCounters();
        if (window.ScrollTrigger) window.ScrollTrigger.refresh();
    }

    /* Kembali dari cache peramban (bfcache): mesin gerak dinyalakan ulang. */
    window.addEventListener('pageshow', function (event) {
        if (event.persisted && !state.started) start();
    });

    /* Lenis dilepas saat halaman ditinggalkan agar tidak menahan memori. */
    window.addEventListener('pagehide', function () {
        if (state.ticker && window.gsap) window.gsap.ticker.remove(state.ticker);
        if (state.lenis) state.lenis.destroy();
        state.lenis = null;
        state.ticker = null;
        state.started = false;
    });

    if (reduceQuery && reduceQuery.addEventListener) {
        reduceQuery.addEventListener('change', function () {
            if (!prefersReduced() || !state.lenis) return;
            /* Ticker harus dilepas lebih dulu: tanpa itu ia terus memanggil
               raf() pada instance Lenis yang sudah dihancurkan. */
            if (state.ticker && window.gsap) window.gsap.ticker.remove(state.ticker);
            state.ticker = null;
            state.lenis.destroy();
            state.lenis = null;
            root.classList.remove('bk-lenis');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }

    window.BkMotion = {
        refresh: refresh,
        get lenis() { return state.lenis; }
    };
})();
