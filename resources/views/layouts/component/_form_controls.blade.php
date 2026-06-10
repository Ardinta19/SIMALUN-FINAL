{{--
|--------------------------------------------------------------------------
| Azka Laundry — Shared Form Controls (progressive enhancement)
|--------------------------------------------------------------------------
| A single, SELF-CONTAINED source of truth for professional, mobile-first
| form controls used across the whole app (ui-ux-revamp feature).
|
| It enhances *native* controls in place via opt-in data-attributes, so the
| submitted field NAMES / VALUES / FORMATS never change (no regression):
|   - <select> keeps its option values and still submits.
|   - <input type="number"> keeps its numeric string value.
|   - <input type="date"> keeps YYYY-MM-DD (the format Laravel expects).
|
| This partial depends on NOTHING from the host page: it ships its own
| <style> (literal colors, no token vars) and <script>. It is safe to drop
| on ANY page (revamped or not) because every selector is scoped under the
| `fc-` prefix / `data-fc-*` attributes and never touches generic globals.
|
| ── PUBLIC USAGE CONTRACT ──────────────────────────────────────────────
| 1. Include ONCE near the end of <body>:
|        @include('layouts.component._form_controls')
|
| 2. Opt-in by adding a data-attribute to the native control:
|        Custom select (bottom-sheet):  <select name="x" data-fc-select>
|        Number stepper:                <input type="number" name="x" data-fc-stepper>
|        Segmented (2–4 options):       <select name="x" data-fc-segmented>
|        Date picker (calendar sheet):  <input type="date" name="x" data-fc-date>
|
|    Optional helpers:
|        data-fc-title="..."        → bottom-sheet / calendar title
|        data-fc-placeholder="..."  → trigger placeholder text (select/date)
|
| 3. window.initFormControls(root = document)
|        - Runs automatically on DOMContentLoaded.
|        - Idempotent: never double-enhances (enhanced nodes are flagged).
|        - Call it again after injecting markup to enhance new rows, e.g.
|              window.initFormControls(newRowElement);
--}}

<style>
/* ==========================================================================
   Form Controls — scoped styles (everything lives under .fc-* / data-fc-*)
   Literal palette: brand #0077b6 · brand-dark #002f5c · accent #FF6B35
   success #00C48C · danger #ef4444 · muted #94a3b8 · borders #e8f0fe/#e2e8f0
   bg #f8fafc · white #fff · radius 16/12/999 · font 'Plus Jakarta Sans'
   ========================================================================== */

/* Keep the native control in the DOM (so it still submits) but visually hidden
   and non-interactive once an enhancer has taken over. */
.fc-native-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0 0 0 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

/* Body scroll lock while a sheet is open (dedicated class, no global leak). */
.fc-scroll-lock { overflow: hidden !important; }

