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

    window.bkRadial = { open: open, close: close, element: menu };
    syncAria();
    if (menu.open) open();
})();
