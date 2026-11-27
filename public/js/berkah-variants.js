/* ══════════════════════════════════════════════════════════════════════════
   Berkah Kreatif — varian warna & total baris yang langsung berubah
   ──────────────────────────────────────────────────────────────────────────
   Satu tugas: pemilih warna ([data-bk-variants]). Klik satu swatch → cincin
   aktif, nilai `input[name="variant_id"]` di formulir keranjang, label
   "Warna: …", pratinjau foto (bila varian punya foto) atau latar contoh, dan
   batas jumlah unit ikut berganti. Tanpa JavaScript semuanya sudah diisi
   server, jadi formulir tetap bisa dikirim.

   Harga TIDAK berubah antarvarians (keputusan pemilik usaha): varian hanya
   menentukan warna, stok, dan foto. Kolom "Total" yang berubah setiap kali
   jumlah disentuh sudah dicabut pada putaran 36 — harga satuan ditulis sekali
   oleh server dan tidak beranimasi; hitungan uang terjadi di keranjang dan di
   halaman pesanan.
   ══════════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    /** Wadah yang memuat satu pemilih warna beserta formulir & pratinjaunya. */
    function scopeOf(picker) {
        return picker.closest('[data-bk-product]')
            || picker.closest('.bk-product-detail')
            || picker.closest('form')
            || picker.parentNode;
    }

    function qtyInput(scope) {
        var wrap = scope.querySelector('[data-bk-qty]');

        return wrap ? wrap.querySelector('input[type="number"]') : null;
    }

    function setStockLimit(scope, stock) {
        var input = qtyInput(scope);
        if (!input) return;

        var limit = Math.max(1, Math.min(20, Number(stock) || 0));
        input.setAttribute('max', String(limit));
        if (Number(input.value) > limit) input.value = String(limit);
        // Beri tahu pengendali jumlah di UI kit supaya tombol +/− ikut
        // menonaktifkan diri pada batas yang baru.
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function initPicker(picker) {
        if (picker.getAttribute('data-bk-ready') === '1') return;
        picker.setAttribute('data-bk-ready', '1');

        var scope = scopeOf(picker);
        var form = picker.closest('form') || scope.querySelector('form');
        var hidden = form ? form.querySelector('input[name="variant_id"]') : null;
        var label = scope.querySelector('[data-bk-variant-label]');
        var image = scope.querySelector('[data-bk-preview-img]');
        var placeholder = scope.querySelector('[data-bk-preview-placeholder]');
        var buttons = Array.prototype.slice.call(picker.querySelectorAll('[data-variant-id]'));
        // Foto papan dipakai lagi saat varian tanpa foto dipilih.
        var defaultPhoto = image ? image.getAttribute('src') : '';

        if (!buttons.length) return;

        function select(button) {
            if (!button || button.disabled) return;

            buttons.forEach(function (other) {
                var active = other === button;
                other.classList.toggle('is-active', active);
                other.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            var id = button.getAttribute('data-variant-id') || '';
            var name = button.getAttribute('data-variant-name') || '';
            var color = button.getAttribute('data-variant-color') || '';
            var stock = button.getAttribute('data-variant-stock') || '0';
            var photo = button.getAttribute('data-variant-image') || '';

            if (hidden) hidden.value = id;
            if (label) label.textContent = 'Warna: ' + name;

            if (image) image.src = photo || defaultPhoto;
            if (placeholder) placeholder.style.setProperty('--bk-swatch', color);

            setStockLimit(scope, stock);
        }

        picker.addEventListener('click', function (event) {
            var button = event.target.closest && event.target.closest('[data-variant-id]');
            if (button && picker.contains(button)) select(button);
        });

        // Tombol panah kiri/kanan berpindah antars watch; ini pola pemilih
        // warna yang lazim dan jauh lebih cepat daripada Tab satu per satu.
        picker.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
            var current = document.activeElement;
            var index = buttons.indexOf(current);
            if (index < 0) return;
            event.preventDefault();
            var next = buttons[(index + (event.key === 'ArrowRight' ? 1 : buttons.length - 1)) % buttons.length];
            if (next) { next.focus(); select(next); }
        });

        var active = picker.querySelector('[aria-pressed="true"]:not([disabled])')
            || buttons.filter(function (button) { return !button.disabled; })[0];
        if (active) select(active);
    }

    function initAll() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-bk-variants]'), initPicker);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.BkVariants = { refresh: initAll };
})();