/* ---- Trigger button (custom select & date) ----------------------------- */
.fc-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    width: 100%;
    min-height: 48px;
    padding: 12px 16px;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: #002f5c;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    -webkit-tap-highlight-color: transparent;
}
.fc-trigger:hover { border-color: #cbd8ea; }
.fc-trigger:focus-visible {
    outline: none;
    border-color: #0077b6;
    box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.18);
}
.fc-trigger.is-placeholder .fc-trigger__text { color: #94a3b8; font-weight: 500; }
.fc-trigger__text {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.fc-trigger__icon {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    color: #0077b6;
}

/* ---- Stepper ------------------------------------------------------------ */
.fc-stepper {
    display: inline-flex;
    align-items: stretch;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    max-width: 100%;
}
.fc-stepper__btn {
    flex-shrink: 0;
    width: 44px;
    min-height: 44px;
    border: none;
    background: #f8fafc;
    color: #0077b6;
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.12s ease, color 0.12s ease;
    -webkit-tap-highlight-color: transparent;
}
.fc-stepper__btn:hover { background: #e8f0fe; }
.fc-stepper__btn:active { background: #d7e6fb; }
.fc-stepper__btn:disabled { color: #cbd5e1; cursor: not-allowed; background: #f8fafc; }
.fc-stepper__btn:focus-visible {
    outline: none;
    box-shadow: inset 0 0 0 2px #0077b6;
}
/* The native number input keeps its name/value; we just style it inline. */
.fc-stepper input[type="number"] {
    width: 64px;
    min-width: 48px;
    border: none;
    text-align: center;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: #002f5c;
    background: #fff;
    -moz-appearance: textfield;
    appearance: textfield;
}
.fc-stepper input[type="number"]::-webkit-outer-spin-button,
.fc-stepper input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.fc-stepper input[type="number"]:focus { outline: none; }
.fc-stepper:focus-within { border-color: #0077b6; box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.18); }

/* ---- Segmented control -------------------------------------------------- */
.fc-segmented {
    display: inline-flex;
    padding: 4px;
    gap: 4px;
    background: #f1f5f9;
    border-radius: 12px;
    max-width: 100%;
    flex-wrap: nowrap;
}
.fc-segmented__btn {
    flex: 1 1 0;
    min-height: 40px;
    padding: 8px 14px;
    border: none;
    border-radius: 9px;
    background: transparent;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    -webkit-tap-highlight-color: transparent;
}
.fc-segmented__btn:hover { color: #002f5c; }
.fc-segmented__btn.is-active {
    background: #fff;
    color: #0077b6;
    box-shadow: 0 1px 4px rgba(0, 47, 92, 0.12);
}
.fc-segmented__btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px #0077b6;
}

/* ---- Bottom sheet (shared by select & date) ---------------------------- */
.fc-sheet-overlay {
    position: fixed;
    inset: 0;
    z-index: 9998;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}
.fc-sheet-backdrop {
    position: absolute;
    inset: 0;
    top: 0; right: 0; bottom: 0; left: 0;
    background: rgba(0, 47, 92, 0.45);
    -webkit-backdrop-filter: blur(3px);
    backdrop-filter: blur(3px);
    opacity: 0;
    transition: opacity 0.25s ease;
}
.fc-sheet-overlay.is-visible .fc-sheet-backdrop { opacity: 1; }
.fc-sheet-panel {
    position: relative;
    background: #fff;
    border-radius: 16px 16px 0 0;
    width: 100%;
    max-width: 520px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(100%);
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
    box-shadow: 0 -2px 16px rgba(0, 47, 92, 0.12);
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
.fc-sheet-overlay.is-visible .fc-sheet-panel { transform: translateY(0); }
.fc-sheet-handle {
    width: 40px;
    height: 4px;
    border-radius: 999px;
    background: #e2e8f0;
    margin: 12px auto 0;
    flex-shrink: 0;
}
.fc-sheet-header {
    padding: 16px 20px 12px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
}
.fc-sheet-title {
    margin: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    color: #002f5c;
}
.fc-sheet-body {
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 8px 12px 16px;
}

/* ---- Bottom-sheet option list (custom select) -------------------------- */
.fc-opt-group {
    padding: 14px 12px 6px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
}
.fc-opt {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    min-height: 48px;
    padding: 12px;
    border: none;
    border-radius: 12px;
    background: transparent;
    text-align: left;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    color: #002f5c;
    cursor: pointer;
    transition: background 0.12s ease;
    -webkit-tap-highlight-color: transparent;
}
.fc-opt:hover { background: #f8fafc; }
.fc-opt:active { background: #f1f5f9; }
.fc-opt:focus-visible { outline: none; box-shadow: inset 0 0 0 2px #0077b6; }
.fc-opt:disabled { color: #cbd5e1; cursor: not-allowed; }
.fc-opt__label { flex: 1; }
.fc-opt__check {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    color: #0077b6;
    opacity: 0;
    transform: scale(0.6);
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.fc-opt.is-selected { background: #e8f0fe; }
.fc-opt.is-selected .fc-opt__check { opacity: 1; transform: scale(1); }

/* ---- Calendar (date picker) -------------------------------------------- */
.fc-cal { font-family: 'Plus Jakarta Sans', sans-serif; }
.fc-cal__nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 4px 12px;
}
.fc-cal__navbtn {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 12px;
    background: #f8fafc;
    color: #0077b6;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.12s ease;
    -webkit-tap-highlight-color: transparent;
}
.fc-cal__navbtn:hover { background: #e8f0fe; }
.fc-cal__navbtn:focus-visible { outline: none; box-shadow: 0 0 0 2px #0077b6; }
.fc-cal__navbtn svg { width: 18px; height: 18px; }
.fc-cal__month {
    font-weight: 800;
    font-size: 0.95rem;
    color: #002f5c;
}
.fc-cal__grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}
.fc-cal__dow {
    text-align: center;
    font-size: 0.7rem;
    font-weight: 700;
    color: #94a3b8;
    padding: 6px 0;
}
.fc-cal__day {
    aspect-ratio: 1 / 1;
    min-height: 40px;
    border: none;
    border-radius: 12px;
    background: transparent;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    color: #002f5c;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.12s ease, color 0.12s ease;
    -webkit-tap-highlight-color: transparent;
}
.fc-cal__day:hover:not(:disabled) { background: #e8f0fe; }
.fc-cal__day:focus-visible { outline: none; box-shadow: inset 0 0 0 2px #0077b6; }
.fc-cal__day.is-empty { visibility: hidden; cursor: default; }
.fc-cal__day.is-today { color: #0077b6; box-shadow: inset 0 0 0 1.5px #0077b6; }
.fc-cal__day.is-selected { background: #0077b6; color: #fff; }
.fc-cal__day.is-selected.is-today { box-shadow: none; }
.fc-cal__day:disabled { color: #cbd5e1; cursor: not-allowed; }
.fc-cal__actions {
    display: flex;
    gap: 8px;
    margin-top: 14px;
}
.fc-cal__action {
    flex: 1;
    min-height: 44px;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    color: #002f5c;
    cursor: pointer;
    transition: background 0.12s ease, border-color 0.12s ease;
    -webkit-tap-highlight-color: transparent;
}
.fc-cal__action:hover { background: #f8fafc; }
.fc-cal__action:focus-visible { outline: none; border-color: #0077b6; box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.18); }
.fc-cal__action--today { color: #0077b6; border-color: #e8f0fe; background: #f8fafc; }
.fc-cal__action--clear { color: #ef4444; border-color: #fde2e2; }

/* ---- Reduced motion: keep functionality, drop the movement ------------- */
@media (prefers-reduced-motion: reduce) {
    .fc-sheet-backdrop,
    .fc-sheet-panel,
    .fc-trigger,
    .fc-stepper,
    .fc-segmented__btn,
    .fc-opt,
    .fc-opt__check,
    .fc-cal__day {
        transition: none !important;
    }
    .fc-sheet-panel { transform: translateY(0); }
}
</style>

<script>
/* ==========================================================================
   Form Controls — self-contained progressive enhancement engine.
   Exposes window.initFormControls(root = document). Idempotent + reusable.
   ========================================================================== */
(function () {
    'use strict';

    /* Indonesian month / day abbreviations for friendly date formatting. */
    var MONTHS_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var DOW_ID    = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    /* ---------------------------------------------------------------------
       Small DOM / value helpers
       --------------------------------------------------------------------- */
    function el(tag, cls, attrs) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (attrs) Object.keys(attrs).forEach(function (k) { node.setAttribute(k, attrs[k]); });
        return node;
    }

    function fire(node, type) {
        node.dispatchEvent(new Event(type, { bubbles: true }));
    }

    /* Already enhanced? (idempotency guard) */
    function done(node) {
        if (node.getAttribute('data-fc-enhanced') === '1') return true;
        node.setAttribute('data-fc-enhanced', '1');
        return false;
    }

    /* Resolve a human title for a control: explicit > associated <label> > placeholder. */
    function resolveTitle(node, fallback) {
        var t = node.getAttribute('data-fc-title');
        if (t) return t;
        if (node.id) {
            var lbl = document.querySelector('label[for="' + node.id + '"]');
            if (lbl && lbl.textContent.trim()) return lbl.textContent.trim();
        }
        return fallback || 'Pilih';
    }

    /* SVG icon snippets (stroke = currentColor). */
    function chevronDown() {
        return '<svg class="fc-trigger__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
    }
    function checkIcon() {
        return '<svg class="fc-opt__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>';
    }
    function calIcon() {
        return '<svg class="fc-trigger__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
    }

    /* ---------------------------------------------------------------------
       Shared bottom sheet. Returns a close() function.
       opts: { title, build(bodyEl, close) }
       --------------------------------------------------------------------- */
    var _openSheets = 0;

    function openSheet(opts) {
        var overlay  = el('div', 'fc-sheet-overlay', { role: 'dialog', 'aria-modal': 'true' });
        var backdrop = el('div', 'fc-sheet-backdrop');
        var panel    = el('div', 'fc-sheet-panel');
        var handle   = el('div', 'fc-sheet-handle');
        var header   = el('div', 'fc-sheet-header');
        var titleEl  = el('h3', 'fc-sheet-title');
        var body     = el('div', 'fc-sheet-body');

        var titleId = 'fc-sheet-title-' + Math.random().toString(36).slice(2, 8);
        titleEl.id = titleId;
        titleEl.textContent = opts.title || 'Pilih';
        overlay.setAttribute('aria-labelledby', titleId);

        header.appendChild(titleEl);
        panel.appendChild(handle);
        panel.appendChild(header);
        panel.appendChild(body);
        overlay.appendChild(backdrop);
        overlay.appendChild(panel);
        document.body.appendChild(overlay);

        var closed = false;
        function close() {
            if (closed) return;
            closed = true;
            overlay.classList.remove('is-visible');
            document.removeEventListener('keydown', onKey);
            _openSheets = Math.max(0, _openSheets - 1);
            if (_openSheets === 0) document.body.classList.remove('fc-scroll-lock');
            window.setTimeout(function () {
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            }, 320);
        }

        function onKey(e) { if (e.key === 'Escape') close(); }

        backdrop.addEventListener('click', close);
        document.addEventListener('keydown', onKey);

        opts.build(body, close);

        _openSheets++;
        document.body.classList.add('fc-scroll-lock');
        /* Trigger the slide/opacity transition on the next frame. */
        requestAnimationFrame(function () { overlay.classList.add('is-visible'); });

        return close;
    }

    /* ---------------------------------------------------------------------
       1) CUSTOM SELECT  —  <select data-fc-select>
       --------------------------------------------------------------------- */
    function enhanceSelect(sel) {
        if (done(sel)) return;

        var placeholder = sel.getAttribute('data-fc-placeholder') || '';
        var trigger = el('button', 'fc-trigger', {
            type: 'button',
            'aria-haspopup': 'dialog'
        });
        var textSpan = el('span', 'fc-trigger__text');
        trigger.appendChild(textSpan);
        trigger.insertAdjacentHTML('beforeend', chevronDown());

        function selectedOption() {
            return sel.options[sel.selectedIndex] || null;
        }
        function isPlaceholderOpt(opt) {
            return !opt || opt.value === '' || opt.disabled;
        }
        function syncTrigger() {
            var opt = selectedOption();
            if (isPlaceholderOpt(opt)) {
                textSpan.textContent = placeholder || (opt ? opt.textContent.trim() : '') || 'Pilih';
                trigger.classList.add('is-placeholder');
            } else {
                textSpan.textContent = opt.textContent.trim();
                trigger.classList.remove('is-placeholder');
            }
        }

        function build(body, close) {
            /* Walk children to preserve <optgroup> structure as section headers. */
            Array.prototype.forEach.call(sel.children, function (child) {
                if (child.tagName === 'OPTGROUP') {
                    var head = el('div', 'fc-opt-group');
                    head.textContent = child.label || '';
                    body.appendChild(head);
                    Array.prototype.forEach.call(child.children, function (o) { addOpt(o, body, close); });
                } else if (child.tagName === 'OPTION') {
                    addOpt(child, body, close);
                }
            });
        }

        function addOpt(opt, body, close) {
            /* Skip empty placeholder options from the list (shown via trigger). */
            if (opt.value === '' && opt.textContent.trim() !== '' && opt.disabled) return;
            var btn = el('button', 'fc-opt', { type: 'button' });
            if (opt.disabled) btn.disabled = true;
            if (opt.selected) { btn.classList.add('is-selected'); btn.setAttribute('aria-selected', 'true'); }
            var lbl = el('span', 'fc-opt__label');
            lbl.textContent = opt.textContent.trim();
            btn.appendChild(lbl);
            btn.insertAdjacentHTML('beforeend', checkIcon());
            btn.addEventListener('click', function () {
                if (opt.disabled) return;
                sel.value = opt.value;
                fire(sel, 'change');
                syncTrigger();
                close();
            });
            body.appendChild(btn);
        }

        trigger.addEventListener('click', function () {
            openSheet({ title: resolveTitle(sel, placeholder || 'Pilih'), build: build });
        });

        sel.classList.add('fc-native-hidden');
        sel.setAttribute('tabindex', '-1');
        sel.parentNode.insertBefore(trigger, sel.nextSibling);

        /* Reflect external programmatic changes back onto the trigger. */
        sel.addEventListener('change', syncTrigger);
        syncTrigger();
    }

    /* ---------------------------------------------------------------------
       2) STEPPER  —  <input type="number" data-fc-stepper>
       --------------------------------------------------------------------- */
    function decimalsOf(step) {
        var s = String(step);
        var i = s.indexOf('.');
        return i === -1 ? 0 : (s.length - i - 1);
    }

    function enhanceStepper(input) {
        if (done(input)) return;

        var step = parseFloat(input.getAttribute('step')) || 1;
        var dec  = decimalsOf(step);
        var hasMin = input.hasAttribute('min');
        var hasMax = input.hasAttribute('max');
        var min = parseFloat(input.getAttribute('min'));
        var max = parseFloat(input.getAttribute('max'));

        var wrap = el('div', 'fc-stepper');
        var minus = el('button', 'fc-stepper__btn', { type: 'button', 'aria-label': 'Kurangi' });
        var plus  = el('button', 'fc-stepper__btn', { type: 'button', 'aria-label': 'Tambah' });
        minus.textContent = '\u2212'; /* − */
        plus.textContent  = '+';

        /* Place the wrapper, then move the native input inside it (keeps name/value). */
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(minus);
        wrap.appendChild(input);
        wrap.appendChild(plus);

        function clamp(v) {
            if (hasMin && v < min) v = min;
            if (hasMax && v > max) v = max;
            return v;
        }
        function current() {
            var v = parseFloat(input.value);
            if (isNaN(v)) v = hasMin ? min : 0;
            return v;
        }
        function setVal(v) {
            v = clamp(v);
            input.value = dec > 0 ? v.toFixed(dec) : String(v);
            fire(input, 'input');
            fire(input, 'change');
            refresh();
        }
        function refresh() {
            var v = current();
            minus.disabled = hasMin && (v - step) < min && v <= min;
            plus.disabled  = hasMax && (v + step) > max && v >= max;
        }

        minus.addEventListener('click', function () { setVal(current() - step); });
        plus.addEventListener('click', function () { setVal(current() + step); });
        input.addEventListener('change', refresh);
        input.addEventListener('input', refresh);
        refresh();
    }

    /* ---------------------------------------------------------------------
       3) SEGMENTED  —  <select data-fc-segmented> (2–4 options)
       --------------------------------------------------------------------- */
    function enhanceSegmented(sel) {
        if (done(sel)) return;

        var opts = Array.prototype.filter.call(sel.options, function (o) { return true; });
        if (opts.length < 2 || opts.length > 4) {
            /* Out of supported range — fall back to a custom select instead. */
            sel.removeAttribute('data-fc-enhanced');
            sel.setAttribute('data-fc-select', '');
            enhanceSelect(sel);
            return;
        }

        var group = el('div', 'fc-segmented', { role: 'radiogroup', 'aria-label': resolveTitle(sel, 'Pilihan') });
        var buttons = [];

        function reflect() {
            buttons.forEach(function (b) {
                var active = b.getAttribute('data-value') === sel.value;
                b.classList.toggle('is-active', active);
                b.setAttribute('aria-checked', active ? 'true' : 'false');
            });
        }

        opts.forEach(function (o) {
            var btn = el('button', 'fc-segmented__btn', { type: 'button', role: 'radio', 'data-value': o.value });
            btn.textContent = o.textContent.trim();
            btn.addEventListener('click', function () {
                sel.value = o.value;
                fire(sel, 'change');
                reflect();
            });
            buttons.push(btn);
            group.appendChild(btn);
        });

        sel.classList.add('fc-native-hidden');
        sel.setAttribute('tabindex', '-1');
        sel.parentNode.insertBefore(group, sel.nextSibling);
        sel.addEventListener('change', reflect);
        reflect();
    }

    /* ---------------------------------------------------------------------
       4) DATE PICKER  —  <input type="date" data-fc-date> (or text)
       --------------------------------------------------------------------- */
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function toISO(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }
    function parseISO(v) {
        if (!v) return null;
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v.trim());
        if (!m) return null;
        var y = +m[1], mo = +m[2] - 1, d = +m[3];
        var dt = new Date(y, mo, d);
        if (dt.getFullYear() !== y || dt.getMonth() !== mo || dt.getDate() !== d) return null;
        return dt;
    }
    function friendly(dt) {
        return DOW_ID[dt.getDay()] + ', ' + dt.getDate() + ' ' + MONTHS_ID[dt.getMonth()] + ' ' + dt.getFullYear();
    }

    function enhanceDate(input) {
        if (done(input)) return;

        var placeholder = input.getAttribute('data-fc-placeholder') || 'Pilih tanggal';
        var required = input.hasAttribute('required');
        var minDt = parseISO(input.getAttribute('min'));
        var maxDt = parseISO(input.getAttribute('max'));

        var trigger = el('button', 'fc-trigger', { type: 'button', 'aria-haspopup': 'dialog' });
        var textSpan = el('span', 'fc-trigger__text');
        trigger.appendChild(textSpan);
        trigger.insertAdjacentHTML('beforeend', calIcon());

        function syncTrigger() {
            var dt = parseISO(input.value);
            if (dt) {
                textSpan.textContent = friendly(dt);
                trigger.classList.remove('is-placeholder');
            } else {
                textSpan.textContent = placeholder;
                trigger.classList.add('is-placeholder');
            }
        }

        function outOfRange(dt) {
            if (minDt && dt < stripTime(minDt)) return true;
            if (maxDt && dt > stripTime(maxDt)) return true;
            return false;
        }
        function stripTime(dt) { return new Date(dt.getFullYear(), dt.getMonth(), dt.getDate()); }

        function build(body, close) {
            var today = stripTime(new Date());
            var selected = parseISO(input.value);
            var view = selected ? new Date(selected.getFullYear(), selected.getMonth(), 1)
                                : new Date(today.getFullYear(), today.getMonth(), 1);

            var cal = el('div', 'fc-cal');
            body.appendChild(cal);

            function render() {
                cal.innerHTML = '';

                /* Month navigation. */
                var nav = el('div', 'fc-cal__nav');
                var prev = el('button', 'fc-cal__navbtn', { type: 'button', 'aria-label': 'Bulan sebelumnya' });
                var next = el('button', 'fc-cal__navbtn', { type: 'button', 'aria-label': 'Bulan berikutnya' });
                prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
                next.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';
                var monthLbl = el('div', 'fc-cal__month');
                monthLbl.textContent = MONTHS_ID[view.getMonth()] + ' ' + view.getFullYear();
                prev.addEventListener('click', function () { view.setMonth(view.getMonth() - 1); render(); });
                next.addEventListener('click', function () { view.setMonth(view.getMonth() + 1); render(); });
                nav.appendChild(prev); nav.appendChild(monthLbl); nav.appendChild(next);
                cal.appendChild(nav);

                /* Grid: weekday headers + day cells. */
                var grid = el('div', 'fc-cal__grid');
                DOW_ID.forEach(function (d) {
                    var dow = el('div', 'fc-cal__dow');
                    dow.textContent = d;
                    grid.appendChild(dow);
                });

                var first = new Date(view.getFullYear(), view.getMonth(), 1);
                var startDow = first.getDay();
                var daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();

                for (var i = 0; i < startDow; i++) {
                    grid.appendChild(el('div', 'fc-cal__day is-empty'));
                }
                for (var day = 1; day <= daysInMonth; day++) {
                    (function (day) {
                        var cellDate = new Date(view.getFullYear(), view.getMonth(), day);
                        var cell = el('button', 'fc-cal__day', { type: 'button' });
                        cell.textContent = day;
                        if (cellDate.getTime() === today.getTime()) cell.classList.add('is-today');
                        if (selected && cellDate.getTime() === stripTime(selected).getTime()) {
                            cell.classList.add('is-selected');
                            cell.setAttribute('aria-selected', 'true');
                        }
                        if (outOfRange(cellDate)) {
                            cell.disabled = true;
                        } else {
                            cell.addEventListener('click', function () {
                                input.value = toISO(cellDate.getFullYear(), cellDate.getMonth(), cellDate.getDate());
                                fire(input, 'change');
                                syncTrigger();
                                close();
                            });
                        }
                        grid.appendChild(cell);
                    })(day);
                }
                cal.appendChild(grid);

                /* Quick actions: "Hari ini" + "Hapus" (clear, only when optional). */
                var actions = el('div', 'fc-cal__actions');
                var todayBtn = el('button', 'fc-cal__action fc-cal__action--today', { type: 'button' });
                todayBtn.textContent = 'Hari ini';
                if (outOfRange(today)) {
                    todayBtn.disabled = true;
                } else {
                    todayBtn.addEventListener('click', function () {
                        input.value = toISO(today.getFullYear(), today.getMonth(), today.getDate());
                        fire(input, 'change');
                        syncTrigger();
                        close();
                    });
                }
                actions.appendChild(todayBtn);

                if (!required) {
                    var clearBtn = el('button', 'fc-cal__action fc-cal__action--clear', { type: 'button' });
                    clearBtn.textContent = 'Hapus';
                    clearBtn.addEventListener('click', function () {
                        input.value = '';
                        fire(input, 'change');
                        syncTrigger();
                        close();
                    });
                    actions.appendChild(clearBtn);
                }
                cal.appendChild(actions);
            }

            render();
        }

        trigger.addEventListener('click', function () {
            openSheet({ title: resolveTitle(input, placeholder), build: build });
        });

        /* Hide the native date UI but keep the input as the YYYY-MM-DD holder. */
        input.classList.add('fc-native-hidden');
        input.setAttribute('tabindex', '-1');
        input.parentNode.insertBefore(trigger, input.nextSibling);
        input.addEventListener('change', syncTrigger);
        syncTrigger();
    }

    /* ---------------------------------------------------------------------
       Public init — idempotent, scoped to `root`.
       --------------------------------------------------------------------- */
    function each(root, selector, fn) {
        Array.prototype.forEach.call(root.querySelectorAll(selector), fn);
        /* Also handle the root node itself when it matches. */
        if (root.matches && root.matches(selector)) fn(root);
    }

    window.initFormControls = function (root) {
        root = root || document;
        each(root, 'select[data-fc-segmented]', enhanceSegmented);
        each(root, 'select[data-fc-select]', enhanceSelect);
        each(root, 'input[type="number"][data-fc-stepper]', enhanceStepper);
        each(root, '[data-fc-date]', enhanceDate);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { window.initFormControls(document); });
    } else {
        window.initFormControls(document);
    }
})();
</script>
