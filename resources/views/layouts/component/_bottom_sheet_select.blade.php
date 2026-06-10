{{--
    Custom bottom-sheet select component (replaces native <select> on mobile)
    Usage: @include('layouts.component._bottom_sheet_select')

    Trigger via JS:
    showBottomSelect({
        title: 'Pilih Kurir',
        options: [
            { value: '1', label: 'Budi Setiawan', sub: 'Aktif' },
            { value: '2', label: 'Andi Pratama', sub: 'Aktif' },
        ],
        onSelect: function(value, label) { ... }
    });
--}}

<div id="bsheet-overlay" class="bsheet-overlay" style="display:none;" role="dialog" aria-modal="true">
    <div class="bsheet-backdrop" id="bsheet-backdrop"></div>
    <div class="bsheet-panel" id="bsheet-panel">
        <div class="bsheet-handle"></div>
        <div class="bsheet-header">
            <h3 class="bsheet-title" id="bsheet-title">Pilih Opsi</h3>
        </div>
        <div class="bsheet-list" id="bsheet-list"></div>
    </div>
</div>

{{--
    Styling derives all color / radius / shadow / spacing from the canonical
    Design_Tokens emitted by layouts.component._tokens (and mirrored in
    resources/css/app.css). Each var() carries a literal fallback equal to the
    canonical value so the component still renders correctly on unrevamped
    pages that do not yet @include the token sheet (Req 9.7 — no regression).
    Neutral greys map to the canonical surface scale (--surface-100 / -200 /
    -400 / -raised); the panel scrim derives from --brand-900 via color-mix.
--}}
<style>
.bsheet-overlay {
    position: fixed;
    inset: 0;
    top: 0; right: 0; bottom: 0; left: 0;
    z-index: 9998;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}
.bsheet-backdrop {
    position: absolute;
    inset: 0;
    top: 0; right: 0; bottom: 0; left: 0;
    background: rgba(0, 47, 77, 0.45); /* brand-900 navy scrim (fallback) */
    background: color-mix(in srgb, var(--brand-900, #002f4d) 45%, transparent);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
    opacity: 0;
    transition: opacity 0.25s ease;
}
.bsheet-backdrop.visible { opacity: 1; }
.bsheet-panel {
    position: relative;
    background: var(--surface-raised, #fff);
    border-radius: var(--radius-card, 16px) var(--radius-card, 16px) 0 0;
    max-width: 520px;
    width: 100%;
    max-height: 70vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transform: translateY(100%);
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
    box-shadow: var(--shadow-nav, 0 -2px 16px rgba(0, 47, 92, 0.08));
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
.bsheet-panel.visible { transform: translateY(0); }
.bsheet-handle {
    width: var(--space-10, 40px);
    height: var(--space-1, 4px);
    border-radius: var(--radius-pill, 9999px);
    background: var(--surface-200, #e2e8f0);
    margin: var(--space-3, 12px) auto 0;
}
.bsheet-header {
    padding: var(--space-4, 16px) var(--space-5, 20px) var(--space-3, 12px);
    border-bottom: 1px solid var(--surface-100, #f1f5f9);
}
.bsheet-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    color: var(--brand-900, #002f4d);
}
.bsheet-list {
    overflow-y: auto;
    padding: var(--space-2, 8px) var(--space-3, 12px) var(--space-4, 16px);
    -webkit-overflow-scrolling: touch;
}
.bsheet-item {
    display: flex;
    align-items: center;
    gap: var(--space-3, 12px);
    padding: var(--space-4, 16px) var(--space-3, 12px);
    border-radius: var(--radius-btn, 12px);
    cursor: pointer;
    transition: background 0.12s;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
    font-family: 'Plus Jakarta Sans', sans-serif;
    -webkit-tap-highlight-color: transparent;
}
.bsheet-item:active { background: var(--surface-100, #f1f5f9); }
.bsheet-item__icon {
    width: var(--space-10, 40px); height: var(--space-10, 40px);
    border-radius: var(--radius-btn, 12px);
    background: var(--brand-100, #e0efff); /* brand tint */
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.bsheet-item__icon svg { width: 20px; height: 20px; color: var(--brand-500, #0077b6); }
.bsheet-item__body { flex: 1; }
.bsheet-item__label {
    font-weight: 700;
    font-size: 0.88rem;
    color: var(--brand-900, #002f4d);
}
.bsheet-item__sub {
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--surface-400, #94a3b8);
    margin-top: 1px;
}
.bsheet-item__check {
    width: var(--space-5, 20px); height: var(--space-5, 20px);
    border-radius: 50%;
    border: 2px solid var(--surface-200, #e2e8f0);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s;
}
.bsheet-item.is-selected .bsheet-item__check {
    background: var(--brand-500, #0077b6);
    border-color: var(--brand-500, #0077b6);
}
.bsheet-item.is-selected .bsheet-item__check::after {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--surface-raised, #fff);
}
</style>

<script>
(function() {
    var _onSelectCallback = null;

    window.showBottomSelect = function(options) {
        var overlay = document.getElementById('bsheet-overlay');
        var backdrop = document.getElementById('bsheet-backdrop');
        var panel = document.getElementById('bsheet-panel');
        var title = document.getElementById('bsheet-title');
        var list = document.getElementById('bsheet-list');

        title.textContent = options.title || 'Pilih Opsi';
        _onSelectCallback = options.onSelect || null;

        // Build option list
        list.innerHTML = '';
        (options.options || []).forEach(function(opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'bsheet-item';
            btn.setAttribute('data-value', opt.value);
            btn.innerHTML =
                '<div class="bsheet-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>' +
                '<div class="bsheet-item__body">' +
                    '<div class="bsheet-item__label">' + (opt.label || '') + '</div>' +
                    (opt.sub ? '<div class="bsheet-item__sub">' + opt.sub + '</div>' : '') +
                '</div>' +
                '<div class="bsheet-item__check"></div>';

            btn.addEventListener('click', function() {
                // Mark selected
                list.querySelectorAll('.bsheet-item').forEach(function(el) { el.classList.remove('is-selected'); });
                btn.classList.add('is-selected');

                // Callback after short delay for visual feedback
                setTimeout(function() {
                    if (_onSelectCallback) _onSelectCallback(opt.value, opt.label);
                    closeSheet();
                }, 180);
            });

            list.appendChild(btn);
        });

        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(function() {
            backdrop.classList.add('visible');
            panel.classList.add('visible');
        });
    };

    function closeSheet() {
        var overlay = document.getElementById('bsheet-overlay');
        var backdrop = document.getElementById('bsheet-backdrop');
        var panel = document.getElementById('bsheet-panel');

        backdrop.classList.remove('visible');
        panel.classList.remove('visible');

        setTimeout(function() {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
            _onSelectCallback = null;
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var backdrop = document.getElementById('bsheet-backdrop');
        if (backdrop) backdrop.addEventListener('click', closeSheet);
    });
})();
</script>
