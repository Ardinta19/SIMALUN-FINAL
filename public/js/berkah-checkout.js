/* ══════════════════════════════════════════════════════════════════════════
   Berkah Kreatif — checkout bertahap

   Tiga tugas, semuanya bertujuan mengurangi rasa "penuh" di halaman checkout:

   1. Tiap papan dibagi menjadi tiga bagian (Jadwal → Ucapan & desain →
      Serah terima). Hanya satu bagian yang terlihat; tab di atasnya menjadi
      peta posisi dan menandai bagian yang sudah lengkap.
   2. Kemajuan pengisian dihitung dari isian wajib yang benar-benar aktif
      (alamat hanya wajib bila diantar/dijemput), lalu ditampilkan sebagai
      bilah, persentase, dan daftar kesiapan di samping.
   3. Menekan "Kirim pesanan" saat masih ada isian kosong tidak menampilkan
      galat server: halaman langsung membuka bagian yang belum lengkap dan
      memfokuskan isian pertama. Tanpa JavaScript, semua bagian tetap terbuka
      dan formulir tetap terkirim seperti biasa.

   Skrip ini juga memuat perilaku lama yang dipindahkan dari dalam view:
   tanggal pengembalian mengikuti tanggal acara, tombol "pakai tanggal yang
   sama", bidang alamat yang hanya aktif saat diantar/dijemput, salin detail
   unit sebelumnya, dan pemeriksaan kode voucher.
   ══════════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var form = document.querySelector('[data-co-form]');
    if (!form) return;

    var units = Array.prototype.slice.call(form.querySelectorAll('[data-co-unit]'));
    var submit = form.querySelector('[data-checkout-submit]');
    var submitLabel = submit ? submit.textContent : '';
    var status = form.querySelector('[data-checkout-status]');
    var terms = form.querySelector('input[name="terms"]');
    var reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var labels = ['Jadwal', 'Ucapan & desain', 'Serah terima'];

    var progressLabel = document.querySelector('[data-co-progress-label]');
    var progressPct = document.querySelector('[data-co-progress-pct]');
    var progressBar = document.querySelector('[data-co-progressbar]');
    var progressFill = document.querySelector('[data-co-progress-fill]');

    /** Peta pengaktif panel per unit, supaya bisa dipanggil dari luar unit. */
    var activators = new WeakMap();
    /** Penghitung ulang per unit, dipanggil setelah perilaku tanggal/alamat siap. */
    var refreshers = [];

    function setStatus(message) {
        if (status) status.textContent = message;
    }

    function isFilled(field) {
        if (field.type === 'checkbox' || field.type === 'radio') return field.checked;
        return String(field.value == null ? '' : field.value).trim() !== '';
    }

    /** Isian wajib yang benar-benar berlaku: yang dinonaktifkan tidak dihitung. */
    function requiredFields(scope) {
        return Array.prototype.filter.call(scope.querySelectorAll('[required]'), function (field) {
            return !field.disabled && field.type !== 'hidden' && field.type !== 'submit';
        });
    }

    function missingFields(scope) {
        return requiredFields(scope).filter(function (field) {
            return !isFilled(field);
        });
    }

    function markComplete(node, complete) {
        if (!node) return;
        if (complete) node.setAttribute('data-complete', '1');
        else node.removeAttribute('data-complete');
    }

    function scrollTo(node, block) {
        if (!node) return;
        node.scrollIntoView({ block: block || 'center', behavior: reduce ? 'auto' : 'smooth' });
    }

    /* Ask helper: pakai modal bersama bila tersedia, balon peramban hanya
       sebagai cadangan untuk peramban tanpa skrip modal. */
    function ask(options, onYes) {
        if (typeof window.showConfirmModal === 'function') {
            window.showConfirmModal({
                title: options.title || 'Konfirmasi',
                message: options.message || '',
                confirmText: options.confirmText || 'Lanjutkan',
                cancelText: 'Batal',
                type: options.type || 'info',
                onConfirm: onYes,
            });
            return;
        }
        if (window.confirm(options.message || '')) onYes();
    }

    /* ── Bagian per unit ─────────────────────────────────────────────────── */
    units.forEach(function (unit, index) {
        var tabs = Array.prototype.slice.call(unit.querySelectorAll('[data-co-tab]'));
        var panels = Array.prototype.slice.call(unit.querySelectorAll('[data-co-panel]'));
        var prev = unit.querySelector('[data-co-prev]');
        var next = unit.querySelector('[data-co-next]');
        var state = unit.querySelector('[data-co-unit-state]');
        var checklist = document.querySelector('[data-co-check-unit="' + (index + 1) + '"]');
        var active = 1;

        function activate(step) {
            active = Math.min(3, Math.max(1, step));
            tabs.forEach(function (tab) {
                var on = tab.getAttribute('data-co-tab') === String(active);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                tab.tabIndex = on ? 0 : -1;
            });
            panels.forEach(function (panel) {
                if (panel.getAttribute('data-co-panel') === String(active)) panel.setAttribute('data-co-active', '');
                else panel.removeAttribute('data-co-active');
            });
            if (prev) prev.hidden = active === 1;
            if (next) {
                next.textContent = active === 3
                    ? 'Lanjut ke ketentuan'
                    : 'Lanjut: ' + labels[active];
            }
        }

        function firstMissingInPanel(step) {
            var panel = unit.querySelector('[data-co-panel="' + step + '"]');
            return panel ? missingFields(panel)[0] : null;
        }

        /* Balon validasi menempel pada posisi elemen saat dipanggil. Bila bagian
           baru saja dibuka, tata letak belum dihitung ulang; dan bila elemennya
           sendiri tidak punya kotak tata letak (bagian tertutup), Chrome
           menaruh balon di sudut kiri atas halaman — terlihat seperti pesan
           nyasar. Karena itu balon hanya diminta setelah satu bingkai dan hanya
           untuk elemen yang benar-benar tampil; pesan status tetap memberi tahu
           apa yang kurang. */
        function reportValiditySoon(field) {
            if (typeof field.reportValidity !== 'function') return;
            var show = function () {
                if (field.getClientRects().length === 0) return;
                field.reportValidity();
            };
            if (typeof requestAnimationFrame === 'function') requestAnimationFrame(show);
            else show();
        }

        /* Buka semua lapisan yang menutupi isian: tab bagian, lalu kotak alamat
           yang hanya muncul saat "diantar"/"dijemput". */
        function revealField(field) {
            var panel = field.closest('[data-co-panel]');
            if (panel) activate(Number(panel.getAttribute('data-co-panel')));
            var box = field.closest('[data-delivery-fields], [data-return-fields]');
            if (box) box.hidden = false;
        }

        function showField(field) {
            revealField(field);
            field.focus({ preventScroll: true });
            scrollTo(field);
            reportValiditySoon(field);
        }

        function refresh() {
            var missing = missingFields(unit).length;
            if (state) {
                state.textContent = missing ? missing + ' isian lagi' : 'Lengkap';
                markComplete(state, missing === 0);
            }
            tabs.forEach(function (tab) {
                var panel = unit.querySelector('[data-co-panel="' + tab.getAttribute('data-co-tab') + '"]');
                markComplete(tab, panel ? missingFields(panel).length === 0 : false);
            });
            if (checklist) {
                var counter = checklist.querySelector('[data-co-check-count]');
                if (counter) counter.textContent = missing ? missing + ' isian lagi' : 'Selesai';
                markComplete(checklist, missing === 0);
            }
        }

        tabs.forEach(function (tab, tabIndex) {
            tab.addEventListener('click', function () {
                activate(Number(tab.getAttribute('data-co-tab')));
            });
            // Panah kiri/kanan berpindah tab, sama seperti pola tab standar.
            tab.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
                event.preventDefault();
                var step = event.key === 'ArrowRight' ? Math.min(3, tabIndex + 1) : Math.max(1, tabIndex);
                activate(step);
                var target = unit.querySelector('[data-co-tab="' + step + '"]');
                if (target) target.focus();
            });
        });

        if (prev) prev.addEventListener('click', function () { activate(active - 1); });

        if (next) {
            next.addEventListener('click', function () {
                if (active < 3) {
                    var missing = firstMissingInPanel(active);
                    if (missing) {
                        showField(missing);
                        setStatus('Bagian ini belum lengkap. Isi ' + missingFields(unit.querySelector('[data-co-panel="' + active + '"]')).length + ' isian lagi.');
                        return;
                    }
                    activate(active + 1);
                    return;
                }

                var firstEmpty = missingFields(unit)[0];
                if (firstEmpty) {
                    showField(firstEmpty);
                    setStatus('Unit ' + (index + 1) + ' masih ada isian wajib yang kosong.');
                    return;
                }

                var termsBox = document.getElementById('checkout-terms');
                if (termsBox) scrollTo(termsBox, 'start');
                if (terms && !terms.checked) {
                    window.setTimeout(function () { terms.focus({ preventScroll: true }); }, reduce ? 0 : 280);
                    setStatus('Unit ' + (index + 1) + ' lengkap. Centang persetujuan ketentuan untuk mengirim.');
                } else {
                    setStatus('Semua bagian lengkap. Periksa ringkasan lalu kirim pesanan.');
                }
            });
        }

        activators.set(unit, activate);
        refreshers.push(refresh);
        activate(1);
        refresh();
        unit.addEventListener('input', refresh);
        unit.addEventListener('change', refresh);
    });

    /* ── Kemajuan keseluruhan ────────────────────────────────────────────── */
    var stageNodes = {
        unit: document.querySelector('[data-co-stage="unit"]'),
        terms: document.querySelector('[data-co-stage="terms"]'),
        send: document.querySelector('[data-co-stage="send"]'),
    };

    function setStage(node, state) {
        if (!node) return;
        node.setAttribute('data-state', state);
    }

    function updateProgress() {
        var required = requiredFields(form);
        var missing = required.filter(function (field) { return !isFilled(field); });
        var done = required.length - missing.length;
        var pct = required.length ? Math.round((done / required.length) * 100) : 100;

        if (progressLabel) {
            progressLabel.textContent = missing.length
                ? done + ' dari ' + required.length + ' isian wajib'
                : 'Semua isian wajib lengkap';
        }
        if (progressPct) progressPct.textContent = pct + '%';
        if (progressBar) {
            progressBar.setAttribute('aria-valuenow', String(pct));
            progressBar.setAttribute('aria-valuetext', done + ' dari ' + required.length + ' isian wajib terisi');
        }
        if (progressFill) progressFill.style.width = pct + '%';
        if (progressBar) {
            if (pct === 100) progressBar.classList.add('is-complete');
            else progressBar.classList.remove('is-complete');
        }

        var unitsDone = units.every(function (unit) { return missingFields(unit).length === 0; });
        var termsDone = !terms || terms.checked;

        setStage(stageNodes.unit, unitsDone ? 'done' : 'active');
        setStage(stageNodes.terms, termsDone ? 'done' : (unitsDone ? 'active' : 'todo'));
        setStage(stageNodes.send, unitsDone && termsDone ? 'active' : 'todo');

        var termsItem = document.querySelector('[data-co-check="terms"]');
        if (termsItem) {
            var counter = termsItem.querySelector('[data-co-check-count]');
            if (counter) counter.textContent = termsDone ? 'Disetujui' : 'Centang persetujuan di bawah';
            markComplete(termsItem, termsDone);
        }

        if (submit) {
            if (missing.length === 0) submit.classList.add('is-ready');
            else submit.classList.remove('is-ready');
        }
    }

    // Penanda bahwa skrip berjalan: CSS baru menyembunyikan bagian yang tidak
    // aktif setelah penanda ini ada, sehingga tanpa JavaScript seluruh isian
    // tetap terbaca dan formulir tetap bisa dikirim.
    var root = form.closest('[data-bk-co]');
    if (root) root.setAttribute('data-co-ready', '');

    /* Validasi bawaan peramban berhenti pada kendali yang tidak terlihat
       (bagian yang sedang tertutup), sehingga pesannya tidak pernah muncul dan
       formulir terasa "tidak bereaksi". Karena itu validasi diambil alih:
       skrip memeriksa isian wajib, membuka bagiannya lebih dulu, lalu memanggil
       reportValidity() agar pesan Indonesia dari UI kit tetap dipakai.
       Tanpa JavaScript, atribut ini tidak pernah dipasang dan validasi bawaan
       tetap berlaku seperti biasa. */
    form.setAttribute('novalidate', 'novalidate');

    form.addEventListener('input', updateProgress);
    form.addEventListener('change', updateProgress);
    if (terms) terms.addEventListener('change', updateProgress);

    /* ── Tanggal, alamat, salin unit, voucher ────────────────────────────── */
    function setupUnitBehaviour(unit, index, all) {
        var eventDate = unit.querySelector('[name$="[event_on]"]');
        var returnDate = unit.querySelector('[name$="[ends_on]"]');
        if (eventDate && returnDate) {
            var updateDates = function () {
                returnDate.min = eventDate.value;
                if (eventDate.value && !returnDate.value) returnDate.value = eventDate.value;
            };
            eventDate.addEventListener('change', updateDates);
            updateDates();
        }

        var sameDay = unit.querySelector('[data-same-day]');
        var sameDayStatus = unit.querySelector('[data-same-day-status]');
        if (sameDay && eventDate && returnDate) {
            sameDay.hidden = false;
            sameDay.addEventListener('click', function () {
                if (!eventDate.value) {
                    if (sameDayStatus) sameDayStatus.textContent = 'Isi tanggal acara terlebih dahulu.';
                    eventDate.focus();
                    return;
                }
                returnDate.value = eventDate.value;
                returnDate.dispatchEvent(new Event('change', { bubbles: true }));
                if (sameDayStatus) sameDayStatus.textContent = 'Tanggal pengembalian disamakan dengan tanggal acara.';
            });
        }

        function toggleFields(selectName, expected, fieldsSelector) {
            var select = unit.querySelector('[name$="[' + selectName + ']"]');
            var fields = unit.querySelector(fieldsSelector);
            if (!select || !fields) return;
            var update = function () {
                // Isian yang sedang ditandai galat oleh server tetap terbuka,
                // supaya pesan galatnya tidak tersembunyi.
                var visible = select.value === expected || !!fields.querySelector('[aria-invalid="true"]');
                fields.hidden = !visible;
                Array.prototype.forEach.call(fields.querySelectorAll('input, select, textarea'), function (input) {
                    input.disabled = !visible;
                });
                var area = fields.querySelector('textarea');
                if (area) area.required = select.value === expected;
            };
            select.addEventListener('change', update);
            update();
        }

        Array.prototype.forEach.call(unit.querySelectorAll('[data-saved-address]'), function (wrapper) {
            var choice = wrapper.querySelector('[data-address-choice]');
            var target = wrapper.parentElement.querySelector('textarea');
            if (!choice || !target) return;
            wrapper.hidden = false;
            choice.addEventListener('change', function () {
                var option = choice.options[choice.selectedIndex];
                if (!option || !option.value) return;
                var address = option.dataset.address;
                var apply = function () {
                    target.value = address;
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                };
                if (target.value && target.value !== address) {
                    ask({
                        title: 'Ganti alamat?',
                        message: 'Alamat yang sudah diisi akan diganti dengan alamat tersimpan.',
                        confirmText: 'Ganti alamat',
                    }, apply);
                } else {
                    apply();
                }
            });
        });

        toggleFields('delivery_method', 'delivery', '[data-delivery-fields]');
        toggleFields('return_method', 'collection', '[data-return-fields]');

        var copyButton = unit.querySelector('[data-copy-previous]');
        if (!copyButton || index === 0) return;
        copyButton.hidden = false;
        var help = unit.querySelector('[data-copy-help]');
        if (help) help.hidden = false;

        copyButton.addEventListener('click', function () {
            ask({
                title: 'Salin detail unit sebelumnya?',
                message: 'Jadwal, penerima, ucapan, desain, dan serah terima unit sebelumnya akan disalin ke unit ini. Pilihan paket tidak disalin.',
                confirmText: 'Salin detail',
            }, function () {
                var previous = all[index - 1];
                var copied = 0;
                ['event_on', 'ends_on', 'recipient_name', 'greeting', 'design_notes', 'delivery_method', 'delivery_address', 'delivery_university_id', 'return_method', 'return_address', 'return_university_id'].forEach(function (field) {
                    var source = previous.querySelector('[name$="[' + field + ']"]');
                    var target = unit.querySelector('[name$="[' + field + ']"]');
                    if (!source || !target) return;
                    if (target.tagName === 'SELECT' && !Array.prototype.some.call(target.options, function (option) { return option.value === source.value; })) return;
                    target.value = source.value;
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                    copied += 1;
                });
                var copyStatus = unit.querySelector('[data-copy-status]');
                if (copyStatus) copyStatus.textContent = copied + ' isian disalin. Periksa sebelum mengirim.';
                updateProgress();
            });
        });
    }

    units.forEach(setupUnitBehaviour);

    /* ── Kode voucher ────────────────────────────────────────────────────── */
    var voucherButton = form.querySelector('[data-voucher-check]');
    var voucherCode = form.querySelector('#voucher_code');
    var voucherStatus = form.querySelector('[data-voucher-status]');
    var voucherUrl = form.getAttribute('data-voucher-url');

    function estimateBase() {
        var total = 0;
        units.forEach(function (unit) {
            var price = parseInt(unit.dataset.unitPrice || '0', 10) || 0;
            var priceUnit = unit.dataset.unitPriceUnit || 'event';
            var packageSelect = unit.querySelector('select[name$="[package_id]"]');
            var chosen = packageSelect && packageSelect.value ? packageSelect.options[packageSelect.selectedIndex] : null;
            if (chosen && chosen.dataset.price) {
                total += parseInt(chosen.dataset.price, 10) || 0;
                return;
            }
            var start = unit.querySelector('[name$="[event_on]"]');
            var end = unit.querySelector('[name$="[ends_on]"]');
            var days = 1;
            if (start && end && start.value && end.value && end.value >= start.value) {
                days = Math.round((new Date(end.value) - new Date(start.value)) / 86400000) + 1;
            }
            total += priceUnit === 'day' ? price * days : price;
        });
        return total;
    }

    if (voucherButton && voucherCode && voucherStatus && voucherUrl) {
        voucherButton.addEventListener('click', function () {
            var code = voucherCode.value.trim();
            if (!code) {
                voucherStatus.textContent = 'Isi kode voucher terlebih dahulu.';
                voucherCode.focus();
                return;
            }
            var tokenField = form.querySelector('input[name="_token"]');
            voucherButton.disabled = true;
            voucherStatus.textContent = 'Memeriksa kode...';
            fetch(voucherUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': tokenField ? tokenField.value : '' },
                body: JSON.stringify({ code: code, base: estimateBase() }),
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (payload) {
                    return { ok: response.ok, payload: payload };
                });
            }).then(function (result) {
                var payload = result.payload || {};
                if (!result.ok || !payload.ok) {
                    voucherCode.setAttribute('aria-invalid', 'true');
                    voucherStatus.textContent = payload.message || 'Kode tidak dapat dipakai.';
                    return;
                }
                voucherCode.removeAttribute('aria-invalid');
                voucherStatus.textContent = payload.message + ' Perkiraan potongan ' + payload.discount_text + '. ' + payload.note;
            }).catch(function () {
                voucherStatus.textContent = 'Tidak dapat memeriksa kode saat ini. Kode tetap diperiksa saat pesanan dikirim.';
            }).then(function () {
                voucherButton.disabled = false;
            });
        });
    }

    /* ── Kirim pesanan ───────────────────────────────────────────────────── */
    form.addEventListener('submit', function (event) {
        if (submit && submit.getAttribute('data-busy') === '1') {
            event.preventDefault();
            return;
        }

        var missing = requiredFields(form).filter(function (field) { return !isFilled(field); });
        if (missing.length) {
            event.preventDefault();
            var first = missing[0];
            var owner = first.closest('[data-co-unit]');
            var openUnit = owner ? activators.get(owner) : null;
            var panel = first.closest('[data-co-panel]');
            if (openUnit && panel) openUnit(Number(panel.getAttribute('data-co-panel')));
            var box = first.closest('[data-delivery-fields], [data-return-fields]');
            if (box) box.hidden = false;
            first.focus({ preventScroll: true });
            scrollTo(first);
            reportValiditySoon(first);
            setStatus('Masih ada ' + missing.length + ' isian wajib. Halaman sudah membuka bagian yang belum lengkap.');
            return;
        }

        if (submit) {
            submit.setAttribute('data-busy', '1');
            submit.disabled = true;
            submit.textContent = 'Mengirim pesanan…';
        }
        form.setAttribute('aria-busy', 'true');
        setStatus('Mohon tunggu. Jangan tutup halaman ini.');
    });

    window.addEventListener('pageshow', function () {
        if (!submit) return;
        submit.removeAttribute('data-busy');
        submit.disabled = false;
        submit.textContent = submitLabel;
        form.removeAttribute('aria-busy');
        setStatus('');
    });

    /* Galat dari server: buka unit & bagian yang memuat isian bermasalah. */
    var invalid = form.querySelector('[aria-invalid="true"]');
    if (invalid) {
        var owner = invalid.closest('[data-co-unit]');
        var activate = owner ? activators.get(owner) : null;
        var panel = invalid.closest('[data-co-panel]');
        if (activate && panel) activate(Number(panel.getAttribute('data-co-panel')));
        invalid.focus({ preventScroll: true });
        scrollTo(invalid);
    }

    // Setelah tanggal/alamat terpasang, satu isian bisa berubah status wajib
    // (alamat hanya wajib saat diantar/dijemput), jadi hitung ulang semuanya.
    refreshers.forEach(function (refresh) { refresh(); });
    updateProgress();
})();
