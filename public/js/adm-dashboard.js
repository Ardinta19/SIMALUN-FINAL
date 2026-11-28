/* ==========================================================================
   BERKAH KREATIF — DASBOR ADMIN (adm-dashboard.js)
   --------------------------------------------------------------------------
   Dua pekerjaan, satu berkas, dan keduanya bersifat tambahan di atas markup
   server:

   1. PULAU REACT — grafik arus kas.
      Markup Blade merender grafik tujuh hari yang sudah bisa dibaca. Ketika
      React + ReactDOM + htm berhasil dimuat dari `public/vendor`, komponen di
      bawah menggantinya dengan versi interaktif: sakelar 7/14/30 hari,
      sorotan per batang, dan keterangan angkanya. Tanpa salah satu pustaka
      itu, fungsi ini berhenti lebih awal dan grafik server tetap tampil.

      Ditulis tanpa JSX dan tanpa langkah build: `htm` mengubah template
      literal menjadi pemanggilan `React.createElement`. Tidak ada CDN, tidak
      ada `unsafe-eval`.

   2. KOREOGRAFI GSAP — busur jam, batang, dan angka berjalan.
      Memakai GSAP + ScrollTrigger + SplitText yang sudah ada di
      `public/vendor/gsap` dan dimuat global lewat partial `_form_controls`.
      Begitu GSAP tersedia, `body.adm-gsap` menyala dan keyframe CSS untuk
      batang/jam dimatikan supaya tidak ada dua mesin yang berebut properti
      yang sama.

   Semua gerak berhenti bila `prefers-reduced-motion: reduce`. Tidak ada teks
   yang disembunyikan lewat CSS, jadi kegagalan skrip tidak pernah membuat
   halaman kehilangan isi.
   ========================================================================== */
