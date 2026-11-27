/* ══════════════════════════════════════════════════════════════════════════
   Berkah Kreatif — keranjang yang berubah tanpa memuat ulang
   ──────────────────────────────────────────────────────────────────────────
   Tombol − dan + (juga "Hapus") di halaman keranjang mengirim formulir seperti
   biasa, tetapi bila JavaScript hidup formulir itu dikirim lewat fetch dengan
   `Accept: application/json`. Server (RentalCartController) mengembalikan
   jumlah unit per kelompok, subtotal kelompok, dan total keranjang yang
   dihitung ulang dari sesi — bukan angka kiriman peramban.

   Yang diperbarui di tempat: angka unit baris, subtotal baris, total keranjang,
   dan jumlah unit di kepala halaman. Baris yang habis dihapus; keranjang yang
   menjadi kosong memuat ulang halaman supaya tampilan "masih kosong" muncul.

   Bila permintaan gagal (stok habis, tanggal lewat, jaringan putus), halaman
   dimuat ulang agar pesan dari server tampil seperti biasa. Tanpa JavaScript,
   semuanya tetap bekerja lewat pengiriman formulir biasa.
   ══════════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var PULSE = 450;

    function pulse(element) {
        if (!element) return;
        element.classList.remove('is-updated');
        // Memaksa reflow kecil supaya animasi bisa dijalankan ulang.
        void element.offsetWidth;
        element.classList.add('is-updated');
        window.setTimeout(function () { element.classList.remove('is-updated'); }, PULSE);
    }

    function rupiah(value) {
        return 'Rp ' + Math.max(0, Math.round(Number(value) || 0)).toLocaleString('id-ID');
    }

    function rowFor(key) {
        var rows = document.querySelectorAll('[data-cart-group]');
        for (var i = 0; i < rows.length; i += 1) {
            if (rows[i].getAttribute('data-cart-group') === key) return rows[i];
        }

        return null;
    }

    function apply(payload) {
        if (!payload || typeof payload !== 'object') return window.location.reload();

        var total = document.querySelector('[data-cart-total]');
        if (total) {
            total.textContent = payload.total_label || rupiah(payload.total);
            pulse(total);
        }

        var counter = document.querySelector('[data-cart-total-count]');
        if (counter) {
            counter.textContent = payload.count + ' unit';
            pulse(counter);
        }

        (payload.groups || []).forEach(function (group) {
            var row = rowFor(group.key);
            if (!row) return;
            if (group.count < 1) {
                row.remove();

                return;
            }
            var count = row.querySelector('[data-cart-count]');
            if (count) count.textContent = String(group.count);
            var subtotal = row.querySelector('[data-cart-subtotal]');
            if (subtotal) subtotal.textContent = rupiah(group.subtotal);
        });

        // Tampilan keranjang kosong hanya ada di markup server.
        if ((payload.count || 0) < 1) window.location.reload();
    }

    function submitLive(form) {
        var data = new FormData(form);
        var buttons = Array.prototype.slice.call(form.querySelectorAll('button'));
        buttons.forEach(function (button) { button.disabled = true; });
        form.setAttribute('aria-busy', 'true');

        window.fetch(form.action, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            if (!response.ok) throw new Error('permintaan gagal');

            return response.json();
        }).then(apply).catch(function () {
            // Pesan galat server (mis. stok warna habis) baru terlihat setelah
            // halaman dimuat ulang — itu jalur yang sudah dikenal pengguna.
            window.location.reload();
        }).then(function () {
            buttons.forEach(function (button) { button.disabled = false; });
            form.removeAttribute('aria-busy');
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest && event.target.closest('form[data-cart-live]');
        if (!form || !window.fetch || !window.FormData) return;
        event.preventDefault();
        submitLive(form);
    });
})();
