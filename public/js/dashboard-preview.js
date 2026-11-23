(() => {
    const preview = document.querySelector('[data-catalog-preview]');
    if (!preview || !window.fetch || !window.DOMParser) return;
    const catalog = preview.dataset.catalogUrl;
    const skeleton = preview.querySelector('[data-preview-skeleton]');
    const fallback = preview.querySelector('p');

    /* Kerangka hanya ditampilkan setelah skrip benar-benar berjalan; tanpa
       JavaScript ia tetap [hidden] dan teks cadangan yang terbaca. Tinggi
       kartu kerangka sama dengan kartu katalog (media 4/3 + tiga baris teks +
       tombol), jadi bagian di bawah pratinjau tidak bergeser. */
    const startLoading = () => {
        preview.classList.add('is-loading');
        preview.setAttribute('aria-busy', 'true');
        if (skeleton) skeleton.hidden = false;
    };

    /* Selalu dipanggil di ketiga ujung alur (berhasil, kosong, gagal) supaya
       kerangka tidak pernah tertinggal menutupi teks cadangan. */
    const stopLoading = () => {
        preview.classList.remove('is-loading');
        preview.setAttribute('aria-busy', 'false');
        if (skeleton) skeleton.hidden = true;
    };

    window.addEventListener('pageshow', event => {
        if (event.persisted && location.pathname === new URL(catalog, location.href).pathname) location.replace(catalog + '#koleksi');
    });

    // Halaman yang dipulihkan dari cache (bfcache) bisa kembali dengan kelas
    // "sedang memuat" yang tertinggal; keadaan dibersihkan saat selesai/gagal.
    startLoading();

    fetch(catalog, { cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'text/html' } })
        .then(response => { if (!response.ok || response.redirected) throw new Error('Catalog unavailable'); return response.text(); })
        .then(html => {
            const page = new DOMParser().parseFromString(html, 'text/html');
            const products = page.querySelector('[data-catalog-products]');
            if (!products) throw new Error('Catalog markup unavailable');
            const cards = [...products.querySelectorAll('.bk-product-card')].slice(0, 4);
            if (!cards.length) {
                stopLoading();
                if (fallback) fallback.textContent = 'Belum ada papan di katalog saat ini.';
                return;
            }
            preview.replaceChildren(...cards.map(card => document.importNode(card, true)));
            stopLoading();
            preview.addEventListener('click', event => {
                const link = event.target.closest('a');
                if (!link || !link.matches('.bk-product-image-link, .bk-product-name') || !link.closest('.bk-product-card') || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
                // Back from a previewed item returns to the full catalogue, rather than this preview.
                try { history.replaceState(history.state, '', catalog + '#koleksi'); } catch (_) { /* The detail page also has an explicit catalogue link. */ }
            });
        }).catch(() => {
            // Katalog gagal diambil: kembalikan teks cadangan, jangan tinggalkan kerangka.
            stopLoading();
        });
})();
