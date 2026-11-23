/*!
 * Berkah Kreatif — Form Controls engine
 * ------------------------------------------------------------------
 * Progressive enhancement untuk SEMUA kontrol form native:
 *
 *   <select>                     → listbox/popover (sheet di ponsel)
 *   <select data-fc-segmented>   → segmented control (2–4 opsi)
 *   <input type="date">          → kalender Indonesia
 *   <input type="number" data-fc-stepper> → stepper
 *
 * Nilai, nama field, dan format yang dikirim TIDAK berubah: kontrol
 * native tetap berada di DOM (disembunyikan secara visual).
 *
 * Publik:
 *   window.initFormControls(root = document)
 *   window.fcPick({ anchor, title, subtitle, options, selected, onSelect })
 *
 * Animasi memakai GSAP lokal (public/vendor/gsap) bila tersedia dan
 * selalu menghormati prefers-reduced-motion.
 */
(function () {
    'use strict';

    var root = document.documentElement;
    var MONTHS_ID = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var DOW_SHORT = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    var DOW_FULL = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var MOBILE_QUERY = '(max-width: 720px)';
    var uidCounter = 0;

    root.classList.add('fc-ui');

    /* ================================================================
       Helper dasar
       ================================================================ */

    function el(tag, cls, attrs) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (attrs) {
            Object.keys(attrs).forEach(function (key) {
                if (attrs[key] !== null && attrs[key] !== undefined && attrs[key] !== false) {
                    node.setAttribute(key, attrs[key] === true ? '' : attrs[key]);
                }
            });
        }
        return node;
    }

    function uid(prefix) {
        uidCounter += 1;
        return prefix + '-' + uidCounter;
    }

    function fire(node, type) {
        node.dispatchEvent(new Event(type, { bubbles: true }));
    }

    function each(scope, selector, fn) {
        if (!scope) return;
        var list = [];
        if (scope.querySelectorAll) {
            list = Array.prototype.slice.call(scope.querySelectorAll(selector));
        }
        if (scope.matches && scope.matches(selector)) list.unshift(scope);
        list.forEach(fn);
    }

    function cssEscape(value) {
        if (window.CSS && window.CSS.escape) return window.CSS.escape(value);
        return String(value).replace(/["\\]/g, '\\$&');
    }

    function isMobile() {
        return !!(window.matchMedia && window.matchMedia(MOBILE_QUERY).matches);
    }

    function reducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function hasGsap() {
        return !!(window.gsap && typeof window.gsap.to === 'function');
    }

    function focusQuiet(node) {
        if (!node || typeof node.focus !== 'function') return;
        try {
            node.focus({ preventScroll: true });
        } catch (e) {
            node.focus();
        }
    }

    var CONTROL_TAGS = /^(SELECT|INPUT|TEXTAREA|BUTTON|OPTION|OPTGROUP|SVG|IMG|SCRIPT|STYLE)$/;

    /* Teks label tanpa isi kontrol/opsi di dalamnya (untuk label pembungkus). */
    function ownLabelText(label) {
        var text = '';
        Array.prototype.forEach.call(label.childNodes, function (child) {
            if (child.nodeType === 3) {
                text += child.nodeValue;
            } else if (child.nodeType === 1 && !CONTROL_TAGS.test(child.tagName)) {
                text += child.textContent;
            }
        });
        return text.replace(/\s+/g, ' ').trim();
    }

    function labelText(node) {
        var aria = (node.getAttribute('aria-label') || '').trim();
        if (aria) return aria;
        var id = node.id;
        if (id) {
            var lbl = document.querySelector('label[for="' + cssEscape(id) + '"]');
            if (lbl) {
                var t = ownLabelText(lbl) || lbl.textContent.trim();
                if (t) return t;
            }
        }
        var wrapping = node.closest ? node.closest('label') : null;
        if (wrapping) {
            var wt = ownLabelText(wrapping);
            if (wt) return wt;
        }
        return (node.getAttribute('name') || '').replace(/[_-]+/g, ' ').trim();
    }

    function cleanTitle(text) {
        return String(text || '')
            .replace(/\(\s*wajib\s*\)/gi, '')
            .replace(/\*/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function resolveTitle(node, fallback) {
        var explicit = (node.getAttribute('data-fc-title') || '').trim();
        if (explicit) return explicit;
        var fromLabel = cleanTitle(labelText(node));
        if (fromLabel) {
            return fromLabel.charAt(0).toUpperCase() + fromLabel.slice(1);
        }
        return fallback || 'Pilih';
    }

    function svgChevron() {
        return '<svg class="fc-trigger__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
    }

    function svgCalendar() {
        return '<svg class="fc-trigger__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M16 2v4M8 2v4M3 9.5h18"/></svg>';
    }

    function svgCheck() {
        return '<svg class="fc-opt__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>';
    }

    /* ================================================================
       Kunci scroll (tanpa lompatan)
       ================================================================ */

    var lock = { count: 0, y: 0, htmlBehavior: '' };

    function lockScroll() {
        lock.count += 1;
        if (lock.count > 1) return;
        var body = document.body;
        lock.y = window.pageYOffset || root.scrollTop || 0;
        lock.htmlBehavior = root.style.scrollBehavior;
        root.style.scrollBehavior = 'auto';
        body.style.position = 'fixed';
        body.style.top = '-' + lock.y + 'px';
        body.style.left = '0';
        body.style.right = '0';
        body.style.width = '100%';
        body.style.overflow = 'hidden';
        body.classList.add('fc-scroll-lock');
    }

    function unlockScroll() {
        if (lock.count === 0) return;
        lock.count -= 1;
        if (lock.count > 0) return;
        var body = document.body;
        body.classList.remove('fc-scroll-lock');
        body.style.position = '';
        body.style.top = '';
        body.style.left = '';
        body.style.right = '';
        body.style.width = '';
        body.style.overflow = '';
        try {
            window.scrollTo(0, lock.y);
        } catch (e) {
            root.scrollTop = lock.y;
        }
        root.style.scrollBehavior = lock.htmlBehavior || '';
    }

    /* ================================================================
       Layer (popover desktop / sheet ponsel) — inti animasi GSAP
       ================================================================ */

    var activeLayer = null;

    function closeActiveLayer() {
        if (activeLayer) {
            activeLayer.close({ restoreFocus: false });
        }
    }

    function openLayer(opts) {
        opts = opts || {};
        closeActiveLayer();

        var mobile = opts.forceSheet === true || (opts.forceSheet !== false && isMobile());
        var trigger = opts.anchor || null;
        var returnTo = trigger || document.activeElement;
        var titleId = uid('fc-layer-title');
        var descId = opts.subtitle ? uid('fc-layer-sub') : null;

        var overlay = el('div', 'fc-layer ' + (mobile ? 'fc-layer--sheet' : 'fc-layer--pop'));
        var backdrop = el('div', 'fc-backdrop');
        var panel = el('div', mobile ? 'fc-sheet' : 'fc-pop', {
            role: 'dialog',
            'aria-modal': mobile ? 'true' : 'false',
            'aria-labelledby': titleId
        });
        if (descId) panel.setAttribute('aria-describedby', descId);
        panel.id = uid('fc-layer');

        if (mobile) panel.appendChild(el('div', 'fc-sheet__grip'));

        var head = el('div', 'fc-layer__head');
        var heading = el('div', 'fc-layer__heading');
        var title = el('h3', 'fc-layer__title');
        title.id = titleId;
        title.textContent = opts.title || 'Pilih';
        heading.appendChild(title);
        if (opts.subtitle) {
            var sub = el('p', 'fc-layer__sub');
            sub.id = descId;
            sub.textContent = opts.subtitle;
            heading.appendChild(sub);
        }
        var closeBtn = el('button', 'fc-layer__close', { type: 'button', 'aria-label': 'Tutup' });
        closeBtn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
        head.appendChild(heading);
        head.appendChild(closeBtn);

        var body = el('div', 'fc-layer__body');
        panel.appendChild(head);
        panel.appendChild(body);
        overlay.appendChild(backdrop);
        overlay.appendChild(panel);

        /* Dialog <dialog> hidup di top layer; layer harus ikut di dalamnya
           agar tidak menjadi inert saat filter dibuka dengan showModal(). */
        var host = document.body;
        if (trigger && trigger.closest) {
            var dialogHost = trigger.closest('dialog');
            if (dialogHost && dialogHost.open) host = dialogHost;
        }
        host.appendChild(overlay);

        var locked = mobile && host === document.body;
        var closed = false;
        var api = { panel: panel, body: body, close: close };

        function cleanup() {
            document.removeEventListener('keydown', onDocKey);
            window.removeEventListener('scroll', onViewportChange, true);
            window.removeEventListener('resize', onViewportChange);
            if (locked) unlockScroll();
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            if (trigger && trigger.setAttribute && trigger.hasAttribute && trigger.hasAttribute('aria-expanded')) {
                trigger.setAttribute('aria-expanded', 'false');
            }
            if (activeLayer && activeLayer.overlay === overlay) activeLayer = null;
            if (opts.onClose) opts.onClose();
        }

        function animateOut(done) {
            if (!hasGsap() || reducedMotion()) {
                done();
                return;
            }
            var gsap = window.gsap;
            gsap.killTweensOf([panel, backdrop]);
            gsap.to(backdrop, { opacity: 0, duration: 0.16, ease: 'power1.out' });
            if (mobile) {
                gsap.to(panel, {
                    yPercent: 100,
                    duration: 0.24,
                    ease: 'power2.in',
                    onComplete: done
                });
            } else {
                gsap.to(panel, {
                    opacity: 0,
                    y: 6,
                    scale: 0.98,
                    duration: 0.16,
                    ease: 'power1.in',
                    onComplete: done
                });
            }
        }

        function close(options) {
            if (closed) return;
            closed = true;
            options = options || {};
            var restore = options.restoreFocus !== false;
            var focusInside = overlay.contains(document.activeElement) || document.activeElement === document.body;
            animateOut(function () {
                cleanup();
                if (restore && focusInside) focusQuiet(returnTo);
            });
        }

        function animateIn() {
            if (mobile) {
                var animY = panel.offsetHeight > 0 ? panel.offsetHeight : window.innerHeight * 0.6;
                if (hasGsap() && !reducedMotion()) {
                    var gsap = window.gsap;
                    gsap.fromTo(backdrop, { opacity: 0 }, { opacity: 1, duration: 0.22, ease: 'power1.out' });
                    gsap.fromTo(panel, { y: animY }, { y: 0, duration: 0.42, ease: 'power3.out' });
                    var items = panel.querySelectorAll('.fc-opt, .fc-cal__day, .fc-cal__head, .fc-cal__foot');
                    if (items.length) {
                        gsap.fromTo(items, { y: 12, opacity: 0 }, {
                            y: 0,
                            opacity: 1,
                            duration: 0.32,
                            delay: 0.08,
                            stagger: 0.012,
                            ease: 'power2.out',
                            clearProps: 'transform,opacity'
                        });
                    }
                } else {
                    backdrop.classList.add('fc-anim-backdrop');
                    panel.classList.add('fc-anim-panel');
                }
            } else {
                if (hasGsap() && !reducedMotion()) {
                    var g = window.gsap;
                    g.fromTo(panel, { y: 10, scale: 0.97, opacity: 0, transformOrigin: 'top center' }, {
                        y: 0,
                        scale: 1,
                        opacity: 1,
                        duration: 0.22,
                        ease: 'power2.out'
                    });
                } else {
                    panel.classList.add('fc-anim-panel');
                }
            }
        }

        function place() {
            if (mobile) return;
            var anchor = trigger;
            if (!anchor || anchor === document.body || anchor === root || !anchor.getBoundingClientRect) {
                var widthCenter = Math.max(220, Math.min(360, root.clientWidth - 24));
                panel.style.width = widthCenter + 'px';
                panel.style.left = Math.round((root.clientWidth - widthCenter) / 2) + 'px';
                panel.style.top = Math.round(window.innerHeight * 0.18) + 'px';
                return;
            }
            var rect = anchor.getBoundingClientRect();
            var vw = root.clientWidth || window.innerWidth;
            var vh = window.innerHeight;
            var margin = 10;
            var gap = 8;
            var width = Math.max(220, Math.min(360, rect.width));
            panel.style.width = width + 'px';
            var height = panel.offsetHeight;
            var left = Math.min(Math.max(margin, rect.left), Math.max(margin, vw - width - margin));
            var top = rect.bottom + gap;
            if (top + height > vh - margin) {
                var above = rect.top - height - gap;
                top = above >= margin ? above : Math.max(margin, vh - height - margin);
            }
            panel.style.left = Math.round(left) + 'px';
            panel.style.top = Math.round(top) + 'px';
            var offscreen = rect.bottom < 0 || rect.top > vh;
            if (offscreen) close({ restoreFocus: false });
        }

        var rafPending = false;
        function onViewportChange() {
            if (rafPending) return;
            rafPending = true;
            window.requestAnimationFrame(function () {
                rafPending = false;
                if (!closed) place();
            });
        }

        function onDocKey(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
                return;
            }
            if (event.key !== 'Tab') return;
            var focusables = panel.querySelectorAll('button:not([disabled]), [href], input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
            if (!focusables.length) return;
            var first = focusables[0];
            var last = focusables[focusables.length - 1];
            if (event.shiftKey && (document.activeElement === first || !panel.contains(document.activeElement))) {
                event.preventDefault();
                focusQuiet(last);
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                focusQuiet(first);
            }
        }

        backdrop.addEventListener('click', function () { close(); });
        closeBtn.addEventListener('click', function () { close(); });
        document.addEventListener('keydown', onDocKey);
        window.addEventListener('scroll', onViewportChange, true);
        window.addEventListener('resize', onViewportChange);

        if (locked) lockScroll();

        if (trigger && trigger.setAttribute) {
            trigger.setAttribute('aria-expanded', 'true');
            if (!trigger.hasAttribute('aria-controls')) {
                trigger.setAttribute('aria-controls', panel.id);
            }
        }

        if (host !== document.body) {
            host.addEventListener('close', function onHostClose() {
                host.removeEventListener('close', onHostClose);
                close({ restoreFocus: false });
            });
        }

        activeLayer = { overlay: overlay, close: close };

        opts.build(body, api);

        if (!mobile) {
            place();
            window.requestAnimationFrame(place);
        }
        animateIn();

        var initial = panel.querySelector('[data-autofocus]') || panel.querySelector('[aria-selected="true"]') || panel.querySelector('.fc-cal__day.is-selected') || panel.querySelector('.fc-cal__day:not(:disabled)') || panel.querySelector('.fc-opt:not([disabled])') || closeBtn;
        if (initial) focusQuiet(initial);

        return api;
    }

    /* ================================================================
       Native value hook — sinkron walau diubah lewat JavaScript
       ================================================================ */

    function hookValue(node, sync, proto) {
        try {
            var descriptor = Object.getOwnPropertyDescriptor(proto, 'value');
            if (!descriptor || !descriptor.set) return;
            var own = Object.getOwnPropertyDescriptor(node, 'value');
            if (own && own.set && own.__fcHooked) return;
            var setter = function (value) {
                descriptor.set.call(this, value);
                try {
                    sync();
                } catch (e) { /* jangan ganggu pemanggil */ }
            };
            setter.__fcHooked = true;
            Object.defineProperty(node, 'value', {
                configurable: true,
                enumerable: true,
                get: function () { return descriptor.get.call(this); },
                set: setter
            });
        } catch (e) { /* browser lama: abaikan */ }
    }

    function hookFocus(node, trigger) {
        node.addEventListener('focus', function () {
            if (node.classList.contains('fc-native-hidden')) {
                focusQuiet(trigger);
            }
        });
    }

    function bindLabel(node, trigger) {
        if (!node.id) return;
        each(document, 'label[for="' + cssEscape(node.id) + '"]', function (lbl) {
            lbl.addEventListener('click', function (event) {
                event.preventDefault();
                focusQuiet(trigger);
            });
        });
    }

    /* Validasi required: kontrol native kehilangan atribut required agar
       browser tidak mencoba memfokuskan elemen tersembunyi. Kita validasi
       sendiri SEBELUM handler submit halaman berjalan (fase capture). */
    function takeRequired(node) {
        if (!node.required && node.getAttribute('aria-required') !== 'true') return;
        node.setAttribute('data-fc-required', '1');
        node.required = false;
        node.setAttribute('aria-required', 'true');
    }

    var controls = [];

    function registerControl(node, trigger, sync) {
        node.setAttribute('data-fc-enhanced', '1');
        node.__fcSync = sync;

        var errorId = uid('fc-error');
        var error = el('p', 'fc-error', { id: errorId, hidden: true, role: 'alert' });
        trigger.insertAdjacentElement('afterend', error);

        var described = (node.getAttribute('aria-describedby') || '').trim();
        trigger.setAttribute('aria-describedby', [described, errorId].filter(Boolean).join(' '));
        if (node.getAttribute('data-fc-required') === '1') {
            trigger.setAttribute('aria-required', 'true');
        }

        function setError(message) {
            if (message) {
                error.textContent = message;
                error.hidden = false;
                trigger.setAttribute('aria-invalid', 'true');
            } else {
                error.textContent = '';
                error.hidden = true;
                trigger.removeAttribute('aria-invalid');
            }
        }

        node.__fcSetError = setError;
        controls.push({ node: node, trigger: trigger, setError: setError, sync: sync });

        node.addEventListener('change', function () { setError(''); });
        return setError;
    }

    function errorMessage(node, trigger) {
        var title = node.getAttribute('data-fc-title') || cleanTitle(labelText(node)) || 'Kolom ini';
        return 'Pilih ' + String(title).toLowerCase() + ' terlebih dahulu.';
    }

    function visible(node) {
        if (node.disabled) return false;
        if (node.type === 'hidden') return false;
        if (node.closest && node.closest('[hidden]')) return false;
        if (node.offsetParent === null && !node.getClientRects().length) return false;
        return true;
    }

    function focusableTarget(trigger) {
        if (!trigger) return null;
        if (trigger.matches && trigger.matches('button, [href], input, select, textarea, [tabindex]')) return trigger;
        return trigger.querySelector('button:not([disabled]), [tabindex="0"]') || trigger;
    }

    function validateForm(form) {
        var firstBad = null;
        each(form, '[data-fc-required="1"]', function (node) {
            if (firstBad) return;
            if (!visible(node)) return;
            var value = node.value === null || node.value === undefined ? '' : String(node.value).trim();
            if (value !== '') return;
            firstBad = node;
        });
        if (!firstBad) return true;

        var trigger = firstBad.__fcTrigger || null;
        var message = firstBad.__fcSetError ? errorMessage(firstBad, trigger) : '';
        if (firstBad.__fcSetError) firstBad.__fcSetError(message);
        else if (trigger) trigger.setAttribute('aria-invalid', 'true');

        if (trigger) {
            var focusTarget = focusableTarget(trigger);
            focusQuiet(focusTarget);
            if (typeof focusTarget.scrollIntoView === 'function') {
                try {
                    focusTarget.scrollIntoView({ block: 'center', behavior: reducedMotion() ? 'auto' : 'smooth' });
                } catch (e) { /* browser lama: lewati animasi scroll */ }
            }
        }
        return false;
    }

    function validateRangeConflicts(form) {
        var bad = null;
        controls.forEach(function (control) {
            if (bad) return;
            var node = control.node;
            if ((node.__fcRangeError || node.__fcValueError || node.__fcOrderError) && form.contains(node)) bad = node;
        });
        if (!bad) return true;
        var trigger = bad.__fcTrigger || null;
        if (trigger) {
            var target = focusableTarget(trigger);
            focusQuiet(target);
            if (typeof target.scrollIntoView === 'function') {
                try {
                    target.scrollIntoView({ block: 'center', behavior: reducedMotion() ? 'auto' : 'smooth' });
                } catch (e) { /* browser lama: lewati animasi scroll */ }
            }
        }
        return false;
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || form.nodeName !== 'FORM') return;
        if (!validateRangeConflicts(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        if (!form.querySelector('[data-fc-required="1"]')) return;
        if (!validateForm(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    document.addEventListener('reset', function (event) {
        var form = event.target;
        if (!form || form.nodeName !== 'FORM') return;
        window.setTimeout(function () {
            controls.forEach(function (control) {
                if (form.contains(control.node)) {
                    control.sync();
                    control.setError('');
                }
            });
        }, 0);
    }, true);

    window.addEventListener('pageshow', function () {
        controls.forEach(function (control) { control.sync(); });
    });

    /* ================================================================
       1. Select → trigger + listbox
       ================================================================ */

    function enhanceSelect(select) {
        if (!select || select.getAttribute('data-fc-enhanced') === '1') return;
        if (select.multiple) return;

        var title = resolveTitle(select, 'Pilih opsi');
        var placeholder = (select.getAttribute('data-fc-placeholder') || '').trim();
        var trigger = el('button', 'fc-trigger', {
            type: 'button',
            'aria-haspopup': 'listbox',
            'aria-expanded': 'false',
            'aria-label': title
        });
        var text = el('span', 'fc-trigger__text');
        trigger.appendChild(text);
        trigger.insertAdjacentHTML('beforeend', svgChevron());

        function optionAt() {
            return select.options[select.selectedIndex] || null;
        }

        function syncTrigger() {
            var opt = optionAt();
            var value = select.value === null || select.value === undefined ? '' : String(select.value);
            if (!opt) {
                text.textContent = placeholder || 'Pilih';
                trigger.classList.add('is-placeholder');
            } else if (value === '' && placeholder) {
                text.textContent = placeholder;
                trigger.classList.add('is-placeholder');
            } else {
                var label = opt.textContent.trim() || placeholder || 'Pilih';
                text.textContent = label;
                trigger.classList.toggle('is-placeholder', value === '' && !opt.textContent.trim());
            }
        }

        function buildList(body, api) {
            var list = el('div', 'fc-list', { role: 'listbox', 'aria-label': title, tabindex: '-1' });
            var options = [];

            function addOption(opt) {
                var value = String(opt.value);
                var label = opt.textContent.trim() || placeholder || 'Pilih';
                var btn = el('button', 'fc-opt', {
                    type: 'button',
                    role: 'option',
                    'aria-selected': String(select.value) === value ? 'true' : 'false'
                });
                if (opt.disabled) {
                    btn.disabled = true;
                    btn.setAttribute('aria-disabled', 'true');
                }
                var wrap = el('span', 'fc-opt__body');
                var lbl = el('span', 'fc-opt__label');
                lbl.textContent = label;
                wrap.appendChild(lbl);
                btn.appendChild(wrap);
                btn.insertAdjacentHTML('beforeend', svgCheck());

                btn.addEventListener('click', function () {
                    if (opt.disabled) return;
                    select.value = opt.value;
                    syncTrigger();
                    fire(select, 'input');
                    fire(select, 'change');
                    if (select.__fcSetError) select.__fcSetError('');
                    api.close({ restoreFocus: true });
                });

                options.push(btn);
                list.appendChild(btn);
            }

            Array.prototype.forEach.call(select.children, function (child) {
                if (child.tagName === 'OPTGROUP') {
                    var group = el('div', 'fc-group', { role: 'presentation' });
                    group.textContent = child.label || '';
                    list.appendChild(group);
                    Array.prototype.forEach.call(child.children, function (opt) {
                        if (opt.tagName === 'OPTION') addOption(opt);
                    });
                } else if (child.tagName === 'OPTION') {
                    addOption(child);
                }
            });

            body.appendChild(list);

            list.addEventListener('keydown', function (event) {
                var enabled = options.filter(function (btn) { return !btn.disabled; });
                var index = enabled.indexOf(document.activeElement);
                var next = null;
                if (event.key === 'ArrowDown') next = enabled[(index + 1) % enabled.length];
                else if (event.key === 'ArrowUp') next = enabled[(index - 1 + enabled.length) % enabled.length];
                else if (event.key === 'Home') next = enabled[0];
                else if (event.key === 'End') next = enabled[enabled.length - 1];
                else if (event.key.length === 1 && /\S/.test(event.key)) {
                    var char = event.key.toLowerCase();
                    next = enabled.find(function (btn, i) {
                        return i > index && btn.textContent.trim().toLowerCase().charAt(0) === char;
                    }) || enabled.find(function (btn) {
                        return btn.textContent.trim().toLowerCase().charAt(0) === char;
                    });
                }
                if (next) {
                    event.preventDefault();
                    focusQuiet(next);
                }
            });

            var selected = list.querySelector('[aria-selected="true"]:not([disabled])');
            if (selected) selected.setAttribute('data-autofocus', '');
        }

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (trigger.disabled) return;
            openLayer({ anchor: trigger, title: title, build: buildList });
        });

        trigger.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                trigger.click();
            }
        });

        select.classList.add('fc-native-hidden');
        select.setAttribute('aria-hidden', 'true');
        select.setAttribute('tabindex', '-1');
        select.parentNode.insertBefore(trigger, select.nextSibling);

        takeRequired(select);
        registerControl(select, trigger, syncTrigger);
        select.__fcTrigger = trigger;
        hookValue(select, syncTrigger, window.HTMLSelectElement ? HTMLSelectElement.prototype : Object.getPrototypeOf(select));
        hookFocus(select, trigger);
        bindLabel(select, trigger);
        mirrorDisabled(select, trigger);
        select.addEventListener('change', syncTrigger);
        syncTrigger();
    }

    /* ================================================================
       2. Segmented (2–4 opsi)
       ================================================================ */

    function enhanceSegmented(select) {
        if (!select || select.getAttribute('data-fc-enhanced') === '1') return;
        var options = Array.prototype.slice.call(select.options);
        if (options.length < 2 || options.length > 4) {
            select.removeAttribute('data-fc-segmented');
            enhanceSelect(select);
            return;
        }

        var title = resolveTitle(select, 'Pilihan');
        var group = el('div', 'fc-segmented', { role: 'radiogroup', 'aria-label': title });
        var buttons = [];

        function reflect() {
            buttons.forEach(function (btn) {
                var active = btn.getAttribute('data-value') === String(select.value);
                btn.setAttribute('aria-checked', active ? 'true' : 'false');
                btn.tabIndex = active ? 0 : -1;
            });
            if (select.__fcSetError) select.__fcSetError('');
        }

        options.forEach(function (opt) {
            var btn = el('button', 'fc-segmented__btn', {
                type: 'button',
                role: 'radio',
                'data-value': opt.value,
                'aria-checked': 'false'
            });
            btn.textContent = opt.textContent.trim();
            if (opt.disabled) {
                btn.disabled = true;
                btn.setAttribute('aria-disabled', 'true');
            }
            btn.addEventListener('click', function () {
                select.value = opt.value;
                reflect();
                fire(select, 'input');
                fire(select, 'change');
            });
            btn.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
                var enabled = buttons.filter(function (b) { return !b.disabled; });
                var index = enabled.indexOf(document.activeElement);
                var next = enabled[(index + (event.key === 'ArrowRight' ? 1 : -1) + enabled.length) % enabled.length];
                if (next) {
                    event.preventDefault();
                    focusQuiet(next);
                    next.click();
                }
            });
            buttons.push(btn);
            group.appendChild(btn);
        });

        select.classList.add('fc-native-hidden');
        select.setAttribute('aria-hidden', 'true');
        select.setAttribute('tabindex', '-1');
        select.parentNode.insertBefore(group, select.nextSibling);

        takeRequired(select);
        registerControl(select, group, reflect);
        select.__fcTrigger = group;
        hookValue(select, reflect, window.HTMLSelectElement ? HTMLSelectElement.prototype : Object.getPrototypeOf(select));
        hookFocus(select, group);
        bindLabel(select, group.querySelector('button') || group);
        mirrorDisabled(select, group.querySelectorAll('.fc-segmented__btn'));
        select.addEventListener('change', reflect);
        reflect();
    }

    // Segmented tetap mengikuti status disabled dari select.
    function mirrorDisabled(node, targets) {
        var list = typeof targets.length === 'number' && !targets.tagName ? Array.prototype.slice.call(targets) : [targets];
        function sync() {
            var off = !!node.disabled;
            list.forEach(function (target) {
                if (target.tagName === 'BUTTON' && target.classList.contains('fc-segmented__btn')) {
                    target.disabled = off;
                } else if (target.classList && target.classList.contains('fc-trigger')) {
                    target.disabled = off;
                }
                target.setAttribute('aria-disabled', off ? 'true' : 'false');
            });
        }
        if (window.MutationObserver) {
            new MutationObserver(sync).observe(node, { attributes: true, attributeFilter: ['disabled'] });
        }
        sync();
    }

    /* ================================================================
       3. Stepper
       ================================================================ */

    function decimalsOf(step) {
        var text = String(step);
        var dot = text.indexOf('.');
        return dot === -1 ? 0 : text.length - dot - 1;
    }

    function enhanceStepper(input) {
        if (!input || input.getAttribute('data-fc-enhanced') === '1') return;

        var step = parseFloat(input.getAttribute('step')) || 1;
        var dec = decimalsOf(step);
        var hasMin = input.hasAttribute('min');
        var hasMax = input.hasAttribute('max');
        var min = parseFloat(input.getAttribute('min'));
        var max = parseFloat(input.getAttribute('max'));

        var wrap = el('div', 'fc-stepper');
        var minus = el('button', 'fc-stepper__btn', { type: 'button', 'aria-label': 'Kurangi' });
        var plus = el('button', 'fc-stepper__btn', { type: 'button', 'aria-label': 'Tambah' });
        minus.textContent = '\u2212';
        plus.textContent = '+';

        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(minus);
        wrap.appendChild(input);
        wrap.appendChild(plus);

        function clamp(value) {
            if (hasMin && value < min) value = min;
            if (hasMax && value > max) value = max;
            return value;
        }

        function current() {
            var value = parseFloat(input.value);
            if (isNaN(value)) value = hasMin ? min : 0;
            return value;
        }

        function setValue(value) {
            value = clamp(value);
            input.value = dec > 0 ? value.toFixed(dec) : String(value);
            refresh();
            fire(input, 'input');
            fire(input, 'change');
        }

        function refresh() {
            var value = current();
            minus.disabled = hasMin && value <= min;
            plus.disabled = hasMax && value >= max;
         }

        minus.addEventListener('click', function () { setValue(current() - step); });
        plus.addEventListener('click', function () { setValue(current() + step); });
        input.addEventListener('input', refresh);
        input.addEventListener('change', refresh);

        input.setAttribute('data-fc-enhanced', '1');
        refresh();
    }

    /* ================================================================
       4. Kalender
       ================================================================ */

    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    function isoOf(date) {
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    function parseISO(value) {
        if (!value) return null;
        var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value).trim());
        if (!match) return null;
        var year = +match[1];
        var month = +match[2] - 1;
        var day = +match[3];
        var date = new Date(year, month, day);
        if (date.getFullYear() !== year || date.getMonth() !== month || date.getDate() !== day) return null;
        return date;
    }

    function stripTime(date) {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate());
    }

    function addDays(date, amount) {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate() + amount);
    }

    function monthStart(date) {
        return new Date(date.getFullYear(), date.getMonth(), 1);
    }

    function friendly(date) {
        return DOW_SHORT[date.getDay()] + ', ' + date.getDate() + ' ' + MONTHS_SHORT[date.getMonth()] + ' ' + date.getFullYear();
    }

    function fullDate(date) {
        return DOW_FULL[date.getDay()] + ', ' + date.getDate() + ' ' + MONTHS_ID[date.getMonth()] + ' ' + date.getFullYear();
    }

    // Daftar tanggal yang sudah dibooking: data-fc-disabled='["2026-12-01", ...]'
    function parseBlockedDates(raw) {
        if (!raw) return null;
        var list;
        try { list = JSON.parse(raw); } catch (e) { return null; }
        if (!list || !list.length) return null;
        var map = Object.create(null);
        list.forEach(function (iso) { if (typeof iso === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(iso)) map[iso] = true; });
        return Object.keys(map).length ? map : null;
    }

    /* Rentang tanggal: dua kolom (mulai & selesai) tetap bisa membentuk
       rentang yang melewati hari penuh, jadi rentangnya diperiksa sebelum
       formulir dikirim, bukan cuma tanggal ujungnya. */
    var rangeGroups = Object.create(null);

    /* Satu cache per URL ketersediaan supaya beberapa unit di halaman yang
       sama tidak meminta data yang sama berulang kali. */
    var availabilityCache = Object.create(null);

    function registerRange(input) {
        var name = (input.getAttribute('data-fc-range') || '').trim();
        if (!name) return;
        var group = rangeGroups[name] || (rangeGroups[name] = []);
        if (group.indexOf(input) === -1) group.push(input);
        function onValueChange() {
            refreshProductPeers(input);
            syncRangeBounds(name);
            checkRange(name);
        }
        input.addEventListener('change', onValueChange);
        input.addEventListener('input', onValueChange);
        checkRange(name);
    }

    function firstBlockedBetween(map, from, to) {
        if (!map || !from || !to) return null;
        var cursor = stripTime(from);
        var last = stripTime(to);
        while (cursor.getTime() <= last.getTime()) {
            if (map[isoOf(cursor)]) return new Date(cursor.getTime());
            cursor = addDays(cursor, 1);
        }
        return null;
    }

    /* Tanggal kembali tidak boleh mendahului tanggal acara. Batas min
       kolom kedua ikut disetel supaya kalender tidak menawarkan hari
       sebelum tanggal acara yang baru dipilih. */
    function syncRangeBounds(name) {
        var group = rangeGroups[name];
        if (!group || group.length < 2) return false;
        var start = group[0];
        var end = group[group.length - 1];
        if (!end.__fcBaseMin) end.__fcBaseMin = end.getAttribute('min') || '';
        var from = parseISO(start.value);
        if (from) end.setAttribute('min', isoOf(from));
        else if (end.__fcBaseMin) end.setAttribute('min', end.__fcBaseMin);
        else end.removeAttribute('min');
        var picked = parseISO(end.value);
        if (from && picked && picked.getTime() < from.getTime()) {
            end.__fcOrderError = true;
            if (end.__fcSetError) end.__fcSetError('Tanggal pengembalian tidak boleh sebelum tanggal acara dan penerimaan.');
            return true;
        }
        if (end.__fcOrderError) {
            end.__fcOrderError = false;
            if (end.__fcSetError) end.__fcSetError('');
        }
        return false;
    }

    function checkRange(name) {
        var group = rangeGroups[name];
        if (!group || group.length < 2) return;
        var from = null;
        var to = null;
        group.forEach(function (node) {
            var day = parseISO(node.value);
            if (!day) return;
            if (!from || day.getTime() < from.getTime()) from = day;
            if (!to || day.getTime() > to.getTime()) to = day;
        });
        var target = group[group.length - 1];
        if (!target || !target.__fcSetError) return;
        if (!from || !to) {
            if (target.__fcRangeError) {
                target.__fcRangeError = false;
                target.__fcSetError('');
            }
            return;
        }
        var blockedHit = null;
        var peerHit = null;
        group.forEach(function (node) {
            if (!blockedHit) blockedHit = firstBlockedBetween(node.__fcBlocked, from, to);
            if (!peerHit) peerHit = firstBlockedBetween(node.__fcExtraBlocked, from, to);
        });
        if (blockedHit) {
            target.__fcRangeError = true;
            target.__fcSetError('Rentang tanggal memuat ' + friendly(blockedHit) + ' yang sudah penuh. Pilih tanggal lain atau pecah jadi dua pesanan.');
        } else if (peerHit) {
            target.__fcRangeError = true;
            target.__fcSetError('Rentang tanggal memuat ' + friendly(peerHit) + ' yang sudah dipakai unit lain di keranjang ini. Pilih tanggal lain atau kurangi jumlah unit.');
        } else if (target.__fcRangeError) {
            target.__fcRangeError = false;
            target.__fcSetError('');
        }
    }

    /* Unit lain di halaman yang sama dengan produk sama: kalau stok produk
       tinggal sedikit, tanggal yang sudah dipakai unit lain otomatis penuh
       untuk unit ini, jadi kalender menolaknya lebih awal. */
    function unitRanges() {
        var nodes = document.querySelectorAll('[data-fc-product][data-fc-unit]');
        var spans = Object.create(null);
        for (var i = 0; i < nodes.length; i += 1) {
            var node = nodes[i];
            var day = parseISO(node.value);
            if (!day) continue;
            var key = node.getAttribute('data-fc-product') + '|' + node.getAttribute('data-fc-unit');
            var span = spans[key] || (spans[key] = { from: day, to: day });
            if (day.getTime() < span.from.getTime()) span.from = day;
            if (day.getTime() > span.to.getTime()) span.to = day;
        }
        return spans;
    }

    function peerBlockedFor(input) {
        var product = (input.getAttribute('data-fc-product') || '').trim();
        var unit = (input.getAttribute('data-fc-unit') || '').trim();
        var stock = parseInt(input.getAttribute('data-fc-stock') || '', 10);
        if (!product || !unit || !(stock > 0)) return null;
        var ownKey = product + '|' + unit;
        var spans = unitRanges();
        var counts = Object.create(null);
        Object.keys(spans).forEach(function (key) {
            if (key === ownKey || key.indexOf(product + '|') !== 0) return;
            var span = spans[key];
            var cursor = span.from;
            while (cursor.getTime() <= span.to.getTime()) {
                var iso = isoOf(cursor);
                counts[iso] = (counts[iso] || 0) + 1;
                cursor = addDays(cursor, 1);
            }
        });
        var map = null;
        Object.keys(counts).forEach(function (iso) {
            if (counts[iso] + 1 > stock) {
                map = map || Object.create(null);
                map[iso] = true;
            }
        });
        return map;
    }

    /* Tanggal yang sudah terisi tapi ternyata penuh (dibooking orang lain
       atau dipakai unit lain) diberi galat sejak halaman dimuat, bukan baru
       saat kolomnya disentuh. */
    function reportDateProblem(input) {
        if (!input.__fcSetError) return;
        var picked = parseISO(input.value);
        var iso = picked ? isoOf(picked) : null;
        if (!iso) {
            if (input.__fcValueError) {
                input.__fcValueError = false;
                input.__fcSetError('');
            }
            return;
        }
        if (input.__fcBlocked && input.__fcBlocked[iso]) {
            input.__fcValueError = true;
            input.__fcSetError('Tanggal ' + friendly(picked) + ' sudah dibooking. Silakan pilih tanggal lain.');
        } else if (input.__fcExtraBlocked && input.__fcExtraBlocked[iso]) {
            input.__fcValueError = true;
            input.__fcSetError('Tanggal ' + friendly(picked) + ' sudah dipakai unit lain dengan papan yang sama. Stok tidak cukup untuk keduanya.');
        } else if (input.__fcValueError) {
            input.__fcValueError = false;
            input.__fcSetError('');
        }
    }

    function refreshPeerBlocked(input) {
        input.__fcExtraBlocked = peerBlockedFor(input);
        reportDateProblem(input);
        var name = (input.getAttribute('data-fc-range') || '').trim();
        if (name) {
            syncRangeBounds(name);
            checkRange(name);
        }
    }

    /* Satu unit berubah → unit lain dengan produk sama ikut dihitung ulang. */
    function refreshProductPeers(input) {
        var product = (input.getAttribute('data-fc-product') || '').trim();
        if (!product) {
            refreshPeerBlocked(input);
            return;
        }
        var nodes = document.querySelectorAll('[data-fc-product]');
        for (var i = 0; i < nodes.length; i += 1) {
            if ((nodes[i].getAttribute('data-fc-product') || '').trim() === product) refreshPeerBlocked(nodes[i]);
        }
    }

    function enhanceDate(input) {
        if (!input || input.getAttribute('data-fc-enhanced') === '1') return;

        var blocked = parseBlockedDates(input.getAttribute('data-fc-disabled'));
        var refreshUrl = (input.getAttribute('data-fc-refresh-url') || '').trim();
        var currentlyFetching = false;
        var currentRender = null;
        input.__fcBlocked = blocked;
        input.__fcExtraBlocked = null;
        refreshPeerBlocked(input);
        var title = resolveTitle(input, 'Pilih tanggal');
        var placeholder = (input.getAttribute('data-fc-placeholder') || '').trim() || 'Pilih tanggal';
        var trigger = el('button', 'fc-trigger', {
            type: 'button',
            'aria-haspopup': 'dialog',
            'aria-expanded': 'false',
            'aria-label': title
        });
        var text = el('span', 'fc-trigger__text');
        trigger.appendChild(text);
        trigger.insertAdjacentHTML('beforeend', svgCalendar());

        function blockedMapFrom(list) {
            var map = Object.create(null);
            var any = false;
            (list || []).forEach(function (iso) {
                if (typeof iso === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(iso)) {
                    map[iso] = true;
                    any = true;
                }
            });
            return any ? map : null;
        }

        /* Stok bisa berubah (admin menambah/mengurangi unit); angka terbaru
           dipakai perhitungan unit lain di halaman yang sama. */
        function applyStock(stock) {
            if (typeof stock !== 'number' || !isFinite(stock) || stock < 0) return;
            var product = (input.getAttribute('data-fc-product') || '').trim();
            if (!product) {
                input.setAttribute('data-fc-stock', String(stock));
                return;
            }
            var nodes = document.querySelectorAll('[data-fc-product]');
            for (var i = 0; i < nodes.length; i += 1) {
                if ((nodes[i].getAttribute('data-fc-product') || '').trim() === product) {
                    nodes[i].setAttribute('data-fc-stock', String(stock));
                }
            }
        }

        /* Tab yang dibiarkan lama bisa menampilkan tanggal yang sudah penuh
           sebagai kosong. Saat kalender dibuka, daftar terbaru diambil dan
           kalender digambar ulang kalau berubah. Gagal ambil = pakai daftar
           lama, server tetap memeriksa saat dikirim. */
        function maybeRefresh() {
            if (!refreshUrl || typeof window.fetch !== 'function') return;
            var cached = availabilityCache[refreshUrl];
            if (cached) {
                blocked = cached.blocked;
                input.__fcBlocked = blocked;
                if (Date.now() - cached.at < 60000) return;
            }
            if (currentlyFetching) return;
            currentlyFetching = true;
            var request;
            try {
                request = window.fetch(refreshUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
            } catch (e) {
                currentlyFetching = false;
                return;
            }
            request.then(function (response) {
                return response && response.ok ? response.json() : null;
            }).then(function (data) {
                currentlyFetching = false;
                if (!data || !Array.isArray(data.blocked)) return;
                var map = blockedMapFrom(data.blocked);
                availabilityCache[refreshUrl] = { at: Date.now(), blocked: map };
                blocked = map;
                input.__fcBlocked = blocked;
                applyStock(data.stock);
                refreshProductPeers(input);
                reportDateProblem(input);
                if (currentRender && document.querySelector('.fc-layer')) currentRender(null);
            }).catch(function () { currentlyFetching = false; });
        }

        function syncTrigger() {
            var date = parseISO(input.value);
            if (date) {
                text.textContent = friendly(date);
                trigger.classList.remove('is-placeholder');
            } else {
                text.textContent = placeholder;
                trigger.classList.add('is-placeholder');
            }
        }

        function setDate(date) {
            try {
                input.value = isoOf(date);
            } catch (e) {
                return;
            }
            syncTrigger();
            fire(input, 'input');
            fire(input, 'change');
            if (input.__fcSetError) input.__fcSetError('');
        }

        function buildCalendar(body, api) {
            var cal = el('div', 'fc-cal');
            body.appendChild(cal);

            var min = parseISO(input.getAttribute('min'));
            var max = parseISO(input.getAttribute('max'));
            var today = stripTime(new Date());
            var selected = parseISO(input.value);
            var view = selected ? monthStart(selected) : (min && min.getTime() > today.getTime() ? monthStart(min) : monthStart(today));

            function dayDisabled(date) {
                var day = stripTime(date);
                if (min && day.getTime() < stripTime(min).getTime()) return true;
                if (max && day.getTime() > stripTime(max).getTime()) return true;
                var iso = isoOf(day);
                if (blocked && blocked[iso]) return true;
                if (input.__fcExtraBlocked && input.__fcExtraBlocked[iso]) return true;
                return false;
            }

            function render(focusTarget) {
                selected = parseISO(input.value);
                cal.textContent = '';

                var head = el('div', 'fc-cal__head');
                var prev = el('button', 'fc-cal__navbtn', { type: 'button', 'aria-label': 'Bulan sebelumnya' });
                var next = el('button', 'fc-cal__navbtn', { type: 'button', 'aria-label': 'Bulan berikutnya' });
                prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
                next.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';
                var monthId = uid('fc-month');
                var month = el('p', 'fc-cal__month', { id: monthId, 'aria-live': 'polite' });
                month.textContent = MONTHS_ID[view.getMonth()] + ' ' + view.getFullYear();

                var prevMonth = new Date(view.getFullYear(), view.getMonth() - 1, 1);
                var nextMonth = new Date(view.getFullYear(), view.getMonth() + 1, 1);
                if (min && new Date(prevMonth.getFullYear(), prevMonth.getMonth() + 1, 0).getTime() < stripTime(min).getTime()) prev.disabled = true;
                if (max && nextMonth.getTime() > stripTime(max).getTime()) next.disabled = true;

                prev.addEventListener('click', function () {
                    view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
                    render(new Date(view.getFullYear(), view.getMonth(), Math.min(focusTarget ? focusTarget.getDate() : 1, 28)));
                });
                next.addEventListener('click', function () {
                    view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
                    render(new Date(view.getFullYear(), view.getMonth(), Math.min(focusTarget ? focusTarget.getDate() : 1, 28)));
                });
                head.appendChild(prev);
                head.appendChild(month);
                head.appendChild(next);
                cal.appendChild(head);

                var grid = el('div', 'fc-cal__grid', { role: 'grid', 'aria-labelledby': monthId });
                DOW_SHORT.forEach(function (label, index) {
                    var dow = el('div', 'fc-cal__dow', { role: 'columnheader', 'aria-label': DOW_FULL[index] });
                    dow.textContent = label;
                    grid.appendChild(dow);
                });

                var first = new Date(view.getFullYear(), view.getMonth(), 1);
                var offset = first.getDay();
                var daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();

                for (var blank = 0; blank < offset; blank += 1) {
                    grid.appendChild(el('div', 'fc-cal__day is-empty', { 'aria-hidden': 'true' }));
                }

                for (var day = 1; day <= daysInMonth; day += 1) {
                    (function (dayNumber) {
                        var date = new Date(view.getFullYear(), view.getMonth(), dayNumber);
                        var cell = el('button', 'fc-cal__day', {
                            type: 'button',
                            role: 'gridcell',
                            'data-date': isoOf(date),
                            'aria-label': fullDate(date),
                            tabindex: '-1'
                        });
                        cell.textContent = dayNumber;
                        if (date.getTime() === today.getTime()) cell.classList.add('is-today');
                        if (selected && date.getTime() === stripTime(selected).getTime()) {
                            cell.classList.add('is-selected');
                            cell.setAttribute('aria-selected', 'true');
                        }
                        if (dayDisabled(date)) {
                            cell.disabled = true;
                            cell.setAttribute('aria-disabled', 'true');
                            if (blocked && blocked[isoOf(date)]) {
                                cell.classList.add('is-blocked');
                                cell.setAttribute('aria-label', fullDate(date) + ', sudah dibooking');
                                cell.setAttribute('title', 'Sudah dibooking');
                            } else if (input.__fcExtraBlocked && input.__fcExtraBlocked[isoOf(date)]) {
                                cell.classList.add('is-blocked');
                                cell.setAttribute('aria-label', fullDate(date) + ', sudah dipakai unit lain di keranjang ini');
                                cell.setAttribute('title', 'Sudah dipakai unit lain di keranjang ini');
                            }
                        } else {
                            cell.addEventListener('click', function () {
                                setDate(date);
                                api.close({ restoreFocus: true });
                            });
                        }
                        grid.appendChild(cell);
                    })(day);
                }

                grid.addEventListener('keydown', function (event) {
                    var active = document.activeElement;
                    if (!active || !active.classList || !active.classList.contains('fc-cal__day')) return;
                    var current = parseISO(active.getAttribute('data-date'));
                    if (!current) return;
                    var target = null;
                    if (event.key === 'ArrowLeft') target = addDays(current, -1);
                    else if (event.key === 'ArrowRight') target = addDays(current, 1);
                    else if (event.key === 'ArrowUp') target = addDays(current, -7);
                    else if (event.key === 'ArrowDown') target = addDays(current, 7);
                    else if (event.key === 'Home') target = addDays(current, -current.getDay());
                    else if (event.key === 'End') target = addDays(current, 6 - current.getDay());
                    else if (event.key === 'PageUp') {
                        event.preventDefault();
                        view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
                        render(current);
                        return;
                    } else if (event.key === 'PageDown') {
                        event.preventDefault();
                        view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
                        render(current);
                        return;
                    }
                    if (!target) return;
                    event.preventDefault();
                    if (dayDisabled(target)) return;
                    if (target.getMonth() !== view.getMonth() || target.getFullYear() !== view.getFullYear()) {
                        view = monthStart(target);
                        render(target);
                    } else {
                        var button = grid.querySelector('[data-date="' + isoOf(target) + '"]');
                        if (button) focusQuiet(button);
                    }
                });

                cal.appendChild(grid);

                /* Satu hari saja yang masuk urutan Tab (roving tabindex). */
                var tabbable = grid.querySelector('.fc-cal__day.is-selected:not(:disabled)')
                    || (today.getMonth() === view.getMonth() && today.getFullYear() === view.getFullYear()
                        ? grid.querySelector('[data-date="' + isoOf(today) + '"]:not(:disabled)')
                        : null)
                    || grid.querySelector('.fc-cal__day:not(:disabled)');
                if (tabbable) tabbable.tabIndex = 0;

                var foot = el('div', 'fc-cal__foot');
                var todayBtn = el('button', 'fc-cal__action fc-cal__action--today', { type: 'button' });
                todayBtn.textContent = 'Hari ini';
                todayBtn.disabled = dayDisabled(today);
                todayBtn.addEventListener('click', function () {
                    setDate(today);
                    api.close({ restoreFocus: true });
                });
                foot.appendChild(todayBtn);

                if (!input.getAttribute('data-fc-required')) {
                    var clearBtn = el('button', 'fc-cal__action fc-cal__action--clear', { type: 'button' });
                    clearBtn.textContent = 'Hapus tanggal';
                    clearBtn.addEventListener('click', function () {
                        input.value = '';
                        syncTrigger();
                        fire(input, 'input');
                        fire(input, 'change');
                        if (input.__fcSetError) input.__fcSetError('');
                        api.close({ restoreFocus: true });
                    });
                    foot.appendChild(clearBtn);
                }
                cal.appendChild(foot);

                var notes = [];
                if (min && min.getTime() > today.getTime()) notes.push('Tanggal paling awal ' + friendly(min) + '.');
                if (max) notes.push('Tanggal paling akhir ' + friendly(max) + '.');
                if (blocked) notes.push('Tanggal bergaris sudah dibooking dan tidak bisa dipilih.');
                if (input.__fcExtraBlocked) notes.push('Tanggal yang sudah dipakai unit lain di keranjang ini juga tidak bisa dipilih.');
                if (notes.length) {
                    var note = el('p', 'fc-cal__note');
                    note.textContent = notes.join(' ');
                    cal.appendChild(note);
                }

                if (focusTarget) {
                    var targetButton = grid.querySelector('[data-date="' + isoOf(focusTarget) + '"]:not(:disabled)');
                    if (targetButton) focusQuiet(targetButton);
                } else if (selected) {
                    var selectedButton = grid.querySelector('.fc-cal__day.is-selected:not(:disabled)');
                    if (selectedButton) {
                        selectedButton.setAttribute('data-autofocus', '');
                        focusQuiet(selectedButton);
                    }
                }
            }

            currentRender = render;
            render(null);
        }

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (trigger.disabled) return;
            refreshPeerBlocked(input);
            maybeRefresh();
            openLayer({ anchor: trigger, title: title, subtitle: 'Pilih tanggal pada kalender', build: buildCalendar });
        });

        trigger.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                trigger.click();
            }
        });

        input.classList.add('fc-native-hidden');
        input.setAttribute('aria-hidden', 'true');
        input.setAttribute('tabindex', '-1');
        input.parentNode.insertBefore(trigger, input.nextSibling);

        takeRequired(input);
        registerControl(input, trigger, syncTrigger);
        input.__fcTrigger = trigger;
        hookValue(input, syncTrigger, window.HTMLInputElement ? HTMLInputElement.prototype : Object.getPrototypeOf(input));
        hookFocus(input, trigger);
        bindLabel(input, trigger);
        mirrorDisabled(input, trigger);
        input.addEventListener('change', syncTrigger);
        input.addEventListener('input', syncTrigger);
        registerRange(input);
        refreshPeerBlocked(input);
        input.addEventListener('change', function () { reportDateProblem(input); });
        syncTrigger();
    }

    /* ================================================================
       4b. Bulan  —  <input type="month">
       ================================================================ */

    function parseMonth(value) {
        var match = /^(\d{4})-(\d{2})$/.exec(String(value || '').trim());
        if (!match) return null;
        var year = +match[1];
        var month = +match[2] - 1;
        if (month < 0 || month > 11) return null;
        return new Date(year, month, 1);
    }

    function isoMonth(date) {
        return date.getFullYear() + '-' + pad(date.getMonth() + 1);
    }

    function enhanceMonth(input) {
        if (!input || input.getAttribute('data-fc-enhanced') === '1') return;

        var title = resolveTitle(input, 'Pilih bulan');
        var placeholder = (input.getAttribute('data-fc-placeholder') || '').trim() || 'Pilih bulan';
        var trigger = el('button', 'fc-trigger', {
            type: 'button',
            'aria-haspopup': 'dialog',
            'aria-expanded': 'false',
            'aria-label': title
        });
        var text = el('span', 'fc-trigger__text');
        trigger.appendChild(text);
        trigger.insertAdjacentHTML('beforeend', svgCalendar());

        function syncTrigger() {
            var month = parseMonth(input.value);
            if (month) {
                text.textContent = MONTHS_ID[month.getMonth()] + ' ' + month.getFullYear();
                trigger.classList.remove('is-placeholder');
            } else {
                text.textContent = placeholder;
                trigger.classList.add('is-placeholder');
            }
        }

        function setMonth(date) {
            input.value = isoMonth(date);
            syncTrigger();
            fire(input, 'input');
            fire(input, 'change');
            if (input.__fcSetError) input.__fcSetError('');
        }

        function buildMonths(body, api) {
            var cal = el('div', 'fc-cal');
            body.appendChild(cal);

            var min = parseMonth(input.getAttribute('min'));
            var max = parseMonth(input.getAttribute('max'));
            var today = new Date();
            var selected = parseMonth(input.value);
            var viewYear = (selected || today).getFullYear();

            function monthDisabled(date) {
                if (min && date.getTime() < min.getTime()) return true;
                if (max && date.getTime() > max.getTime()) return true;
                return false;
            }

            function render(focusMonth) {
                selected = parseMonth(input.value);
                cal.textContent = '';

                var head = el('div', 'fc-cal__head');
                var prev = el('button', 'fc-cal__navbtn', { type: 'button', 'aria-label': 'Tahun sebelumnya' });
                var next = el('button', 'fc-cal__navbtn', { type: 'button', 'aria-label': 'Tahun berikutnya' });
                prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
                next.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';
                if (min && viewYear <= min.getFullYear()) prev.disabled = true;
                if (max && viewYear >= max.getFullYear()) next.disabled = true;
                prev.addEventListener('click', function () {
                    viewYear -= 1;
                    render(focusMonth);
                });
                next.addEventListener('click', function () {
                    viewYear += 1;
                    render(focusMonth);
                });

                var label = el('p', 'fc-cal__month', { 'aria-live': 'polite' });
                label.textContent = String(viewYear);
                head.appendChild(prev);
                head.appendChild(label);
                head.appendChild(next);
                cal.appendChild(head);

                var grid = el('div', 'fc-months', { role: 'grid', 'aria-label': 'Bulan ' + viewYear });
                for (var month = 0; month < 12; month += 1) {
                    (function (monthIndex) {
                        var date = new Date(viewYear, monthIndex, 1);
                        var button = el('button', 'fc-month', {
                            type: 'button',
                            role: 'gridcell',
                            'data-month': isoMonth(date),
                            'aria-label': MONTHS_ID[monthIndex] + ' ' + viewYear
                        });
                        button.textContent = MONTHS_SHORT[monthIndex];
                        if (selected && selected.getFullYear() === viewYear && selected.getMonth() === monthIndex) {
                            button.classList.add('is-selected');
                            button.setAttribute('aria-selected', 'true');
                        }
                        if (today.getFullYear() === viewYear && today.getMonth() === monthIndex) {
                            button.classList.add('is-today');
                        }
                        if (monthDisabled(date)) {
                            button.disabled = true;
                            button.setAttribute('aria-disabled', 'true');
                        } else {
                            button.addEventListener('click', function () {
                                setMonth(date);
                                api.close({ restoreFocus: true });
                            });
                        }
                        if (focusMonth !== null && focusMonth !== undefined && focusMonth === monthIndex && !button.disabled) {
                            button.setAttribute('data-autofocus', '');
                        }
                        grid.appendChild(button);
                    })(month);
                }
                cal.appendChild(grid);

                var foot = el('div', 'fc-cal__foot');
                var thisMonth = el('button', 'fc-cal__action fc-cal__action--today', { type: 'button' });
                thisMonth.textContent = 'Bulan ini';
                thisMonth.disabled = monthDisabled(new Date(today.getFullYear(), today.getMonth(), 1));
                thisMonth.addEventListener('click', function () {
                    setMonth(new Date(today.getFullYear(), today.getMonth(), 1));
                    api.close({ restoreFocus: true });
                });
                foot.appendChild(thisMonth);
                cal.appendChild(foot);

                if (min || max) {
                    var note = el('p', 'fc-cal__note');
                    var parts = [];
                    if (min) parts.push('Mulai ' + MONTHS_ID[min.getMonth()] + ' ' + min.getFullYear() + '.');
                    if (max) parts.push('Sampai ' + MONTHS_ID[max.getMonth()] + ' ' + max.getFullYear() + '.');
                    note.textContent = parts.join(' ');
                    cal.appendChild(note);
                }
            }

            render(selected ? selected.getMonth() : null);
        }

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (trigger.disabled) return;
            openLayer({ anchor: trigger, title: title, subtitle: 'Pilih bulan', build: buildMonths });
        });

        input.classList.add('fc-native-hidden');
        input.setAttribute('aria-hidden', 'true');
        input.setAttribute('tabindex', '-1');
        input.parentNode.insertBefore(trigger, input.nextSibling);

        takeRequired(input);
        registerControl(input, trigger, syncTrigger);
        input.__fcTrigger = trigger;
        hookValue(input, syncTrigger, window.HTMLInputElement ? HTMLInputElement.prototype : Object.getPrototypeOf(input));
        hookFocus(input, trigger);
        bindLabel(input, trigger);
        mirrorDisabled(input, trigger);
        input.addEventListener('change', syncTrigger);
        input.addEventListener('input', syncTrigger);
        syncTrigger();
    }

    /* ================================================================
       5. fcPick — bottom sheet/popover generik (dipakai walk-in, dll.)
       ================================================================ */

    window.fcPick = function (opts) {
        opts = opts || {};
        var options = opts.options || [];
        var anchor = opts.anchor || document.activeElement;
        var selectedValue = opts.selected === null || opts.selected === undefined ? '' : String(opts.selected);

        openLayer({
            anchor: anchor,
            title: opts.title || 'Pilih',
            subtitle: opts.subtitle || '',
            build: function (body, api) {
                var list = el('div', 'fc-list', { role: 'listbox', 'aria-label': opts.title || 'Pilih', tabindex: '-1' });
                var buttons = [];

                options.forEach(function (option) {
                    var btn = el('button', 'fc-opt', {
                        type: 'button',
                        role: 'option',
                        'aria-selected': String(option.value) === selectedValue ? 'true' : 'false'
                    });
                    if (option.disabled) {
                        btn.disabled = true;
                        btn.setAttribute('aria-disabled', 'true');
                    }
                    var wrap = el('span', 'fc-opt__body');
                    var label = el('span', 'fc-opt__label');
                    label.textContent = option.label || option.value;
                    wrap.appendChild(label);
                    if (option.sub) {
                        var sub = el('span', 'fc-opt__sub');
                        sub.textContent = option.sub;
                        wrap.appendChild(sub);
                    }
                    btn.appendChild(wrap);
                    btn.insertAdjacentHTML('beforeend', svgCheck());

                    btn.addEventListener('click', function () {
                        api.close({ restoreFocus: true });
                        if (typeof opts.onSelect === 'function') opts.onSelect(option.value, option);
                    });
                    buttons.push(btn);
                    list.appendChild(btn);
                });

                body.appendChild(list);

                list.addEventListener('keydown', function (event) {
                    var enabled = buttons.filter(function (btn) { return !btn.disabled; });
                    var index = enabled.indexOf(document.activeElement);
                    var next = null;
                    if (event.key === 'ArrowDown') next = enabled[(index + 1) % enabled.length];
                    else if (event.key === 'ArrowUp') next = enabled[(index - 1 + enabled.length) % enabled.length];
                    else if (event.key === 'Home') next = enabled[0];
                    else if (event.key === 'End') next = enabled[enabled.length - 1];
                    if (next) {
                        event.preventDefault();
                        focusQuiet(next);
                    }
                });

                var selected = list.querySelector('[aria-selected="true"]:not([disabled])');
                if (selected) selected.setAttribute('data-autofocus', '');
            }
        });
    };

    /* ================================================================
       6. Init publik + pemantauan form dinamis
       ================================================================ */

    window.initFormControls = function (scope) {
        scope = scope || document;
        root.classList.add('fc-ui');

        each(scope, 'select', function (select) {
            if (select.closest && select.closest('.fc-layer')) return;
            if (select.hasAttribute('data-fc-native') || select.hasAttribute('data-fc-plain')) return;
            if (select.getAttribute('data-fc-enhanced') === '1') return;
            if (select.hasAttribute('data-fc-segmented')) enhanceSegmented(select);
            else enhanceSelect(select);
        });

        each(scope, 'input[type="date"], input[data-fc-date]', function (input) {
            if (input.closest && input.closest('.fc-layer')) return;
            if (input.hasAttribute('data-fc-native')) return;
            if (input.type !== 'date' && !input.hasAttribute('data-fc-date')) return;
            enhanceDate(input);
        });

        each(scope, 'input[type="month"]', function (input) {
            if (input.closest && input.closest('.fc-layer')) return;
            if (input.hasAttribute('data-fc-native')) return;
            enhanceMonth(input);
        });

        each(scope, 'input[type="number"][data-fc-stepper]', function (input) {
            if (input.closest && input.closest('.fc-layer')) return;
            enhanceStepper(input);
        });
    };

    function boot() {
        window.initFormControls(document);
        observeDynamicForms();
    }

    function observeDynamicForms() {
        if (!window.MutationObserver || window.__fcObserver) return;
        var queue = [];
        var scheduled = false;

        function flush() {
            scheduled = false;
            var nodes = queue.splice(0, queue.length);
            nodes.forEach(function (node) {
                if (!node.isConnected) return;
                if (node.closest && (node.closest('.fc-layer') || node.closest('[data-fc-enhanced="1"]'))) return;
                window.initFormControls(node);
            });
        }

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.classList && (node.classList.contains('fc-trigger') || node.classList.contains('fc-layer') || node.classList.contains('fc-error'))) return;
                    if (!node.querySelector) return;
                    if (node.matches && node.matches('select, input[type="date"], input[type="month"], input[data-fc-date], input[data-fc-stepper]')) {
                        queue.push(node);
                        scheduled = true;
                    } else if (node.querySelector('select, input[type="date"], input[type="month"], input[data-fc-date], input[data-fc-stepper]')) {
                        queue.push(node);
                        scheduled = true;
                    }
                });
            });
            if (scheduled) window.requestAnimationFrame(flush);
        });

        observer.observe(document.body, { childList: true, subtree: true });
        window.__fcObserver = observer;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
