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
    // Warna bilah peramban mobile mengikuti tema; nilai terang disimpan dulu
    // karena tiap halaman memakai warna mereknya sendiri.
    const themeColorMetas = Array.from(document.querySelectorAll('meta[name="theme-color"]')).map((el) => ({
        el,
        light: el.getAttribute('content') || '',
    }));
    const DARK_THEME_COLOR = '#140f12';
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

    /* data-theme dan color-scheme harus selalu sepasang: kalau hanya
       data-theme yang berubah, kontrol bawaan peramban (scrollbar, date
       picker) tertinggal di tema sebelumnya. */
    const setThemeAttribute = (theme) => {
        const next = theme === 'dark' ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        root.style.colorScheme = next;
        themeColorMetas.forEach(({ el, light }) => {
            el.setAttribute('content', next === 'dark' ? DARK_THEME_COLOR : light);
        });
        return next;
    };
    const isFollowingSystem = () => readStored() === null;

    const listeners = new Set();

    function applyTheme(theme, { persist = true } = {}) {
        const next = setThemeAttribute(theme);
        if (persist) {
            try { window.localStorage.setItem(STORE_KEY, next); } catch { /* mode privat */ }
        }
        listeners.forEach((fn) => { try { fn(next); } catch { /* abaikan */ } });
        syncThemeControls();
    }

    function useSystemTheme() {
        try { window.localStorage.removeItem(STORE_KEY); } catch { /* abaikan */ }
        const theme = setThemeAttribute(systemDark() ? 'dark' : 'light');
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
    // Cincin luar ikut bergerak bersama kenop supaya tidak tertinggal di sisi lain.
    const knobRing = switchSvg ? switchSvg.querySelector('.bk-theme-svg__knob-ring') : null;
    const knobNodes = [knob, knobRing].filter(Boolean);

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

    // Arah sakelar: terang di kiri (matahari), gelap di kanan (bulan).
    // Nilainya dibaca dari markup supaya geometri SVG jadi satu sumber kebenaran.
    function knobOffset(dark) {
        const raw = knob ? (dark ? knob.dataset.knobDark : knob.dataset.knobLight) : null;
        const value = Number(raw);
        if (raw !== null && raw !== undefined && raw !== '' && Number.isFinite(value)) return value;
        return dark ? 0 : -152;
    }

    function syncKnob(dark, animate = false) {
        if (!knob) return;
        const x = knobOffset(dark);
        const fill = dark ? '#F26B6B' : '#2BDB7F';
        const duration = animate && !prefersReduced() ? 0.5 : 0;
        if (window.gsap) {
            knobNodes.forEach((node) => {
                const vars = { x, duration, ease: 'expo.out', overwrite: 'auto' };
                if (node === knob) vars.fill = fill;
                window.gsap.to(node, vars);
            });
        } else {
            const transform = x ? 'translate(' + x + ', 0)' : '';
            knobNodes.forEach((node) => node.setAttribute('transform', transform));
            knob.style.fill = fill;
        }
    }

    // Tangan robot: selalu datang dari KANAN. Grup dalam (#bk-theme-hand-art)
    // dicerminkan sekali (scale -1) supaya telapak berada di kanan kenop dan
    // lengan menjulur keluar tepi kanan panggung, untuk tema terang maupun gelap.
    const HAND_FINGER_TIP = 78;
    const HAND_PARK_RIGHT = 544;
    function handTarget(dark) {
        const base = knob ? Number(knob.getAttribute('cx')) : NaN;
        const center = (Number.isFinite(base) ? base : 328) + knobOffset(dark);
        // Saat dicerminkan ujung jari berada di -78 dari titik nol grup.
        return center + HAND_FINGER_TIP;
    }
    // Posisi awal/akhir untuk putaran animasi berikutnya.
    const handState = { from: HAND_PARK_RIGHT, to: handTarget(false) };

    function primeHand(dark) {
        const art = switchSvg ? switchSvg.querySelector('#bk-theme-hand-art') : null;
        if (art && art.getAttribute('transform') !== 'scale(-1 1)') {
            art.setAttribute('transform', 'scale(-1 1)');
        }
        handState.from = HAND_PARK_RIGHT;
        handState.to = handTarget(dark);
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
        // Nilai fungsi dibaca ulang setiap restart, jadi arah masuk tangan
        // selalu mengikuti sisi kenop yang baru dipilih.
        tl.set(handWrap, { x: () => handState.from, rotation: 0, opacity: 1 });
        tl.to(handWrap, { x: () => handState.to, duration: 0.75, ease: 'expo.out' });
        if (thumb) {
            tl.fromTo(thumb, { rotation: 45 }, {
                rotation: 0,
                duration: 0.9,
                ease: 'elastic(0.7, 0.3)',
                transformOrigin: '55% 95%',
            }, '-=0.6');
        }
        if (hand1 && hand2) {
            tl.to([hand1, hand2], { opacity: (i) => (i === 0 ? 0 : 1), duration: 0.12 }, '-=0.45');
        }
        tl.to(handWrap, { x: () => handState.from, duration: 0.5, ease: 'back.in(1.15)' }, '+=0.12');
        if (thumb) tl.to(thumb, { rotation: 23, duration: 0.3, ease: 'power2.in' }, '-=0.42');
        tl.to(handWrap, { opacity: 0, duration: 0.22 }, '-=0.15');
        return tl;
    }

    function playRobotSwitch() {
        const next = toggleTheme();
        const dark = next === 'dark';
        primeHand(dark);
        syncKnob(dark, true);
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
            setThemeAttribute(event.newValue);
            syncThemeControls();
        } else {
            setThemeAttribute(systemDark() ? 'dark' : 'light');
            syncThemeControls();
        }
    });

    try {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (isFollowingSystem()) {
                setThemeAttribute(systemDark() ? 'dark' : 'light');
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
       5. Suka (hati) — tanpa memuat ulang halaman
       ============================================================ */

    const likeScope = (button) => button.closest('[data-bk-feedback]') || document;

    const announceLike = (button, text) => {
        const status = likeScope(button).querySelector('[data-bk-like-status]');
        if (status) status.textContent = text;
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.bk-heart');
        if (!button) return;
        const form = button.closest('form');

        // Tanpa fetch/FormData, biarkan form terkirim seperti biasa.
        if (!form || typeof window.fetch !== 'function' || typeof window.FormData !== 'function') return;

        event.preventDefault();
        if (button.dataset.bkBusy === '1') return;

        const nowLiked = button.dataset.bkLiked === '1';
        const next = nowLiked ? '0' : '1';
        const body = new FormData(form);
        // Nilai untuk klik berikutnya dikirim eksplisit; input `liked` milik
        // form tidak diubah supaya perilaku tanpa JavaScript tetap sama.
        body.set('liked', next);

        button.dataset.bkBusy = '1';
        button.classList.add('is-pending');

        fetch(form.action, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then((response) => {
            if (!response.ok) throw new Error('like');
            return response.json();
        }).then((data) => {
            const liked = !!data.liked;
            button.dataset.bkLiked = liked ? '1' : '0';
            button.setAttribute('aria-pressed', liked ? 'true' : 'false');
            button.setAttribute('aria-label', liked ? (button.dataset.bkLabelOn || 'Batalkan suka') : (button.dataset.bkLabelOff || 'Suka'));
            button.classList.toggle('is-liked', liked);
            const sr = button.querySelector('.bk-sr-only');
            if (sr) sr.textContent = liked ? '♥ Disukai' : '♡ Suka';
            const total = likeScope(button).querySelector('[data-bk-like-total]');
            if (total && typeof data.count === 'number') total.textContent = '♡ ' + data.count + ' suka';
            announceLike(button, liked ? 'Ditambahkan ke daftar suka' : 'Suka dibatalkan');
            if (liked && !prefersReduced()) {
                button.classList.add('is-celebrating');
                window.setTimeout(() => button.classList.remove('is-celebrating'), 650);
            }
        }).catch(() => {
            // Gagal jaringan atau sesi kedaluwarsa: kirim lewat form agar
            // pengguna tetap mendapat umpan balik dari server.
            form.submit();
        }).finally(() => {
            button.dataset.bkBusy = '0';
            button.classList.remove('is-pending');
        });
    }, true);

    /* ============================================================
       6. Jumlah unit (− / +)
       ============================================================ */

    const qtyInput = (wrap) => wrap.querySelector('.bk-qty__input') || wrap.querySelector('input[type="number"]');
    const qtyBounds = (input) => ({
        min: Number.isFinite(Number(input.min)) && input.min !== '' ? Number(input.min) : 1,
        max: Number.isFinite(Number(input.max)) && input.max !== '' ? Number(input.max) : 20,
    });

    const syncQty = (wrap) => {
        const input = qtyInput(wrap);
        if (!input) return;
        const { min, max } = qtyBounds(input);
        const value = Number(input.value);
        const current = Number.isFinite(value) ? value : min;
        wrap.querySelectorAll('[data-bk-qty-step]').forEach((btn) => {
            const dir = Number(btn.dataset.bkQtyStep) || 1;
            const blocked = (dir < 0 && current <= min) || (dir > 0 && current >= max);
            btn.disabled = blocked;
            btn.setAttribute('aria-disabled', blocked ? 'true' : 'false');
        });
    };

    const clampQty = (wrap) => {
        const input = qtyInput(wrap);
        if (!input) return;
        const { min, max } = qtyBounds(input);
        let value = Math.round(Number(input.value));
        if (!Number.isFinite(value)) value = min;
        value = Math.min(max, Math.max(min, value));
        if (String(value) !== input.value) input.value = String(value);
        syncQty(wrap);
    };

    document.querySelectorAll('[data-bk-qty]').forEach(clampQty);

    document.addEventListener('input', (event) => {
        const wrap = event.target.closest && event.target.closest('[data-bk-qty]');
        if (wrap) syncQty(wrap);
    });

    document.addEventListener('change', (event) => {
        const wrap = event.target.closest && event.target.closest('[data-bk-qty]');
        if (wrap) clampQty(wrap);
    });

    document.addEventListener('click', (event) => {
        const step = event.target.closest && event.target.closest('[data-bk-qty-step]');
        if (!step) return;
        const wrap = step.closest('[data-bk-qty]');
        const input = wrap && qtyInput(wrap);
        if (!wrap || !input) return;
        event.preventDefault();
        const { min, max } = qtyBounds(input);
        const dir = Number(step.dataset.bkQtyStep) || 1;
        const value = Number(input.value);
        const current = Number.isFinite(value) ? value : min;
        input.value = String(Math.min(max, Math.max(min, current + dir)));
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        syncQty(wrap);
    }, true);
})();
