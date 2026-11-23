{{--
    Shared confirmation modal component (all roles)
    Usage: @include('layouts.component._confirm_modal')
           @include('layouts.component.customer._confirm_modal')   // alias, berkas sama

    Trigger via JS:
    showConfirmModal({
        title: 'Hapus catatan ini?',
        message: 'Catatan kas yang dihapus tidak bisa dikembalikan.',
        confirmText: 'Ya, Konfirmasi',
        cancelText: 'Batal',
        type: 'info',       // 'danger' | 'warning' | 'info' | 'success'
        onConfirm: function() { document.getElementById('form-id').submit(); }
    });

    Palet: TIDAK ada nilai warna di berkas ini. Semua warna, radius, jarak,
    dan ukuran huruf datang dari token --bk-* (berkah-ui-kit.css untuk nilai
    terang, berkah-theme.css untuk nilai gelap/cetak). `type-info` dulu memakai
    palet biru (#005f92) di salinan pelanggan dan #0d6fb8 di salinan bersama;
    keduanya kini plum (--bk-state-info-*), satu keluarga dengan merek wine
    tetapi tetap terbedakan dari merah "bahaya".
--}}

<div id="confirm-modal-overlay" class="confirm-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message">
    <div class="confirm-backdrop" id="confirm-backdrop" aria-hidden="true"></div>
    <div class="confirm-card" id="confirm-card" data-lenis-prevent>
        <div class="confirm-icon" id="confirm-icon" aria-hidden="true"></div>
        <h3 class="confirm-title" id="confirm-title"></h3>
        <p class="confirm-message" id="confirm-message"></p>
        <div class="confirm-actions">
            <button type="button" class="confirm-btn confirm-btn--cancel" id="confirm-cancel">Batal</button>
            <button type="button" class="confirm-btn confirm-btn--confirm" id="confirm-ok">Konfirmasi</button>
        </div>
    </div>

    {{-- Glyph status dirender server-side oleh partial ikon (Lucide), lalu
         dipindahkan ke #confirm-icon sesuai `type`. Tiap glyph dibungkus
         .bk-i supaya ukurannya mengikuti font-size induk (1.5rem = 24px)
         dan tidak bergantung pada atribut width/height SVG. --}}
    <div id="confirm-icon-glyphs" hidden aria-hidden="true">
        <span data-type="warning"><span class="bk-i">@include('layouts.component._icon', ['name' => 'alert-triangle', 'size' => 24])</span></span>
        <span data-type="danger"><span class="bk-i">@include('layouts.component._icon', ['name' => 'x-circle', 'size' => 24])</span></span>
        <span data-type="info"><span class="bk-i">@include('layouts.component._icon', ['name' => 'info', 'size' => 24])</span></span>
        <span data-type="success"><span class="bk-i">@include('layouts.component._icon', ['name' => 'check', 'size' => 24])</span></span>
    </div>
</div>

<style>
/* Satu-satunya nilai warna di berkas ini adalah #fff untuk teks tombol;
   seluruh palet status, permukaan, jarak, radius, dan tipe mengikuti token. */
.confirm-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--bk-space-5);
    /* Ruang aman bawah supaya tombol tidak tertutup bilah gestur. */
    padding-bottom: calc(var(--bk-space-5) + env(safe-area-inset-bottom, 0px));
    overflow-y: auto;
    overscroll-behavior: contain;
}
.confirm-backdrop {
    position: absolute;
    inset: 0;
    background: var(--bk-scrim);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity var(--bk-dur-3, 260ms) var(--bk-ease, ease);
}
.confirm-backdrop.visible { opacity: 1; }
.confirm-card {
    position: relative;
    width: 100%;
    max-width: 340px;
    /* WebView lama mengabaikan dvh; nilai vh dulu sebagai cadangan. */
    max-height: calc(100vh - 40px);
    max-height: calc(100dvh - 40px);
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: var(--bk-space-6) var(--bk-space-5) var(--bk-space-5);
    border: 1px solid var(--bk-modal-line);
    border-radius: var(--bk-r-card, 20px);
    background: var(--bk-modal-bg);
    color: var(--bk-modal-ink);
    text-align: center;
    box-shadow: var(--bk-modal-shadow);
    transform: scale(.85) translateY(var(--bk-space-5));
    opacity: 0;
    transition: transform var(--bk-dur-3, 260ms) var(--bk-ease-spring, cubic-bezier(.34, 1.32, .64, 1)),
                opacity var(--bk-dur-3, 260ms) var(--bk-ease, ease);
}
.confirm-card.visible {
    transform: scale(1) translateY(0);
    opacity: 1;
}
.confirm-icon {
    display: grid;
    place-items: center;
    width: 56px;
    height: 56px;
    margin: 0 auto var(--bk-space-3);
    border: 2px solid transparent;
    border-radius: 50%;
    font-size: 1.5rem;
    line-height: 0;
}
.confirm-icon.type-danger { background: var(--bk-state-danger-soft); border-color: var(--bk-state-danger-line); color: var(--bk-state-danger-ink); }
.confirm-icon.type-warning { background: var(--bk-state-warning-soft); border-color: var(--bk-state-warning-line); color: var(--bk-state-warning-ink); }
.confirm-icon.type-info { background: var(--bk-state-info-soft); border-color: var(--bk-state-info-line); color: var(--bk-state-info-ink); }
.confirm-icon.type-success { background: var(--bk-state-success-soft); border-color: var(--bk-state-success-line); color: var(--bk-state-success-ink); }
.confirm-title {
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
    font-weight: 800;
    font-size: var(--bk-fs-lg);
    line-height: var(--bk-lh-tight);
    color: var(--bk-modal-ink);
    margin: 0 0 var(--bk-space-2);
    overflow-wrap: anywhere;
}
.confirm-message {
    font-size: var(--bk-fs-md);
    font-weight: 600;
    line-height: var(--bk-lh-snug);
    color: var(--bk-modal-ink-mid);
    margin: 0 0 var(--bk-space-5);
    overflow-wrap: anywhere;
}
.confirm-actions {
    display: flex;
    gap: var(--bk-space-2);
}
.confirm-btn {
    flex: 1 1 0;
    min-height: var(--bk-touch);
    min-width: 0;
    padding: var(--bk-space-3) var(--bk-space-4);
    border: 0;
    border-radius: var(--bk-r-control, 12px);
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
    font-size: var(--bk-fs-sm);
    font-weight: 700;
    line-height: var(--bk-lh-tight);
    color: var(--bk-modal-ink);
    background: transparent;
    cursor: pointer;
    touch-action: manipulation;
    transition: transform var(--bk-dur-1, 120ms) var(--bk-ease, ease), box-shadow var(--bk-dur-1, 120ms) var(--bk-ease, ease), filter var(--bk-dur-1, 120ms) var(--bk-ease, ease);
}
.confirm-btn:active { transform: scale(.97); }
.confirm-btn:focus-visible {
    outline: 3px solid var(--bk-focus-ring);
    outline-offset: 2px;
}
.confirm-btn--cancel {
    background: var(--bk-cancel-bg);
    color: var(--bk-cancel-ink);
    border: 1.5px solid var(--bk-cancel-line);
}
.confirm-btn--cancel:hover { background: var(--bk-cancel-bg-hover); }
.confirm-btn--confirm {
    color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .18);
}
.confirm-btn--confirm:hover { filter: brightness(1.08); }
.confirm-btn--confirm.type-danger { background: var(--bk-state-danger-solid); }
.confirm-btn--confirm.type-warning { background: var(--bk-state-warning-solid); }
.confirm-btn--confirm.type-info { background: var(--bk-state-info-solid); }
.confirm-btn--confirm.type-success { background: var(--bk-state-success-solid); }

