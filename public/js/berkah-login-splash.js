/*!
 * Berkah Kreatif — animasi splash halaman login
 * ------------------------------------------------------------------
 * Alur: overlay brand muncul → logo & kelopak masuk → nama brand →
 * garis emas → progress → overlay membuka form login (GSAP).
 *
 * Aturan main:
 *  - Hanya tampil bila markup #bk-splash sudah dibuka oleh inline guard
 *    (setiap kunjungan ke /login, kecuali ada galat validasi, pengguna
 *    memakai prefers-reduced-motion, atau meminta `?splash=0`).
 *  - Bisa dilewati: klik di mana saja, tombol "Lewati", atau Escape.
 *  - Bila GSAP tidak termuat, overlay tetap ditutup dengan transisi CSS.
 */
(function () {
    'use strict';

    var splash = document.getElementById('bk-splash');
    if (!splash || splash.hidden) return;

    var done = false;
    var timeline = null;
    var gsap = window.gsap;
    var inertTargets = [document.getElementById('page'), document.getElementById('btn-back')].filter(Boolean);

    var supportsInert = 'inert' in HTMLElement.prototype;

    function setInert(on) {
        inertTargets.forEach(function (node) {
            if (supportsInert) {
                node.inert = on;
                return;
            }
            /* Fallback browser lama: sembunyikan dari AT + matikan tab. */
            if (on) {
                node.setAttribute('aria-hidden', 'true');
                Array.prototype.forEach.call(node.querySelectorAll('a, button, input, select, textarea, [tabindex]'), function (item) {
                    item.setAttribute('data-splash-tabindex', item.getAttribute('tabindex') || '');
                    item.setAttribute('tabindex', '-1');
                });
            } else {
                node.removeAttribute('aria-hidden');
                Array.prototype.forEach.call(node.querySelectorAll('[data-splash-tabindex]'), function (item) {
                    var saved = item.getAttribute('data-splash-tabindex');
                    item.removeAttribute('data-splash-tabindex');
                    if (saved) item.setAttribute('tabindex', saved);
                    else item.removeAttribute('tabindex');
                });
            }
        });
    }

    setInert(true);

    function removeSplash() {
        splash.classList.add('is-leaving');
        window.setTimeout(function () {
            if (splash.parentNode) splash.parentNode.removeChild(splash);
        }, 480);
    }

    function finish() {
        if (done) return;
        done = true;
        document.removeEventListener('keydown', onKey);
        splash.removeEventListener('click', onSkip);
        var skipBtn = splash.querySelector('.bk-splash__skip');
        if (skipBtn) skipBtn.removeEventListener('click', onSkip);
        setInert(false);
        removeSplash();
    }

    function skip() {
        if (done) return;
        if (timeline && gsap) {
            gsap.killTweensOf(splash);
            gsap.to(splash, {
                opacity: 0,
                duration: 0.24,
                ease: 'power2.out',
                onComplete: finish
            });
            timeline.kill();
            timeline = null;
            done = true;
            document.removeEventListener('keydown', onKey);
            splash.removeEventListener('click', onSkip);
            var skipBtn = splash.querySelector('.bk-splash__skip');
            if (skipBtn) skipBtn.removeEventListener('click', onSkip);
            window.setTimeout(function () {
                if (splash.parentNode) splash.parentNode.removeChild(splash);
            }, 420);
        } else {
            finish();
        }
    }

    function onSkip() {
        skip();
    }

    function onKey(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            skip();
        }
    }

    splash.addEventListener('click', onSkip);
    var skipBtn = splash.querySelector('.bk-splash__skip');
    if (skipBtn) {
        skipBtn.addEventListener('click', onSkip);
        try {
            skipBtn.focus({ preventScroll: true });
        } catch (e) {
            skipBtn.focus();
        }
    }
    document.addEventListener('keydown', onKey);

    if (!gsap) {
        /* Fallback tanpa GSAP: biarkan CSS yang menutup overlay. */
        splash.style.transition = 'opacity .4s ease';
        window.setTimeout(function () {
            splash.style.opacity = '0';
            finish();
        }, 1200);
        return;
    }

    var brand = splash.querySelector('.bk-splash__brand');
    var eyebrow = splash.querySelector('.bk-splash__eyebrow');
    var tagline = splash.querySelector('.bk-splash__tagline');
    var rule = splash.querySelector('.bk-splash__rule');
    var bar = splash.querySelector('.bk-splash__bar span');
    var logo = splash.querySelector('.bk-splash__logo');
    var petals = splash.querySelectorAll('.bk-splash__petals span');

    timeline = gsap.timeline({
        defaults: { ease: 'power3.out' },
        onComplete: finish
    });

    timeline
        // Satu putaran penuh: logo adalah tanda bulat, jadi 360° terasa wajar
        // dan menegaskan "kedatangan" tanpa terlihat seperti spinner.
        .from(logo, { scale: 0.6, rotate: -360, opacity: 0, duration: 1.05, ease: 'power3.out' })
        .from(petals, { scale: 0.4, opacity: 0, duration: 0.62, stagger: 0.075 }, '-=0.46')
        .from(eyebrow, { y: 12, opacity: 0, duration: 0.34 }, '-=0.34')
        .from(brand, { y: 20, opacity: 0, duration: 0.46 }, '-=0.22')
        .from(rule, { scaleX: 0, opacity: 0, duration: 0.4 }, '-=0.22')
        .from(tagline, { y: 10, opacity: 0, duration: 0.36 }, '-=0.2')
        .to(bar, { scaleX: 1, duration: 0.9, ease: 'power1.inOut' }, '-=0.85')
        .to(splash, { opacity: 0, duration: 0.44, ease: 'power2.inOut' }, '+=0.05')
        .to(logo, { scale: 1.08, duration: 0.44, ease: 'power2.inOut' }, '<')
        .from('#header', { y: -14, duration: 0.46, ease: 'power2.out' }, '<')
        .from('#card', { y: 24, duration: 0.52, ease: 'power2.out' }, '<+=0.06');

    /* Tombol "Lewati" selalu bisa dijangkau keyboard. */
    if (skipBtn) {
        splash.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && event.target === skipBtn) {
                event.preventDefault();
                skip();
            }
        });
    }
})();