(function () {
    'use strict';

    var reduceQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    function reduced() {
        return !!(reduceQuery && reduceQuery.matches);
    }

    var pemformat;
    try {
        pemformat = new Intl.NumberFormat('id-ID');
    } catch (e) {
        pemformat = { format: function (n) { return String(n); } };
    }

    function rupiah(n) {
        return 'Rp ' + pemformat.format(Math.round(Number(n) || 0));
    }

    /* ── 1. Pulau React: grafik arus kas ─────────────────────────────────── */

    function pasangGrafik() {
        var host = document.querySelector('[data-adm-chart]');
        if (!host) return;

        var React = window.React;
        var ReactDOM = window.ReactDOM;
        var htm = window.htm;

        // Cadangan (markup Blade) dibiarkan apa adanya bila satu saja hilang.
        if (!React || !ReactDOM || !htm || !ReactDOM.createRoot) return;

        var hari;
        try {
            hari = JSON.parse(host.getAttribute('data-adm-days') || '[]');
        } catch (err) {
            return;
        }
        if (!hari || !hari.length) return;

        var html = htm.bind(React.createElement);
        var useState = React.useState;
        var useEffect = React.useEffect;
        var useMemo = React.useMemo;
        var useRef = React.useRef;

        var RENTANG = [7, 14, 30];

        function Grafik() {
            var rentangState = useState(7);
            var rentang = rentangState[0];
            var setRentang = rentangState[1];

            var aktifState = useState(-1);
            var aktif = aktifState[0];
            var setAktif = aktifState[1];

            var wadah = useRef(null);

            var baris = useMemo(function () {
                return hari.slice(Math.max(0, hari.length - rentang));
            }, [rentang]);

            var ringkas = useMemo(function () {
                var masuk = 0;
                var keluar = 0;
                var puncak = 1;
                baris.forEach(function (d) {
                    masuk += Number(d.masuk) || 0;
                    keluar += Number(d.keluar) || 0;
                    puncak = Math.max(puncak, Number(d.masuk) || 0, Number(d.keluar) || 0);
                });
                return { masuk: masuk, keluar: keluar, puncak: puncak };
            }, [baris]);

            function tinggi(nilai) {
                var angka = Number(nilai) || 0;
                if (angka <= 0) return '0%';
                return Math.max(3, Math.round((angka / ringkas.puncak) * 100)) + '%';
            }

            // GSAP menggerakkan batang setiap kali rentang berganti; tanpa GSAP
            // (atau saat gerak dikurangi) batang langsung tampil di tinggi akhir.
            useEffect(function () {
                var node = wadah.current;
                if (!node) return;
                if (window.gsap && !reduced()) {
                    window.gsap.fromTo(
                        node.querySelectorAll('.adm-bars__fill'),
                        { scaleY: 0 },
                        {
                            scaleY: 1,
                            duration: 0.5,
                            ease: 'power2.out',
                            transformOrigin: '50% 100%',
                            stagger: { each: 0.028, from: 'start' },
                            overwrite: true,
                            clearProps: 'transform'
                        }
                    );
                }
                // ScrollTrigger perlu tahu tinggi halaman yang berubah.
                if (window.BkMotion && window.BkMotion.refresh) window.BkMotion.refresh();
            }, [rentang]);

            var simpul = baris.map(function (d, i) {
                var label = d.label;
                if (rentang > 7) {
                    var langkah = Math.ceil(rentang / 7);
                    label = i % langkah === 0 ? d.label : '\u00a0';
                }

                return html`<div className="adm-bars__day" key=${d.iso || i}>
                    <button
                        type="button"
                        className="adm-bars__hit"
                        data-aktif=${aktif === i ? 'true' : 'false'}
                        aria-label=${d.tanggal + ': masuk ' + rupiah(d.masuk) + ', keluar ' + rupiah(d.keluar)}
                        onMouseEnter=${function () { setAktif(i); }}
                        onMouseLeave=${function () { setAktif(-1); }}
                        onFocus=${function () { setAktif(i); }}
                        onBlur=${function () { setAktif(-1); }}
                    >
                        <span className="adm-bars__pair">
                            <span className="adm-bars__fill adm-bars__fill--in" style=${{ height: tinggi(d.masuk) }}></span>
                            <span className="adm-bars__fill adm-bars__fill--out" style=${{ height: tinggi(d.keluar) }}></span>
                        </span>
                        <span className="adm-bars__label" aria-hidden="true">${label}</span>
                    </button>
                </div>`;
            });

            var sorot = aktif >= 0 ? baris[aktif] : null;
            var keterangan = sorot
                ? sorot.tanggal + ' · masuk ' + rupiah(sorot.masuk) + ' · keluar ' + rupiah(sorot.keluar)
                : 'Sorot atau fokuskan satu batang untuk melihat angkanya.';

            return html`<div className="adm-chart__inner">
                <div className="adm-chart__bar">
                    <div className="adm-chart__seg" role="group" aria-label="Rentang grafik">
                        ${RENTANG.map(function (jumlah) {
                            return html`<button
                                key=${jumlah}
                                type="button"
                                className="adm-chart__segbtn"
                                aria-pressed=${rentang === jumlah ? 'true' : 'false'}
                                onClick=${function () { setRentang(jumlah); setAktif(-1); }}
                            >${jumlah} hari</button>`;
                        })}
                    </div>
                    <p className="adm-chart__sum">${rentang} hari · masuk ${rupiah(ringkas.masuk)} · keluar ${rupiah(ringkas.keluar)}</p>
                </div>

                <div className="adm-bars adm-bars--live" ref=${wadah}>
                    ${simpul}
                </div>

                <p className="adm-chart__tip" role="status" aria-live="polite">${keterangan}</p>
            </div>`;
        }

        ReactDOM.createRoot(host).render(html`<${Grafik} />`);
    }

    /* ── 2. Koreografi GSAP ──────────────────────────────────────────────── */

    function pemicu(el) {
        if (!window.ScrollTrigger || !el) return undefined;
        return { trigger: el, start: 'top 88%', once: true };
    }

    function angkaBerjalan(el, nilai, akhiran, opsi) {
        var gsap = window.gsap;
        var tujuan = Number(nilai);
        if (!gsap || !el || !isFinite(tujuan)) return;

        if (tujuan <= 0) {
            el.textContent = pemformat.format(0) + akhiran;
            return;
        }

        var wadah = { v: 0 };
        el.textContent = pemformat.format(0) + akhiran;

        gsap.to(wadah, {
            v: tujuan,
            duration: 1.1,
            ease: 'power2.out',
            scrollTrigger: pemicu(opsi && opsi.el ? opsi.el : el),
            onUpdate: function () {
                el.textContent = pemformat.format(Math.round(wadah.v)) + akhiran;
            },
            onComplete: function () {
                el.textContent = pemformat.format(tujuan) + akhiran;
            }
        });
    }

    function judulDipecah(gsap) {
        var h1 = document.querySelector('.adm-page-head h1');
        if (!h1 || !window.SplitText) return;

        try {
            if (gsap.registerPlugin) gsap.registerPlugin(window.SplitText);
            var pecah = window.SplitText.create(h1, { type: 'words', aria: 'auto', mask: 'words' });
            gsap.from(pecah.words, {
                yPercent: 110,
                opacity: 0,
                duration: 0.65,
                ease: 'power3.out',
                stagger: 0.05
            });
        } catch (err) {
            /* Gagal memecah judul bukan masalah: teksnya tetap utuh. */
        }
    }

    function gerak() {
        var gsap = window.gsap;
        if (!gsap || reduced()) return;

        document.body.classList.add('adm-gsap');
        if (window.ScrollTrigger) gsap.registerPlugin(window.ScrollTrigger);

        // Busur jam: dari kosong ke persentase pemakaian, sekali per halaman.
        Array.prototype.forEach.call(document.querySelectorAll('.adm-gauge'), function (kartu) {
            var busur = kartu.querySelector('.adm-gauge__value');
            if (busur) {
                var akhir = parseFloat(window.getComputedStyle(busur).strokeDashoffset);
                if (isFinite(akhir)) {
                    gsap.fromTo(busur, { strokeDashoffset: 264 }, {
                        strokeDashoffset: akhir,
                        duration: 1.15,
                        ease: 'power2.out',
                        scrollTrigger: pemicu(kartu)
                    });
                }
            }

            var persen = kartu.querySelector('[data-adm-persen]');
            if (persen) {
                angkaBerjalan(persen, persen.getAttribute('data-adm-persen'), '%', { el: kartu });
            }
        });

        // Batang cadangan (React tidak berjalan): tumbuh berurutan dari bawah.
        var cadangan = document.querySelectorAll('.adm-bars:not(.adm-bars--live) .adm-bars__fill');
        if (cadangan.length) {
            gsap.from(cadangan, {
                scaleY: 0,
                transformOrigin: '50% 100%',
                duration: 0.55,
                ease: 'power2.out',
                stagger: 0.05,
                scrollTrigger: pemicu(cadangan[0])
            });
        }

        // Angka utama: pemasukan hari ini berjalan dari nol.
        var utama = document.querySelector('[data-adm-nilai]');
        if (utama) {
            angkaBerjalan(utama, utama.getAttribute('data-adm-nilai'), '');
        }

        judulDipecah(gsap);
    }

    /* ── 3. Mulai ────────────────────────────────────────────────────────── */

    function mulai() {
        pasangGrafik();
        gerak();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mulai, { once: true });
    } else {
        mulai();
    }
})();
