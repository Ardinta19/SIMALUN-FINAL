/* ==========================================================================
   BERKAH KREATIF — ADMIN INTERACTION & MOTION (berkah-admin.js)
   --------------------------------------------------------------------------
   Satu skrip untuk seluruh halaman admin:

     1. Reveal bertahap (IntersectionObserver, aman tanpa GSAP)
     2. Hitung angka naik (GSAP bila tersedia, fallback tanpa animasi)
     3. Bar kemajuan (lebar mengikuti data-adm-progress)
     4. Drawer & sidebar mobile (fokus terkurung, Esc, klik latar)
     5. Toast flash (tutup otomatis, jeda saat hover/fokus)
     6. Status topbar saat digulir + pintasan "/" ke kotak pencarian
     7. Keadaan memuat tombol kirim (anti klik ganda)

   Semua efek dihentikan saat prefers-reduced-motion: reduce.
   Tidak ada navigasi yang dicegat dan tidak ada form yang diubah namanya.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.documentElement;
    var reduce = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var reduced = !!(reduce && reduce.matches);
    var gsap = typeof window.gsap !== 'undefined' ? window.gsap : null;

    if (reduced) root.classList.add('adm-reduced');

    function on(el, type, fn, opts) {
        if (el) el.addEventListener(type, fn, opts || false);
    }

    function each(selector, scope, fn) {
        var list = (scope || document).querySelectorAll(selector);
        Array.prototype.forEach.call(list, fn);
    }

    /* ---------------------------------------------------------------------
       1. Reveal bertahap
       ------------------------------------------------------------------ */
    function markStagger(scope) {
        var groups = {};
        each('[data-adm-reveal]', scope, function (el) {
            var key = el.getAttribute('data-adm-reveal') || 'default';
            var index = groups[key] || 0;
            el.style.setProperty('--adm-i', Math.min(index, 12));
            groups[key] = index + 1;
        });
    }

    function reveal(scope) {
        var targets = (scope || document).querySelectorAll('[data-adm-reveal]:not(.is-in)');
        if (!targets.length) return;

        if (reduced || !('IntersectionObserver' in window)) {
            Array.prototype.forEach.call(targets, function (el) { el.classList.add('is-in'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-in');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });

        Array.prototype.forEach.call(targets, function (el) { observer.observe(el); });
    }

    /* ---------------------------------------------------------------------
       2. Hitung angka naik
       ------------------------------------------------------------------ */
    function format(value, decimals) {
        var fmt = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
        return fmt.format(value);
    }

    function countUp(scope) {
        each('[data-adm-count]', scope, function (el) {
            if (el.dataset.admCounted === '1') return;
            el.dataset.admCounted = '1';

            var to = parseFloat(el.getAttribute('data-to') || el.getAttribute('data-adm-count') || el.textContent.replace(/[^\d.,-]/g, '').replace(/\./g, '').replace(',', '.'));
            if (!isFinite(to)) return;

            var from = parseFloat(el.getAttribute('data-from') || '0');
            var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
            var prefix = el.getAttribute('data-prefix') || '';
            var suffix = el.getAttribute('data-suffix') || '';
            var duration = parseFloat(el.getAttribute('data-duration') || '1.1');

            var write = function (value) {
                el.textContent = prefix + format(value, decimals) + suffix;
            };

            if (reduced || !gsap) {
                write(to);
                return;
            }

            var state = { value: from };
            gsap.to(state, {
                value: to,
                duration: duration,
                ease: 'power3.out',
                onUpdate: function () { write(state.value); },
                onComplete: function () { write(to); }
            });
        });
    }

    /* ---------------------------------------------------------------------
       3. Bar kemajuan
       ------------------------------------------------------------------ */
    function bars(scope) {
        each('[data-adm-progress]', scope, function (bar) {
            var raw = parseFloat(bar.getAttribute('data-adm-progress'));
            if (!isFinite(raw)) return;
            var value = Math.max(0, Math.min(100, raw));
            var fill = bar.querySelector('.adm-bar__fill') || bar;
            fill.style.width = '0%';
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    fill.style.width = value + '%';
                    if (value >= 100) fill.classList.add('is-full');
                });
            });
        });
    }

    /* ---------------------------------------------------------------------
       4. Drawer, sidebar mobile, fokus terkurung
       ------------------------------------------------------------------ */
    var lastFocus = null;

    function focusables(container) {
        return container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
    }

    function openLayer(layer, backdrop) {
        if (!layer) return;
        lastFocus = document.activeElement;
        layer.setAttribute('data-open', 'true');
        layer.removeAttribute('aria-hidden');
        if (backdrop) backdrop.setAttribute('data-open', 'true');
        document.body.classList.add('adm-layer-open');
        var list = focusables(layer);
        if (list.length) list[0].focus({ preventScroll: true });
    }

    function closeLayer(layer, backdrop) {
        if (!layer || layer.getAttribute('data-open') !== 'true') return;
        layer.setAttribute('data-open', 'false');
        layer.setAttribute('aria-hidden', 'true');
        if (backdrop) backdrop.setAttribute('data-open', 'false');
        document.body.classList.remove('adm-layer-open');
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus({ preventScroll: true });
    }

    function layers() {
        var backdrop = document.querySelector('.adm-backdrop');

        // Sidebar mobile
        var sidebar = document.querySelector('.adm-sidebar');

        var syncSidebar = function () {
            if (!sidebar) return;
            var isOpen = sidebar.getAttribute('data-open') === 'true';
            var mobile = window.innerWidth <= 700;
            if (!mobile) {
                sidebar.removeAttribute('aria-hidden');
            } else if (!isOpen) {
                sidebar.setAttribute('aria-hidden', 'true');
            } else {
                sidebar.removeAttribute('aria-hidden');
            }
            /* Ada dua tombol pembuka: topbar dan slot "Lainnya" di bilah bawah. */
            each('[data-adm-nav-toggle]', document, function (btn) {
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        };

        each('[data-adm-nav-toggle]', document, function (btn) {
            on(btn, 'click', function () {
                if (sidebar.getAttribute('data-open') === 'true') closeLayer(sidebar, backdrop);
                else openLayer(sidebar, backdrop);
                syncSidebar();
            });
        });

        // Drawer umum
        each('[data-adm-open]', document, function (btn) {
            on(btn, 'click', function (event) {
                event.preventDefault();
                var id = btn.getAttribute('data-adm-open');
                openLayer(document.getElementById(id), backdrop);
            });
        });

        each('[data-adm-close]', document, function (btn) {
            on(btn, 'click', function (event) {
                event.preventDefault();
                closeLayer(btn.closest('.adm-drawer, .adm-sidebar'), backdrop);
            });
        });

        on(backdrop, 'click', function () {
            closeLayer(document.querySelector('.adm-drawer[data-open="true"]'), backdrop);
            closeLayer(sidebar, backdrop);
            syncSidebar();
        });

        // Tutup sidebar setelah memilih menu di layar kecil.
        each('.adm-sidebar a', document, function (link) {
            on(link, 'click', function () {
                if (window.innerWidth <= 700) {
                    closeLayer(sidebar, backdrop);
                    syncSidebar();
                }
            });
        });

        on(document, 'keydown', function (event) {
            if (event.key !== 'Escape') return;
            var openDrawer = document.querySelector('.adm-drawer[data-open="true"]');
            if (openDrawer) return closeLayer(openDrawer, backdrop);
            if (sidebar && sidebar.getAttribute('data-open') === 'true') {
                closeLayer(sidebar, backdrop);
                syncSidebar();
            }
        });

        // Kurung fokus di dalam lapisan yang terbuka.
        on(document, 'keydown', function (event) {
            if (event.key !== 'Tab') return;
            var layer = document.querySelector('.adm-drawer[data-open="true"]') || (sidebar && sidebar.getAttribute('data-open') === 'true' ? sidebar : null);
            if (!layer) return;
            var list = Array.prototype.filter.call(focusables(layer), function (el) { return el.offsetParent !== null; });
            if (!list.length) return;
            var first = list[0];
            var last = list[list.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault(); last.focus({ preventScroll: true });
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault(); first.focus({ preventScroll: true });
            }
        });

        var onResize = function () {
            if (window.innerWidth > 700 && sidebar) closeLayer(sidebar, backdrop);
            syncSidebar();
        };
        on(window, 'resize', onResize, { passive: true });
        syncSidebar();
    }

    /* ---------------------------------------------------------------------
       5. Toast
       ------------------------------------------------------------------ */
    function dismiss(toast) {
        if (!toast || toast.classList.contains('is-leaving')) return;
        toast.classList.add('is-leaving');
        window.setTimeout(function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 280);
    }

    function toasts(scope) {
        each('[data-adm-toast]', scope, function (toast) {
            var life = parseInt(toast.getAttribute('data-adm-life') || '6000', 10);
            toast.style.setProperty('--adm-toast-life', life + 'ms');

            var timer = null;
            var start = function () {
                if (reduced) return;
                timer = window.setTimeout(function () { dismiss(toast); }, life);
            };
            var pause = function () {
                if (timer) { window.clearTimeout(timer); timer = null; }
            };

            start();
            on(toast, 'mouseenter', pause);
            on(toast, 'mouseleave', start);
            on(toast, 'focusin', pause);
            on(toast, 'focusout', start);
            each('[data-adm-toast-close]', toast, function (btn) {
                on(btn, 'click', function (event) {
                    event.preventDefault(); pause(); dismiss(toast);
                });
            });
        });
    }

    /* ---------------------------------------------------------------------
       6. Topbar + pintasan pencarian
       ------------------------------------------------------------------ */
    function chrome() {
        var topbar = document.querySelector('.adm-topbar');
        if (topbar) {
            var apply = function () {
                if (window.scrollY > 4) topbar.classList.add('is-scrolled');
                else topbar.classList.remove('is-scrolled');
            };
            apply();
            on(window, 'scroll', apply, { passive: true });
        }

        on(document, 'keydown', function (event) {
            if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) return;
            var tag = (document.activeElement && document.activeElement.tagName) || '';
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (document.activeElement && document.activeElement.isContentEditable)) return;
            var input = document.querySelector('.adm-app [data-adm-search], .adm-app input[type="search"], .adm-app input[name="q"]');
            if (!input) return;
            event.preventDefault();
            input.focus();
            if (typeof input.select === 'function') input.select();
        });
    }

    /* ---------------------------------------------------------------------
       7. Keadaan memuat formulir
       ------------------------------------------------------------------ */
    function forms() {
        each('.adm-app form', document, function (form) {
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method !== 'post' || form.hasAttribute('data-adm-no-loading')) return;

            on(form, 'submit', function (event) {
                // Klik kedua saat pengiriman pertama berjalan: batalkan,
                // supaya data tidak terkirim dua kali.
                if (form.dataset.admSubmitting === '1') {
                    event.preventDefault();
                    return;
                }

                var submitter = form.querySelector('button[type="submit"]:not(.adm-btn--quiet), button:not([type]):not(.adm-btn--quiet)');
                if (!submitter) return;
                form.dataset.admSubmitting = '1';
                submitter.classList.add('is-loading');
                submitter.setAttribute('aria-busy', 'true');
                // Jangan biarkan tombol macet bila unggahan ditolak peramban.
                window.setTimeout(function () {
                    if (form.dataset.admSubmitting === '1' && document.body.contains(form) && !form.checkValidity()) {
                        form.dataset.admSubmitting = '0';
                        submitter.classList.remove('is-loading');
                        submitter.removeAttribute('aria-busy');
                    }
                }, 12000);
            });

            // Kembalikan keadaan bila halaman dipulihkan dari cache riwayat.
            on(window, 'pageshow', function () {
                form.dataset.admSubmitting = '0';
                each('.is-loading', form, function (btn) {
                    btn.classList.remove('is-loading');
                    btn.removeAttribute('aria-busy');
                });
            });
        });
    }

    /* ---------------------------------------------------------------------
       Inisialisasi
       ------------------------------------------------------------------ */
    function init(scope) {
        var area = scope || document;
        try {
            markStagger(area);
            reveal(area);
            countUp(area);
            bars(area);
            toasts(area);
        } catch (error) {
            // Jangan pernah membiarkan konten tersembunyi karena satu galat.
            each('[data-adm-reveal]', area, function (el) { el.classList.add('is-in'); });
            if (window.console && console.warn) console.warn('[adm] animasi dilewati:', error);
        }
    }

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    ready(function () {
        init(document);
        layers();
        chrome();
        forms();

        // Jaring aman: bila observer/GSAP gagal, semua tetap terlihat.
        window.setTimeout(function () {
            each('[data-adm-reveal]:not(.is-in)', document, function (el) { el.classList.add('is-in'); });
        }, 1600);

        if (reduce && typeof reduce.addEventListener === 'function') {
            reduce.addEventListener('change', function (event) {
                reduced = event.matches;
                root.classList.toggle('adm-reduced', reduced);
                if (reduced) {
                    each('[data-adm-reveal]', document, function (el) { el.classList.add('is-in'); });
                }
            });
        }
    });

    window.BKAdmin = {
        init: init,
        /* Ikon toast memakai geometri Lucide yang sama dengan partial Blade.
           Disimpan sebagai string kecil di sini supaya toast yang dibuat saat
           runtime tidak perlu memuat ulang berkas ikon. */
        toastGlyph: function (variant) {
            var paths = {
                ok: '<polyline points="20 6 9 17 4 12"/>',
                danger: '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/>',
                warn: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>'
            };
            return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" '
                + 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                + (paths[variant] || paths.ok) + '</svg>';
        },
        toast: function (message, options) {
            var host = document.querySelector('.adm-toasts');
            if (!host) return null;
            var opts = options || {};
            var variant = opts.variant || 'ok';
            var node = document.createElement('div');
            node.className = 'adm-toast adm-toast--' + variant;
            node.setAttribute('data-adm-toast', '');
            node.setAttribute('role', 'status');
            node.innerHTML = '<span class="adm-toast__icon" aria-hidden="true">' + window.BKAdmin.toastGlyph(variant) + '</span>'
                + '<div class="adm-toast__body">' + (opts.title ? '<strong class="adm-toast__title">' + opts.title + '</strong>' : '') + String(message || '') + '</div>'
                + '<button type="button" class="adm-toast__close" data-adm-toast-close aria-label="Tutup pemberitahuan">&times;</button>'
                + '<span class="adm-toast__bar" aria-hidden="true"></span>';
            host.appendChild(node);
            toasts(host);
            return node;
        }
    };
})();
