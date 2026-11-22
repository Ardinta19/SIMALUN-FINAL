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
    const updateHeader = () => { if (!header.contains(document.activeElement) && !document.querySelector('.bk-dialog[open]')) header.classList.toggle('is-compact', window.scrollY > 180 && !holdOpen); };
    window.addEventListener('scroll', () => { if (window.scrollY < 80) holdOpen = false; updateHeader(); }, { passive: true });
    expand.hidden = false;
    expand.addEventListener('click', () => { holdOpen = true; header.classList.remove('is-compact'); (window.matchMedia('(max-width: 700px)').matches ? header.querySelector('.bk-menu-trigger') : header.querySelector('.bk-command-trigger')).focus(); });
    function openDialog(dialog, trigger) {
        if (!dialog || dialog.open) return;
        dialog.dataset.returnFocus = trigger?.dataset.openDialog || '';
        dialog.showModal();
        (dialog.querySelector('[data-command-search]') || dialog.querySelector('[data-filter-form] input') || dialog.querySelector('[data-close-dialog]')).focus();
    }
    triggers.forEach(button => button.addEventListener('click', () => openDialog(document.getElementById(button.dataset.openDialog), button)));
    document.querySelectorAll('.bk-dialog').forEach(dialog => {
        dialog.querySelector('[data-close-dialog]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('close', () => { const trigger = document.querySelector(`[data-open-dialog="${dialog.dataset.returnFocus}"]`); if (trigger && (!header.contains(trigger) || !header.classList.contains('is-compact'))) trigger.focus(); else expand.focus(); });
    });
    const search = document.querySelector('[data-command-search]');
    search?.addEventListener('input', () => {
        let count = 0; document.querySelectorAll('[data-command-item]').forEach(item => { const match = item.textContent.toLocaleLowerCase('id').includes(search.value.trim().toLocaleLowerCase('id')); item.hidden = !match; if (match) count++; });
        document.querySelector('[data-command-empty]').hidden = count > 0;
    });
    document.addEventListener('keydown', event => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k' && !window.matchMedia('(max-width: 700px)').matches) { event.preventDefault(); if (header.classList.contains('is-compact')) { holdOpen = true; header.classList.remove('is-compact'); } openDialog(document.getElementById('bk-command'), header.querySelector('.bk-command-trigger')); } });
    updateHeader();
})();
