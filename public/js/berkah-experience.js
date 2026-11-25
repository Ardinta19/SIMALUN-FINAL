(() => {
    const revealHash = () => {
        let id; try { id = decodeURIComponent(location.hash.slice(1)); } catch { return; }
        if (!id) return;
        const target = document.getElementById(id);
        if (!target) return;
        if (target.tagName === 'DETAILS') target.open = true;
        let parent = target.parentElement;
        while (parent) { if (parent.tagName === 'DETAILS') parent.open = true; parent = parent.parentElement; }
    };
    window.addEventListener('hashchange', revealHash);
    document.addEventListener('click', event => { const link = event.target.closest('a[href^="#"]'); if (link && link.hash === location.hash) revealHash(); });
    revealHash();
    const header = document.querySelector('[data-bk-header]');

    /* Gulir programatik harus lewat Lenis bila mesin gerak aktif: memanggil
       window.scrollTo() saat Lenis berjalan membuat dua pengendali berebut
       posisi (halaman bisa melompat balik). */
    const lenisNow = () => (window.BkMotion && window.BkMotion.lenis) || null;
    const scrollPageTo = (top, animate, done) => {
        const lenis = lenisNow();
        let settled = false;
        const finish = () => { if (settled) return; settled = true; if (done) done(); };

        if (lenis && typeof lenis.scrollTo === 'function') {
            try {
                lenis.scrollTo(top, {
                    duration: animate ? 0.7 : 0,
                    immediate: !animate,
                    force: true,
                    onComplete: finish
                });
                window.setTimeout(finish, animate ? 900 : 120);
                return;
            } catch (error) { /* jatuh ke gulir asli */ }
        }

        try {
            window.scrollTo({ top: top, left: 0, behavior: animate ? 'smooth' : 'auto' });
        } catch (error) {
            window.scrollTo(0, top);
        }
        window.setTimeout(finish, animate ? 700 : 60);
    };

    /* Saat logo ringkas dipakai kembali ke atas, header ditahan dalam bentuk
       ringkas sampai gulir selesai. Tanpa penahan ini header berubah lagi di
       tengah jalan, tombol berpindah dari bawah kursor, dan klik berikutnya
       mendarat pada isi halaman di belakangnya. */
    let headerHold = 0;
    const clock = () => (window.performance && typeof window.performance.now === 'function' ? window.performance.now() : Date.now());
    // Logo ringkas berfungsi sebagai "kembali ke atas" dan tetap hidup walau
    // dukungan <dialog> tidak tersedia, jadi dipasang sebelum pengecekan itu.
    const compactLogo = header && header.querySelector('[data-bk-expand]');
    if (compactLogo) {
        compactLogo.addEventListener('click', event => {
            event.preventDefault();
            let reduce = false;
            try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch { reduce = false; }

            headerHold = clock() + 1200;
            scrollPageTo(0, !reduce, () => { headerHold = 0; syncHeader(); });
            window.setTimeout(() => { if (headerHold) { headerHold = 0; syncHeader(); } }, 1250);

            const topLink = header.querySelector('.bk-logo');
            if (topLink) {
                window.setTimeout(() => { try { topLink.focus({ preventScroll: true }); } catch { topLink.focus(); } }, reduce ? 0 : 320);
            }
        });
    }
    if (!header || typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return;
    document.documentElement.classList.add('bk-enhanced');
    const expand = header.querySelector('[data-bk-expand]');
    const filterForm = document.querySelector('[data-filter-form]');
    const filterSlot = document.querySelector('[data-filter-slot]');
    if (filterForm && filterSlot) filterSlot.appendChild(filterForm);
    const triggers = document.querySelectorAll('[data-open-dialog]');
    triggers.forEach(button => { button.hidden = false; });
    // Status header: ringkas saat halaman digulir, tetap terlihat saat menu dibuka.
    // Tidak ada flag buka-tahan permanen: header tidak boleh menghilang setelah
    // logo ringkas diketuk lalu halaman digulir lagi.
    const radialMenu = header.querySelector('.bk-header-radial');
    const menuOpen = () => !!document.querySelector('.bk-dialog[open], .bk-header-radial[open]');
    const focusWithoutScroll = element => { if (!element) return; try { element.focus({ preventScroll: true }); } catch { element.focus(); } };
    // `:focus-visible` tidak konsisten di WebView lama; lacak modalitas input.
    let keyboardMode = false;
    document.addEventListener('keydown', () => { keyboardMode = true; }, true);
    document.addEventListener('pointerdown', () => { keyboardMode = false; }, true);
    const keyboardFocusInsideHeader = () => {
        const active = document.activeElement;
        return keyboardMode && !!active && active !== expand && header.contains(active) && active.offsetParent !== null;
    };
    let pinned = false;

    /* Morph ringkas ⇄ penuh.
       Selama ini perubahan navbar menjadi pil hanya "melompat": kelas
       is-compact memasang display:none pada isi lebar, jadi tidak ada yang
       bisa dianimasikan CSS. Di sini ukuran kotak header diambil sebelum dan
       sesudah kelas berubah, lalu dianimasikan per frame (pola FLIP
       sederhana). Isi yang muncul/menghilang diberi animasi halus lewat
       `.bk-header--morphing` di berkah-motion.css.
       Tanpa GSAP, saat gerak dimatikan, atau bila ukurannya tidak berubah,
       kelas dipasang seperti sebelumnya — jadi perilaku dasarnya tetap. */
    const motionAllowed = () => {
        try { return !window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch { return true; }
    };
    /* Tween yang tertinggal (tab disembunyikan tepat saat mulai) tidak boleh
       meninggalkan ukuran inline dan kelas penanda. Setiap morph memakai
       nomor urut; hanya morph terbaru yang boleh membersihkan. */
    let morphTicket = 0;
    let morphStamp = 0;
    const morphSettle = (gsap, ticket) => {
        if (ticket !== morphTicket) return;
        gsap.set(header, { clearProps: 'width,height,transform' });
        header.classList.remove('bk-header--morphing');
    };
    const morphHeader = mutate => {
        const gsap = window.gsap;
        if (!gsap || !motionAllowed() || typeof gsap.fromTo !== 'function') {
            mutate();
            return;
        }
        /* Gulir sangat cepat memicu morph dua arah dalam hitungan
           milidetik. Tween yang saling menyusul membuat header tertinggal di
           belakang jari (terasa "patah" dan seperti menunggu), dan tween
           lama bisa menahan ukuran inline yang sudah tidak berlaku. Selama
           masih di dalam 320 ms dari morph sebelumnya, ukuran dipasang
           langsung tanpa animasi — gerak cepat memang tidak butuh tween. */
        const stampNow = clock();
        if (stampNow - morphStamp < 320) {
            morphStamp = stampNow;
            morphTicket += 1;
            gsap.killTweensOf(header);
            gsap.set(header, { clearProps: 'width,height,transform' });
            header.classList.remove('bk-header--morphing');
            mutate();
            return;
        }
        const classBefore = header.className;
        const boxBefore = header.getBoundingClientRect();
        mutate();
        if (header.className === classBefore) return;
        const boxAfter = header.getBoundingClientRect();
        if (!boxBefore.width || !boxAfter.width) return;
        const shiftX = boxBefore.left - boxAfter.left;
        const shiftY = boxBefore.top - boxAfter.top;
        if (Math.abs(boxBefore.width - boxAfter.width) < 6
            && Math.abs(boxBefore.height - boxAfter.height) < 4
            && Math.abs(shiftX) < 6 && Math.abs(shiftY) < 6) return;
        const ticket = ++morphTicket;
        morphStamp = clock();
        header.classList.add('bk-header--morphing');
        gsap.killTweensOf(header);
        /* xPercent: -50 mengembalikan perataan tengah milik CSS
           (`transform: translateX(-50%)`) yang akan ditimpa GSAP begitu ia
           menulis transform. x/y menutup selisih posisi absolut ⇄ fixed,
           sehingga pil tidak "melompat" saat berganti mode di tepi atas
           layar: ia tumbuh dari tempat navbar penuh terakhir terlihat. */
        gsap.fromTo(header,
            { xPercent: -50, x: shiftX, y: shiftY, width: boxBefore.width, height: boxBefore.height },
            {
                x: 0,
                y: 0,
                width: boxAfter.width,
                height: boxAfter.height,
                duration: 0.34,
                ease: 'power2.inOut',
                overwrite: true,
                onComplete: () => morphSettle(gsap, ticket)
            });
        window.setTimeout(() => morphSettle(gsap, ticket), 700);
    };

    /* Ambang pil ringkas diturunkan dari kotak header itu sendiri, bukan dari
       angka mati. Ambang lama (180 px) menyisakan celah: tepi bawah navbar
       penuh sudah lewat layar pada ±86 px, tetapi pil baru muncul di 180 px,
       jadi selama ±94 px gulir tidak ada header sama sekali. Titik keluar
       diukur saat header masih penuh dan di-cache, sebab setelah ringkas
       header sudah `fixed` dan tidak lagi mencerminkan posisinya di dokumen.
       Histeresis 8 px mencegah pil berkedip ketika gulir berhenti tepat di
       ambang. */
    let headerExit = 0;
    const headerExitPoint = () => {
        /* Diukur hanya saat header benar-benar berada di alirannya sendiri:
           mode ringkas/pinned membuatnya `fixed`, dan tween morph menulis
           ukuran inline — keduanya tidak mencerminkan posisi di dokumen. */
        if (header.classList.contains('is-compact') || header.classList.contains('is-pinned') || header.classList.contains('bk-header--morphing')) return headerExit;
        const rect = header.getBoundingClientRect();
        if (rect.height > 0) headerExit = Math.max(0, rect.bottom + window.scrollY);
        return headerExit;
    };
    const compactAt = () => Math.max(32, headerExitPoint() - 2);
    const expandAt = () => Math.max(16, headerExitPoint() - 10);

    const syncHeader = () => {
        if (clock() < headerHold) return;
        const scrolled = window.scrollY >= (header.classList.contains('is-compact') ? expandAt() : compactAt());
        if (!scrolled) {
            pinned = false;
            // Sudah penuh: jangan ukur ulang kotak header di setiap event.
            if (!header.classList.contains('is-compact') && !header.classList.contains('is-pinned')) return;
            morphHeader(() => header.classList.remove('is-compact', 'is-pinned'));
            return;
        }
        if (menuOpen() || keyboardFocusInsideHeader()) {
            // Menu terbuka / fokus keyboard di header: jangan biarkan header
            // kembali absolut lalu hilang dari layar.
            pinned = true;
            // Busur radial bertumpu pada tombolnya sendiri. Melebarkan pil
            // ringkas saat busur terbuka akan menggeser titik tumpu di tengah
            // animasi, jadi pil ringkas ditahan apa adanya — posisinya sudah
            // `position: fixed`, tidak bisa hilang dari layar.
            if (radialMenu && radialMenu.open && header.classList.contains('is-compact')) {
                return;
            }
            if (header.classList.contains('is-pinned') && !header.classList.contains('is-compact')) return;
            morphHeader(() => {
                header.classList.remove('is-compact');
                header.classList.add('is-pinned');
            });
            return;
        }
        pinned = false;
        if (header.classList.contains('is-compact') && !header.classList.contains('is-pinned')) return;
        morphHeader(() => {
            header.classList.remove('is-pinned');
            header.classList.add('is-compact');
        });
    };
    const updateHeader = syncHeader;
    /* Satu sinkronisasi per frame: pembacaan getBoundingClientRect di dalam
       handler scroll tanpa penjadwalan bisa berjalan puluhan kali per frame
       saat gulir cepat dan memaksa tata letak ulang. */
    let headerFrame = 0;
    const queueHeaderSync = () => {
        if (headerFrame) return;
        headerFrame = window.requestAnimationFrame(() => { headerFrame = 0; syncHeader(); });
    };
    window.addEventListener('scroll', queueHeaderSync, { passive: true });
    window.addEventListener('resize', () => {
        // Hanya buang ukuran cache bila header sedang bisa diukur; kalau tidak,
        // nilai lama jauh lebih baik daripada 0 (pil akan muncul terlalu dini).
        if (!header.classList.contains('is-compact') && !header.classList.contains('is-pinned')) headerExit = 0;
        queueHeaderSync();
    }, { passive: true });
    if (radialMenu) {
        // Sinkronkan dua arah: membuka menu harus langsung memin header, dan
        // menutupnya harus mengembalikan mode ringkas.
        radialMenu.addEventListener('toggle', syncHeader);
    }
    header.addEventListener('focusout', () => { window.setTimeout(syncHeader, 0); });
    // Disclosure native tidak menutup saat klik di luar/Escape; tambahkan agar
    // menu selalu bisa ditutup dan header kembali ringkas.
    const closeRadialMenu = () => {
        // Animasi keluar dipegang berkah-radial.js: menutup lewat sini akan
        // menghapus [open] seketika dan memotong gerak menyusut. Minta dulu,
        // baru jatuh ke penutupan langsung bila berkas itu tidak termuat.
        if (window.bkRadial && typeof window.bkRadial.close === 'function' && window.bkRadial.close()) {
            syncHeader();
            return;
        }
        if (radialMenu && radialMenu.open) radialMenu.open = false;
        syncHeader();
    };
    document.addEventListener('pointerdown', event => {
        if (!radialMenu || !radialMenu.open) return;
        if (header.contains(event.target) || (event.target.closest && event.target.closest('dialog'))) return;
        closeRadialMenu();
    }, true);
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || !radialMenu || !radialMenu.open) return;
        closeRadialMenu();
        focusWithoutScroll(radialMenu.querySelector('summary'));
    });
    if (expand) expand.hidden = false;
    function openDialog(dialog, trigger) {
        if (!dialog || dialog.open || document.querySelector('.bk-dialog[open]')) return;
        dialog.dataset.returnFocus = trigger?.dataset.openDialog || '';
        // Keadaan pembuka harus ikut terbaca pembaca layar, bukan hanya visual.
        triggers.forEach(button => button.setAttribute('aria-expanded', button === trigger ? 'true' : 'false'));
        dialog.dataset.returnScroll = String(window.scrollY);
        dialog.showModal();
        focusWithoutScroll(dialog.querySelector('[data-filter-form] [aria-invalid="true"]') || dialog.querySelector('[data-filter-form] input') || dialog.querySelector('[data-close-dialog]'));
    }
    triggers.forEach(button => button.addEventListener('click', () => openDialog(document.getElementById(button.dataset.openDialog), button)));
    document.querySelectorAll('.bk-dialog').forEach(dialog => {
        dialog.querySelector('[data-close-dialog]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('close', () => {
            triggers.forEach(button => button.setAttribute('aria-expanded', 'false'));
            const savedScroll = Number(dialog.dataset.returnScroll || NaN);
            // Dialog modal dapat memaksa halaman melompat ke atas; kembalikan posisi baca semula.
            if (Number.isFinite(savedScroll) && window.scrollY + 4 < savedScroll) {
                scrollPageTo(savedScroll, false);
            }
            syncHeader();
            const trigger = document.querySelector(`[data-open-dialog="${dialog.dataset.returnFocus}"]`);
            const back = trigger && (!header.contains(trigger) || !header.classList.contains('is-compact')) ? trigger : expand;
            focusWithoutScroll(back);
            /* Pemicu bisa saja sudah tidak bisa difokuskan saat dialog ditutup
               (mis. filter diterapkan lalu tombolnya disembunyikan). Fokus yang
               gagal mendarat akan jatuh ke <body>, jadi pakai tombol logo
               sebagai jaring pengaman — sama seperti busur radial di ponsel. */
            if (back && expand && back !== expand && document.activeElement !== back) {
                focusWithoutScroll(expand);
            }
            syncHeader();
        });
    });
    if (filterForm?.dataset.filterErrors === '1') openDialog(document.getElementById('bk-filter'), document.querySelector('[data-open-dialog="bk-filter"]'));
    updateHeader();
})();
