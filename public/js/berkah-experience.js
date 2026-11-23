(() => {
    const revealHash = () => {
        let id; try { id = decodeURIComponent(location.hash.slice(1)); } catch { return; }
        if (!id) return;
        const target = document.getElementById(id);
        if (!target) return;
        if (target.tagName === 'DETAILS') target.open = true;
        let parent = target.parentElement;
        while (parent) { if (parent.tagName === 'DETAILS') parent.open = true; parent = parent.parentElement; }
    };
    window.addEventListener('hashchange', revealHash);
    document.addEventListener('click', event => { const link = event.target.closest('a[href^="#"]'); if (link && link.hash === location.hash) revealHash(); });
    revealHash();
    const header = document.querySelector('[data-bk-header]');

    /* Gulir programatik harus lewat Lenis bila mesin gerak aktif: memanggil
       window.scrollTo() saat Lenis berjalan membuat dua pengendali berebut
       posisi (halaman bisa melompat balik). */
    const lenisNow = () => (window.BkMotion && window.BkMotion.lenis) || null;
    const scrollPageTo = (top, animate, done) => {
        const lenis = lenisNow();
        let settled = false;
        const finish = () => { if (settled) return; settled = true; if (done) done(); };

        if (lenis && typeof lenis.scrollTo === 'function') {
            try {
                lenis.scrollTo(top, {
                    duration: animate ? 0.7 : 0,
                    immediate: !animate,
                    force: true,
                    onComplete: finish
                });
                window.setTimeout(finish, animate ? 900 : 120);
                return;
            } catch (error) { /* jatuh ke gulir asli */ }
        }

        try {
            window.scrollTo({ top: top, left: 0, behavior: animate ? 'smooth' : 'auto' });
        } catch (error) {
            window.scrollTo(0, top);
        }
        window.setTimeout(finish, animate ? 700 : 60);
    };

    /* Saat logo ringkas dipakai kembali ke atas, header ditahan dalam bentuk
       ringkas sampai gulir selesai. Tanpa penahan ini header berubah lagi di
       tengah jalan, tombol berpindah dari bawah kursor, dan klik berikutnya
       mendarat pada isi halaman di belakangnya. */
    let headerHold = 0;
    const clock = () => (window.performance && typeof window.performance.now === 'function' ? window.performance.now() : Date.now());
    // Logo ringkas berfungsi sebagai "kembali ke atas" dan tetap hidup walau
    // dukungan <dialog> tidak tersedia, jadi dipasang sebelum pengecekan itu.
    const compactLogo = header && header.querySelector('[data-bk-expand]');
    if (compactLogo) {
        compactLogo.addEventListener('click', event => {
            event.preventDefault();
            let reduce = false;
            try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch { reduce = false; }

            headerHold = clock() + 1200;
            scrollPageTo(0, !reduce, () => { headerHold = 0; syncHeader(); });
            window.setTimeout(() => { if (headerHold) { headerHold = 0; syncHeader(); } }, 1250);

            const topLink = header.querySelector('.bk-logo');
            if (topLink) {
                window.setTimeout(() => { try { topLink.focus({ preventScroll: true }); } catch { topLink.focus(); } }, reduce ? 0 : 320);
            }
        });
    }
    if (!header || typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return;
    document.documentElement.classList.add('bk-enhanced');
    const expand = header.querySelector('[data-bk-expand]');
    const filterForm = document.querySelector('[data-filter-form]');
    const filterSlot = document.querySelector('[data-filter-slot]');
    if (filterForm && filterSlot) filterSlot.appendChild(filterForm);
    const triggers = document.querySelectorAll('[data-open-dialog]');
    triggers.forEach(button => { button.hidden = false; });
    // Status header: ringkas saat halaman digulir, tetap terlihat saat menu dibuka.
    // Tidak ada flag buka-tahan permanen: header tidak boleh menghilang setelah
    // logo ringkas diketuk lalu halaman digulir lagi.
    const radialMenu = header.querySelector('.bk-header-radial');
    const menuOpen = () => !!document.querySelector('.bk-dialog[open], .bk-header-radial[open]');
    const focusWithoutScroll = element => { if (!element) return; try { element.focus({ preventScroll: true }); } catch { element.focus(); } };
    // `:focus-visible` tidak konsisten di WebView lama; lacak modalitas input.
    let keyboardMode = false;
    document.addEventListener('keydown', () => { keyboardMode = true; }, true);
    document.addEventListener('pointerdown', () => { keyboardMode = false; }, true);
    const keyboardFocusInsideHeader = () => {
        const active = document.activeElement;
        return keyboardMode && !!active && active !== expand && header.contains(active) && active.offsetParent !== null;
    };
    let pinned = false;
    const syncHeader = () => {
        if (clock() < headerHold) return;
        const scrolled = window.scrollY > 180;
        if (!scrolled) {
            pinned = false;
            header.classList.remove('is-compact', 'is-pinned');
            return;
        }
        if (menuOpen() || keyboardFocusInsideHeader()) {
            // Menu terbuka / fokus keyboard di header: jangan biarkan header
            // kembali absolut lalu hilang dari layar.
            pinned = true;
            header.classList.remove('is-compact');
            header.classList.add('is-pinned');
            return;
        }
        pinned = false;
        header.classList.remove('is-pinned');
        header.classList.add('is-compact');
    };
    const updateHeader = syncHeader;
    window.addEventListener('scroll', syncHeader, { passive: true });
    if (radialMenu) {
        // Sinkronkan dua arah: membuka menu harus langsung memin header, dan
        // menutupnya harus mengembalikan mode ringkas.
        radialMenu.addEventListener('toggle', syncHeader);
    }
    header.addEventListener('focusout', () => { window.setTimeout(syncHeader, 0); });
    // Disclosure native tidak menutup saat klik di luar/Escape; tambahkan agar
    // menu selalu bisa ditutup dan header kembali ringkas.
    const closeRadialMenu = () => { if (radialMenu && radialMenu.open) radialMenu.open = false; syncHeader(); };
    document.addEventListener('pointerdown', event => {
        if (!radialMenu || !radialMenu.open) return;
        if (header.contains(event.target) || (event.target.closest && event.target.closest('dialog'))) return;
        closeRadialMenu();
    }, true);
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || !radialMenu || !radialMenu.open) return;
        closeRadialMenu();
        focusWithoutScroll(radialMenu.querySelector('summary'));
    });
    if (expand) expand.hidden = false;
    function openDialog(dialog, trigger) {
        if (!dialog || dialog.open || document.querySelector('.bk-dialog[open]')) return;
        dialog.dataset.returnFocus = trigger?.dataset.openDialog || '';
        dialog.dataset.returnScroll = String(window.scrollY);
        dialog.showModal();
        focusWithoutScroll(dialog.querySelector('[data-filter-form] [aria-invalid="true"]') || dialog.querySelector('[data-filter-form] input') || dialog.querySelector('[data-close-dialog]'));
    }
    triggers.forEach(button => button.addEventListener('click', () => openDialog(document.getElementById(button.dataset.openDialog), button)));
    document.querySelectorAll('.bk-dialog').forEach(dialog => {
        dialog.querySelector('[data-close-dialog]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('close', () => {
            const savedScroll = Number(dialog.dataset.returnScroll || NaN);
            // Dialog modal dapat memaksa halaman melompat ke atas; kembalikan posisi baca semula.
            if (Number.isFinite(savedScroll) && window.scrollY + 4 < savedScroll) {
                scrollPageTo(savedScroll, false);
            }
            syncHeader();
            const trigger = document.querySelector(`[data-open-dialog="${dialog.dataset.returnFocus}"]`);
            focusWithoutScroll(trigger && (!header.contains(trigger) || !header.classList.contains('is-compact')) ? trigger : expand);
            syncHeader();
        });
    });
    if (filterForm?.dataset.filterErrors === '1') openDialog(document.getElementById('bk-filter'), document.querySelector('[data-open-dialog="bk-filter"]'));
    updateHeader();
})();
