{{--
    Form loading state utility — prevents double-submit on all forms
    Usage: @include('layouts.component._form_loading')

    Automatically hooks into all <form> elements on the page.
    Disables submit button + shows loading spinner on click.

    Design System: the spinner animates ONLY transform + opacity (Req 4.6)
    and never animates layout-affecting properties (Req 4.7). Colors/radius
    derive from canonical tokens — the indicator color uses the button's own
    text color (currentColor) so it stays token-conformant on any
    canonical-token-styled button, the corner uses --radius-pill, and the box
    + offsets use the 4px-base spacing scale. Reduced motion is honored
    (Req 5.3). Styling assumes the canonical _tokens partial is loaded.
--}}
<style>
.btn-loading {
    position: relative;
    pointer-events: none;
    opacity: 0.7;
}
.btn-loading::after {
    content: '';
    position: absolute;
    /* Spinner box derives from the 4px-base spacing scale (no literal sizes) */
    width: var(--space-4, 16px); height: var(--space-4, 16px);
    /* Track + indicator derive from the button's text color (no literal colors) */
    border: 2px solid color-mix(in srgb, currentColor 30%, transparent);
    border-top-color: currentColor;
    border-radius: var(--radius-pill, 9999px);
    right: var(--space-3, 12px);
    top: 50%;
    transform-origin: center;
    /*
        Animate ONLY transform + opacity (Req 4.6) — no layout-affecting
        properties (Req 4.7). The fade-in uses opacity, the spin uses
        transform; vertical centering is folded into the transform so we
        never animate top/margin.
    */
    animation:
        btn-fade 0.18s ease-out both,
        btn-spin 0.6s linear infinite;
    will-change: transform, opacity;
}
/* Fade the indicator in via opacity only (no layout/size change) */
@keyframes btn-fade {
    from { opacity: 0; }
    to   { opacity: 1; }
}
/* Spin via transform only; keep vertical centering inside the transform */
@keyframes btn-spin {
    from { transform: translateY(-50%) rotate(0deg); }
    to   { transform: translateY(-50%) rotate(360deg); }
}

/*
    Reduced motion (Req 5.3): stop the non-essential spin/fade and apply an
    immediate, motion-free state — the indicator stays visible (centered via
    a static transform) so the loading state is still conveyed.
*/
@media (prefers-reduced-motion: reduce) {
    .btn-loading::after {
        animation: none;
        opacity: 1;
        transform: translateY(-50%);
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                // Skip if already loading
                if (form.dataset.loading === 'true') {
                    e.preventDefault();
                    return;
                }

                // Find submit button
                var btn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (!btn) return;

                // Mark as loading
                form.dataset.loading = 'true';
                btn.classList.add('btn-loading');
                btn.disabled = true;

                // Auto-reset after 8s (for network timeout cases)
                setTimeout(function() {
                    form.dataset.loading = '';
                    btn.classList.remove('btn-loading');
                    btn.disabled = false;
                }, 8000);
            });
        });
    });
})();
</script>
