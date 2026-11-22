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
    const menuButton = header.querySelector('[data-open-dialog="bk-menu"]');
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
        radialMenu.addEventListener('toggle', () => { if (!radialMenu.open) syncHeader(); });
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
    if (expand) {
        expand.addEventListener('click', () => {
            if (!header.classList.contains('is-compact')) return;
            // Jangan membuka header absolut di luar layar: pin ke viewport
            // lalu langsung tampilkan menu yang dituju.
            pinned = true;
            header.classList.remove('is-compact');
            header.classList.add('is-pinned');
            const compactViewport = window.matchMedia('(max-width: 700px)').matches;
            if (compactViewport && radialMenu) {
                const summary = radialMenu.querySelector('summary');
                if (summary && !radialMenu.open) {
                    summary.click();
                    radialMenu.open = true;
                }
                focusWithoutScroll(summary);
                syncHeader();
                return;
            }
            if (compactViewport && menuButton) {
                menuButton.click();
                return;
            }
            focusWithoutScroll(header.querySelector('.bk-desktop-nav a') || expand);
            syncHeader();
        });
    }
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
                try { window.scrollTo({ top: savedScroll, left: 0, behavior: 'auto' }); } catch { window.scrollTo(0, savedScroll); }
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
