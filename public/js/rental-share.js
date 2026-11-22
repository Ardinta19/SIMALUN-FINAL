(() => {
    const button = document.querySelector('[data-copy-share]');
    const input = document.getElementById('share-link');
    const status = document.querySelector('[data-copy-status]');
    if (!button || !input || !status || !navigator.clipboard?.writeText || !window.isSecureContext) return;
    button.hidden = false;
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(input.value);
            status.textContent = 'Tautan disalin. Bagikan hanya kepada orang yang kamu pilih.';
        } catch (_) {
            status.textContent = 'Tidak dapat menyalin otomatis. Pilih dan salin tautan secara manual.';
            input.focus();
            input.select();
        }
    });
})();
