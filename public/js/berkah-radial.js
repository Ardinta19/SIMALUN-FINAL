/* Menu radial pelanggan — sebaran tujuan, keluar-masuk, dan kunci gulir.
 *
 * Kenapa berkas sendiri: menu ini dipakai di beberapa tata letak (katalog,
 * dasbor, pesanan) dan tetap harus bekerja di halaman yang tidak memuat
 * berkah-experience.js. Tanpa JavaScript pun menu tetap terbuka karena
 * memakai <details>; berkas ini hanya menambah gerak dan perilaku.
 *
 * Aset: GSAP dan Lenis disajikan lokal dari public/vendor (tanpa CDN).
 * Yang dianimasikan hanya variabel CSS --p pada tiap tujuan; transform,
 * hover, dan :active tetap dipegang CSS, jadi tidak ada transform inline
 * yang menempel dan mengunci keadaan aktif.
 */
(() => {
    'use strict';

    if (window.bkRadial) return;

    const RADIAL_SELECTOR = '[data-customer-radial]';
    const menus = Array.prototype.slice.call(document.querySelectorAll(RADIAL_SELECTOR));
    if (!menus.length) return;

    /* Satu halaman bisa memuat lebih dari satu busur (busur header dan menu
       atas pada halaman yang memakai header sendiri). Dulu skrip hanya
       mengambil busur pertama; sekarang setiap busur mendapat keadaan,
       pendengar, dan geometrinya sendiri, dan membuka satu menutup yang lain. */
    const registry = [];

    const setup = menu => {
        const summary = menu.querySelector('summary');
        const panel = menu.querySelector('.customer-radial__panel');
        // `.customer-radial__item` mencakup tautan tujuan dan slot tombol tema,
        // jadi keduanya ikut menyebar dan menyusut bersama.
        const items = Array.prototype.slice.call(menu.querySelectorAll('.customer-radial__item'));
        if (!summary || !items.length) return;
        const badges = Array.prototype.slice.call(menu.querySelectorAll('.customer-radial__badge'));

        const narrow = window.matchMedia('(max-width: 700px)');
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const gsapCore = () => (window.gsap && typeof window.gsap.timeline === 'function') ? window.gsap : null;
        const lenisCore = () => (window.BkMotion && window.BkMotion.lenis) || null;

        // Posisi bawaan datang dari server (busur kiri-bawah). Disimpan supaya
        // bisa dipulihkan bila layar tidak cukup lebar untuk busur bentuk U.
        const defaults = items.map(item => [
            item.style.getPropertyValue('--rx') || '0px',
            item.style.getPropertyValue('--ry') || '0px'
        ]);

        /* Slot yang benar-benar berisi. Slot tema kosong (tombolnya diparkir
           di header, mis. saat layar lebar atau halaman tanpa kit) disembunyikan
           CSS lewat `:empty`; kalau tetap dihitung, busur punya satu lubang dan
           jarak antarpusat jadi tidak rata. */
        const filledItems = () => items.filter(item =>
            !item.classList.contains('customer-radial__item--theme') || item.childElementCount > 0
        );

        const ARC_GAP = 50;          // jarak antarpusat lingkaran 46 px
        const ARC_RADIUS_MAX = 150;
        const ARC_RADIUS_MIN = 88;
        const ARC_EDGE = 23;         // setengah lingkaran, untuk batas layar

        /* Busur bentuk U: titik-titik dibagi simetris di bawah tombol (90° di
           tengah), dipakai saat tombol punya ruang di KIRI dan KANAN — keadaan
           pil ringkas atau tombol yang berada di tengah. Radius dipilih dari
           yang terbesar ke terkecil sampai seluruh busur masuk layar; null
           berarti tidak muat, dan pemanggil memakai busur kiri-bawah server. */
        const layoutArc = (count, left, right) => {
            if (count < 2) return null;
            const avail = Math.min(left, right) - 10;
            for (let radius = ARC_RADIUS_MAX; radius >= ARC_RADIUS_MIN; radius -= 2) {
                const step = 2 * Math.asin(Math.min((ARC_GAP / 2) / radius, 1));
                const span = (count - 1) * step;
                if (radius * Math.sin(span / 2) + ARC_EDGE > avail) continue;
                const points = [];
                for (let index = 0; index < count; index++) {
                    const angle = (Math.PI / 2) + (span / 2) - (index * step);
                    points.push([
                        Math.round(radius * Math.cos(angle)),
                        Math.round(radius * Math.sin(angle))
                    ]);
                }
                return { radius: radius, points: points };
            }
            return null;
        };

        /* Busur satu sisi ke arah yang punya ruang. Dipakai saat tombol
           menempel di KIRI layar: busur bawaan server selalu membuka ke kiri,
           jadi di sana ia akan keluar layar. Bentuknya cermin dari busur
           server — radius sama, sudut 0° → 101°. */
        const ARC_SPAN = 101;
        const layoutSide = (count, room) => {
            if (count < 2) return null;
            const avail = room - 10;
            const ideal = ARC_SPAN / (count - 1);
            let radius = Math.max(ARC_RADIUS_MIN, Math.round(25 / Math.sin((ideal / 2) * Math.PI / 180)));
            while (radius >= ARC_RADIUS_MIN && radius + ARC_EDGE > avail) radius -= 2;
            if (radius < ARC_RADIUS_MIN) return null;
            const step = 2 * Math.asin(Math.min((ARC_GAP / 2) / radius, 1));
            const points = [];
            for (let index = 0; index < count; index++) {
                const angle = index * step; // 0° = ke kanan
                points.push([
                    Math.round(radius * Math.cos(angle)),
                    Math.round(radius * Math.sin(angle))
                ]);
            }
            return { radius: radius, points: points };
        };

        /* Kotak pandang tempat busur harus muat, dihitung dari titik tumpu
           tombol. Tiap tujuan berjari 23 px ditambah 10 px napas dari tepi. */
        const arcViewport = () => {
            const width = window.innerWidth || document.documentElement.clientWidth || 0;
            const height = window.innerHeight || document.documentElement.clientHeight || 0;
            const rect = summary.getBoundingClientRect();
            const centerX = rect.left + (rect.width / 2);
            const centerY = rect.top + (rect.height / 2);
            return {
                width: width,
                height: height,
                centerX: centerX,
                centerY: centerY,
                maxLeft: Math.max(0, centerX - ARC_EDGE - 10),
                maxRight: Math.max(0, width - centerX - ARC_EDGE - 10),
                maxUp: Math.max(0, centerY - ARC_EDGE - 10),
                maxDown: Math.max(0, height - centerY - ARC_EDGE - 10)
            };
        };

        /* Setiap titik dijepit ke area aman pandangan. Tanpa ini, tombol yang
           menempel di tepi kanan membuat tujuan terakhir keluar layar; elemen
           absolut itu ikut melebarkan dokumen sehingga seluruh halaman bisa
           digeser mendatar (pil header bergeser, tepi kartu terpotong). */
        const clampPoints = (points, ctx) => points.map(point => [
            Math.max(-ctx.maxLeft, Math.min(ctx.maxRight, point[0])),
            Math.max(-ctx.maxUp, Math.min(ctx.maxDown, point[1]))
        ]);

        const useServerArc = () => {
            items.forEach((item, index) => {
                item.style.setProperty('--rx', defaults[index][0]);
                item.style.setProperty('--ry', defaults[index][1]);
            });
            menu.classList.remove('is-arc-u');
            menu.classList.remove('is-arc-opposite');
        };

        /* Menulis satu set titik ke semua tujuan sekaligus, setelah dijepit ke
           area aman. `mode` hanya mengatur penanda kelas untuk CSS. */
        const commitArc = (ctx, slots, rawPoints, mode) => {
            const safe = clampPoints(rawPoints, ctx);
            slots.forEach((item, index) => {
                item.style.setProperty('--rx', Math.round(safe[index][0]) + 'px');
                item.style.setProperty('--ry', Math.round(safe[index][1]) + 'px');
            });
            menu.classList.toggle('is-arc-u', mode === 'u');
            menu.classList.toggle('is-arc-opposite', mode === 'side');
        };

        const applyArc = () => {
            if (!summary.getBoundingClientRect) {
                useServerArc();
                return;
            }
            const ctx = arcViewport();
            // Tanpa tata letak (mis. jsdom) ukuran 0: pertahankan nilai server.
            if (!ctx.width || !ctx.height) return;
            const slots = filledItems();
            if (!slots.length) {
                useServerArc();
                return;
            }
            const roomLeft = ctx.centerX;
            const roomRight = ctx.width - ctx.centerX;

            const arc = layoutArc(slots.length, roomLeft, roomRight);
            if (arc) {
                commitArc(ctx, slots, arc.points, 'u');
                return;
            }
            // Ruang kiri jelas lebih sempit: cermin busur server ke kanan.
            if (roomRight > roomLeft + 24) {
                const mirror = layoutSide(slots.length, roomRight);
                if (mirror) {
                    commitArc(ctx, slots, mirror.points, 'side');
                    return;
                }
            }
            // Busur bawaan server (kiri-bawah) — tetap dijepit bila tombolnya
            // menempel di tepi kanan.
            commitArc(ctx, slots, defaults.map(point => [
                parseFloat(point[0]) || 0,
                parseFloat(point[1]) || 0
            ]), 'server');
        };

        /* Header bisa berganti bentuk tepat setelah busur dibuka (pil ringkas
           ⇄ penuh/pinned), dan titik tumpu tombolnya ikut bergeser selama
           morph 0,34 dtk. Bentuk busur dihitung ulang setiap kali posisi
           tombol berubah, selama ±0,55 dtk — bukan sekali saja saat membuka,
           yang dulu meninggalkan busur dihitung dari posisi lama sehingga
           tujuan terakhir terpotong tepi layar. */
        const nowMs = () => (window.performance && typeof window.performance.now === 'function')
            ? window.performance.now()
            : Date.now();
        let followFrame = 0;
        let followUntil = 0;
        let followKey = '';
        const followPivot = () => {
            followFrame = 0;
            if (!menu.open) return;
            const rect = summary.getBoundingClientRect();
            const key = Math.round(rect.left) + ':' + Math.round(rect.top) + ':' + Math.round(rect.width) + ':' + Math.round(rect.height);
            if (key !== followKey) {
                followKey = key;
                applyArc();
            }
            if (nowMs() < followUntil) followFrame = window.requestAnimationFrame(followPivot);
        };
        const followPivotFor = milliseconds => {
            followUntil = nowMs() + milliseconds;
            followKey = '';
            if (!followFrame && window.requestAnimationFrame) {
                followFrame = window.requestAnimationFrame(followPivot);
            }
        };
        const stopFollowing = () => {
            if (followFrame && window.cancelAnimationFrame) window.cancelAnimationFrame(followFrame);
            followFrame = 0;
            followUntil = 0;
        };

        let timeline = null;
        let closing = false;

        /* Satu angka menggerakkan seluruh busur: 0 = menumpuk di tombol, 1 = di
           tempat tujuan. Ditulis sebagai custom property supaya GSAP tidak perlu
           menyentuh `transform` (yang juga dipakai hover dan :active). */
        const setProgress = value => {
            items.forEach(item => item.style.setProperty('--p', value));
        };

        /* Penanda kelas dipakai bersama dengan berkah-motion.js: pengamat
           kelas di sana menyalakan kembali Lenis setiap kali kelas <html>
           berubah, dan tanpa penanda ini busur yang sedang terbuka bisa
           kehilangan kunci gulirnya. */
        const freezeScroll = frozen => {
            document.documentElement.classList.toggle('bk-scroll-frozen', !!frozen);
            const lenis = lenisCore();
            if (!lenis || typeof lenis.stop !== 'function') return;
            try {
                frozen ? lenis.stop() : lenis.start();
            } catch (error) {
                /* Lenis versi lain: biarkan gulir asli berjalan. */
            }
        };

        const syncAria = () => {
            summary.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
        };

        /* Kalau fokus keyboard sedang berada di salah satu tujuan saat menu
           ditutup (gulir, klik di luar, layar melebar), fokus tidak boleh jatuh
           ke <body>: kembalikan ke tombol pembuka. */
        const restoreFocus = () => {
            if (panel && panel.contains(document.activeElement)) {
                try {
                    summary.focus({ preventScroll: true });
                } catch (error) {
                    summary.focus();
                }
            }
        };

        /* Busur terbuka penuh, tanpa sisa animasi yang terpotong: dipanggil di
           akhir tween masuk maupun saat penutupan dibatalkan. */
        const settle = () => {
            if (!menu.open) return;
            setProgress(1);
            // Lencana juga harus penuh: bila animasi dilewati (reduced-motion,
            // GSAP tidak termuat, atau tween dipotong), `--bp` bisa tertinggal
            // di 0 dan lencananya tak pernah terlihat.
            badges.forEach(badge => badge.style.setProperty('--bp', '1'));
        };

        const open = () => {
            syncAria();
            if (!menu.open) return;
            closing = false;
            // Halaman dengan dua busur (header + menu atas) tidak boleh
            // menampilkan keduanya sekaligus.
            registry.forEach(other => {
                if (other.element !== menu && other.element.open) other.close();
            });
            // Posisi busur ditentukan saat membuka: pil ringkas berada di tengah,
            // header penuh menaruh tombol di ujung kanan. Keduanya butuh bentuk
            // yang berbeda, dan lebarnya bisa berubah setelah rotasi.
            applyArc();
            // Pivot masih bisa bergeser selama header bermorph.
            followPivotFor(550);
            freezeScroll(true);

            const gsap = gsapCore();
            if (!gsap || reduceMotion.matches) {
                settle();
                return;
            }

            menu.classList.add('is-gsap');
            setProgress(0);
            if (timeline) timeline.kill();
            timeline = gsap.timeline({ onComplete: settle });
            timeline.to(items, {
                '--p': 1,
                duration: 0.42,
                ease: 'expo.out',
                // Jarak antar-tujuan dirapatkan: dulu 55 ms, sehingga dua tujuan
                // terakhir masih di tengah tombol saat mata sudah menyapu busur —
                // terbaca sebagai “menunya belum lengkap”.
                stagger: 0.028
            });
            if (badges.length) {
                /* Lencana menyusul dengan pantulan kecil, seperti pil angka pada
                   menu radial Chrome. Yang dianimasikan --bp, bukan `transform`:
                   GSAP menulis transform inline secara permanen, dan itu akan
                   mengunci skala lencana setelah busur pertama dibuka. */
                badges.forEach(badge => badge.style.setProperty('--bp', '0'));
                timeline.to(badges, {
                    '--bp': 1,
                    duration: 0.4,
                    ease: 'back.out(2.4)',
                    stagger: 0.05
                }, '-=0.34');
            }
        };

        /* Mengembalikan true bila penutupan ditangani di sini; pemanggil lain
           (berkah-experience.js) tidak boleh langsung menghapus [open] karena
           animasi keluar butuh panel masih terlihat. */
        const close = () => {
            if (!menu.open) return false;
            freezeScroll(false);

            const gsap = gsapCore();
            if (!gsap || reduceMotion.matches) {
                if (timeline) timeline.kill();
                closing = false;
                setProgress(0);
                restoreFocus();
                menu.open = false;
                syncAria();
                return true;
            }
            if (closing) return true;

            closing = true;
            if (timeline) timeline.kill();
            timeline = gsap.timeline({
                onComplete: () => {
                    closing = false;
                    restoreFocus();
                    menu.open = false;
                    syncAria();
                }
            });
            timeline.to(items, {
                '--p': 0,
                duration: 0.24,
                ease: 'power2.in',
                stagger: { each: 0.03, from: 'end' }
            });
            return true;
        };

        /* Membuka: biarkan <details> bekerja sendiri, lalu `toggle` yang
           menjalankan animasi masuk. Menutup lewat tombol: tahan dulu supaya
           tujuan sempat menyusut ke pusat. */
        summary.addEventListener('click', event => {
            if (!menu.open) return;
            if (!gsapCore() || reduceMotion.matches) return;
            event.preventDefault();
            // Klik kedua saat busur sedang menyusut dulu ditelan: menu menutup
            // pelan-pelan dan pengguna harus mengklik sekali lagi. Sekarang klik
            // itu membatalkan penutupan dan mengembalikan seluruh tujuan.
            if (closing) {
                closing = false;
                if (timeline) timeline.kill();
                timeline = gsapCore().timeline({ onComplete: settle });
                timeline.to(items, {
                    '--p': 1,
                    duration: 0.3,
                    ease: 'power2.out',
                    stagger: { each: 0.02, from: 'end' }
                });
                return;
            }
            close();
        });

        menu.addEventListener('toggle', () => {
            if (menu.open) {
                open();
                return;
            }
            stopFollowing();
            freezeScroll(false);
            /* Ditutup dari luar skrip ini (berkah-experience.js, header
               diganti, atau [open] dilepas program lain): hentikan tween yang
               masih berjalan dan kembalikan seluruh tujuan ke titik pusat.
               Dulu, saat `is-gsap` aktif, sisa animasi menempel dan busur
               berikutnya terbuka dari keadaan separuh. */
            if (timeline) timeline.kill();
            closing = false;
            setProgress(0);
            syncAria();
            restoreFocus();
        });

        /* Tombol tema tinggal di dalam busur (lihat berkah-ui-kit.js). Saat ia
           ditekan, tutup busur lebih dulu supaya dialog tema tidak bertumpuk
           dengan panel yang masih terbuka. */
        document.addEventListener('click', event => {
            if (!menu.open) return;
            if (!event.target.closest || !event.target.closest('.customer-radial__item--theme')) return;
            close();
        }, true);

        document.addEventListener('pointerdown', event => {
            if (!menu.open) return;
            if (menu.contains(event.target)) return;
            if (event.target.closest && event.target.closest('dialog[open]')) return;
            close();
        }, true);

        /* Navigasi panah: busur adalah satu kelompok tujuan, jadi arah panah
           harus berpindah antar-tujuan (Tab tetap bekerja seperti biasa).
           Urutan DOM sama dengan urutan visual busur, jadi panah kanan/bawah
           maju dan panah kiri/atas mundur, memutar di ujung. */
        const focusableOf = item => (item.matches('a, button')
            ? item
            : item.querySelector('a, button, [tabindex]:not([tabindex="-1"])'));

        menu.addEventListener('keydown', event => {
            if (!menu.open) return;
            const keys = ['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp', 'Home', 'End'];
            if (keys.indexOf(event.key) < 0) return;
            const slots = filledItems().map(focusableOf).filter(Boolean);
            if (!slots.length) return;
            const holder = document.activeElement && document.activeElement.closest
                ? document.activeElement.closest('.customer-radial__item')
                : null;
            const current = slots.findIndex(node => holder && holder.contains(node));
            let next = 0;
            if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = slots.length - 1;
            else if (current < 0) next = 0;
            else if (event.key === 'ArrowRight' || event.key === 'ArrowDown') next = (current + 1) % slots.length;
            else next = (current - 1 + slots.length) % slots.length;
            event.preventDefault();
            try {
                slots[next].focus({ preventScroll: true });
            } catch (error) {
                slots[next].focus();
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || !menu.open) return;
            close();
            try {
                summary.focus({ preventScroll: true });
            } catch (error) {
                summary.focus();
            }
        });

        /* Menu hanya ada di ponsel; saat layar melebar, tutup tanpa animasi agar
           gulir tidak tertinggal terkunci. */
        const onViewportChange = () => {
            if (narrow.matches) return;
            freezeScroll(false);
            if (timeline) timeline.kill();
            closing = false;
            if (menu.open) {
                menu.open = false;
                syncAria();
            }
        };
        /* Rotasi/lebar berubah saat menu terbuka: hitung ulang bentuk busurnya. */
        let arcTimer = 0;
        const queueArc = () => {
            if (!menu.open) return;
            window.clearTimeout(arcTimer);
            arcTimer = window.setTimeout(applyArc, 120);
        };
        window.addEventListener('resize', queueArc);
        window.addEventListener('orientationchange', queueArc);

        /* Kembali lewat tombol Back (bfcache): keadaan DOM dan Lenis ikut
           dipulihkan, termasuk <details open> dan gulir yang masih berhenti.
           Tanpa reset, halaman kembali dengan busur terbuka dan tidak bisa
           digulir. Login/checkout sudah memakai pola `pageshow` yang sama
           untuk tombol kirim. */
        window.addEventListener('pageshow', event => {
            if (!event.persisted) return;
            if (timeline) timeline.kill();
            closing = false;
            if (menu.open) menu.open = false;
            setProgress(0);
            syncAria();
            freezeScroll(false);
        });

        /* Halaman tanpa Lenis tetap harus menutup menu saat digulir: busur
           menggantung di atas konten, jadi membiarkannya terbuka saat halaman
           bergerak membuat sasarannya berpindah dari bawah jari. */
        window.addEventListener('scroll', () => {
            if (!menu.open || lenisCore()) return;
            close();
        }, { passive: true });

        if (narrow.addEventListener) {
            narrow.addEventListener('change', onViewportChange);
        } else if (narrow.addListener) {
            narrow.addListener(onViewportChange);
        }

        const api = { open: open, close: close, element: menu, layoutArc: layoutArc };
        registry.push(api);
        syncAria();
        if (menu.open) open();
        return api;
    };

    const instances = menus.map(setup).filter(Boolean);
    if (!instances.length) return;
    window.bkRadials = instances;
    window.bkRadial = instances[0];
})();
