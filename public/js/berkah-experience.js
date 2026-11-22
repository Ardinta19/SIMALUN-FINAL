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
    let holdOpen = false;
    const updateHeader = () => { if (!header.contains(document.activeElement) && !document.querySelector('.bk-dialog[open], .bk-header-radial[open]')) header.classList.toggle('is-compact', window.scrollY > 180 && !holdOpen); };
    window.addEventListener('scroll', () => { if (window.scrollY < 80) holdOpen = false; updateHeader(); }, { passive: true });
    expand.hidden = false;
    expand.addEventListener('click', () => { holdOpen = true; header.classList.remove('is-compact'); (window.matchMedia('(max-width: 700px)').matches ? (header.querySelector('.bk-menu-trigger') || header.querySelector('.bk-header-radial summary')) : header.querySelector('.bk-desktop-nav a'))?.focus(); });
    function openDialog(dialog, trigger) {
        if (!dialog || dialog.open || document.querySelector('.bk-dialog[open]')) return;
        dialog.dataset.returnFocus = trigger?.dataset.openDialog || '';
        dialog.showModal();
        (dialog.querySelector('[data-filter-form] [aria-invalid="true"]') || dialog.querySelector('[data-filter-form] input') || dialog.querySelector('[data-close-dialog]'))?.focus();
    }
    triggers.forEach(button => button.addEventListener('click', () => openDialog(document.getElementById(button.dataset.openDialog), button)));
    document.querySelectorAll('.bk-dialog').forEach(dialog => {
        dialog.querySelector('[data-close-dialog]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('close', () => { const trigger = document.querySelector(`[data-open-dialog="${dialog.dataset.returnFocus}"]`); if (trigger && (!header.contains(trigger) || !header.classList.contains('is-compact'))) trigger.focus(); else expand.focus(); });
    });
    if (filterForm?.dataset.filterErrors === '1') openDialog(document.getElementById('bk-filter'), document.querySelector('[data-open-dialog="bk-filter"]'));
    updateHeader();
})();
