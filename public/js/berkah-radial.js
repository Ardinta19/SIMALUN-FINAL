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

    const menu = document.querySelector('[data-customer-radial]');
    if (!menu || window.bkRadial) return;

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

    const useServerArc = () => {
        items.forEach((item, index) => {
            item.style.setProperty('--rx', defaults[index][0]);
            item.style.setProperty('--ry', defaults[index][1]);
        });
        menu.classList.remove('is-arc-u');
        menu.style.removeProperty('--bk-orbit');
    };

    const applyArc = () => {
        if (!summary.getBoundingClientRect) {
            useServerArc();
            return;
        }
        const rect = summary.getBoundingClientRect();
        const width = window.innerWidth || document.documentElement.clientWidth || 0;
        // Tanpa tata letak (mis. jsdom) ukuran 0: pertahankan nilai server.
        if (!rect.width || !width) return;
        const center = rect.left + (rect.width / 2);
        const arc = layoutArc(items.length, center, width - center);
        if (!arc) {
            useServerArc();
            return;
        }
        items.forEach((item, index) => {
            item.style.setProperty('--rx', arc.points[index][0] + 'px');
            item.style.setProperty('--ry', arc.points[index][1] + 'px');
        });
        menu.classList.add('is-arc-u');
        // Cincin orbit dan gumpalan ikut membesar bersama busur; dipasang di
        // <details> supaya diwarisi seluruh isi panel.
        menu.style.setProperty('--bk-orbit', Math.round((arc.radius + 12) * 2) + 'px');
    };

    let timeline = null;
    let closing = false;

    /* Satu angka menggerakkan seluruh busur: 0 = menumpuk di tombol, 1 = di
       tempat tujuan. Ditulis sebagai custom property supaya GSAP tidak perlu
       menyentuh `transform` (yang juga dipakai hover dan :active). */
    const setProgress = value => {
        items.forEach(item => item.style.setProperty('--p', value));
    };

    const freezeScroll = frozen => {
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

    const open = () => {
        syncAria();
        if (!menu.open) return;
        // Posisi busur ditentukan saat membuka: pil ringkas berada di tengah,
        // header penuh menaruh tombol di ujung kanan. Keduanya butuh bentuk
        // yang berbeda, dan lebarnya bisa berubah setelah rotasi.
        applyArc();
        freezeScroll(true);

        const gsap = gsapCore();
        if (!gsap || reduceMotion.matches) {
            setProgress(1);
            return;
        }

        menu.classList.add('is-gsap');
        setProgress(0);
        if (timeline) timeline.kill();
        timeline = gsap.timeline();
        timeline.to(items, {
            '--p': 1,
            duration: 0.52,
            ease: 'expo.out',
            stagger: 0.055
        });
        if (badges.length) {
            /* Lencana menyusul dengan pantulan kecil, seperti pil angka pada
               menu radial Chrome. */
            timeline.fromTo(badges, { scale: 0.3, opacity: 0 }, {
                scale: 1,
                opacity: 1,
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
        close();
    });

    menu.addEventListener('toggle', () => {
        if (menu.open) {
            open();
            return;
        }
        freezeScroll(false);
        if (!menu.classList.contains('is-gsap')) setProgress(0);
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

    window.bkRadial = { open: open, close: close, element: menu, layoutArc: layoutArc };
    syncAria();
    if (menu.open) open();
})();
