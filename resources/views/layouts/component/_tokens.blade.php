{{--
|--------------------------------------------------------------------------
| Berkah Kreatif — Canonical Design Tokens (single source of truth)
|--------------------------------------------------------------------------
| Emits the canonical :root CSS custom properties that mirror
| tailwind.config.js one-to-one. Self-contained standalone pages that do
| NOT load the compiled app.css @include this partial in <head> (right
| after _head_meta) so every revamped page shares identical token
| names/values regardless of whether it is compiled through Vite.
|
| Token names/values are kept in lockstep with tailwind.config.js and the
| :root block in resources/css/app.css. Do not introduce per-page literal
| colors, radii, shadows, or spacing — reference these tokens instead.
--}}
<style>
    :root {
        /* Brand (tailwind: colors.brand) — wine/rose, bukan biru lama.
           Nilai ini yang dipakai halaman mandiri; tailwind.config.js dan
           resources/css/app.css mencerminkan angka yang sama. */
        --brand-100: #f8e9ed;
        --brand-500: #85092d;
        --brand-900: #54051f;

        /* Accent (tailwind: colors.accent) */
        --accent-500: #c2410c;

        /* Semantic (tailwind: colors.success / warning / danger) */
        --success-500: #087f5b;
        --warning-500: #b45309;
        --danger-500: #c62f2f;

        /* Surface (tailwind: colors.surface) */
        --surface-50: #faf4f3;
        --surface-100: #fff7f7;
        --surface-200: #f0dfe1;
        --surface-400: #6f5a63;
        --surface-raised: #ffffff;

        /* Radius (tailwind: borderRadius) */
        --radius-card: 16px;
        --radius-btn: 12px;
        --radius-pill: 9999px;

        /* Shadows (tailwind: boxShadow) */
        --shadow-card: 0 2px 12px rgba(84, 5, 31, 0.06);
        --shadow-nav: 0 -2px 16px rgba(84, 5, 31, 0.08);

        /* Spacing scale — explicit 4px base unit (multiples of 4px) */
        --space-1: 4px;
        --space-2: 8px;
        --space-3: 12px;
        --space-4: 16px;
        --space-5: 20px;
        --space-6: 24px;
        --space-7: 28px;
        --space-8: 32px;
        --space-9: 36px;
        --space-10: 40px;
        --space-11: 44px;
        --space-12: 48px;
        --space-14: 56px;
        --space-16: 64px;
        --space-20: 80px;
        --space-24: 96px;
    }
</style>
