/*!
 * Berkah Kreatif — UI kit mikro-interaksi
 * ------------------------------------------------------------------
 * Dimuat oleh resources/views/layouts/component/_ui_kit.blade.php.
 *
 * Tanggung jawab:
 *   1. Tema terang/gelap: tombol, dialog, sakelar robot (GSAP), localStorage.
 *   2. Tombol tambah ke keranjang: status "menambahkan" tanpa mengubah nama
 *      field maupun nilai yang dikirim.
 *   3. Tombol suka: umpan balik hati + konfeti tanpa mengubah input `liked`.
 *
 * Tidak ada yang berjalan bila elemennya tidak ada; aman dimuat di semua
 * halaman. Semua animasi menghormati prefers-reduced-motion.
 */
(() => {
    'use strict';

    const STORE_KEY = 'bk-theme';
    const root = document.documentElement;
    const prefersReduced = () => {
        try { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch { return false; }
    };

    /* ============================================================
       1. Tema
       ============================================================ */

    const systemDark = () => {
        try { return window.matchMedia('(prefers-color-scheme: dark)').matches; } catch { return false; }
    };

    const readStored = () => {
        try {
            const value = window.localStorage.getItem(STORE_KEY);
            return value === 'dark' || value === 'light' ? value : null;
        } catch { return null; }
    };

    const currentTheme = () => (root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
    const isFollowingSystem = () => readStored() === null;

    const listeners = new Set();

    function applyTheme(theme, { persist = true } = {}) {
        const next = theme === 'dark' ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        root.style.colorScheme = next;
        if (persist) {
            try { window.localStorage.setItem(STORE_KEY, next); } catch { /* mode privat */ }
        }
        listeners.forEach((fn) => { try { fn(next); } catch { /* abaikan */ } });
        syncThemeControls();
    }

    function useSystemTheme() {
        try { window.localStorage.removeItem(STORE_KEY); } catch { /* abaikan */ }
        const theme = systemDark() ? 'dark' : 'light';
        root.setAttribute('data-theme', theme);
        listeners.forEach((fn) => { try { fn(theme); } catch { /* abaikan */ } });
        syncThemeControls();
    }

    const toggleTheme = () => {
        applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        return currentTheme();
    };

    window.BkTheme = { get: currentTheme, set: applyTheme, toggle: toggleTheme, system: useSystemTheme };

    /* ============================================================
       2. Tombol tema + dialog
       ============================================================ */

    const toggleButton = document.querySelector('[data-bk-theme-toggle]');
    const dialog = document.getElementById('bk-theme-dialog');
    const switchSvg = document.getElementById('bk-theme-switch');
    const knob = switchSvg ? switchSvg.querySelector('.bk-theme-svg__knob') : null;

    function syncThemeControls() {
        const dark = currentTheme() === 'dark';

        if (toggleButton) {
            toggleButton.setAttribute('aria-pressed', dark ? 'true' : 'false');
            toggleButton.setAttribute('aria-label', dark ? 'Tema gelap aktif. Buka pengaturan tema' : 'Tema terang aktif. Buka pengaturan tema');
        }
        document.querySelectorAll('[data-bk-theme-set]').forEach((button) => {
            button.setAttribute('aria-pressed', button.dataset.bkThemeSet === currentTheme() ? 'true' : 'false');
        });
        const auto = document.querySelector('[data-bk-theme-auto]');
        if (auto) auto.setAttribute('aria-pressed', isFollowingSystem() ? 'true' : 'false');

        if (switchSvg) {
            switchSvg.setAttribute('aria-checked', dark ? 'true' : 'false');
            switchSvg.setAttribute('aria-label', dark ? 'Tema gelap. Aktifkan tema terang' : 'Tema terang. Aktifkan tema gelap');
        }
        syncKnob(dark);
    }

    function syncKnob(dark, animate = false) {
        if (!knob) return;
        const BkKnobOffset = -152; const x = dark ? BkKnobOffset : 0;
        const fill = dark ? '#F26B6B' : '#2BDB7F';
        if (window.gsap) {
            window.gsap.to(knob, { x, fill, duration: animate && !prefersReduced() ? 0.5 : 0, ease: 'expo.out', overwrite: 'auto' });
        } else {
            knob.setAttribute('transform', dark ? 'translate(-152, 0)' : '');
            knob.style.fill = fill;
        }
    }

    function placeToggle() {
        if (!toggleButton) return;
        const host = document.querySelector('[data-bk-theme-host]');
        // offsetParent null berarti host disembunyikan lewat CSS (mis. header ponsel).
        if (host && host.offsetParent !== null) {
            host.appendChild(toggleButton);
            toggleButton.classList.remove('bk-theme-dock');
        } else {
            if (document.body && toggleButton.parentElement !== document.body) document.body.appendChild(toggleButton);
            toggleButton.classList.add('bk-theme-dock');
        }
        toggleButton.hidden = false;
    }

    let lastFocus = null;

    function openDialog() {
        if (!dialog) return;
        lastFocus = document.activeElement;
        if (typeof dialog.showModal === 'function') {
            try { dialog.showModal(); } catch { dialog.setAttribute('open', ''); }
        } else {
            dialog.setAttribute('open', '');
        }
        document.body.classList.add('bk-theme-open');
        syncThemeControls();
        const target = dialog.querySelector('[data-bk-theme-set], .bk-theme-dialog__close');
        if (target) {
            try { target.focus({ preventScroll: true }); } catch { target.focus(); }
        }
    }

    function closeDialog() {
        if (!dialog) return;
        if (typeof dialog.close === 'function' && dialog.open) {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }
        document.body.classList.remove('bk-theme-open');
    }

    if (toggleButton) {
        toggleButton.addEventListener('click', openDialog);
    }

    if (dialog) {
        dialog.querySelectorAll('[data-bk-close-theme]').forEach((button) => {
            button.addEventListener('click', closeDialog);
        });
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) closeDialog();
        });
        dialog.addEventListener('close', () => {
            document.body.classList.remove('bk-theme-open');
            if (lastFocus && typeof lastFocus.focus === 'function') {
                try { lastFocus.focus({ preventScroll: true }); } catch { lastFocus.focus(); }
            }
        });
        dialog.querySelectorAll('[data-bk-theme-set]').forEach((button) => {
            button.addEventListener('click', () => applyTheme(button.dataset.bkThemeSet));
        });
        const auto = dialog.querySelector('[data-bk-theme-auto]');
        if (auto) auto.addEventListener('click', useSystemTheme);
    }

    /* ============================================================
       3. Sakelar robot (GSAP)
       ============================================================ */

    let robotTimeline = null;

    function buildRobotTimeline() {
        if (!window.gsap || !switchSvg) return null;
        const handWrap = switchSvg.querySelector('#bk-theme-hand-wrap');
        const thumb = switchSvg.querySelector('#bk-theme-thumb');
        const hand2 = switchSvg.querySelector('#bk-theme-hand-2');
        const hand1 = switchSvg.querySelector('#bk-theme-hand-1');
        if (!handWrap || !knob) return null;

        const tl = window.gsap.timeline({ paused: true, defaults: { ease: 'expo.inOut' } });
        tl.set(handWrap, { x: 0, rotation: 0, opacity: 1 });
        tl.to(handWrap, { x: 40, duration: 0.4 });
        tl.to(handWrap, { x: 196, duration: 0.55 }, '-=0.05');
        if (thumb) {
            tl.fromTo(thumb, { rotation: 45 }, {
                rotation: 0,
                duration: 1,
                ease: 'elastic(0.7, 0.3)',
                transformOrigin: '55% 95%',
            }, '-=0.35');
        }
        if (hand1 && hand2) {
            tl.to([hand1, hand2], { opacity: (i) => (i === 0 ? 0 : 1), duration: 0.12 }, '-=0.5');
        }
        tl.to(handWrap, { x: 0, duration: 0.45, ease: 'back.in(1.2)' }, '+=0.1');
        if (thumb) tl.to(thumb, { rotation: 23, duration: 0.3, ease: 'power2.in' }, '-=0.4');
        tl.to(handWrap, { opacity: 0, duration: 0.2 }, '-=0.15');
        return tl;
    }

    function playRobotSwitch() {
        const next = toggleTheme();
        syncKnob(next === 'dark', true);
        if (prefersReduced()) return;
        if (!robotTimeline) robotTimeline = buildRobotTimeline();
        if (robotTimeline) {
            robotTimeline.restart();
        } else if (knob) {
            // Fallback tanpa GSAP: tetap ada perpindahan halus lewat transisi CSS.
            knob.style.transition = 'transform .4s ease';
        }
    }

    if (switchSvg) {
        switchSvg.setAttribute('tabindex', '0');
        switchSvg.setAttribute('role', 'switch');
        switchSvg.addEventListener('click', playRobotSwitch);
        switchSvg.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ' && event.key !== 'Spacebar') return;
            event.preventDefault();
            playRobotSwitch();
        });
    }

    /* Sinkron antar-tab. */
    window.addEventListener('storage', (event) => {
        if (event.key !== STORE_KEY) return;
        if (event.newValue === 'dark' || event.newValue === 'light') {
            root.setAttribute('data-theme', event.newValue);
            syncThemeControls();
        } else {
            root.setAttribute('data-theme', systemDark() ? 'dark' : 'light');
            syncThemeControls();
        }
    });

    try {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (isFollowingSystem()) {
                root.setAttribute('data-theme', systemDark() ? 'dark' : 'light');
                syncThemeControls();
            }
        });
    } catch { /* Safari lama */ }

    syncThemeControls();
    placeToggle();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', placeToggle, { once: true });
    }

    // Header katalog menyembunyikan aksi desktop di layar sempit. Tanpa ini,
    // tombol yang sudah dipindah ke slot header bisa ikut tersembunyi saat
    // jendela diperkecil (atau tertinggal melayang saat diperbesar).
    let placeTimer = 0;
    const queuePlace = () => {
        window.clearTimeout(placeTimer);
        placeTimer = window.setTimeout(placeToggle, 140);
    };
    window.addEventListener('resize', queuePlace);
    window.addEventListener('orientationchange', queuePlace);

    /* ============================================================
       4. Tambah ke keranjang
       ============================================================ */

    const cartLabel = (button) => button.querySelector('.bk-atc__label');

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const button = form.querySelector('.bk-atc');
        if (!button) return;

        // JANGAN menonaktifkan tombol: tombol ini bisa membawa name/value
        // (mis. name="next" value="cart") yang harus tetap terkirim.
        button.classList.add('is-loading');
        button.setAttribute('aria-busy', 'true');
        const label = cartLabel(button);
        if (label) {
            button.dataset.bkLabel = label.textContent;
            label.textContent = 'Menambahkan…';
        }
    }, true);

    /* ============================================================
       5. Suka (hati)
       ============================================================ */

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.bk-heart');
        if (!button) return;
        const pressed = button.getAttribute('aria-pressed') === 'true' ? 'false' : 'true';
        button.setAttribute('aria-pressed', pressed);
        if (!prefersReduced()) {
            button.classList.add('is-celebrating');
            window.setTimeout(() => button.classList.remove('is-celebrating'), 650);
        }
        // Form tetap dikirim apa adanya; input `liked` tidak diubah.
    }, true);
})();
