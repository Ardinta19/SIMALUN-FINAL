(() => {
    const preview = document.querySelector('[data-catalog-preview]');
    if (!preview || !window.fetch || !window.DOMParser) return;
    const catalog = preview.dataset.catalogUrl;
    window.addEventListener('pageshow', event => {
        if (event.persisted && location.pathname === new URL(catalog, location.href).pathname) location.replace(catalog + '#koleksi');
    });
    fetch(catalog, { cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'text/html' } })
        .then(response => { if (!response.ok || response.redirected) throw new Error('Catalog unavailable'); return response.text(); })
        .then(html => {
            const page = new DOMParser().parseFromString(html, 'text/html');
            const products = page.querySelector('[data-catalog-products]');
            if (!products) throw new Error('Catalog markup unavailable');
            const cards = [...products.querySelectorAll('.bk-product-card')].slice(0, 4);
            if (!cards.length) { preview.querySelector('p').textContent = 'Belum ada papan di katalog saat ini.'; return; }
            preview.replaceChildren(...cards.map(card => document.importNode(card, true)));
            preview.addEventListener('click', event => {
                const link = event.target.closest('a');
                if (!link || !link.closest('.bk-product-card') || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
                // Back from a previewed item returns to the full catalogue, rather than this preview.
                try { history.replaceState(history.state, '', catalog + '#koleksi'); } catch (_) { /* The detail page also has an explicit catalogue link. */ }
            });
        }).catch(() => { /* Keep the catalogue link available when the preview cannot load. */ });
})();