/* Halaman terkunci selama dialog terbuka; dipasang/dilepas skrip. */
body.confirm-locked { overflow: hidden; }

@media (max-width: 340px) {
    .confirm-actions { flex-direction: column; }
}
@media (prefers-reduced-motion: reduce) {
    .confirm-backdrop, .confirm-card { transition: none; }
    .confirm-card { transform: none; }
}
</style>

<script>
(function() {
    var TYPES = ['danger', 'warning', 'info', 'success'];
    var _onConfirmCallback = null;
    var _lastFocus = null;
    var _paddingRight = '';
    var _open = false;
    var _locked = false;

    function byId(id) { return document.getElementById(id); }

    function glyphFor(type) {
        var store = byId('confirm-icon-glyphs');
        var node = store ? store.querySelector('[data-type="' + type + '"]') : null;
        return node ? node.innerHTML : '';
    }

    /* Kunci gulir: Lenis berhenti lewat penanda `lenis-stopped-lock`
       (dibaca berkah-motion.js), gulir asli dikunci oleh kelas di body.
       Padding kanan dikompensasi supaya bilah gulir yang hilang tidak
       menggeser tata letak di belakang dialog. */
    /* Isi halaman di belakang dialog harus tidak bisa dijangkau pembaca layar
       maupun Tab. `aria-modal` saja tidak cukup: sejumlah pembaca layar tetap
       menelusuri isi di belakangnya. Keadaan awal tiap simpul disimpan supaya
       atribut yang memang sudah ada (mis. aria-hidden dekoratif) dipulihkan
       apa adanya, bukan dihapus. */
    var _inerted = new WeakMap();

    function setPageInert(on) {
        var overlay = byId('confirm-modal-overlay');
        if (!overlay || !document.body) return;

        Array.prototype.forEach.call(document.body.children, function (node) {
            if (node === overlay || node.contains(overlay)) return;
            if (node.nodeName === 'SCRIPT' || node.nodeName === 'STYLE' || node.nodeName === 'LINK') return;

            if (on) {
                if (!_inerted.has(node)) {
                    _inerted.set(node, {
                        inert: node.hasAttribute('inert'),
                        hidden: node.getAttribute('aria-hidden')
                    });
                }
                node.setAttribute('inert', '');
                node.setAttribute('aria-hidden', 'true');
                return;
            }

            var before = _inerted.get(node);
            if (!before) return;
            if (!before.inert) node.removeAttribute('inert');
            if (before.hidden === null) node.removeAttribute('aria-hidden');
            else node.setAttribute('aria-hidden', before.hidden);
            _inerted.delete(node);
        });
    }

    function lockPage() {
        /* Dialog bisa dibuka ulang sebelum dialog lama selesai ditutup; kalau
           kunci dipasang dua kali, padding cadangan tertimpa dan sisa padding
           tertinggal di body setelah ditutup. */
        if (_locked) return;
        _locked = true;
        setPageInert(true);
        var gap = window.innerWidth - document.documentElement.clientWidth;
        _paddingRight = document.body.style.paddingRight;
        if (gap > 0) document.body.style.paddingRight = gap + 'px';
        document.body.classList.add('confirm-locked');
        document.documentElement.classList.add('lenis-stopped-lock');
    }

    function unlockPage() {
        if (!_locked) return;
        _locked = false;
        setPageInert(false);
        document.body.classList.remove('confirm-locked');
        document.documentElement.classList.remove('lenis-stopped-lock');
        document.body.style.paddingRight = _paddingRight;
        _paddingRight = '';
    }

    window.showConfirmModal = function(options) {
        options = options || {};

        var overlay = byId('confirm-modal-overlay');
        var backdrop = byId('confirm-backdrop');
        var card = byId('confirm-card');
        var icon = byId('confirm-icon');
        var title = byId('confirm-title');
        var message = byId('confirm-message');
        var cancelBtn = byId('confirm-cancel');
        var okBtn = byId('confirm-ok');

        if (!overlay || !card || !title || !message || !cancelBtn || !okBtn) return;

        // Nama tipe tak dikenal jatuh ke 'info', bukan kelas tanpa gaya.
        var type = TYPES.indexOf(options.type) >= 0 ? options.type : 'info';

        _lastFocus = document.activeElement;

        title.textContent = options.title || 'Konfirmasi';
        message.textContent = options.message || 'Apakah Anda yakin?';
        cancelBtn.textContent = options.cancelText || 'Batal';
        okBtn.textContent = options.confirmText || 'Ya, Lanjutkan';

        if (icon) {
            icon.className = 'confirm-icon type-' + type;
            icon.innerHTML = glyphFor(type) || glyphFor('info');
        }
        okBtn.className = 'confirm-btn confirm-btn--confirm type-' + type;

        _onConfirmCallback = typeof options.onConfirm === 'function' ? options.onConfirm : null;

        overlay.style.display = 'flex';
        backdrop.classList.remove('visible');
        card.classList.remove('visible');
        lockPage();
        _open = true;

        requestAnimationFrame(function() {
            backdrop.classList.add('visible');
            card.classList.add('visible');
        });

        // Fokus awal: aksi yang tidak merusak. Untuk tipe 'danger' fokus jatuh
        // ke Batal supaya Enter yang tak sengaja tidak menghapus.
        var initial = type === 'danger' ? cancelBtn : okBtn;
        try { initial.focus({ preventScroll: true }); } catch (e) { initial.focus(); }
    };

    function closeModal() {
        var overlay = byId('confirm-modal-overlay');
        var backdrop = byId('confirm-backdrop');
        var card = byId('confirm-card');

        if (!overlay || !_open) return;
        _open = false;

        backdrop.classList.remove('visible');
        card.classList.remove('visible');
        _onConfirmCallback = null;

        var finish = function() {
            // Dialog bisa dibuka lagi sebelum transisi tutup selesai; jangan
            // sampai timer lama menyembunyikan dialog yang baru terbuka.
            if (_open) return;
            overlay.style.display = 'none';
            unlockPage();
        };

        // Tunggu transisi tutup, tetapi jangan menggantung bila tab tak terlihat.
        var done = false;
        var once = function() { if (done) return; done = true; finish(); };
        card.addEventListener('transitionend', once, { once: true });
        setTimeout(once, 260);

        if (_lastFocus && document.contains(_lastFocus) && typeof _lastFocus.focus === 'function') {
            try { _lastFocus.focus({ preventScroll: true }); } catch (e) { _lastFocus.focus(); }
        }
        _lastFocus = null;
    }

    document.addEventListener('keydown', function(e) {
        if (!_open) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            closeModal();
            return;
        }

        if (e.key !== 'Tab') return;

        // Jebak fokus di dalam dialog: Batal <-> Konfirmasi.
        var cancelBtn = byId('confirm-cancel');
        var okBtn = byId('confirm-ok');
        if (!cancelBtn || !okBtn) return;

        var first = cancelBtn;
        var last = okBtn;

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        } else if (document.activeElement !== first && document.activeElement !== last) {
            e.preventDefault();
            first.focus();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        var cancelBtn = byId('confirm-cancel');
        var okBtn = byId('confirm-ok');
        var backdrop = byId('confirm-backdrop');

        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);

        if (okBtn) {
            okBtn.addEventListener('click', function() {
                var callback = _onConfirmCallback;
                closeModal();
                if (callback) callback();
            });
        }
    });
})();
</script>
