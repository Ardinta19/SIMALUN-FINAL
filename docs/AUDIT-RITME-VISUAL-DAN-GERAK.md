# Audit ritme visual & lapisan gerak — lintas peran

Tanggal: 22 November 2026
Cakupan: **guest** (katalog publik, QRIS), **auth** (login, daftar, splash),
**customer** (beranda → katalog → checkout → pesanan → pembayaran), dan
**admin** (shell, dasbor, modul sewa).
Dimensi yang diperiksa: tipografi, ukuran teks, line-height, spacing
(padding/margin/gap), whitespace, spacing scale, proximity, hierarki,
alignment & grid, kartu, area sentuh, safe area, responsif, serta
**gerak** (GSAP, ScrollTrigger, Lenis, micro-interaction).

Berkas bukti: `resources/views/{catalog,rental,roles,auth}`, skelet
`layouts/{admin,guest}`, `catalog/layout.blade.php`,
`public/css/berkah-{admin,theme,ui-kit,experience,controls,motion}.css`,
`public/js/berkah-*.js`, `public/vendor/{gsap,lenis}`.

---

## 1. Ringkasan

Tiga akar masalah, bukan dua puluh:

| # | Akar | Akibat yang terlihat |
|---|---|---|
| A | **Tidak ada tangga ukuran.** Setiap berkas CSS memilih ukuran sendiri: `.55`, `.6`, `.62`, `.64`, `.66`, `.68`, `.7`, `.72`, `.73`, `.74`, `.76`, `.78` rem. Semuanya huruf kapital dengan `letter-spacing` lebar. | Label penting (grup menu, kolom tabel, label bilah bawah) berukuran 9–11 px; mata cepat lelah, label terlihat "berdesakan" walau jaraknya cukup. |
| B | **Jarak ditulis sebagai angka lepas** (`padding: 9px 12px`, `gap: 10px`, `margin: 0 0 16px`) tanpa skala. | Irama vertikal berubah tiap halaman; proximity tidak konsisten: judul dan isinya kadang lebih jauh daripada isi ke blok berikutnya. |
| C | **Gerak dipasang per halaman.** GSAP dimuat di semua halaman, tetapi hanya dipakai admin, sheet kontrol, splash, dan sakelar tema. Tidak ada gulir halus, tidak ada kemunculan berbasis posisi gulir, tidak ada micro-interaction pada kartu/tombol. | Halaman terasa "diam": tidak ada isyarat bahwa konten bertingkat dan bisa digulir; interaksi terasa kasar dibanding panel admin. |

Perbaikan putaran ini menyentuh ketiganya: **satu skala** (`--bk-fs-*`,
`--bk-space-*`, `--adm-fs-*`, `--adm-space-*`), **batas bawah teks 12 px**,
**satu mesin gerak** (`berkah-motion.js`) dengan pembagian tugas yang jelas
antara Lenis, GSAP, dan ScrollTrigger, plus **area sentuh 44 px**.

---

## 2. Temuan per peran

### 2.1 Guest — katalog publik & QRIS

| Dimensi | Temuan | Status |
|---|---|---|
| Tipografi | Kerangka `catalog/layout` sudah punya dasar yang baik (`line-height:1.6`, `input` 1 rem, judul `clamp`). Masalah ada di label mikro: `.bk-kicker`/`.bk-eyebrow` 10–11 px dengan tracking `.14em`. | **Diperbaiki**: seluruh teks ≤ 11,5 px di `berkah-experience.css` dinaikkan ke 12 px, tracking dirapatkan ke `.08–.12em`. |
| Line-height | Judul 1.3, isi 1.6 — dua nilai untuk seluruh halaman. | **Disatukan**: judul 1.2 (`--bk-lh-tight`), isi 1.65 (`--bk-lh-body`). |
| Spacing | `.bk-filter-bar` `margin-bottom:20px`, `.bk-section-heading` `gap:16px`, kartu `padding:clamp(16px,2vw,22px)` — tiga sumber jarak tanpa tangga. | **Sebagian**: token `--bk-space-1..10` tersedia; pemakaian menyeluruh menyusul per halaman. |
| Grid | `.grid` kartu sudah `auto-fit` + `minmax`, aman di 320 px. | Sesuai. |
| Kartu | Foto `aspect-ratio`, radius 14–16 px, elevasi hanya saat hover. | **Ditambah**: elevasi halus saat kartu disentuh kursor (`data-bk-tilt`). |
| Area sentuh | `.bk-qty__btn` 36 px, `.bk-atc` 48 px, tautan kategori 30-an px. | **Diperbaiki**: pada penunjuk kasar semua kendali ikon ≥ 44 px; `touch-action: manipulation` menghapus jeda 300 ms. |
| Safe area | Sheet filter sudah memakai `env(safe-area-inset-bottom)`. | **Ditambah**: pengaman `left/right` untuk lanskap berponi. |
| Gerak | Statis. | **Ditambah**: hero muncul, langkah "Cara memesan" berurutan, kartu katalog berurutan saat masuk layar, parallax halus di hero pelanggan. |

### 2.2 Auth — login, daftar, splash

| Dimensi | Temuan | Status |
|---|---|---|
| Hierarki | Splash merek → kartu formulir. Urutannya benar, tetapi kartu tidak punya "naik" saat muncul. | Gerak splash (GSAP timeline, `rotate:-360`, `inert`) sudah kuat; **tidak diubah** agar kontrak `UiDefaultsTest` tetap utuh. |
| Tipografi | `.card-sub`, `.form-label` konsisten; pesan galat memakai `.alert`. | **Selesai (putaran 3)**: ⚠️/✅ diganti `alert-triangle`/`check-circle`; ikon kolom (email/HP/password/alamat) dan sakelar mata memakai glyph Lucide.
| Spacing | Jarak `form-group` 18–20 px, tombol 48 px. | Sesuai. |
| Area sentuh | Input 48 px, tombol `.bk-auth` 48 px. | Sesuai. |
| Gerak | GSAP timeline splash + morph tombol. | Sesuai; Lenis aktif di halaman auth agar gulir formulir panjang terasa sama. |

### 2.3 Customer — beranda → pesanan → pembayaran

| Dimensi | Temuan | Status |
|---|---|---|
| Spacing scale | Semua jarak lepas (`18px`, `16px`, `14px`) meski halaman ini dijadikan acuan responsif. | **Sebagian**: token tersedia; beranda & daftar pesanan memakai kemunculan berurutan yang membuat jarak terasa lebih sengaja. |
| Hierarki | Beranda: sambutan → pratinjau katalog → catatan pengujian. Baik. | Sesuai |
| Grid | Pratinjau katalog diisi `dashboard-preview.js`; tinggi kartu melompat saat data masuk. | **Belum**: perlu skeleton setinggi kartu sebenarnya. |
| Label mikro | Label status pesanan dan "REFERENSI PESANAN" 11 px kapital. | **Diperbaiki** lewat batas 12 px pada `berkah-experience.css`; kapital dipertahankan karena berfungsi sebagai label, bukan kalimat. |
| Area sentuh | Bilah bawah `.customer-nav__item` sudah 44 px+. | Sesuai |
| Safe area | `padding-bottom: calc(84px + env(...))` pada `body.bk-customer`. | Sesuai |
| Responsif | Mobile/tablet/desktop dibedakan (`bk-desktop-nav`, bilah bawah khusus ≤700 px). | Sesuai — tetap acuan untuk peran lain. |
| Gerak | Statis selain transisi halaman. | **Ditambah**: kemunculan berurutan pada daftar pesanan dan unit, parallax kecil pada bunga beranda. |

### 2.4 Admin — shell, dasbor, modul sewa

| Dimensi | Temuan | Status |
|---|---|---|
| Tipografi mikro | `.adm-brand__tag` 10,9 px, `.adm-nav__group` 10,2 px, `.adm-side-card__label` 10,6 px, `.adm-kicker` 10,9 px, `.adm-table th` 11,2 px, label tabel bertumpuk 11,2 px. | **Diperbaiki**: semua `font-size` ≤ 11,8 px → `var(--adm-fs-2xs)` (12 px); tracking diturunkan ke `.06–.12em` agar tidak berderet. |
| Line-height | Tidak ada aturan global; judul mengikuti bawaan peramban. | **Ditambah**: `h1..h3` 1.2 + `text-wrap: balance`; `p/li` 1.65; paragraf dibatasi `68ch`. |
| Spacing scale | `--adm-gap` sudah ada, tetapi padding kartu, kepala kartu, dan kaki kartu memakai angka lepas. | **Ditambah**: `--adm-space-1..10`; `.adm-grid`, `.adm-card__head`, `.adm-card__foot` memakai tangga itu. |
| Area sentuh | `.adm-nav a` 42 px, `.adm-user__out` 34 px, `.adm-chip--link` 36 px. | **Diperbaiki**: 44/38/38 px, plus aturan `pointer: coarse` untuk semua kendali. |
| Safe area | Bilah bawah ponsel sudah `env()`; konten hanya di `max-width:700px`. | **Ditambah**: `left/right` aman + `padding-bottom` memakai token. |
| Tema gelap | `--adm-surface` dan latar nyaris sama; kartu hanya dibedakan garis 1 px. | **Diperbaiki**: lapisan gelap dirapatkan (`#0e0a0d` / `#1c161a` / `#251d22` / `#2e242a`). |
| Grid | `.adm-grid--2/--3` jatuh ke 1 kolom di ≤760 px. | Sesuai |
| Gerak | Reveal `data-adm-reveal` + GSAP hitung angka. | Sesuai; Lenis kini berlaku juga di admin, dan gulir CSS `smooth` dimatikan agar tidak dobel. |

---

## 3. Lapisan gerak: siapa mengerjakan apa

| Pustaka | Berkas | Tugas | Alasan |
|---|---|---|---|
| **Lenis 1.3.11** | `public/vendor/lenis/lenis.min.js` + `lenis.css` | Gulir halus berbobot di mouse dan trackpad; disinkronkan ke ScrollTrigger; berhenti otomatis saat sheet mengunci gulir. **Gulir sentuh sengaja tidak diambil alih** (`syncTouch: false`). | Gulir adalah interaksi paling sering di semua perangkat, tetapi gestur jari paling halus bila ditangani peramban: momentum, pantulan, dan cubit-zoom tetap asli, dan halaman tidak bisa membeku ketika mesin gerak berhenti sesaat. Lihat §9.55. |
| **GSAP 3.15.0** | `public/vendor/gsap/gsap.min.js` | Tween, timeline splash, hitung angka, tekanan tombol berpegas, kemiringan kartu. | Sudah dipakai; tetap mesin utama. |
| **ScrollTrigger 3.15.0** | `public/vendor/gsap/ScrollTrigger.min.js` | Kemunculan berbasis posisi gulir (`once`), parallax `scrub`, pemicu hitungan. | Satu-satunya cara hemat: hanya elemen yang terlihat yang beranimasi. |
| **Micro-interaction** | `public/js/berkah-motion.js` | `data-bk-press` (tekanan berpegas), `data-bk-tilt` (kartu katalog), `data-bk-count` (angka), `data-bk-reveal`/`-group` (kemunculan), `data-bk-parallax`. | Perlu, tapi harus punya fungsi: mengarahkan mata, bukan menghibur. |

Aturan keselamatan yang dipatuhi:

1. **Tidak ada konten yang disembunyikan lewat CSS.** Keadaan awal animasi dipasang
   GSAP saat skrip berjalan; bila skrip gagal, semua elemen tetap terlihat.
2. `prefers-reduced-motion: reduce` → Lenis, parallax, tilt, dan reveal dimatikan;
   semua elemen diberi `is-in` dan aturan CSS memaksa `transform:none`.
3. `saveData` / `effectiveType: 2g` → Lenis dan parallax dilewati.
4. Semua animasi hanya `transform` + `opacity`; tidak ada animasi `top/left/width`.
5. Lenis dilepas pada `pagehide` dan saat pengguna beralih ke mode gerak minimal.
6. Tanpa CDN: seluruh pustaka disajikan dari `public/vendor` dengan `?v=filemtime`
   (kontrak `tests/Feature/UiDefaultsTest.php`).

---

## 4. Yang dikerjakan pada putaran ini

| Berkas | Perubahan |
|---|---|
| `public/vendor/lenis/lenis.min.js`, `lenis.css` | Lenis 1.3.11 disalin dari npm (`lenis@1.3.11`), disajikan lokal. |
| `public/vendor/gsap/ScrollTrigger.min.js` | ScrollTrigger 3.15.0 dari paket `gsap@3.15.0` (identik dengan `gsap.min.js` yang sudah ada). |
| `public/js/berkah-motion.js` | Mesin gerak baru: Lenis + ScrollTrigger + reveal + parallax + hitung + tekanan + tilt, dengan pengaman reduced-motion/saveData dan API `window.BkMotion.refresh()`. |
| `public/css/berkah-motion.css` | Token durasi/easing, netralisasi `scroll-behavior` saat Lenis aktif, area sentuh 44 px, `touch-action`, dan pengaman safe area untuk sheet. |
| `resources/views/layouts/component/_form_controls.blade.php` | Memuat lenis.css/js, ScrollTrigger, `berkah-motion.css`, dan `berkah-motion.js` — satu titik muat untuk semua peran. |
| `public/css/berkah-ui-kit.css` | Tangga tipe & jarak bersama (`--bk-fs-*`, `--bk-space-*`, `--bk-touch`, `--bk-lh-*`) + lapisan dasar keterbacaan (judul 1.2, `text-wrap`, `68ch`). |
| `public/css/berkah-admin.css` | Tangga `--adm-fs-*`/`--adm-space-*`, batas 12 px untuk 15 deklarasi mikro, tracking dirapatkan, area sentuh 44 px, lapisan tema gelap, aturan `pointer: coarse`. |
| `public/css/berkah-experience.css` | 30 label mikro → 12 px, tracking `.14–.16em` → `.08–.12em`. |
| `public/css/berkah-theme.css` | Token `--theme-card` agar kartu terangkat dari panel pada tema gelap (bukan tambalan per selektor). |
| `catalog/index`, `catalog/show`, `rental/index`, `rental/show`, `roles/customer/dashboard` | Atribut `data-bk-reveal`, `-group`, `-parallax`, `-tilt` pada elemen yang memang bertingkat. |

---

## 5. Sisa pekerjaan (status putaran 4)

Semua butir di bawah **sudah dikerjakan** pada putaran 4 (§9). Yang tersisa
hanya pekerjaan opsional gerak lanjutan, dan itu memang bukan cacat:

| # | Butir | Status |
|---|---|---|
| 1 | Skeleton setinggi kartu pada pratinjau katalog pelanggan | Selesai — `.bk-preview-skeleton` + `dashboard-preview.js` |
| 2 | Densitas tabel admin dan angka statistik yang berhitung naik | Selesai — `td` 8×12 px, `Nominal` rata kanan, `.revenue__val` ikut `data-adm-count` |
| 3 | Adopsi `--bk-fs-*`/`--bk-space-*` di view + audit tablet 768–1024 px | Selesai — 170+ ukuran lepas disapu; `.grid` 3 kolom sampai 1024 px |
| 4 | Gerak lanjutan: `SplitText` hero, `Flip` daftar pesanan | **Belum** — opsional, tidak ada cacat yang ditutup |
| 5 | Palet `type-info` modal konfirmasi | Selesai — plum via token, dua salinan partial digabung |

## 6. Cara memeriksa

```bash
# pustaka lokal & tanpa CDN
grep -rn "cdnjs\|unpkg\|jsdelivr" resources/views | wc -l     # harus 0
ls public/vendor/gsap public/vendor/lenis

# kontrak gerak
php artisan test --filter=UiDefaultsTest
php artisan test --filter=MotionAssetsTest

# keterbacaan: tidak boleh ada ukuran literal di bawah 12 px
php artisan test --filter=TypographyScaleTest
grep -rho "font-size: *0\?\.[0-9]*rem" public/css resources/views | sort -u  # harus kosong
grep -rho "font-size: *\(9\|10\|11\)px" public/css resources/views | sort -u

# ikon & modal
php artisan test --filter=IconSystemTest
grep -rn "data-confirm-glyph\|#005f92" resources/views public/css   # harus kosong
```

Di peramban: matikan JavaScript → konten harus tetap utuh (pratinjau katalog
tetap menampilkan teks cadangan, bukan kerangka kosong); aktifkan
`prefers-reduced-motion: reduce` → tidak boleh ada gerak; buka sheet filter →
gulir halaman di belakangnya harus terkunci; buka modal konfirmasi →
Tab hanya berputar antara Batal dan Konfirmasi, dan fokus kembali ke tombol
pemicu setelah ditutup.


---

## 7. Putaran 2 — checkout & pembayaran

| Dimensi | Temuan | Perbaikan |
|---|---|---|
| Spacing scale | Setiap bagian checkout membawa `style="margin-bottom:16px"` sendiri, jadi irama berubah saat jumlah unit bertambah; jarak antarbagian voucher/ketentuan/persetujuan tidak seirama. | `.checkout-form` kini grid dengan `gap` dari `--bk-space-*`; margin inline di markup dihapus. Satu sumber jarak untuk berapa pun unitnya. |
| Proximity | Judul bagian (`h3`) menempel ke isian di atasnya, sama jauhnya dengan isian di bawahnya. | `h3` memakai `--bk-space-6` di atas (pemisah antar-bagian) dan `--bk-space-2` di bawah (judul dengan isinya). |
| Hierarki aksi | Tombol "Kirim pesanan" selebar teksnya, mudah terlewat di ujung formulir panjang. | 100% lebar di ponsel, min 260 px di desktop, tinggi 52 px, dengan tekanan berpegas (`data-bk-press`). |
| Area sentuh | `summary` bagian unit dan chip "Langsung ke" setinggi ±34 px. | `summary` dan chip ≥ 44 px (`--bk-touch`). |
| Kartu bersarang | Riwayat pembayaran & preview memakai `style="margin:10px 0"` di dalam kartu. | Kelas `.bk-row-card` dengan tangga `--bk-space-3` / `--bk-space-4` dan margin anak pertama/terakhir dinolkan. |
| QRIS | Gambar QRIS diskalakan `max-width:260px` tanpa bidang putih eksplisit; di tema gelap bidang di sekitarnya menjadi gelap sehingga aplikasi pemindai bisa gagal membaca. | `.bk-qris`: bidang putih, padding, radius 18 px, `loading="lazy"`, lebar `min(320px, 100%)`. |
| Angka | Nominal pembayaran memakai lebar digit proporsional. | `font-variant-numeric: tabular-nums` pada `#payment-amount` dan tombol "isi sisa". |

Berkas yang disentuh pada putaran ini: `public/css/berkah-experience.css`,
`resources/views/rental/checkout.blade.php`,
`resources/views/rental/unit.blade.php`, `public/js/berkah-motion.js`
(pembagian tugas roda vs sentuh), `public/css/berkah-motion.css` (token sentuh
pindah ke ui-kit),
`public/css/berkah-ui-kit.css`.


---

## 8. Putaran 3 — ikon dari paket resmi, bukan salinan tangan

### 8.1 Keputusan

Satu keluarga ikon saja: **Lucide**. Bukan menambah keluarga kedua, karena
halaman sudah memakai Lucide di 147 titik dan mencampur dua set membuat
ketebalan serta sudut ikon tidak lagi seragam. Yang berubah adalah **asal
geometrinya**:

| Sebelum | Sesudah |
|---|---|
| 50 path disalin tangan ke dalam `_icon.blade.php` (sulit ditambah, rawan salah kutip). | 75 glyph dari paket resmi `lucide-static` 1.78.0 (ISC), disalin ke `public/vendor/lucide/icons/`, dirakit oleh `tools/build-lucide-icons.mjs` menjadi `_icon_glyphs.php`. |
| Menambah ikon = menyalin `d` path dari internet. | Menambah ikon = salin satu `.svg` + jalankan skrip perakit. |
| Tidak ada jejak lisensi. | `public/vendor/lucide/LICENSE` + `VERSION.txt` + prosedur di dalamnya. |

Logika partial tidak berubah: alias konsep (`'suka' => 'heart'`), normalisasi
ukuran ke {16, 20, 24, 32}, dan fallback `circle-dot` tetap di
`_icon.blade.php`. Artinya kontrak aksesibilitas (`role="img"` vs
`aria-hidden`) dan keseragaman stroke tidak tersentuh.

### 8.2 Emoji yang diganti

| Sebelum | Sekarang | Di mana |
|---|---|---|
| ⚠️ / ✅ | `alert-triangle` / `check-circle` | `.alert` login, daftar, alamat |
| 📧 📱 / 🔒 🔐 / 👁️ 🙈 | `mail`, `phone`, `lock`, `lock-keyhole`, `eye`, `eye-off` | kolom isian & sakelar sandi (login, daftar) |
| 👤 👨 👩 | `user` & label teks saja | formulir daftar (pilihan jenis kelamin tidak perlu pictogram) |
| 🏠 📝 🎉 | `home`, `file-text`, `party-popper` | formulir daftar |
| 🌹 💐 ⏳ 🌺 🌸 🔑 ↩️ 🔄 | `lock-keyhole`, `search`, `clock`, `alert-triangle`, `settings`, `key-round`, `undo-2`, `refresh-cw` | lima halaman galat (hero + tombol aksi) |
| ★ / ♡ ♥ | `star`, `heart` | ringkasan rating & jumlah suka |
| ☰ | `menu` | tombol menu pelanggan |
| 🗂 📒 🎟 🎓 🌷 | `folder-open`, `book-open`, `ticket`, `graduation-cap`, `flower-2` | keadaan kosong panel admin |
| ⚡ ⚠ ℹ️ ✓ | `zap`, `alert-triangle`, `info`, `check` | modal konfirmasi pelanggan |
| ✓ (dot langkah) | tanda centang digambar CSS | indikator langkah pendaftaran |
| ✿ (21 titik) | `flower-2` | kelopak splash, bunga ambient, placeholder foto, penanda kartu pesanan, keadaan kosong |

Emoji bertukar teks di JS juga dibereskan, bukan hanya markup: sakelar sandi
dan ikon pengenal kini menukar glyph yang sudah ada di DOM (bukan menulis
emoji ke `textContent`), jumlah suka diperbarui di `<span>` khusus agar ikon
hati tidak terhapus, dan toast admin menyuntik SVG Lucide yang sama.

### 8.3 Penjaga

`tests/Feature/IconSystemTest.php` memastikan:

1. peta glyph dibangkitkan dari `public/vendor/lucide/icons` dan **sinkron**
   dengan berkas SVG-nya (kalau tidak, tes menyuruh menjalankan skrip perakit);
2. setiap nama ikon yang dipakai di view dapat diselesaikan lewat glyph atau
   alias — tidak ada ikon yang diam-diam jatuh ke `circle-dot`;
3. **nol emoji pictograf** di seluruh `resources/views` (rentang emoji,
   simbol teknika, dan variation selector); panah tipografis biasa tetap boleh;
4. `circle-dot` sebagai fallback resmi masih ada.

Dampak tambahan: CSS `.bk-i` (di `berkah-ui-kit.css`) membuat ikon mewarisi
ukuran huruf induknya, jadi ikon di dalam teks tidak lagi butuh `width/height`
manual — sekaligus menyeragamkan bobot goresan (1,75px untuk ikon teks).

---

## 9. Putaran 4 — sisa §5 ditutup

### 9.1 Modal konfirmasi: satu sumber, satu palet

| Dulu | Sekarang |
|---|---|
| Dua partial: `_confirm_modal` (bersama) dan `customer/_confirm_modal` (salinan). Salinan pelanggan menyimpang: default `type: 'danger'`, `type-info` biru `#005f92`, tanpa `type-success`. | `customer/_confirm_modal` hanya `@include` partial bersama, jadi path lama tetap jalan tetapi tidak ada lagi dua perilaku. |
| Warna ditulis di dalam `<style>` partial (`#e0f4ff`, `#0d6fb8`, `#005f92`, `#c62f2f`, …) lalu ditambal per selektor di tema gelap (`.confirm-icon.type-info`, `.type-danger`, `.type-warning`, `.type-success`, `.confirm-btn--cancel`, `.confirm-message`). | Token `--bk-state-{danger,warning,info,success}-{soft,line,ink,solid}`, `--bk-modal-*`, `--bk-cancel-*`, `--bk-focus-ring`, `--bk-scrim` (terang di `berkah-ui-kit.css`, gelap di `berkah-theme.css` §8, dan dikembalikan di blok `@media print`). Enam tambalan tema gelap dihapus. |
| `type-info` biru — satu-satunya keluarga warna di luar merek. | `type-info` **plum** (`#5a3a70` / `#f4eff8`), masih hangat dan sekeluarga dengan wine, tetapi tidak bisa tertukar dengan merah `type-danger`. |

Detail kecil yang ikut dibereskan:

- jarak/tipe memakai `--bk-space-*` dan `--bk-fs-*`; tombol `min-height: var(--bk-touch)` (44 px) dan `touch-action: manipulation`;
- `#confirm-icon` memakai `<span class="bk-i">` sehingga ikon mengikuti `font-size` induk, bukan atribut `width/height` SVG;
- dialog diberi `aria-describedby="confirm-message"`, ikon dekoratif `aria-hidden`, dan cincin fokus `--bk-focus-ring`;
- fokus awal jatuh ke **Batal** untuk `danger` dan ke aksi utama untuk tipe lain; Tab dijebak di antara dua tombol dan fokus kembali ke pemicu saat ditutup;
- gulir halaman dikunci lewat kelas `confirm-locked` + penanda `lenis-stopped-lock` (dibaca `berkah-motion.js`), dengan kompensasi lebar bilah gulir supaya tata letak tidak bergeser;
- kartu dibatasi `max-height` dengan fallback `vh` lalu `dvh`, `overscroll-behavior: contain`, dan `data-lenis-prevent` supaya pesan panjang tetap bisa digulir;
- `prefers-reduced-motion: reduce` mematikan transisi buka/tutup;
- nama tipe tak dikenal jatuh ke `info`, bukan kelas tanpa gaya.

### 9.2 Pratinjau katalog: kerangka setinggi kartu

`dashboard-preview.js` mengisi empat kartu setelah `fetch` selesai. Dulu wadah
hanya berisi satu paragraf, jadi tinggi bagian melompat saat kartu masuk.
Sekarang:

- markup memuat `.bk-preview-skeleton` (empat kartu: media 4/3, tiga baris teks,
  satu tombol) dengan atribut `hidden` — tanpa JavaScript yang tampil tetap
  teks cadangan, bukan ruang kosong;
- skrip memasang `is-loading` + `aria-busy="true"` **sebelum** `fetch`, dan
  melepasnya di ketiga ujung alur (berhasil / kosong / gagal) sehingga kerangka
  tidak pernah tertinggal;
- denyut kerangka (`bk-skel-pulse`) mati saat `prefers-reduced-motion: reduce`;
- jumlah kartu kerangka selalu empat dan mengikuti grid yang sama dengan hasil
  akhir (1 baris ≥1025 px, 2×2 pada 651–1024 px, 2×2 di ponsel), sehingga
  jumlah baris tidak berubah saat kartu asli masuk.

### 9.3 Admin: densitas tabel dan angka berhitung

- `td`/`th` memakai `--adm-space-2` × `--adm-space-3` (8 × 12 px) menggantikan
  12 px seragam; `.adm-table` memakai `--adm-fs-sm`.
- Kolom `Nominal` rata kanan (`--adm-table__amount`) hanya saat tabel benar-benar
  berbentuk tabel (`min-width: 761px`); pada mode bertumpuk nilainya mengikuti
  kolom kanan.
- Kendali "Ubah catatan manual" dan dua kendali serupa di halaman admin lain
  memakai kelas bersama `.bk-disclosure` (min 38 px, fokus terlihat), bukan lagi
  `style` sebaris.
- `.revenue__val` kini ikut berhitung naik lewat `data-adm-count` (kontrak angka
  naik milik admin; `data-bk-count` tetap milik halaman pelanggan). `.stat__val`
  dan `.adm-stat__value` sudah memakainya lebih dulu, jadi tidak ada lagi angka
  besar yang diam.

### 9.4 Satu tangga tipe, batas bawah 12 px

Sapuan menyeluruh: **170 ukuran lepas di 25 view** dan **132 ukuran lepas di
CSS** diganti token (`--bk-fs-*` untuk guest/auth/customer, `--adm-fs-*` untuk
admin). Nilai 9–11 px yang tadinya lolos di `.55`–`.74rem` (label bilah bawah,
lencana, blok Riwayat, watermark splash, catatan kas) kini 12 px.

Konsekuensi yang sengaja diterima:

- lencana angka (`admin-nav__badge`, `customer-nav__badge`, `bk-action-badge`)
  ikut tumbuh dari 17 px → 19 px, dan lencana admin 16 px → 18 px, karena teks
  12 px tidak muat di lingkaran 16 px;
- override `@media (max-width: 380px) .admin-nav__label { font-size: … }`
  dihapus — dengan lantai 12 px ia menjadi aturan yang tidak berbeda dari
  dasarnya; elipsis tetap menjadi jaring pengaman;
- `.revenue__val span` dipersempit menjadi `.revenue__val .revenue__cur` supaya
  angka yang berhitung tidak mewarisi ukuran mata uang.

### 9.5 Audit tablet 768–1024 px

| Temuan | Perbaikan |
|---|---|
| `.grid` katalog masih empat kolom sampai 900 px; pada 1024 px tiap kartu hanya ±225 px sehingga judul dua baris dan tombol berdesakan. | Empat kolom baru di atas 1024 px; 768–1024 px memakai tiga kolom. |
| Pratinjau pelanggan berisi tepat empat kartu; dengan tiga kolom satu kartu menggantung sendirian. | Pada 651–1024 px pratinjau memakai 2×2 (`.grid.bk-preview-grid`). |
| Shell admin sudah punya pita tablet (`761–1079 px`, sidebar jadi rel 84 px) — tidak ada yang perlu diubah. | Dicatat sebagai sudah benar, bukan diubah. |
| Bilah navigasi bawah pelanggan: label 10 px dan pada ≤350 px turun ke 9 px. | Label 12 px dengan `--bk-lh-tight`; override 350 px dihapus. |

### 9.6 Penjaga baru

`tests/Feature/TypographyScaleTest.php` (4 tes):

1. kedua peran punya token tangga tipe yang sama nilainya;
2. tidak ada ukuran literal di bawah 12 px — termasuk angka di dalam `clamp()`
   dan `px` — di seluruh CSS dan `<style>` view (email/PDF dikecualikan karena
   klien surel tidak mengenal custom property);
3. setiap `var(--bk-fs-*)`/`var(--adm-fs-*)` yang dipakai benar-benar
   didefinisikan;
4. view tidak lagi menulis `font-size` rem di bawah `1rem` sebagai angka lepas.

Tambahan pada putaran detail:

- `UiDefaultsTest::test_map_library_is_served_locally` — Leaflet ada di
  `public/vendor/leaflet`, tidak ada `unpkg` di view.
- `UiDefaultsTest::test_browser_chrome_color_is_one_brand_value` — satu
  `theme-color` (`#54051f`) untuk seluruh aplikasi.
- `UiDefaultsTest::test_old_wine_shade_never_returns` — `#75102e` tidak boleh
  muncul lagi di CSS/view (komentar diabaikan).
- `MotionAssetsTest::test_shared_motion_respects_reduced_motion_and_avoids_blanket_transitions`
  — jaring `prefers-reduced-motion` global ada, dan tidak ada
  `transition: all` di `public/css` maupun view.
- `MotionAssetsTest::test_design_scales_are_shared_tokens` diperketat: setiap
  deklarasi `font-size` di `berkah-admin.css` diperiksa (px/rem/em) **dan**
  token `--adm-fs-*` harus dipakai lebih dari 40 kali, supaya pemeriksaan tidak
  lolos-hampa ketika semua ukuran pindah ke token.

### 9.7 Palet biru terakhir: satu merek, bukan dua

Modal bukan satu-satunya tempat biru lama tertinggal. Setelah disisir, enam
tempat masih memakai palet biru (#0072ad / #0077b6 / #002f5c / #005f92):

| Tempat | Dulu | Sekarang |
|---|---|---|
| `layouts/component/_tokens.blade.php` (token kanonik, dipakai 7 halaman) | `--brand-500: #0072ad` biru | `#85092d` wine; `--surface-*` juga dipanaskan, bayangan `rgba(84,5,31,…)` |
| `roles/admin/{notifications,profile,audit,profile_edit}` | Dua halaman menimpa token secara lokal, dua halaman lain tetap biru — shell admin dua warna. | Override lokal dihapus; keempatnya membaca token kanonik yang sama. |
| `customer/addresses/index` | `:root{--blue:#0072ad;--bg:#f4f8fc}` — seluruh halaman biru. | Nama variabel dipertahankan, nilainya wine/cream, jadi tidak ada satu pun aturan halaman yang perlu disentuh. |
| `profile/edit` | Aksen `#0072ad` pada fokus, tombol, dan tautan kembali. | Wine. |
| `vendor/pagination/customer-simple` | Langkah halaman biru (`#0072ad/#005f92`). | Wine, sepasang dengan aturan gelapnya di `berkah-theme.css`. |
| `roles/customer/addresses/{create,edit}` | `--blue-dark/-mid/-light` (nama lama, isi wine) — dibiarkan, hanya nama. | Tidak diubah: nilainya sudah benar; mengganti nama variabel akan menyentuh ratusan aturan tanpa manfaat visual. |
| `emails/layout`, favicon & `theme-color` | Gradien biru, huruf favicon "A" (sisa nama lama). | Gradien wine, huruf "B", `theme-color` wine. |

`tailwind.config.js` dan `resources/css/app.css` ikut disamakan supaya
"mirror one-to-one" di komentar token tetap benar, meski bundle Vite tidak
dipakai halaman mana pun saat ini.

### 9.8 Pustaka peta: unpkg → `public/vendor/leaflet`

Dua halaman alamat memuat Leaflet 1.9.4 dari `unpkg.com` — satu-satunya CDN
yang tersisa. Sekarang `npm pack leaflet@1.9.4` → `public/vendor/leaflet/`
(`leaflet.js`, `leaflet.css`, `images/*`, `LICENSE`, `VERSION.txt`), dirujuk
dengan `?v=filemtime` seperti aset lokal lain. Ubin peta tetap dari
OpenStreetMap (itu data, bukan aset). Penjaga:
`UiDefaultsTest::test_map_library_is_served_locally`.

### 9.9 Putaran detail: warna bilah, radius, dan gerak

Empat hal kecil yang tersisa setelah palet dan tipografi beres:

| Temuan | Sebelum | Sesudah |
|---|---|---|
| `theme-color` | Tiga nilai berbeda: `#75102e` (pemulihan sandi), `#85092d` (halaman bantuan), `#54051f` (partial pelanggan). | Satu definisi di `layouts/component/_theme_head` → `#54051f`. Skrip tema tetap menukarnya ke `#140f12` saat gelap. Penjaga: `UiDefaultsTest::test_browser_chrome_color_is_one_brand_value`. |
| Radius | `20px`/`12px` ditulis lepas di modal, kerangka pratinjau, dan tombol. | `--bk-r-card: 20px`, `--bk-r-control: 12px`; dipakai dengan fallback `var(--bk-r-card, 20px)`. |
| Ukuran/gerak modal | Judul `1.1rem`, pesan `--bk-fs-sm`, transisi `.3s`/`.25s`/`.15s`, `translateY(20px)`. | `--bk-fs-lg`, `--bk-fs-md`, `--bk-dur-1..3` + `--bk-ease-spring` (semua ber-fallback), `translateY(var(--bk-space-5))`. |
| Transisi | Enam `transition: all` di halaman auth/alamat/profil ikut menganimasikan properti tata letak. | Properti eksplisit (`background-color`, `border-color`, `color`, `box-shadow`). Penjaga: `MotionAssetsTest::test_shared_motion_respects_reduced_motion_and_avoids_blanket_transitions`. |

Aturan gelap modal kembali ditulis per tipe (`.confirm-icon.type-danger` dan
seterusnya) karena `UiKitTest` mengunci keberadaan setiap permukaan — tetapi
isinya hanya `var(--bk-state-*)`, jadi tetap satu sumber warna.

Halaman yang membawa CSS sendiri (auth, alamat, profil) tidak menulis blok
`prefers-reduced-motion`; `berkah-motion.css` — dimuat di semua halaman lewat
`_form_controls` — memegang jaring pengaman `animation-duration/
transition-duration: .01ms !important` dan `animation-iteration-count: 1`,
supaya preferensi sistem tidak bergantung pada disiplin tiap halaman.

Jaring itu semula ditulis di `berkah-theme.css`, dan di sana `UiKitTest`
menolaknya: berkas pemetaan warna dijaga bebas `!important` (aturan tema cukup
mengganti nilai token). Pengaman gerak justru wajib `!important` untuk
mengalahkan aturan komponen yang lebih spesifik, jadi tempatnya memang berkas
gerak. `MotionAssetsTest` kini membaca `berkah-motion.css` — sekaligus mengunci
bentuk selektornya (`*, *::before, *::after`) agar pengaman tidak menyusut
jadi satu komponen saja.

Pemeriksaan `MotionAssetsTest` untuk lantai 12 px dulu hanya mencari literal
`font-size: .NNNrem` di `berkah-admin.css`; begitu ukuran pindah ke
`var(--adm-fs-*)`, pola itu tidak menemukan apa pun. Sekarang berkas itu
memeriksa setiap deklarasi `font-size` (literal px/rem/em) **dan** memastikan
token `--adm-fs-*` benar-benar dipakai (>40 kemunculan), bukan sekadar
dideklarasikan.

### 9.10 Satu wine, bukan dua

Palet kanonik hanya punya tiga langkah (`#f8e9ed`, `#85092d`, `#54051f`), tetapi
`#75102e` masih dipakai 27 kali di halaman katalog, pesan, profil, dan formulir
rental. Dua wine tua yang nyaris kembar itu bukan pilihan desain: `#75102e`
lebih kusam, dan di atas permukaan gelap hanya mencapai 1.5:1 — persis angka
yang sudah diperbaiki di §7c. Semua pemakaiannya kini disatukan ke `#85092d`:

| Berkas | Sebelum | Sesudah |
|---|---|---|
| `berkah-experience.css` (`--bk-wine`, tautan, `accent-color`, `box-shadow`) | 19× `#75102e` | `#85092d` |
| `_navbar_customer.blade.php` (radial, panel, chip) | 3× | `#85092d` |
| `customer-forms.css` (`.page-header` `#751b35`) | 2× | `#85092d` |
| `berkah-auth.css`, `errors/layout` | 1× masing-masing | `#85092d` |

Kontras sesudah penyatuan di atas latar yang dipakai halaman: `#fbf6ef` 9.4:1,
`#f8e9ed` 8.6:1, `#ead7d3` 7.3:1 — semuanya lolos 4.5:1. Penjaga:
`UiDefaultsTest::test_old_wine_shade_never_returns` (komentar CSS/Blade
diabaikan, jadi catatan sejarah di `berkah-theme.css` tetap boleh menyebut
nilai lama).

Ujung gradien `#b6435e` sengaja **tidak** dipaksa masuk tangga brand-50..950 di
`tailwind.config.js`: nilainya hanya dipakai sebagai stop terang gradien dan
hover, dan menggantinya dengan `--brand-400 #c9708a` akan mengubah rupa banner
serta surel.

### 9.11 Layout tamu: halaman auth yang masih berwajah Breeze

Tiga halaman yang benar-benar dipakai — verifikasi email, atur ulang kata
sandi, konfirmasi kata sandi — memakai `layouts/guest` bawaan Breeze:

| Temuan | Sebelum | Sesudah |
|---|---|---|
| Identitas | Logo **Laravel** di tautan beranda tanpa nama aksesibel. | Logo Berkah Kreatif + wordmark; tautan punya nama dari teks di dalamnya. |
| Font | `fonts.bunny.net` (CDN pihak ketiga), Figtree. | Plus Jakarta Sans dihosting sendiri (`public/fonts` + `berkah-fonts.css`), lihat §9.50. |
| Warna | `bg-gray-100`, `text-gray-900`, kartu putih ber-shadow abu. | Token `--bk-page-bg`, `--bk-page-ink`, `--bk-modal-*`, `--bk-field-bg`, `--bk-link-ink`; tema gelap ikut tanpa tambalan per selektor. |
| Kontrol | Komponen Breeze bergantung pada Tailwind (`border-gray-300`, `bg-gray-800`). | Input, label, daftar galat, dan tombol ditata dari token: 44 px, radius `--bk-r-control`, fokus `--bk-focus-ring`. |
| Judul | `config('app.name')` — satu judul untuk semua halaman. | `<x-guest-layout :title="…">` → `<title>` per halaman; `GuestLayout` menerima properti `$title`. |
| Bahasa | Label campur: "Password", "Confirm Password", "Reset Password". | "Kata Sandi Baru", "Ulangi Kata Sandi Baru", "Simpan Kata Sandi Baru". |
| Build | `@vite(['resources/css/app.css', 'resources/js/app.js'])` — halaman gagal total bila manifest Vite tidak ada. | Tanpa `@vite`; gaya datang dari stylesheet lokal. Tailwind/Vite tidak lagi dipanggil halaman mana pun. |

Token halaman mandiri ikut dipetakan untuk gelap (`--theme-bg`, `--theme-ink`,
`--theme-surface-2`, `#efa8b9` untuk tautan — 9:1 di atas permukaan gelap) dan
untuk cetak (kembali terang).

### 9.12 Yang sengaja belum disentuh

- `SplitText` untuk judul hero dan `Flip` untuk daftar pesanan — keduanya
  menambah gerak, bukan memperbaiki cacat; menunggu keputusan.
- Nama variabel `--blue-*` di dua halaman alamat dan komentar `--blue-*` di
  `berkah-theme.css` §7f (nilainya sudah wine).
- Ukuran `11px` di template surel: klien surel tidak mengenal custom property,
  jadi lantai 12 px di sana harus literal dan tidak diuji token.

### 9.13 Klik beruntun, gulir lalu klik

Sisi interaktif punya cacat yang tidak terlihat pada tangkapan layar: elemen
yang sama diklik dua kali, atau halaman digulir dulu lalu elemen diketuk tepat
saat animasi/penundaan masih berjalan. Aturan yang dipakai sekarang:

- **Satu lapisan, satu pemilik.** `openLayer()` di `berkah-controls.js`
  menutup lapisan aktif sebelum membuat yang baru. Lapisan yang sedang
  beranimasi tutup tidak boleh menimpa `aria-expanded` maupun menarik fokus
  dari lapisan pengganti (dijaga oleh tanda `superseded`). Di admin,
  `openLayer()` menutup sidebar/drawer lain lebih dulu karena keduanya berbagi
  satu backdrop, dan pemulihan fokus hanya terjadi bila tak ada lapisan tersisa.
- **Fokus punya tempat pulang yang jelas.** Dialog tema mengembalikan fokus ke
  tombol pembuka; bila `lastFocus` jatuh ke `<body>` (dialog dibuka lewat
  skrip), tombol pembuka dipakai sebagai cadangan.
- **Kiriman ganda dibatalkan, bukan dinonaktifkan.** Tombol kirim tetap bisa
  difokus dan dibaca pembaca layar; `preventDefault()` pada submit kedua yang
  menahan data terkirim dua kali.
- **Penanda sekali-jalan memakai kunci berbeda dari atribut pemicunya.**
  `once(el, key)` menulis `data-<key>`; memakai `bkParallax` sebagai kunci
  membuat guard selalu benar karena atribut `data-bk-parallax` sudah berisi
  kedalaman (mis. `0.08`) — parallax tak pernah dibuat. Kuncinya kini
  `bkParallaxReady`/`bkTiltReady`.
- **Satu jalur gulir programatik.** Semua lompatan memakai `scrollPageTo()`
  (Lenis `scrollTo(..., { immediate, force })` atau `window.scrollTo`), dengan
  `headerHold` 1,2 dtk supaya header ringkas tidak berubah bentuk di tengah
  animasi dan tombol tidak berpindah dari bawah kursor.
- **Sasaran sentuh 44 px.** Tombol menu/tema topbar admin dan chip kategori
  pelanggan ikut membesar lewat `@media (pointer: coarse)`, mengikuti token
  `--adm-touch`/`--bk-touch` yang sudah ada.

Bukti di sandbox (jsdom, tanpa peramban nyata): `interactions` 30, `controls`
18, `modal` 22, `preview` 18, `kit` 34, `race` 9 asersi — semuanya lulus.
Uji `race` sengaja dijalankan juga terhadap kode lama: dua asersinya gagal
(`aria-expanded` tertimpa `false`, fokus ditarik ke pemicu di belakang dialog),
jadi perbaikannya memang menutup celah, bukan sekadar menambah kode.

### 9.14 Formulir: label, kiriman ganda, dan latar dialog

Sapuan aksesibilitas formulir menemukan tiga hal yang tidak terlihat pada
tangkapan layar:

- **Label tanpa pasangan.** `profile/edit.blade.php` menulis `<label>Nama</label>`
  sebagai saudara `<input name="name">` tanpa `for`/`id` — tujuh isian tanpa nama
  yang terbaca pembaca layar, dan mengeklik label tidak memfokuskan isian.
  Halaman lama `customer/addresses/index.blade.php` memakai
  `<div class="label">` untuk dua belas isian. Keduanya kini
  `<label class="label" for="…">` + `id` yang unik (formulir per alamat memakai
  `addr-{{ $address->id }}-…`), dan `.label` diberi `display: block` supaya
  tampilannya tidak bergeser.
- **Latar dialog masih terbaca.** Dialog konfirmasi memakai `role="dialog"`
  dan menjebak Tab, tetapi isi halaman di belakangnya tetap bisa ditelusuri
  pembaca layar. `setPageInert()` di `_confirm_modal.blade.php` memasang
  `inert` + `aria-hidden="true"` pada saudara overlay selama dialog terbuka,
  lalu memulihkan keadaan aslinya (termasuk `aria-hidden` dekoratif yang sudah
  ada) — bukan sekadar menghapus atribut.
- **Kiriman ganda.** `berkah-controls.js` kini memasang kunci kirim di fase
  *bubble*: handler validasi kustom berjalan lebih dulu di fase capture, jadi
  formulir yang ditolak tidak pernah terkunci. Klik kedua dibatalkan selama
  kiriman pertama berjalan, kunci dilepas bila handler lain membatalkan
  kiriman (AJAX/konfirmasi) atau saat halaman dipulihkan dari cache riwayat.

Dua perbaikan kecil menyertainya: `<x-input-error>` memberi `role="alert"`
pada daftar galat (semua halaman auth/profil memakainya), dan isian nomor HP
memakai `type="tel"` (papan ketik angka di ponsel) tanpa mengubah validasi.

### 9.15 Lembar "Lainnya" admin yang mati

Bilah admin mandiri (`_navbar_admin.blade.php`) menyembunyikan tujuan di luar
empat menu harian di balik tombol **Lainnya** pada layar ≤899 px, dan markup
lembar `#admin-sheet` sudah lengkap: backdrop, panel `role="dialog"`,
`aria-modal`, tombol tutup, seluruh grup menu. Yang tidak ada adalah
pengendalinya — tidak satu pun skrip menyentuh `[data-admin-more]`, sehingga
di ponsel tombol itu tidak melakukan apa pun dan separuh menu tak bisa
dijangkau.

`berkah-admin.js` kini memegangnya: buka/tutup idempoten, `aria-expanded`
disinkronkan pada pembuka, fokus pindah ke tautan pertama dan kembali ke
pembuka saat ditutup, Tab terjebak di panel (memakai penjaga Tab yang sama
dengan sidebar), Escape menutup lebih dulu sebelum sidebar/drawer, latar
halaman `inert` + `aria-hidden` selama lembar terbuka (dipulihkan apa adanya),
dan melebar ke ≥900 px menutup lembar karena navigasi sudah pindah ke bilah
atas.

`UiDefaultsTest::test_admin_mobile_sheet_has_a_controller` mengunci pasangan
markup–pengendali ini supaya tidak terulang: tombol yang "terlihat benar"
tetapi tidak tersambung ke skrip adalah cacat yang paling mudah lolos dari
tinjauan visual.

### 9.16 Halaman galat, tombol di dalam formulir, dan gambar di luar layar

Tiga cacat kecil yang lolos dari putaran sebelumnya semuanya berjenis sama:
markup yang *tampak* benar sampai skripnya hilang atau sampai elemennya
dipakai di luar konteks.

**`href="javascript:…"` di halaman galat.** Lima halaman galat (403, 404, 419,
500, 503) memakai `javascript:history.back()` dan `javascript:location.reload()`
pada tombolnya. Tautan seperti itu mati total tanpa JavaScript dan ditolak CSP
ketat; tidak ada cadangan apa pun. Sekarang setiap aksi adalah tautan biasa ke
beranda — halaman yang pasti ada — dan skrip hanya memperhalus: `data-smart-back`
memakai `berkah-smart-back.js` yang sudah dimuat `_ui_kit` (kembali ke halaman
asal hanya bila pengunjung memang datang dari situs ini lewat navigasi internal),
`data-reload-page` memakai pengendali kecil di `errors/layout.blade.php`. Tanpa
skrip, keduanya tetap berpindah ke beranda, bukan diam.

**`<button>` tanpa `type` di dalam `<form>`.** Tombol tanpa `type` selalu
`submit`. Untuk tombol simpan itu benar, tetapi niatnya jadi tak terbaca dan
tombol bantu yang ditambahkan kemudian akan ikut mengirim formulir. Dua puluh
empat tombol di sepuluh view kini menulis `type="submit"` eksplisit. Tiga
komponen (`primary-button`, `secondary-button`, `danger-button`) sengaja
dilewati karena tipenya datang dari pemanggil lewat `$attributes`.

**Gambar di bawah lipatan.** Empat gambar konten — sampul keranjang, pratinjau
desain di detail admin dan detail unit, serta QRIS di pengaturan pembayaran —
kini `loading="lazy" decoding="async"`. Logo dan foto produk utama dibiarkan
eager: keduanya di atas lipatan dan menentukan kesan pertama.

Satu detail palet ikut ditutup: `.bk-experience .hero` masih memakai `#76203a`,
wine kesembilan yang berbeda tipis dari `#85092d` dan hanya muncul sekali di
seluruh berkas. Sekarang `var(--bk-wine, #85092d)`.

Penjaga: `BackUrlSafetyTest::test_error_pages_never_use_javascript_urls` menolak
`javascript:` di view galat dan memastikan kedua kait ada di layout;
`UiDefaultsTest::test_buttons_inside_forms_declare_their_type` memakai pemindai
sadar-kutip (mengganti `->` lebih dulu agar atribut Blade tidak memotong tag)
supaya tombol baru tanpa `type` langsung ketahuan.

#### 9.16a Sapuan lanjutan: isian, judul, dan cache

Sapuan berikutnya menyasar hal-hal yang tidak terlihat di layar tetapi terasa
saat dipakai:

- **`autocomplete` pada isian pribadi.** Empat belas isian email, telepon, dan
  kata sandi di halaman profil dan alamat kini memberi tahu peramban isi yang
  diharapkan: `email`, `tel`, `current-password` untuk kata sandi lama dan
  `new-password` untuk kata sandi baru. Tanpa itu pengelola kata sandi menebak,
  dan isian "ulangi kata sandi" sering terisi acak.
- **Judul halaman seragam.** Dua halaman masih berjudul `Edit Profil` tanpa
  nama merek; sekarang `Edit Profil · Berkah Kreatif`. `rental/help-layout`
  diberi judul cadangan supaya tidak pernah menghasilkan " · Berkah Kreatif"
  yang menggantung.
- **Deskripsi katalog.** `catalog/layout` belum punya `meta description`,
  padahal itu halaman yang dibagikan orang. Sekarang ada, dan tiap halaman
  boleh menimpanya lewat `@section('description')`.
- **`noindex` untuk halaman pribadi.** Verifikasi email, masuk, daftar, lupa
  kata sandi, ringkasan pesanan, lembar harian, dan layout tamu tidak boleh
  masuk indeks mesin pencari — ringkasan pesanan pun memuat nomor pesanan dan
  total tagihan.
- **Versi berkas aset.** `page-transitions.css/js`, `dashboard-preview.js`,
  `rental-share.js`, dan `berkah-auth.css` dimuat tanpa penanda versi, sehingga
  peramban bisa memakai salinan lama setelah pembaruan. Semuanya kini memakai
  pola `?v=` + `filemtime` yang sudah dipakai aset lain. Empat halaman yang
  memakai closure `$asset()` tidak perlu diubah karena closure itu sudah
  menambahkan versi.

#### 9.16b Satu `h1` dan satu landmark `main` per halaman

Tujuh halaman menulis judulnya sebagai `<div>`/`<span>` bergaya judul
(`.header-title`, `.appbar__title`, `.profile-header__title`, `.card-title`,
`.h1`). Bagi mata tidak ada bedanya; bagi pembaca layar dan mesin pencari,
halaman itu tidak punya judul sama sekali. Ketujuhnya kini `<h1>` dengan kelas
yang sama, dan aturan kelasnya diberi `margin:0` supaya jarak vertikal tidak
berubah karena margin bawaan `h1`.

Tiga halaman juga tidak punya landmark `main` sama sekali — pendaftaran,
sunting profil versi ringkas, dan daftar alamat — sehingga pintasan "langsung
ke isi" tidak punya tujuan. Wadah utamanya kini `role="main"`, pola yang sudah
dipakai halaman admin lain. Halaman surel sengaja dilewati: klien surel tidak
mengenali landmark.

### 9.17 Tombol tema di halaman masuk, daftar, dan lupa sandi

Tombol tema dipindahkan `berkah-ui-kit.js` ke `[data-bk-theme-host]` bila
halaman punya slot header (katalog, beranda pelanggan, bilah admin). Halaman
auth tidak punya slot itu, jadi tombol jatuh ke mode melayang: `position:fixed`
di kanan bawah. Di layar ponsel posisi itu persis menimpa pil footer
"© 2026 Berkah Kreatif · Papan Bunga" — tombol tampak menempel di atas teks
footer, dan footer ikut tertutup sebagian.

Halaman auth kini menandai dirinya dengan `class="auth-page"` pada `<body>`
(login, register, lupa sandi). Aturan `.auth-page .bk-theme-btn.bk-theme-dock`
memindahkan tombol ke sudut kanan atas — seberang tombol kembali yang sudah
`fixed` di kiri atas — dengan bentuk yang sama: bulat 44 px, kaca
`rgba(255,255,255,.15)`, garis tipis putih, ikon putih, tanpa bayangan.
Bentuk itu terbaca di latar wine pada mode terang maupun gelap, jadi tidak ada
dua wajah tombol yang berbeda saat tema berganti.

Halaman tamu (`layouts/guest` — konfirmasi kata sandi, verifikasi email) juga
memakai aturan sudut kanan atas lewat `body.guest-body`, tetapi tanpa gaya
kaca: latarnya terang, jadi tombol tetap memakai token `--bk-toggle-*`.

Dialog tema tidak berubah; ia tetap modal di tengah. `UiKitTest::
test_theme_button_never_covers_the_footer_on_auth_screens` mengunci pasangan
kelas penanda, aturan CSS, dan pemuatan `_ui_kit` supaya tombol tidak kembali
melayang di atas footer.

### 9.18 `/profile` yang menjawab 405

Membuka `GET /profile` berakhir `MethodNotAllowedHttpException` dengan pesan
"The GET method is not supported for route profile. Supported methods: PATCH,
DELETE". Penyebabnya: `/profile` hanya didaftarkan untuk `PATCH`
(`profile.update`) dan `DELETE` (`profile.destroy`); halaman profil yang
sebenarnya ada di `/customer/profile` (`customer.profile`) dan
`/admin/profile` (`admin.profile`), sedangkan formulir sunting di
`/profile/edit` (`profile.edit`).

Tidak ada satu pun tautan di aplikasi yang memanggil `route('profile')` —
permintaan itu datang dari alamat yang diketik langsung atau penanda lama —
tetapi URL sering-diketik tetap tidak boleh menjawab 405.

Sekarang ada `GET /profile` di dalam grup `auth` yang mengarahkan sesuai peran:
admin → `admin.profile`, pelanggan → `customer.profile`, peran lain →
`profile.edit`. Tamu tetap diarahkan middleware ke halaman masuk. Nama rutenya
`profile.show` supaya tidak bertabrakan dengan nama `profile` milik
`PATCH`/`DELETE`.

`ProfileTest::test_alamat_pendek_profile_mengarahkan_ke_profil_sesuai_peran`
dan `test_tamu_yang_membuka_profile_diarahkan_ke_halaman_masuk` mengunci
perilaku ini.

### 9.19 Kata "Array" yang membocorkan diri ke HTML

`ProfileTest::test_profile_and_address_forms_render_safely_after_non_scalar_retry`
mengirim `name => ['Budi']` (larik, bukan teks) lalu memastikan halaman
`/profile/edit` tetap tampil tanpa mencetak kata "Array". Tes itu gagal, dan
penyebabnya bukan data lama: halaman memuat `_confirm_modal`, dan skrip
sebaris di dalamnya menulis `Array.prototype.forEach.call(document.body.children, …)`.
Skrip apa pun yang menyebut nama konstruktor larik akan menaruh kata itu di
HTML — tepat kata yang dipakai tes sebagai penanda kebocoran.

Dua hal diperbaiki:

1. Skrip modal memakai `[].forEach.call(...)`, bentuk yang sama tanpa menyebut
   nama konstruktor. Perilakunya identik, termasuk untuk `HTMLCollection`.
2. `roles/admin/profile_edit.blade.php` — satu-satunya halaman sunting profil
   yang masih memakai `old('name')` mentah — kini memakai penjaga `$oldText`
   yang sama dengan halaman pelanggan (`is_scalar($value) ? (string) $value : ''`),
   jadi kiriman berbentuk larik tidak pernah tercetak sebagai teks larik.

Penjaga: `UiDefaultsTest::test_no_view_prints_the_word_array` memindai seluruh
view (komentar Blade dibuang lebih dulu, karena tidak ikut terkirim ke
peramban) dan gagal bila kata itu muncul lagi di markup atau skrip sebaris.

### 9.20 Audit layout lima layar ponsel: header, strip langkah, dan dok tema

Lima tangkapan layar ponsel (katalog tamu, sheet menu, splash masuk, modal tema
terang, modal tema gelap) diperiksa bersama-sama. Tujuannya bukan menambal satu
per satu, melainkan mencari akar yang sama: **satu kontrol global (tombol tema)
dipaksa muat dengan cara yang salah, dan satu strip bergulir tidak memberi
isyarat bahwa masih ada isi di kanan.**

Akar masalah yang ditemukan:

1. **Dok tema menutupi kartu produk.** Di layar ≤700 px, ui-kit menyembunyikan
   `.bk-theme-host` (slot di header) sehingga `placeToggle()` milik
   `berkah-ui-kit.js` jatuh ke mode `bk-theme-dock`: tombol `position: fixed`
   pada `bottom: 92px`. Nilai itu memang dinaikkan di atas bilah bawah, tetapi
   artinya tombol melayang **di atas isi halaman** — pada katalog tamu tepat di
   atas kartu produk, dan pada halaman pelanggan di atas kartu terakhir.
   Komentar di `berkah-ui-kit.js` sendiri sudah menyebut "mis. header ponsel":
   desainnya memang menyediakan slot, hanya CSS yang menutupnya.
2. **Header mengabaikan `safe-area-inset-top`.** `viewport-fit=cover` dipakai
   di semua layout, tetapi `.bk-header` memakai `top: 16px` tetap. Di perangkat
   berponi header kaca masuk ke bawah status bar; `.bk-header-space` juga tidak
   menyediakan ruang tambahan sehingga hero ikut tertarik ke atas.
3. **Strip tiga langkah terpotong tanpa isyarat.** Di ponsel `.bk-how` menjadi
   `display:flex; overflow-x:auto`, tetapi tanpa snap, tanpa padding tepi, dan
   tanpa tanda bahwa langkah "03" masih ada — pada tangkapan layar hanya
   terlihat separuh angka berikutnya.
4. **Area sentuh di bawah 44 px.** Tombol stepper jumlah `−`/`+` 40×40 px dan
   chip kategori 42 px, padahal tombol lain di kartu yang sama 44 px. Di layar
   sentuh selisih 4 px terasa: jempol meleset ke kolom isian jumlah.
5. **Ikon bilah tamu tidak satu keluarga.** "Keranjang" memakai mesin terbang
   `▢` dan "Masuk" memakai `○`, sementara "Katalog" memakai SVG. Ukuran dan
   ketebalannya tidak pernah bisa sama karena bukan ikon yang sama.
6. **Irama bagian terlalu longgar di layar pendek.** `.bk-filter-bar` dan
   `.bk-section-heading` memakai jarak desktop (20 px / 32 px) berturut-turut,
   sehingga dua judul bagian berdesakan di ponsel.
7. **Lenis bisa menahan gulir mendatar.** `SCROLL_REGIONS` tidak memuat
   `.bk-how` maupun `.bk-category-strip`; di desktop roda/trackpad mendatar
   pada dua wadah itu direbut Lenis dan halaman yang bergulir, bukan stripnya.

Perbaikan yang diterapkan (semua memakai token/skala yang sudah ada):

- `berkah-ui-kit.css`: slot tema ditampilkan kembali di ≤700 px. Tombolnya
  sudah 44 px dari `.bk-theme-btn`, jadi tidak perlu ukuran baru.
- `berkah-experience.css`: `.bk-desktop-actions > a` disembunyikan di ponsel
  (keranjang/notifikasi/profil sudah punya jalur di bilah bawah), sementara
  slot tema tetap tampil; header ringkas juga mempertahankan slot tema sehingga
  tombol tidak hilang setelah halaman digulir.
- `berkah-experience.css`: `top`/`height` header, header-space (96/88/80 px),
  header ringkas, dan tautan lompat kini memakai
  `calc(env(safe-area-inset-top, 0px) + …)`. Tanpa poni nilainya sama persis
  dengan sebelumnya. Lebar pil ringkas menjadi `auto` karena kini memuat dua
  sampai tiga kendali; `width` tidak bisa dianimasikan ke `auto`, jadi transisi pil
  dibatasi ke `top` saja daripada menyisakan animasi yang tidak pernah jalan.
- `berkah-experience.css`: `scroll-margin-top` tujuan `#koleksi`/bagian unit ikut
  safe-area, jadi lompatan ke "Temukan yang tepat" tidak mendarat di bawah pil
  header di perangkat berponi.
- `berkah-experience.css`: `.bk-how` mendapat `scroll-snap-type: x proximity`,
  `scroll-padding-inline`, tinggi sasaran minimum per langkah, dan tepi kanan
  yang memudar (`mask-image`) sebagai isyarat "masih ada langkah lain".
- Area sentuh: `.bk-qty__btn`/`.bk-qty__input` 44 px di ≤700 px, chip kategori
  44 px.
- Ikon bilah tamu memakai `_icon` yang sama (`cart`, `account`), bukan `▢`/`○`.
- Irama ponsel: `.bk-filter-bar{margin-bottom:16px}`,
  `.bk-section-heading{margin:26px 0 10px}`, judul kartu 1rem. Blok ini
  diletakkan **paling akhir** berkas; bila digabung ke media query di awal
  (sebelum aturan dasar), aturan dasar yang menyusul akan menang dan
  penyesuaiannya tidak pernah berlaku.
- `berkah-motion.js`: `.bk-how` dan `.bk-category-strip` masuk
  `SCROLL_REGIONS` sehingga keduanya memakai gulir asli
  (`data-lenis-prevent`) dan tidak direbut Lenis.
- `catalog/index.blade.php`: `.bk-filter-bar` dan `.bk-section-heading` ikut
  `[data-bk-reveal="up"]` sehingga GSAP dipakai sesuai fungsinya (kemunculan
  bertahap), bukan animasi tempelan. Tautan hero **tidak** memakai
  `[data-bk-press]`: GSAP menulis `transform` inline, dan itu akan menimpa
  `transform: translateY(-2px)` milik `:hover` secara permanen setelah sekali
  ditekan. Tekanan di sana cukup lewat `:active` CSS.
- Modal tema: jeda ilustrasi→petunjuk 10 px dan jarak pilihan 18 px; di ≤420 px
  ketiga pilihan membagi lebar baris (`flex: 1 1 auto`, min 96 px) agar tidak
  menggantung di tengah.
- `layouts/guest.blade.php`: H1 kartu tamu (verifikasi email, konfirmasi kata
  sandi) dinaikkan ke `clamp(1.25rem, 1rem + 1.4vw, 1.5rem)`. Sebelumnya H1
  dan H2 sama-sama 17 px — hanya 2 px di atas teks isi 15 px, sehingga judul
  halaman tidak terbaca sebagai judul.

Penjaga: `ResponsiveLayoutTest::test_catalog_header_respects_the_notch_and_keeps_the_theme_slot`
dan `test_mobile_catalog_rhythm_uses_scroll_snap_and_44px_targets`.

### 9.21 Komponen kiriman, kotak centang, dan dua tangkapan layar admin

Referensi yang dikirim (gooey menu, pill nav, add-to-cart, auth button, delete,
heart, sakelar robot) diperiksa satu per satu terhadap kode yang ada. Sebagian
besar **sudah terpasang** dari audit sebelumnya; yang belum ada hanya detail
kecil. Yang benar-benar rusak justru dua hal yang tidak ada di daftar: kontras
tautan di bilah sisi admin versi gelap, dan rupa kotak centang yang berbeda-beda.

**A. Pemetaan komponen (sudah ada, tidak diduplikasi)**

| Kiriman | Sudah ada di | Catatan |
|---|---|---|
| Gooey radial menu (Lucas Bebber) | `customer/_radial_customer.blade.php` + `_navbar_customer.blade.php` | Empat tujuan dalam busur, latar `feGaussianBlur`+`feColorMatrix` (`#bk-menu-goo`), `<details>` sehingga tetap jalan tanpa JS — **diperbarui §9.26:** gumpalan goo disembunyikan karena terbaca sebagai “latar”, elemen beserta `id="bk-menu-goo"` tetap ada |
| Pill nav / liquid glass / pill ringkas saat digulir | `berkah-experience.css` `.bk-header`, `.bk-header.is-compact`, `berkah-experience.js` (`headerExitPoint`, §9.57) | Kaca + blur, pil mengambang, keadaan ringkas menahan tombol tema |
| Tambah ke keranjang (label + progress) | `.bk-atc`, `.bk-atc__bar`, `berkah-ui-kit.js` (`is-loading`/`is-added`) | Label panjang→pendek, bar progress, status `aria-busy` |
| Tombol auth beranimasi huruf | `.bk-auth` (+ `__chars--a/b`, `__outline`, `__wrap`) | Dipakai `auth/login` & `auth/register`; palet mengikuti merek, bukan biru |
| Tombol hapus melebar | `.bk-del` (ikon → teks saat hover/fokus) | Dipakai `catalog/cart.blade.php` |
| Tombol suka + konfeti | `.bk-heart` (`__filled`, `__outline`, `__burst`) | `catalog/product-feedback.blade.php`, `aria-pressed` |
| Sakelar tema robot + tangan | `#bk-theme-switch` di `_ui_kit`, timeline GSAP di `berkah-ui-kit.js` | Idempoten, menghormati `prefers-reduced-motion` |

Karena itu tidak ada komponen yang disalin ulang: duplikasi akan membuat dua
sumber kebenaran untuk satu perilaku.

**B. Kontras tautan bilah sisi admin (gambar 95)**

Kartu pengguna di bilah sisi memakai `.adm-user` dengan tiga tautan: nama,
"Ubah profil", dan tombol keluar. `.adm-app a { color: var(--adm-wine) }`
(0,1,1) mengalahkan `.adm-user__name { color: #fff }` (0,1,0), sehingga di tema
gelap nama dan "Ubah profil" tercetak **#85092d** di atas kartu **#410418**.
Contoh piksel dari tangkapan layar: teks (133, 9, 45), kartu (65, 4, 24) —
rasio kontras ±1,5:1, praktis tidak terbaca.

Perbaikan:

1. Token baru `--adm-link` (terang `#85092d`, gelap `#f3a8bb`) dan
   `.adm-app a` memakainya. Wine pekat tetap dipakai untuk isian merek
   (tombol utama), bukan untuk teks.
2. `.adm-sidebar :is(.adm-brand, .adm-user__name, .adm-user__edit) { color: #fff }`
   — bilah sisi selalu bergradasi wine, jadi teksnya tetap putih di kedua tema.
3. Hover `.adm-btn--ghost`/`--quiet` yang memakai `--adm-wine-deep` dinaikkan
   ke putih saat gelap.

**C. Tangkapan layar dashboard admin (gambar 96)**

Diukur per piksel, bukan dikira-kira: label kartu (246, 236, 232), judul
sebagian (246, 236, 232), kode pesanan (208, 185, 192), catatan (208, 185, 192),
tautan "Semua" (242, 161, 180), tombol keluar (255, 179, 187). Semua sudah
memakai token tema gelap dengan kontras > 4,5:1 — **tidak ada yang perlu
diubah**. Jarak vertikal yang longgar di kolom kiri berasal dari grid dua kolom
(`.body` → `.section` + `.body-side`) yang memang membiarkan sisa ruang; bukan
cacat tata letak, dan memaksanya rata akan memindahkan tombol keluar dari
urutan baca.

**D. Kotak centang: satu rupa untuk semua peran**

Audit menemukan 16 kotak centang (ingat saya, syarat & ketentuan, alamat utama,
persetujuan DP, konfirmasi admin, voucher aktif, buku kas, pengaturan QRIS).
Semuanya mengandalkan `accent-color` per halaman, sehingga kotaknya 13 px
bawaan peramban, warna tiap peramban berbeda, dan di panel gelap isian putihnya
menonjol. Sekarang satu aturan di `berkah-ui-kit.css`:

- 20×20 px, radius 6 px, garis `--bk-check-line`, isian `--bk-check-bg`;
- saat tercentang: isian `--bk-check-on` (#85092d) + centang SVG putih
  (bukan pseudo-element, karena `::after` tidak andal pada elemen input);
- keadaan `:indeterminate` untuk pemilihan massal di admin;
- cincin fokus 3 px, `:disabled` 45%, transisi warna saja (dimatikan saat
  `prefers-reduced-motion`).

Token gelapnya (`--bk-check-line: #7a5560`, `--bk-check-bg: #241c22`) ada di
`berkah-theme.css`, jadi kotak tetap terlihat di atas kartu #201820. Dua
`style="width:auto"` (checkout, unit) dihapus karena lebar kini satu aturan;
`.terms-cb` dan `.remember-label` tidak lagi menetapkan ukuran sendiri.

Tidak ada checkbox baru yang ditambahkan: setiap pilihan biner di aplikasi ini
sudah punya kotak centang. Menambah kotak tanpa perilaku (mis. pilih massal di
admin) berarti menjanjikan fitur yang belum ada di backend.

**E. Kaca header (liquid glass) dan busur gooey**

`.bk-header-inner` mendapat lapisan kilau (`linear-gradient` putih 34% → 0)
plus `saturate(150%)` pada `backdrop-filter`, sehingga kaca memantulkan warna
hero di belakangnya dan tidak terlihat seperti kotak abu-abu datar. Busur menu
pelanggan kini muncul berurutan (`bk-radial-item-in`, jeda 40–190 ms) dan ikon
menu berputar 90° saat terbuka — meniru morph ikon pada menu gooey, tetapi
hanya transform/opacity sehingga tata letak tidak bergeser; semuanya dimatikan
pada `prefers-reduced-motion`.

**F. Soal "animasi morph"**

Morph antarbentuk tidak disediakan peramban secara umum; yang ada:

1. `filter: url(#goo)` (dipakai menu): melebur bentuk lewat blur + color matrix.
   Gratis, tanpa dependensi, tetapi tidak mengubah satu bentuk menjadi bentuk
   lain — hanya menyatukan/memisahkan gumpalan.
2. **MorphSVGPlugin** (GSAP Club/berbayar): interpolasi `d` antarpath dengan
   penyamaan jumlah titik. Paling mulus, tetapi tidak boleh disalin ke repo ini
   tanpa lisensi.
3. `flubber` (±10 KB, MIT): alternatif gratis untuk interpolasi dua path.
   Menambah pustaka baru untuk satu animasi dekoratif belum sepadan.
4. Properti CSS `d:` (Chromium 105+) atau `clip-path`/`border-radius` transition:
   cukup untuk morph panel, tidak untuk bentuk kompleks.

Keputusan: tetap pada (1) untuk menu dan (4) untuk panel; tidak menambah
dependensi atau plugin berlisensi hanya demi morph dekoratif.

Penjaga: `UiKitTest::test_admin_dark_theme_links_stay_readable` dan
`test_checkboxes_share_one_brand_style`.

### 9.22 Menu radial pelanggan, dibangun ulang mengikuti ChromeRadialMenu

Referensi yang dikirim adalah komponen Framer (`ChromeRadialMenu-siTRTd`):
tombol pusat bulat, tujuan menyebar dalam busur, masing-masing lingkaran
dengan ikon dan lencana angka. Bundel itu ESM runtime Framer, jadi yang bisa
diambil hanya perilaku dan proporsinya — bukan berkasnya.

**A. Penempatan.** Menu duduk di ujung kanan header pelanggan dan hanya tampil
sampai 700 px (di layar lebar tujuan yang sama sudah menjadi tautan header:
keranjang, notifikasi, profil). Pusatnya adalah tombol 48 px yang sebelumnya
berupa tombol "Menu" di ponsel. Jadi tidak ada tombol baru di layar.

**B. Geometri.** Busur dibuka ke kiri-bawah, bukan ke kanan seperti contoh,
karena pusat tombol hanya ±52 px dari tepi kanan layar. Radius 100 px, empat
tujuan pada 168°, 136°, 104°, dan 74°:

| Tujuan | --rx | --ry | Jarak antar-tujuan |
|---|---|---|---|
| Keranjang | −98 px | 21 px | 55 px |
| Pesanan | −72 px | 70 px | 52 px |
| Notifikasi | −24 px | 97 px | 52 px |
| Bantuan | +28 px | 96 px | — |

Lingkaran 46 px (di atas ambang sasaran sentuh 44 px). Pada layar 320 px,
titik paling kanan berada 51 px dari pusat — masih di dalam layar. Busur tidak
pernah menutupi tombol tema/filter secara permanen karena menu tertutup saat
klik di luar, Escape, atau gulir.

**C. Gerak.** Satu angka, `--p` (0→1), menggerakkan seluruh busur: tiap tujuan
punya `translate(calc(var(--rx) * var(--p)), …)` dan `scale(calc(.42 + .58 * var(--p)))`.
GSAP hanya menganimasikan angka itu (`expo.out`, 520 ms, stagger 55 ms), lalu
lencana menyusul dengan `back.out(2.4)`.
**Diperbarui di §9.26–§9.28:** jeda 55 ms → 28 ms, durasi 520 ms → 420 ms, dan
lencana dianimasikan lewat `--bp` (bukan `transform`). Menutup: `power2.in`, 240 ms, stagger
dari tujuan terakhir. Karena GSAP tidak menulis `transform`, hover dan
`:active` tetap bekerja — pelajaran dari `.bk-hero-link`.

**D. Gulir.** Saat busur terbuka, `Lenis.stop()` (kalau Lenis ada) supaya
halaman di belakang tidak bergerak; ditutup → `Lenis.start()`. Di halaman
tanpa Lenis, menu menutup sendiri saat halaman digulir.

**E. Jaring pengaman.** Tanpa JavaScript, `<details>` tetap membuka panel dan
CSS menaruh tiap tujuan pada tempatnya (transisi biasa). `prefers-reduced-motion`
mematikan transisi dan GSAP; keadaan akhir langsung tercapai. `aria-expanded`
pada `<summary>` mengikuti keadaan `open`.

**F. Integrasi.** `berkah-radial.js` memegang seluruh perilaku dan memaparkan
`window.bkRadial`. `berkah-experience.js` dulu menutup menu dengan menghapus
`[open]` seketika — itu akan memotong animasi keluar, jadi fungsi itu sekarang
meminta `window.bkRadial.close()` lebih dulu dan hanya jatuh ke cara lama bila
berkas baru tidak termuat.

**G. Soal "gsap, motion, lenis, dan react".** GSAP dan Lenis sudah lokal
(`public/vendor`). `motion` (dulu Framer Motion) dan React **tidak** ada di
proyek ini: SIMALUN-FINAL adalah aplikasi Blade + PHP tanpa bundler, sehingga
React berarti menambah Vite, Node, dan lapisan hidrasi hanya untuk satu menu —
bukan pertukaran yang sehat untuk halaman yang harus tetap jalan tanpa JS.
Pegas yang diminta `motion` di sini dicapai dengan `back.out` GSAP + kurva
`cubic-bezier(.34,1.4,.64,1)`. Bila suatu saat pindah ke React, padanan
komponennya:

```jsx
import { motion, AnimatePresence } from 'motion/react';

const RING = [[-98, 21], [-72, 70], [-24, 97], [28, 96]];

<AnimatePresence>
  {open && RING.map(([x, y], i) => (
    <motion.a key={i} href={hrefs[i]} className="ring-item"
      initial={{ x: 0, y: 0, scale: 0.42, opacity: 0 }}
      animate={{ x, y, scale: 1, opacity: 1 }}
      exit={{ x: 0, y: 0, scale: 0.42, opacity: 0 }}
      transition={{ type: 'spring', stiffness: 420, damping: 30, delay: i * 0.055 }} />
  ))}
</AnimatePresence>
```

Berkas penjaga: `tests/Feature/RadialMenuTest.php`, ditambah 23 pemeriksaan
jsdom (`/tmp/jsdomtest/radial.test.js`) untuk buka/tutup, klik luar, Escape,
reduced-motion, dan jalur tanpa GSAP.

### 9.23 Radial: tujuan kelima, tetap terjangkau, dan bukti 320 px

**A. Tujuan kelima.** Busur sekarang memuat lima tujuan: Keranjang, Pesanan,
Notifikasi, **Alamat**, Bantuan. Alamat dipilih karena satu-satunya halaman
pelanggan yang belum punya pintu di pita bawah maupun di busur; menambah
"Profil" hanya akan menduplikasi tab yang sudah ada. Radius dinaikkan ke
112 px dan sudut dirapatkan ke 26° (180°, 154°, 128°, 102°, 76°):

| Tujuan | --rx | --ry |
|---|---|---|
| Keranjang | −112 px | 0 px |
| Pesanan | −101 px | 49 px |
| Notifikasi | −69 px | 88 px |
| Alamat | −23 px | 110 px |
| Bantuan | +27 px | 109 px |

Jarak antarpusat 50 px (2·112·sin 13°) — lingkaran 46 px tidak bertumpuk.
Batas kanan: titik terkanan 27 px + 23 px = 50 px, sedangkan ruang bebas dari
pusat tombol ke tepi layar 320 px adalah 52 px. Jadi tidak ada yang terpotong.

**B. Tetap terjangkau setelah digulir.** Sebelumnya pil ringkas menyembunyikan
tombol radial (`display:none!important`), sehingga setelah halaman digulir
tujuan sekunder hanya bisa dicapai dengan menggulir balik ke atas. Sekarang
tombol tetap ada di pil (44 px, setinggi ikon lain). Satu jebakan yang
dihindari: `syncHeader` dulu melebarkan header (`is-pinned`) setiap kali menu
terbuka — kalau itu terjadi pada pil ringkas, titik tumpu busur bergeser di
tengah animasi. Jadi saat radial terbuka **dan** header sedang ringkas, pil
ditahan apa adanya; pil ringkas sudah `position: fixed`, tidak bisa hilang.

**C. Hubungan tombol–panel.** `<summary>` diberi `aria-controls="bk-radial-panel"`
dan panelnya `id="bk-radial-panel"` + `role="group"`, melengkapi `aria-expanded`
yang disinkronkan skrip. Pita bawah tidak berubah, jadi tes navigasi lama tetap
hijau. Satu detail fokus: bila menu ditutup (gulir, klik di luar, layar
melebar) sementara fokus keyboard berada di salah satu tujuan, fokus
dikembalikan ke tombol pembuka — bukan jatuh ke `<body>` di ujung dokumen.

**D. Bukti 320 px di pratinjau.** `docs/preview/radial.html` sekarang memuat
bingkai ponsel 320×620 yang memuat halaman itu sendiri dengan `?embed=1`.
Di dalam iframe, media query berjalan sungguhan — jadi klaim "busur tidak
keluar layar" bisa dilihat, bukan hanya dihitung. Di tampilan lebar, tombol
menu sengaja dipaksa tampil agar mudah dicoba.

Penjaga tambahan di `RadialMenuTest`: lima offset busur, `nth-of-type(5)`,
`route('customer.addresses.index')`, `aria-controls`/`id` panel, aturan pil
ringkas 44 px, dan tidak adanya lagi `display:none` untuk radial di header
ringkas, plus penanda `syncHeader` yang menahan pil.

### 9.24 Tamu memakai busur yang sama, dan tombol tema pindah ke dalamnya

Dua keluhan yang benar dari tangkapan layar: halaman tamu masih memakai dialog
"Jelajahi" bergaya daftar, dan tombol tema duduk terpisah di header.

**A. Satu komponen untuk dua peran.** `catalog/customer-navigation.blade.php`
dulu bercabang: pelanggan mendapat radial, tamu mendapat tombol pemicu dialog.
Sekarang keduanya memakai `_radial_customer.blade.php`; dialog `#bk-menu`
beserta tombol pemicunya dihapus karena tidak ada lagi yang membukanya.
Tujuan menurut peran:

| Peran | Tujuan di busur |
|---|---|
| Tamu | Katalog, Keranjang, Daftar, Masuk |
| Pelanggan | Keranjang, Pesanan, Notifikasi, Alamat, Bantuan |

Pita bawah tamu (`bk-guest-tabs`) tetap ada; busur memberi pintu yang sama
plus "Daftar" yang sebelumnya hanya bisa dicapai lewat halaman masuk.

**B. Geometri dihitung, bukan ditulis per slot.** Dulu lima aturan
`:nth-of-type(n)` menulis posisi tetap; sekarang sudut dibagi rata dari 180°
ke 79° (batas kanan layar 320 px) dan radius dipilih dari target jarak
antarpusat 50 px:

```php
$step   = 101 / ($slots - 1);                       // derajat
$radius = (int) round(25 / sin(deg2rad($step / 2))); // 25 = setengah jarak
```

| Slot | Radius | Posisi (--rx, --ry) |
|---|---|---|
| 5 (tamu) | 114 px | (−114,0) (−103,49) (−73,88) (−28,110) (22,112) |
| 6 (pelanggan) | 143 px | (−143,0) (−134,49) (−109,93) (−70,125) (−23,141) (27,140) |

Titik terkanan 27 px + 23 px = **50 px** < 52 px ruang bebas; jarak antarpusat
tepat 50 px sehingga lingkaran 46 px tidak bertumpuk. Menambah atau mengurangi
tujuan cukup mengubah isi array — CSS tidak disentuh.

**C. Tombol tema di dalam busur.** Slot terakhir busur adalah
`[data-bk-theme-host="radial"]`. `placeToggle()` di `berkah-ui-kit.js` sekarang
punya tiga tujuan, berurutan:

1. **≤700 px** → slot radial (satu tombol, bukan dua);
2. **>700 px** → slot header (`[data-bk-theme-host]`), seperti sebelumnya;
3. tanpa slot → tetap melayang (`bk-theme-dock`).

Pemeriksaan `offsetParent` sengaja dilewati untuk slot radial: panel memang
tersembunyi saat menu tertutup, dan tombol baru terlihat ketika busur dibuka —
itu maksudnya, bukan kegagalan tata letak. Karena tombolnya sama, `aria-pressed`,
ikon matahari/bulan, dan dialog sakelar robot tidak diduplikasi.

Saat tombol itu ditekan, `berkah-radial.js` menutup busur lebih dulu (animasi
menyusut 240 ms) baru dialog tema tampil — dua lapisan tidak bertumpuk, dan
kunci gulir Lenis dilepas sebelum dialog mengambil alih.

**D. Verifikasi.** `themehost.test.js` (jsdom, 8 pemeriksaan) menguji
perpindahan tombol: ponsel → slot radial, layar melebar → kembali ke header,
halaman tanpa busur → tetap di header. `RadialMenuTest` menambah penjaga untuk
geometri terhitung, tujuan tamu (`register`, `login`), slot tema, dan
`placeToggle` tiga tujuan.

### 9.25 Busur menyesuaikan posisi tombol: bentuk U saat pemicu di tengah

**Masalah.** Busur sebelumnya selalu menyapu dari 180° ke 79°: satu setengah
lingkaran ke **kiri** tombol. Itu benar ketika tombol menempel di ujung kanan
header, tetapi salah begitu header menjadi ringkas (pil). Di pil, tombol menu
berada di tengah dan lima tujuan bertumpuk ke kiri sampai menutupi kartu profil
di bawahnya — persis yang tampak pada tangkapan layar pengguna.

**Keputusan.** Bentuk busur sekarang dihitung saat menu dibuka, dari posisi
tombol di layar:

| Posisi tombol | Bentuk | Sudut |
| --- | --- | --- |
| Rapat ke kanan (header penuh) | busur kiri-bawah dari server | 180° → 79° |
| Di tengah (pil ringkas, banner) | **U** simetris di bawah tombol | 180° → 0° |

Geometri U tidak ditulis di CSS. `layoutArc()` di `berkah-radial.js` mencari
radius terbesar (150 px, turun 2 px sampai 88 px) yang masih memenuhi dua hal:
jarak antarpusat lingkaran 46 px tetap ≈50 px, dan seluruh busur masuk layar
dengan sisa 10 px di kiri-kanan. Sudut tiap slot dihitung dari langkah
`2·asin(25/radius)`, lalu `--rx`/`--ry` ditulis per tujuan. Bila tidak ada radius
yang muat (tombol sudah di ujung kanan), nilai dari server dipulihkan apa adanya —
jadi busur lama tetap menjadi jaring pengaman, bukan dibuang.

**Yang ikut menyesuaikan.** Cincin orbit (`--bk-orbit`, bawaan 244 px) dan
gumpalan goo memakai peubah yang sama, dipasang pada `<details>`; saat busur U
dipakai keduanya membesar mengikuti radius, sehingga bentuk U tidak terlihat
sempit di dalam cincin lama. `useServerArc()` menghapus peubah itu kembali.
**Keduanya dihapus pada §9.26** — di layar keduanya justru terbaca sebagai
“latar” di belakang busur.
Tidak ada perubahan `aria-*`, tidak ada tujuan yang hilang, dan sasaran tetap
46 px (>44 px).

**Kapan dihitung ulang.** Saat menu dibuka, saat `resize`, dan saat
`orientationchange` (ditunda 120 ms agar tidak dihitung tiap piksel). Rotasi
ponsel mengubah posisi pil, jadi bentuk busurnya harus ikut berubah. Bila
`getBoundingClientRect()` memberi ukuran nol (mis. jsdom), nilai server
dipertahankan — tidak ada tata letak yang bisa diukur.

**Pratinjau.** `docs/preview/radial.html` kini memakai pil ringkas di dalam
bingkai 320 px: wordmark disembunyikan dan filter menjadi ikon saja, sehingga
tombol menu berada di tengah dan busur U terlihat. Di halaman lebar, tombol
tetap di ujung kanan dan busur kiri-bawah masih bisa dibandingkan.

**Verifikasi.** `radial.test.js` bertambah 13 pemeriksaan: U punya tujuan di
kiri *dan* kanan, simetris, semuanya di bawah tombol, tidak keluar layar 320 px,
jarak antarpusat ≥46 px, kelas `is-arc-u` dipasang; tombol di ujung kanan
memulihkan `--rx` server dan melepas kelasnya; `layoutArc()` mengembalikan
`null` untuk satu tujuan dan untuk ruang sempit. `RadialMenuTest` menambah
penjaga untuk `is-arc-u`, `ARC_GAP = 50`, dan `orientationchange`.
**Diperbarui di §9.26:** orbit (`--bk-orbit`) dihapus bersama cincin dan
gumpalan, jadi penjaganya ikut dilepas. Harness: phpstub 75/0/200, `.tmp-verify` 26/0, blade-scan
93 view 0/0, jsdom 225/0.

### 9.26 Busur tanpa latar, dan klik kedua tidak lagi setengah jadi

Dua keluhan setelah §9.25 diuji di ponsel. Keduanya berasal dari hal berbeda:
satu dari hiasan, satu dari jalur animasi.

**A. “Seperti ada background saat radial dibuka.”** Di belakang tujuan ada dua
hiasan: cincin orbit 244 px (`::before` pada `.customer-radial__links`) dan
gumpalan goo (`#bk-menu-goo`, empat lingkaran + satu path, blur 6 px) pada
`opacity:.16`. Di atas latar terang keduanya menjadi bidang pucat besar — persis
“latar” yang membuat busurnya tidak jelas. Keduanya dilepas: cincin dihapus dari
CSS, gumpalan diganti `display:none` (elemennya tetap ada karena `id="bk-menu-goo"`
dipakai oleh uji). Yang tersisa hanya lingkaran 46 px milik tiap tujuan plus
cincin tipis 58 px di sekeliling tombol. Konsekuensinya `--bk-orbit` tidak lagi
dipakai: `berkah-radial.js` tidak memasang maupun menghapusnya, dan aturan
`calc(var(--bk-orbit,238px) * .98)` ikut hilang.

**B. “Klik dua kali hasilnya beda: ada yang sedikit, ada yang lengkap.”** Bukan
jumlah tujuan yang berubah — semuanya selalu ada. Yang berubah adalah *jarak*
kedatangan dan *apa yang terjadi pada klik kedua*:

1. Jeda antar-tujuan 55 ms membuat tween masuk total ±0,80 s. Dua tujuan
   terakhir masih di tengah tombol (opacity = `--p`, sekaligus kecil karena
   `scale` ikut `--p`) ketika mata sudah menyapu busur — terbaca “belum
   lengkap”. Jeda dirapatkan ke 28 ms dan durasi tween 0,52 s → 0,42 s, jadi
   seluruh busur tiba dalam ±0,56 s.
2. Setelah tween masuk selesai, `settle()` menulis `--p:1` untuk semua tujuan.
   Animasi yang terpotong tidak bisa lagi meninggalkan tujuan separuh.
3. Klik kedua saat busur sedang menyusut dulu di-`preventDefault` lalu
   diabaikan karena `closing` masih true — menu menutup sendiri dan pengguna
   harus mengklik sekali lagi. Sekarang klik itu **membatalkan** penutupan:
   `closing` dimatikan, timeline lama dibunuh, lalu tujuan dianimasikan kembali
   ke `--p:1` dengan jeda 20 ms dari ujung terakhir (`from:'end'`), sehingga
   busur kembali penuh di tempat, tanpa melompat ke pusat lebih dulu.
   `open()` juga mereset `closing = false`, jadi status tidak bisa tersangkut.

**C. Pratinjau.** `docs/preview/radial.html` menambah tombol “Buka / tutup
busur” (memanggil `click()` pada `<summary>`) untuk mencoba klik berulang tanpa
mencari tombolnya, dan teksnya diperbarui: tidak ada bidang di belakang busur,
jeda 28 ms, serta penjelasan bahwa klik kedua membatalkan penutupan.

**D. Verifikasi.** `radial.test.js` 47 pemeriksaan (tambah 7: klik saat menutup
tidak diteruskan ke `<details>`, seluruh `--p` kembali `1`, menu tetap terbuka,
klik berikutnya menutup lagi; ditambah pemeriksaan bahwa `--bk-orbit` tidak
dipasang pada mode U). `RadialMenuTest` menukar penjaga cincin menjadi penjaga
ketiadaan latar (`goo{display:none}`, tanpa `::before` cincin, tanpa
`--bk-orbit`) dan penjaga `stagger: 0.028`, `duration: 0.42`, `onComplete:
settle`, serta cabang `if (closing)`. Harness: phpstub 75/0/200, `.tmp-verify`
26/0, blade-scan 93 view 0/0, jsdom 232/0.

### 9.27 Busur kedua di halaman yang sama, slot kosong, dan navigasi panah

Sisa detail setelah §9.26. Semuanya soal keadaan yang jarang terlihat tetapi
nyata begitu komponen dipakai di lebih dari satu tempat.

**A. Skrip hanya menangani busur pertama.** `berkah-radial.js` memakai
`document.querySelector('[data-customer-radial]')`, jadi bila satu halaman
memuat dua busur — header (`_navbar_customer`) dan menu atas
(`_radial_header`, untuk halaman yang memakai header sendiri) — hanya yang
pertama mendapat animasi, kunci gulir, dan pemulihan fokus; yang kedua jatuh ke
perilaku `<details>` mentah tanpa gerak. Sekarang skrip memindai
`querySelectorAll` dan menjalankan `setup(menu)` untuk tiap busur: keadaan
(`timeline`, `closing`), pendengar, dan geometri terpisah. `window.bkRadial`
tetap menunjuk busur pertama (dipakai `berkah-experience.js` dan tes), dan
`window.bkRadials` memuat semuanya. Membuka satu busur menutup yang lain lewat
daftar `registry`, supaya dua panel tidak pernah terbuka bersamaan.

Catatan: `_radial_header.blade.php` dulu tidak di-`@include` di mana pun —
halaman katalog memakai `catalog/customer-navigation.blade.php` dan halaman
pelanggan memakai header biasa. Berkas itu kemudian dihapus (§9.41) karena ia
menyalin aturan tampil `.bk-header-radial` yang kini tinggal di partial busur,
dan salinan seperti itu persis yang membuat halaman tamu salah: orang
menyalinnya ke header pelanggan saja. Karena dua busur mungkin ada, id panel
diturunkan dari peran (§9.39).

**B. Slot tema kosong menyisakan lubang.** Slot tema disembunyikan CSS lewat
`:empty` ketika tombol tema diparkir di header (layar >700 px atau halaman tanpa
kit). Geometri lama tetap menghitung slot itu, jadi busur punya satu titik yang
tidak pernah terisi dan jarak antarpusat tidak rata. `filledItems()` menyaring
slot tema tanpa isi setiap kali `applyArc()` berjalan, jadi sudut dibagi untuk
slot yang benar-benar ada; slot kosong tidak pernah dipindahkan dari posisi
server.

**C. Navigasi panah.** Busur adalah satu kelompok tujuan, jadi arah panah
berpindah antar-tujuan: `→`/`↓` maju, `←`/`↑` mundur (memutar di ujung),
`Home`/`End` langsung ke ujung. Urutan DOM sama dengan urutan visual busur, jadi
tidak perlu memetakan sudut. Fokus dipindahkan ke elemen fokus di dalam slot
(tautan tujuan, atau tombol tema di dalam slot tema), `preventScroll` dipakai
agar halaman tidak melompat, dan `preventDefault` menahan gulir halaman saat
panah ditekan. Tab, Escape, dan pemulihan fokus tetap seperti sebelumnya.

**D. Verifikasi.** `radial.test.js` kini 62 pemeriksaan (+15): dua busur
terdaftar, `bkRadial` menunjuk yang pertama, membuka yang kedua menutup yang
pertama dan sebaliknya; slot tema kosong menghasilkan busur 5 titik yang
simetris tanpa memindahkan slot kosong; panah kanan/kiri maju-mundur, memutar di
ujung, `Home`/`End`, dan `preventDefault` pada panah. `RadialMenuTest` menambah
penjaga untuk `RADIAL_SELECTOR`, `window.bkRadials`, `other.element.open`,
`filledItems()`, `childElementCount > 0`, dan keenam tombol panah. Harness:
phpstub 75/0/200, `.tmp-verify` 26/0, blade-scan 93 view 0/0, jsdom 247/0.

### 9.28 Lencana yang tidak lagi menulis transform, dan penutupan dari luar

Dua sisa terakhir dari audit busur: satu pelanggaran aturan yang sudah
ditetapkan sendiri, satu keadaan yang tertinggal saat busur ditutup bukan oleh
skripnya.

**A. Lencana melanggar aturan “GSAP tidak menulis `transform`”.** Animasi
lencana memakai `fromTo(badges, { scale: 0.3, opacity: 0 }, { scale: 1, … })`.
GSAP menulis `transform` **inline dan permanen** pada tiap lencana, sehingga
setelah busur pertama dibuka `scale(calc(.4 + .6 * var(--p)))` dari CSS tidak
pernah berlaku lagi: lencana berhenti mengecil bersama busur dan tidak ikut
memantul pada pembukaan berikutnya. Ini persis pelajaran `data-bk-press` yang
dilarang dipakai pada elemen bertransform hover.

Perbaikan: lencana mendapat variabel sendiri, `--bp` (bawaan 1 supaya tanpa JS
tampilannya sama). GSAP hanya menulis `--bp` 0 → 1 dengan `back.out(2.4)`
(nilai boleh melewati 1, itu yang memberi pantulan), lalu CSS menggabungkannya:

```css
opacity: calc(var(--p) * var(--bp, 1));
transform: scale(calc((.4 + .6 * var(--p)) * (.3 + .7 * var(--bp, 1))));
```

Tidak ada `transform` inline yang tersisa — `berkah-radial.js` kini tidak
mengandung `scale:` sama sekali, dan penjaganya ada di `RadialMenuTest`.

**B. Ditutup dari luar skrip tetap menyisakan keadaan separuh.**
`berkah-experience.js` bisa melepas `[open]` langsung (`radialMenu.open =
false`) ketika `window.bkRadial.close()` tidak tersedia. Dulu cabang `toggle`
hanya menjalankan `setProgress(0)` bila `is-gsap` **belum** dipasang, jadi pada
busur yang pernah dianimasikan sisa `--p` menempel, tween lama masih berjalan,
`closing` bisa tertinggal `true`, dan `aria-expanded` tetap `"true"` padahal
panel sudah tertutup. Sekarang cabang itu selalu: menghentikan `timeline`,
mereset `closing`, `setProgress(0)`, `syncAria()`, lalu `restoreFocus()` —
sehingga busur berikutnya selalu mulai dari nol, dan pembaca layar tidak
mengumumkan menu yang sudah hilang.

**C. Verifikasi.** `radial.test.js` kini 68 pemeriksaan (+6): lencana memakai
`--bp` dan tidak punya `transform` inline, penutupan dari luar mengembalikan
semua `--p` ke 0, `aria-expanded` ikut turun ke `false`, pembukaan berikutnya
tetap penuh, dan klik tombol setelahnya kembali menutup (bukan meneruskan
“pembatalan” yang sudah basi). `RadialMenuTest` menambah penjaga
`opacity:calc(var(--p) * var(--bp,1))`, `setProperty('--bp', '0')`, `'--bp': 1`,
tidak adanya `scale:` di skrip, dan penanda penutupan dari luar. Harness:
phpstub 75/0/200, `.tmp-verify` 26/0, blade-scan 93 view 0/0, jsdom 253/0.

### 9.29 Lencana saat gerak dilewati, dan sisa CSS yang menyesatkan

**A. `--bp` tertinggal di 0.** `settle()` hanya menulis `--p`, sedangkan
`--bp` diset 0 di dalam cabang GSAP. Bila animasi dilewati — `prefers-reduced-
motion: reduce`, GSAP tidak termuat, atau tween dipotong sebelum lencana
mendapat giliran — lencana tetap pada `--bp: 0`, dan karena opacity-nya
`calc(var(--p) * var(--bp,1))`, angkanya tidak pernah terlihat meski busurnya
terbuka penuh. Sekarang `settle()` juga menulis `--bp: 1` untuk semua lencana,
dan cabang tanpa animasi memanggil `settle()` (bukan hanya `setProgress(1)`).

**B. Sisa CSS setelah §9.26.** Aturan `html[data-theme="dark"]
.customer-radial__goo g{...}` tidak berguna lagi karena gumpalannya
`display:none`, dan komentar `/* … cincin orbit */` masih menyebut hiasan yang
sudah dilepas. Keduanya dibersihkan; yang tersisa hanya cincin tipis 58 px di
sekitar tombol, dan komentarnya menyebut itu.

**C. Verifikasi.** `radial.test.js` 71 pemeriksaan (+3): dengan
`prefers-reduced-motion` semua `--p` dan `--bp` bernilai 1 dan tidak ada
timeline yang dibuat. Harness akhir: phpstub 75/0/200, `.tmp-verify` 26/0,
blade-scan 93 view 0/0, jsdom 256/0.

### 9.30 Penanda fokus tombol busur, kontras tinggi, dan fokus dialog tema

**A. Tombol pusat tanpa penanda fokus.** Tata letak katalog memasang aturan
global `summary:focus-visible`, tetapi tata letak pelanggan tidak — dan busur
memakai `<summary>`, bukan `<a>`, sehingga `a:focus-visible` milik pita bawah
tidak menolongnya. Akibatnya pengguna papan ketik di dasbor tidak melihat di
mana fokus berada saat mencapai tombol menu. Sekarang partial radial memasang
sendiri: `outline: 3px solid #bc5b75` dengan `outline-offset: 3px` (dan
`border-radius: 50%` supaya cincinnya bulat), ditambah varian tema gelap
`#f3a8bb` untuk tombol, tujuan, dan tombol tema di dalam slot — di atas latar
`#201820` warna rose tua tadi terlalu redup.

**B. Kontras lebih tinggi.** `berkah-ui-kit.css` dan `berkah-admin.css` sudah
punya blok `@media (prefers-contrast: more)` (garis kartu 2 px). Busur ikut:
garis lingkaran tombol, tujuan, dan tombol tema menjadi 2 px, dan cincin fokus
melebar ke 4 px.

**C. Fokus dialog tema jatuh ke `<body>`.** Sejak tombol tema pindah ke dalam
busur (§9.24), urutannya: tombol ditekan → busur menutup (240 ms) → dialog tema
tampil. Saat dialog ditutup, `berkah-ui-kit.js` mengembalikan fokus ke
`lastFocus`, yaitu tombol tema yang kini berada di dalam `<details>` tertutup.
Peramban menolak memfokuskan elemen tersembunyi, jadi fokus hilang ke `<body>`
dan pengguna papan ketik harus menekan Tab dari awal halaman.

Perbaikannya memilih sasaran yang benar-benar bisa difokuskan:
`hiddenByClosedDetails()` menelusuri induk dan berhenti pada `<details>` yang
tertutup — kecuali elemen itu sendiri adalah `<summary>`-nya, karena summary
tetap terlihat. Urutan sasaran: `lastFocus` → `<summary>` busur → tombol tema.
Di layar lebar (tombol di header) urutannya berhenti di langkah pertama, jadi
tidak ada perubahan perilaku.

**D. Verifikasi.** `themehost.test.js` 11 pemeriksaan (+3): tombol tema ada di
slot busur, dialog terbuka, dan setelah ditutup fokus kembali ke `<summary>`
busur (bukan ke `BUTTON.bk-theme-btn` yang tersembunyi). `RadialMenuTest`
menambah penjaga cincin fokus, varian tema gelap, dan blok
`prefers-contrast: more`; `UiKitTest` menambah penjaga
`hiddenByClosedDetails`, `node.tagName === 'DETAILS' && !node.open`,
`node.querySelector('summary') !== el`, dan urutan cadangan fokus. Harness:
phpstub 76/0/200, `.tmp-verify` 26/0, blade-scan 93 view 0/0, jsdom 259/0.

### 9.31 Pratinjau menampilkan tamu, dan tes yang menjaga geometrinya

**A. Pratinjau hanya memperlihatkan peran pelanggan.** `docs/preview/radial.html`
selalu menggambar lima tujuan pelanggan, padahal busur tamu (empat tujuan)
adalah inti §9.24 dan geometrinya berbeda: 6 titik radius 143 px versus 5 titik
radius 114 px. Karena itu pratinjau kini memuat **dua** busur dalam satu header
tiruan, ditandai `data-demo-role="customer"` dan `"guest"`; CSS menampilkan
salah satunya, dan `?tamu=1` (dipakai bingkai ponsel kedua) memilih tamu. Kedua
busur diinisialisasi `berkah-radial.js` — mungkin karena §9.27 membuat skripnya
menangani semua `[data-customer-radial]`; busur yang tersembunyi berukuran nol
sehingga geometrinya tidak dihitung ulang, dan membuka satu menutup yang lain.

**B. Tidak ada tes yang menjaga pratinjau.** Pratinjau adalah artefak yang
dipakai untuk menilai komponen, tetapi tidak ada yang memeriksa apakah isinya
masih sama dengan partial. `PreviewRadialTest` (baru) menghitung ulang geometri
dengan rumus yang sama seperti Blade — `$span = 101`, `$radius =
max(86, round(25 / sin(step/2)))` — lalu memastikan setiap pasangan
`--rx/--ry` untuk 6 titik dan 5 titik benar-benar tertulis di pratinjau. Tes itu
langsung menemukan satu selisih: saya menulis `-72px` dari hitungan tangan,
sedangkan PHP membulatkan `cos(129.5°) × 114` menjadi **-73px**.

Empat pemeriksaan:`test_pratinjau_memakai_css_dan_skrip_asli` (CSS dari partial,
GSAP/Lenis/`berkah-radial.js` lokal, tanpa CDN), posisi pelanggan 6 titik, posisi
tamu 5 titik + empat tujuan (`Katalog`, `Keranjang`, `Daftar akun`, `Masuk`) +
bingkai `?tamu=1`, dan `test_pratinjau_tidak_menghidupkan_lagi_cincin_atau_gumpalan`
(tidak ada `--bk-orbit` maupun `::before` cincin — penjaga §9.26 di tingkat
pratinjau).

**C. Harness.** phpstub 80/0/200, `.tmp-verify` 26/0, blade-scan 93 view 0/0,
jsdom 259/0.

### 9.32 Kelas cacat yang sama di dialog filter: fokus ke pemicu yang tersembunyi

Setelah fokus dialog tema diperbaiki (§9.30), pola yang sama diperiksa di
seluruh aplikasi. Satu tempat lain memakai pola itu: dialog filter
(`#bk-filter`) di `berkah-experience.js`. Saat ditutup, fokus dikembalikan ke
`[data-open-dialog="bk-filter"]`; kode lama hanya berjaga untuk pemicu yang
berada di header ringkas (dialihkan ke tombol logo). Bila pemicu disembunyikan
karena sebab lain — mis. filter sudah diterapkan lalu tombol filternya tidak
ditampilkan lagi — `focus()` gagal dan fokus jatuh ke `<body>`, sehingga
pengguna papan ketik harus mulai Tab dari awal halaman.

Perbaikannya tidak menebak visibilitas (jsdom tidak punya tata letak, dan
`getClientRects()` selalu kosong di sana): fokus dipindahkan ke sasaran, lalu
**diperiksa hasilnya**. Bila `document.activeElement` bukan sasaran itu, fokus
dipindahkan ke tombol logo — sasaran yang selalu ada ketika header dalam mode
ringkas. Di peramban asli pemeriksaan ini menangkap elemen `display:none`; di
jsdom pemeriksaan ini lolos tanpa mengubah tes lama.

**Verifikasi.** `dialog.test.js` 11 pemeriksaan (+2): pemicu yang `focus()`-nya
dinetralkan → setelah dialog ditutup fokus mendarat di `[data-bk-expand]`.
`UiKitTest` menambah penjaga untuk baris `const back = trigger && …`,
`document.activeElement !== back`, dan `focusWithoutScroll(expand);`. Harness:
phpstub 81/0/200, `.tmp-verify` 26/0, blade-scan 93 view 0/0, jsdom 261/0.

### 9.33 Kembali lewat Back, dan komentar Blade yang tercetak di pratinjau

**A. bfcache memulihkan busur yang terbuka dan gulir yang berhenti.** Busur
mengunci gulir lewat `Lenis.stop()`. Kalau pengguna membuka busur, menekan
tujuan, lalu menekan **Back**, peramban memulihkan halaman dari bfcache:
`<details open>`, kelas `is-gsap`, nilai `--p` terakhir, dan status Lenis yang
masih berhenti semuanya ikut kembali. Hasilnya halaman tampil dengan busur
terbuka dan **tidak bisa digulir** sampai busur ditutup.

Login, register, dan checkout sudah memakai `pageshow` untuk melepas status
tombol kirim; busur kini mengikuti pola yang sama: pada `pageshow` dengan
`event.persisted`, tween dihentikan, `closing` direset, `menu.open` dilepas,
`--p` dikembalikan ke 0, `aria-expanded` disinkronkan, dan `Lenis.start()`
dipanggil. Muat normal (`persisted === false`) tidak menyentuh apa pun.

**B. Komentar Blade tercetak sebagai teks.** Saat menambahkan busur tamu di
§9.31, saya menyertakan komentar bergaya Blade `{{-- … --}}` ke dalam
`docs/preview/radial.html`. Itu berkas HTML statis, bukan view Blade, jadi
komentarnya tidak dibuang — kalimatnya tampil sebagai paragraf di bawah header
tiruan. Sekarang diganti komentar HTML, dan `PreviewRadialTest` menolak `{{`,
`@include`, `@php`, serta `@endforeach` di pratinjau, supaya sintaks Blade tidak
bisa masuk lagi tanpa ketahuan.

**C. Verifikasi.** `radial.test.js` 77 pemeriksaan (+6): gulir terkunci saat
terbuka, `pageshow` persisten menutup busur, mengembalikan semua `--p` ke 0,
menurunkan `aria-expanded`, melepas `Lenis.start()`, dan muat normal tidak
menutup busur. `RadialMenuTest` menambah penjaga `pageshow`/`event.persisted`.
Harness: phpstub 81/0/200, `.tmp-verify` 26/0, blade-scan 93 view 0/0, jsdom
267/0.

### 9.34 Tombol di sisi kiri: busur dicerminkan, bukan tetap menyapu ke kiri

Aturan awal (“busur disesuaikan dengan penempatan”) baru punya dua bentuk:
busur server 180° → 79° (tombol di kanan) dan bentuk U (tombol di tengah).
Penempatan ketiga belum punya jawaban: tombol di **sisi kiri**. Di sana busur
server tetap membuka ke kiri, sehingga tujuannya keluar layar, sedangkan ruang
di kanan justru lapang.

Sekarang `applyArc()` memilih di antara tiga bentuk:

| Ruang kiri vs kanan | Bentuk | Sudut |
| --- | --- | --- |
| Kanan sempit, kiri lapang | busur server | 180° → 79° |
| Keduanya lapang (≥88 px sisi tersempit) | **U** | 180° → 0° |
| Kiri sempit, kanan lebih lapang >24 px | **cermin** (kelas `is-arc-opposite`) | 0° → 101° |

Cermin memakai rumus yang sama dengan server: radius dari jarak antarpusat
50 px (6 slot → 143 px), sudut dibagi rata 101°, hanya arahnya dibalik. Untuk
tombol di `x = 60` pada layar 320 px, tujuannya jatuh di `(143, 0)`, `(134, 49)`,
… `(-27, 141)` — kebalikan dari busur server `(-143, 0)` … `(27, 140)`, dan
seluruhnya tetap di dalam layar (`60 + 143 + 23 = 226 < 320`). Radius
diperkecil 2 px sekaligus bila ruangnya kurang, tetapi tidak di bawah 88 px;
kalau tetap tidak muat, nilai server dipulihkan seperti sebelumnya.

**Verifikasi.** `radial.test.js` 82 pemeriksaan (+5): kelas `is-arc-opposite`
dipasang (bukan `is-arc-u`), tujuan pertama di kanan tombol, `--ry` cermin
(0 → 141, tidak turun), tidak keluar layar 320 px, dan jarak antarpusat tetap
≥46 px. `RadialMenuTest` menambah penjaga `layoutSide`, `is-arc-opposite`, dan
`roomRight > roomLeft + 24`. Harness: phpstub 81/0/200, `.tmp-verify` 26/0,
blade-scan 93 view 0/0, jsdom 272/0.

### 9.35 Pratinjau: bingkai ketiga untuk penempatan di sisi kiri

§9.34 menambah bentuk busur ketiga (cermin), tetapi pratinjau baru
memperlihatkan dua: busur server (halaman lebar) dan bentuk U (bingkai 320 px
dengan pil di tengah). Sekarang ada bingkai ketiga, `?sisi=kiri`, yang menaruh
pil di tepi kiri (`body.demo-kiri .mock-header { justify-content: flex-start }`).
Di dalamnya tombol berada di sekitar `x = 86` pada layar 320 px, sehingga ruang
kanan (234 px) jauh lebih lapang daripada kiri — `layoutArc()` gagal (sisi
tersempit 86 − 10 = 76 px < 88 px) lalu `layoutSide()` mencerminkan busur ke
kanan: `(143, 0)` … `(-27, 141)`, semuanya masih di dalam layar.

Teks pengantar pratinjau juga diperbarui: bukan lagi “lima tujuan menyebar ke
kiri-bawah”, tetapi tiga bentuk menurut tempat tombolnya. `PreviewRadialTest`
menambah pemeriksaan `?embed=1&sisi=kiri`, aturan `body.demo-kiri`, dan
`is-arc-opposite`, di samping bingkai tamu yang sudah ada. Harness: phpstub
82/0/200, `.tmp-verify` 26/0, blade-scan 93 view 0/0, jsdom 272/0.

### 9.36 Pratinjau diuji saat berjalan, bukan hanya saat dibaca

`PreviewRadialTest` memeriksa **isi berkas** pratinjau (geometri, sintaks,
tanpa CDN), tetapi tidak ada yang menjalankannya. Padahal halaman itu memuat
skrip demo sendiri — pengganti tema, tombol “Buka / tutup busur”, pembacaan
`?tamu=1`/`?sisi=kiri`, dan penyuntikan CSS dari partial lewat `fetch`. Satu
`const` ganda atau salah ketik akan mematikan seluruh skrip itu tanpa terdeteksi
tes mana pun (persis seperti komentar Blade di §9.33 yang hanya terlihat saat
halaman dibuka).

`previewpage.test.js` (jsdom, 12 pemeriksaan) memuat `docs/preview/radial.html`
apa adanya, menyiapkan `fetch` tiruan yang mengembalikan isi partial, lalu
memeriksa:

- tidak ada galat skrip saat halaman dimuat;
- CSS partial benar-benar tersuntik ke `<head>` (`.customer-radial__item`,
  `is-gsap`);
- tombol **Terang** mengganti `data-theme` dan `aria-pressed`;
- dua tombol tema di dalam busur (pelanggan dan tamu) memakai pengendali demo;
- tombol “Buka / tutup busur” aman diklik;
- `?tamu=1&sisi=kiri` memasang `demo-guest` + `demo-kiri`, dan halaman memuat
  dua busur.

Sekalian dirapikan: judul `<h1>` tidak lagi menyebut “kanan atas” (tombol kini
bisa di tiga tempat), dan tiga bingkai ponsel diurutkan menurut alur — bentuk U
(kasus dasar) → tamu (jumlah tujuan berbeda) → sisi kiri (bentuk cermin) —
dengan judul “Pil di tengah: bentuk U”, bukan “Pada ponsel 320 px” yang
menyesatkan karena ketiga bingkai selebar 320 px.

**Harness akhir.** phpstub 82/0/200, `.tmp-verify` 26/0, blade-scan 93 view 0/0,
jsdom **284/0** (radial 82, kit 34, modal 26, sheet 16, controls 18, dialog 11,
interactions 30, preview 18, race 9, resize 6, submit 11, themehost 11,
previewpage 12).

### 9.37 Halaman tamu: busur yang mengambang, wordmark yang tertimpa, jarak catatan admin

Empat tangkapan layar berikutnya semuanya berpangkal pada satu berkas yang
salah tempat. Rupa dan aturan tampil tombol busur di dalam header
(`.bk-header-radial`) hanya hidup di `customer/_navbar_customer.blade.php`,
padahal partial itu **hanya** di-include untuk pelanggan
(`catalog/layout.blade.php`, di dalam blok `auth()->user()->role ===
'customer'`). Halaman tamu memakai `customer/_radial_customer.blade.php`
tanpa partial tersebut, jadi `<details>`-nya sampai ke peramban tanpa gaya
apa pun.

**Yang terlihat di tangkapan layar (`image-102`, 447×201 — tamu, ponsel).**
Logo, lalu **lingkaran pink berisi bunga** menimpa awal wordmark sehingga
yang terbaca hanya “Kreatif”, tombol cari, lalu tombol X dengan cincin yang
lebih besar dari ikon lain. Cincin itu `summary::after` yang memang muncul
saat busur terbuka, jadi busur sedang terbuka. Lingkaran pink itu bukan
tombol pemicu, melainkan **tujuan Katalog** (`aria-current="page"` → terisi
penuh). Ia duduk di posisi pemicu karena panelnya tidak punya titik tumpu:
tanpa `position:relative` pada `<details>`, `right:50%` dihitung dari kotak
selebar header, lalu `--rx` negatif menggeser tujuan jauh ke kiri — tepat di
atas wordmark. Busur sisanya (keranjang, daftar, profil, bulan) tumpah ke
bawah menimpa eyebrow hero “PAPAN BUNGA · BERKAH KREATIF”.

**Yang terlihat di layar lebar (`image-103`, 1577×273).** Tanpa aturan
`display:none` di atas 700 px, tombol pink dan keempat tujuannya tetap
tampil di atas hero — menduplikasi “Katalog”, “Masuk”, ikon keranjang, dan
tombol tema yang sudah ada di kanan header. Tombol X di sana juga
memperlihatkan cincin yang menembus sudut header karena kotaknya masih
memakai ukuran dasar (48 px + cincin 5 px) di dalam pil setinggi 70 px.

**Perbaikan.** Aturan tampil pindah ke partial busur itu sendiri, tempat
satu-satunya elemennya dirender — untuk semua peran:

```css
.bk-header-radial{display:none;position:relative;flex:0 0 auto}
.bk-header.is-compact .bk-header-radial>summary{width:44px;height:44px}
@media(max-width:700px){.bk-header-radial{display:block}}
```

`position:relative` yang ikut pindah adalah separuh perbaikannya: panel
kembali bertumpu pada tombolnya, sehingga bentuk U / server / cermin yang
sudah diuji tetap berlaku. Di layar lebar busur hilang seluruhnya (tujuannya
sudah menjadi tautan header). Partial pelanggan tetap menyimpan aturan
`is-compact` yang diuji, dan `body.bk-customer .bk-desktop-nav` tidak
disentuh. Ikon di dalam tombol kini `width:100%;height:100%` agar mengikuti
44 px saat header ringkas, bukan melebar keluar pil.

**Dua navigasi untuk tamu.** Di ≤700 px halaman tamu memuat pita bawah
`.bk-guest-tabs` (katalog, keranjang, masuk) **dan** busur dengan tujuan yang
sama. Pita itu kini hanya untuk halaman tanpa JS (`html:not(.bk-enhanced)`),
sejalan dengan `body.bk-guest{padding-bottom}` yang ikut dibatasi. Dengan JS,
tamu memakai busur; tanpa JS, pita tetap menjadi jalan keluar.

**Wordmark yang terpotong.** `.bk-wordmark` diberi `min-width:0` +
`overflow:hidden`, dan kedua barisnya `text-overflow:ellipsis`. Di 320 px
teksnya memendek dengan elipsis alih-alih menabrak tombol di kanannya.

**`image-104` — catatan “Mode pengujian” dan chipbar.** Dua hal terukur:
catatan berhenti di x≈876 dari 1022 px, sedangkan chipbar memenuhi lebar
(x≈153–1006); dan jarak vertikal catatan→chipbar hanya ±1 px. Penyebabnya
bukan angka acak: `.adm-note` ikut terkena batas baca
`max-width: var(--adm-measure)` (dan `78ch` di ≥1080 px), sementara
`.adm-chipbar` tidak; margin bawah catatan juga tidak pernah ada, jadi
chipbar menempel. Perbaikan: `.adm-note` keluar dari daftar elemen berbatas
baca (ia spanduk, bukan paragraf) dan mendapat `margin: 0 0 16px` — sama
dengan `margin` bawah `.adm-chipbar`, sehingga ritmenya satu skala.

Aksen kiri catatan juga dirapikan. `border-left: 3px` yang berdampingan
dengan tiga sisi 1 px membuat peramban menggambar busur sudut kiri-atas
dengan radius berbeda, dan garisnya terlihat patah. Aksen sekarang lapisan
`::before` selebar 3 px dengan `border-radius` yang sama dengan kartu.

**`image-105` — “Please fill out this field.”** Pesan validasi bawaan
peramban memakai bahasa antarmuka peramban, bukan `lang="id"` halaman.
Sembilan jenis pelanggaran (wajib diisi, format, pola, terlalu pendek,
terlalu panjang, di bawah/atas rentang, kelipatan, angka tak terbaca)
diterjemahkan lewat `setCustomValidity()` pada peristiwa `invalid` di
`berkah-ui-kit.js`; penanda `data-bk-validity` dilepas pada `input`/`change`
supaya pesan tidak terkunci, dan `customError` milik kode lain tidak
ditimpa. Atribut `required`/`type`/`pattern` tidak berubah, jadi pengiriman
form dan perilaku tanpa JS tetap sama.

**Harness setelah perubahan.** phpstub **83/0**/201 (satu tes baru:
terjemahan pesan validasi), `.tmp-verify` **26/0**, blade-scan **93 view
0/0**, jsdom **284/0** + suite validasi baru **15/0**.

### 9.38 Jarak kartu kategori: satu skala untuk chip admin dan pill katalog

Sisa dari `image-104`: pemindaian baris chip memberi lima jarak yang **seragam**
(341→354, 518→531, 620→633, 714→727, 856→869 = 13 px pada gambar), jadi
masalahnya bukan jarak yang tidak rata, melainkan jarak dalam kartunya yang
terlalu sempit: tinggi chip 43 px gambar dengan teks 12 px menyisakan ±9 px di
atas-bawah, sementara sisi kiri-kanan 13 px. Ditambah `gap` 8 px yang lebih
rapat daripada `padding` 13 px, barisan chip terbaca padat di dalam dan renggang
di luar.

Yang dirapikan (satu skala, bukan angka baru yang berdiri sendiri):

| Unsur | Sebelum | Sesudah |
| --- | --- | --- |
| `.adm-chip--link` | tinggi min 38 px, padding 7/13 px | tinggi min 40 px, padding **9/15 px**, radius 12 px |
| `.adm-chipbar` | gap 8 px, margin bawah 16 px | gap **10 px**, margin bawah 16 px |
| `.adm-chipbar` (≤560 px) | gap 6 px | gap **8 px** |
| `.adm-chipbar__label` | `margin-right: 2px` | **4 px** |
| `.adm-note` | tanpa margin bawah | **16 px** (sama dengan chipbar) |
| `.bk-category-strip` | gap 8 px, padding 8/0/12 | gap **10 px**, padding 8/2/12 + `scroll-padding-inline` |

Alasannya sederhana: jarak dalam kartu harus lebih kecil atau sama dengan
jarak antarkartu, dan jarak antarkartu harus lebih kecil daripada jarak
antarkelompok (16 px ke blok berikutnya). Sebelumnya urutannya terbalik.

### 9.39 Tiga sisa yang tidak terlihat di tangkapan layar

Ketiganya tidak muncul di `image-102`–`105`, tetapi berdekatan dengan
perubahan di atas dan akan menggigit cepat atau lambat.

**1. Id panel busur yang statis.** `aria-controls` dan `id` panel sama-sama
dipaku ke `bk-radial-panel`. Skrip sudah mendukung beberapa busur dalam satu
halaman (lihat §9.27), dan `docs/preview/radial.html` bahkan sudah memakai
`bk-radial-panel-tamu` untuk busur kedua — partialnya ketinggalan. Selama
id-nya kembar, `aria-controls` bisa menunjuk panel yang salah. Sekarang id
diturunkan dari peran: `bk-radial-panel` (pelanggan) dan
`bk-radial-panel-tamu` (tamu), sama dengan pratinjau. Skrip memilih panel
lewat kelas, bukan id, jadi tidak ada yang perlu diubah di sana — dan tes
menolak kalau `id="bk-radial-panel"` kembali dipaku.

**2. Kontras paksa (Windows High Contrast) sama sekali belum punya jalur.**
Di mode ini peramban membuang bayangan, latar kaca, dan warna merek, jadi
tiga komponen kami bisa menyatu dengan halaman:

| Komponen | Sebelum | Sekarang |
| --- | --- | --- |
| Tombol + tujuan busur | kaca putih transparan, tanpa bayangan → menghilang | `ButtonFace`/`ButtonText`, garis `ButtonBorder`, tujuan aktif `Highlight` |
| Lencana jumlah | `#85092d` di atas `#fffaf8` | `Highlight`/`HighlightText`, garis `Canvas` |
| Kotak centang | `appearance:none` + centang SVG putih | kembali ke kontrol bawaan (`appearance:auto`) |
| Tombol tema (header/busur/mengambang) | pastel tanpa garis tegas | warna sistem + `Highlight` saat aktif |
| Aksen kiri catatan admin | `::before` berwarna merek | garis `ButtonText` |

Semuanya memakai `forced-color-adjust: none` **hanya** pada elemen yang sudah
digambar dengan warna sistem, sehingga pemetaan ganda tidak terjadi.

**3. Pesan validasi bisa menahan pengiriman.** Setelah `image-105`,
`setCustomValidity()` dipasang pada `invalid` dan dilepas pada `input`/
`change`. Kalau isian diperbaiki tanpa peristiwa itu — autofill, skrip,
atau `form.reset()` dari kode lain — `customError` masih menempel dan form
menolak dikirim tanpa penjelasan. Sekarang penanda dilepas juga pada
`submit` dan `reset` (fase capture, sebelum peramban memvalidasi ulang),
sehingga validasi selalu berbicara tentang keadaan terbaru. Diuji di jsdom:
isian diubah lewat skrip → pesan masih menempel → `submit` melepasnya →
form bisa dikirim.

**4. Cincin tombol menembus pil ringkas.** Cincin `summary::after` berjarak
5 px di luar tombol 48 px. Saat header menjadi pil ringkas (padding 4 px,
tombol 44 px, tinggi pil 52 px), cincin 54 px menyentuh tepi pil — persis
gejala yang terlihat pada tombol X di `image-103`. Di mode ringkas cincinnya
dirapatkan menjadi 3 px, jadi 50 px dan tetap di dalam pil; di header biasa
(tinggi isi 64 px) cincin 58 px tetap utuh.

**5. Wordmark di halaman selain katalog.** Aturan pemendek wordmark hanya ada
untuk `.bk-header--catalog` (beranda), padahal keranjang dan detail produk
memakai header yang sama tanpa kelas itu. `min-width:0` + `overflow:hidden`
sekarang berlaku untuk semua header di ≤700 px, jadi teks memendek dengan
elipsis di halaman mana pun, bukan hanya di beranda.

**Harness.** phpstub **84/0**/201, `.tmp-verify` **26/0**, blade-scan **93
view 0/0**, jsdom **284/0** + validasi **15/0** + validasi-submit **7/0**.

### 9.40 Tujuan yang hilang saat busur disembunyikan, pratinjau yang jujur, dan istilah “Password”

**1. “Daftar” hilang di layar lebar.** Setelah busur tamu dibatasi ke ≤700 px
(§9.37), satu tujuan yang tadinya ikut busur — Daftar akun — tidak punya
padanan di navigasi header. Tamu di layar lebar hanya melihat “Katalog” dan
“Masuk”, dan baru menemukan pendaftaran setelah membuka halaman masuk. Itu
harga yang tidak perlu dibayar: tujuan busur dan tujuan header harus satu
daftar. `.bk-desktop-nav` untuk tamu kini berisi **Masuk** dan **Daftar**
(keduanya dengan `aria-current` saat sedang dibuka), jadi tidak ada tujuan
yang lahir atau mati karena lebar layar. Tes menjaganya dari dua sisi:
sumber view (adanya dua tautan) dan hasil render (pendaftaran muncul dua kali
— header dan busur).

**2. Pratinjau akhirnya membuktikan aturan ≤700 px.** `docs/preview/radial.html`
memaksa `.bk-header-radial` tampil supaya tombolnya bisa diklik. Karena
bingkai ponsel di halaman itu **iframe 320 px**, pemaksaan tersebut tidak
diperlukan di dalamnya — dan justru menyembunyikan fakta bahwa busur hilang
di layar lebar. Sekarang pemaksaan dibatasi ke halaman luar
(`body.demo-page:not(.demo-embed)`), dan bingkai keempat selebar 760 px tidak
memaksa apa pun: di sanalah aturan aslinya terlihat — tombol busur tidak
dirender, dan yang tampil hanya tautan header. Header tiruan juga diberi
`.mock-nav` (Katalog/Masuk/Daftar) yang disembunyikan di bawah 700 px, persis
seperti di aplikasi, plus penyesuaian agar bingkai lebar tidak tampak seperti
ponsel yang diperbesar (merek tampil, pil melebar, label “Cari” kembali).

**3. Satu istilah: “kata sandi”.** Pesan validasi di `lang/id/validation.php`
memakai “kata sandi” (atribut `password` → “kata sandi”, `current_password` →
“kata sandi saat ini”), tetapi label di halaman masuk, daftar, dan tiga
halaman profil memakai “Password”. Akibatnya galat “Kata sandi minimal 8
karakter” bisa muncul di bawah kolom berlabel “Password”. Semua label, judul,
tombol, placeholder, pesan galat sisi klien, dan judul surel atur ulang kini
memakai bentuk yang sama (“Kata Sandi” pada label/judul, “kata sandi” pada
kalimat). Satu entri `current_password` di `validation.php` ikut diperbaiki.

Penjaganya bukan ingatan: `UiAccessibilityTest` memindai seluruh view dan
menolak kata “Password” yang berdiri sendiri — nama seperti `updatePassword`
atau `autocomplete="new-password"` tidak dihitung.

**Harness.** phpstub **86/0**/201, `.tmp-verify` **26/0**, blade-scan **93
view 0/0**, jsdom **284/0** + validasi **22/0** (15 + 7), dan
`previewpage`/`preview` tetap 12/0 dan 18/0.

### 9.41 Partial pembungkus yang mati: sumber salinan aturan busur

`customer/_radial_header.blade.php` (29 baris) tidak di-`@include` di mana pun
sejak halaman katalog memakai `catalog/customer-navigation.blade.php`. Isinya
dua hal: pembungkus `.bk-top-menu` dan **salinan aturan tampil**
`.bk-top-menu .bk-header-radial{display:block}`. Salinan seperti itulah yang
menjadikan `image-103` mungkin: aturan yang sama ditulis ulang di partial
pelanggan, lalu halaman tamu — yang tidak memuat partial itu — tidak punya
aturannya sama sekali.

Berkasnya dihapus, `.bk-top-menu` dicabut dari daftar `:is()` di
`berkah-theme.css` (tidak ada JS yang menyebutnya), dan tes baru menjaga agar
aturan `.bk-header-radial{display:` tidak muncul lagi di view mana pun selain
`_radial_customer.blade.php`. Dengan begitu kesalahan yang sama tidak bisa
kambuh lewat salinan baru.

Harness: phpstub **87/0**/201, blade-scan **92 view 0/0** (berkurang satu
karena berkas dihapus).

#### Koreksi §9.37: aksen kiri butuh `overflow: hidden`

Saat aksen dipindah dari `border-left` ke `::before`, lapisan itu diposisikan
terhadap **kotak padding** — persegi — sementara permukaan kartu berlengkung
14 px. Tanpa pemotongan, ujung atas aksen menonjol keluar lengkung di kiri-atas
(gejala “patah” yang sama, hanya berpindah tempat). `.adm-note` dan
`.adm-app .notice` kini memakai `overflow: hidden` (lengkung kartu yang
memotong lapisannya) plus `overflow-wrap: anywhere` supaya kata panjang
dipatahkan, bukan dipotong. Dikunci oleh
`ResponsiveLayoutTest::test_admin_note_shares_the_rhythm_of_the_blocks_below_it`.

---

## 9.42 — Kata “password” yang lolos, dan surel yang masih biru

Penjaga §9.40 hanya menyisir kata `Password` berhuruf besar. Sapuan ulang
memakai pipeline yang membuang `<style>`/`<script>`, blok `@php`, komentar
Blade, ekspresi `{{ }}`, dan direktif `@…` lebih dulu — lalu memeriksa teks
yang benar-benar dibaca pengunjung (termasuk isi `__('…')`) — menemukan empat
sisa berhuruf kecil pada dua halaman dan satu subjek surel:

| Lokasi | Sebelum | Sesudah |
|---|---|---|
| `auth/confirm-password` | “Silakan konfirmasi password Anda …” | “konfirmasi kata sandi Anda” |
| `emails/reset-password` (3 kalimat) | “mengatur ulang password akun”, “halaman lupa password”, “password lama tetap aman” | “kata sandi …” |
| `ResetPasswordNotification` | subjek `Reset Password Akun …` | `Atur ulang kata sandi akun …` |

Kata “password” tetap sah sebagai nama atribut (`type="password"`,
`autocomplete="new-password"`, `name="password"`) dan sebagai nama variabel
(`updatePassword`), jadi pemindaian teks tampil — bukan pemindaian baris —
adalah satu-satunya cara yang tidak menghasilkan positif palsu. Dikunci
`UiAccessibilityTest::test_kata_password_tidak_tersisa_di_teks_yang_dibaca`
(pembaca: `visibleText()`), diuji ulang dengan menyuntikkan kembali pelanggaran:
tes gagal, lalu hijau setelah dipulihkan.

### Palet surel masih milik templat lama

`emails/reset-password` memakai tombol **#FF6B35 (oranye)** di tengah header
maroon `#54051f → #b6435e`, sementara `emails/layout` memakai teks navy
`#1a2332` dan abu dingin `#64748b`/`#94a3b8` di atas latar `#eef4fa` biru.
Empat warna biru itu tidak pernah ikut palet hangat aplikasi:

| Peran | Sebelum | Sesudah | Kontras di putih |
|---|---|---|---|
| latar bodi | `#eef4fa` | `#faf4f3` | — |
| tinta isi | `#1a2332` | `#321d24` | 13,4 : 1 |
| teks bantu | `#64748b` | `#6f5960` | 6,4 : 1 |
| teks kaki (11 px) | `#94a3b8` | `#806975` | 5,0 : 1 (sebelumnya 2,6 : 1 — gagal AA) |
| tombol utama | `#FF6B35` | `#85092d` | putih 10,1 : 1 |
| garis kaki | `#eef2f7` | `#f0e4e0` | — |

### Artefak build yang masih terlacak

Tiga berkas `public/build` (manifest + satu CSS + satu JS) terlacak git
padahal `/public/build` sudah ada di `.gitignore`, dan **tidak ada satu pun
view yang memuatnya** (nol `@vite`, nol rujukan `build/assets`). Ketiganya
dilepas dari indeks (`git rm --cached`, tetap di disk) dan panduan
`docs/kl/deployment-hostinger.md` diperbaiki: aset produksi dilayani dari
`public/css` + `public/js`, mengunggah `public/build` tidak memperbaiki
tampilan.

---

## 9.43 — Sisa istilah Inggris di teks yang dibaca pengguna

Setelah §9.40/§9.42 menyisir kata “password”, sapuan yang sama diperluas ke
kata Inggris lain yang muncul di teks tampil. Tiga kelompok ditemukan; semuanya
kata yang **sudah punya padanan Indonesia di halaman yang sama**, jadi
inkonsistensinya terlihat langsung oleh pengguna.

### 1. “Preview” → “pratinjau” (21 teks di 8 view + 3 pesan sistem)

| Berkas | Sebelum | Sesudah |
|---|---|---|
| `rental/unit` (9) | “Preview desain”, “Periksa preview”, “Menunggu preview desain dari admin.”, “Gambar preview”, “Unggah preview”, “preview berikutnya”, “preview ini” | “Pratinjau …” |
| `rental/admin-detail` (5) | “Preview desain · versi”, “Unggah preview” | “Pratinjau …” |
| `rental/show`, `rental/progress`, `rental/admin-progress` | “Preview desain menunggu respons Anda.”, “persetujuan preview terbaru” | “Pratinjau …” |
| `roles/admin/dashboard`, `roles/admin/help`, `roles/customer/help` | “unit memerlukan preview awal”, “Upload preview”, “Setelah admin mengirim preview” | “pratinjau awal”, “Unggah pratinjau”, “mengirim pratinjau” |
| `RentalDesignController`, `RentalProgressController`, `RentalReminders` | pesan validasi, flash “Preview desain diunggah.”, judul notifikasi “Preview desain menunggu” | “Pratinjau …” |

Penggantian dilakukan hanya pada **teks tampil**: tag, atribut, ekspresi
`{{ }}`, blok `@php`, dan direktif dilewati lebih dulu, sehingga nama relasi
`previews`, kolom `preview_path`, rute `rental.design.image`, kelas CSS, dan
variabel `$latestPreview` tidak tersentuh. Tiga asersi tes yang memaku teks
lama (`RentalAdminSearchTest`, `RentalUnitDetailTest` ×2, `RentalReminderTest`)
ikut diperbarui agar tetap menguji perilaku yang sama.

### 2. “Audit Trail” → “Jejak Audit”

Halaman `roles/admin/audit/index` sudah lama memakai legenda filter
**“Filter Jejak Audit”** di atas tabel yang judulnya masih **“Audit Trail”** —
dua istilah untuk halaman yang sama, berjarak beberapa piksel. Judul `<title>`,
`<h1>`, komentar kepala berkas, catatan kaki sidebar (“tercatat di jejak
audit”), dan label menu profil admin kini seragam “Jejak Audit”.

### 3. “Reset” → “Atur ulang filter” (6 label)

Halaman audit menulis “Coba ubah atau **atur ulang filter** di atas” pada
keadaan kosong, tetapi tombol di atasnya tertulis “Reset” dan “Reset Filter”.
Label yang sama juga dipakai `rental/index`, `rental/admin`,
`rental/cashbook`, dan `rental/schedule`. Semuanya kini “Atur ulang filter” —
satu istilah, sama dengan “atur ulang kata sandi”.

### 4. `lang/id/ui.php` dihapus

Kamus kecil itu tidak pernah dirujuk (`grep __('ui.`, `trans('ui`, `@lang('ui`
→ nol) dan dua nilainya justru belum diterjemahkan (`'edit' => 'Edit'`,
`'reset' => 'Reset'`). Berkas mati yang isinya bertentangan dengan label yang
benar-benar tampil lebih berbahaya daripada tidak ada, jadi dihapus.

### Penjaga baru

`UiAccessibilityTest::test_tidak_ada_label_inggris_tersisa_di_teks_atau_kamus`
memeriksa **teks tampil semua view** dan **semua nilai `lang/id/*.php`**
terhadap daftar kata `reset, preview, upload, submit, save, cancel, delete,
close, loading, search`. Diverifikasi dengan menyuntikkan dua pelanggaran
sekaligus (satu di view, satu di kamus): tes gagal dengan menyebut keduanya,
lalu hijau setelah dipulihkan. Catatan: `lang_path()` tidak tersedia di
lingkungan harness, sehingga path kamus dibaca lewat `base_path('lang/id')` —
kalau memakai `lang_path()` tesnya ter-*skip* dan penjaga tidak pernah jalan.

---

## 9.44 — Yang hanya terlihat di atribut dan di kamus mati

Penjaga §9.42/§9.43 hanya membaca teks di antara tag. Dua permukaan lain
ternyata menyimpan sisa yang sama:

### 1. Nilai atribut

| Berkas | Atribut | Sebelum | Sesudah |
|---|---|---|---|
| `roles/customer/profile/edit` | `placeholder` | “Masukkan password Anda” | “Masukkan kata sandi Anda” |
| `roles/admin/audit/index` | `placeholder` | “contoh: voucher, order.” | “contoh: voucher, pesanan.” |

`placeholder` dan `aria-label` dibacakan pembaca layar persis seperti teks
tampil, jadi keduanya sekarang ikut dipindai lewat helper `attributeText()`
(placeholder, aria-label, alt, title — id dan kelas tidak ikut).

### 2. Label yang lolos karena bukan daftar kata Inggris

Sapuan kata sebelumnya hanya mengenal sepuluh kata. Menambahkan
`login|logout|action|update|order` menemukan enam lagi — semuanya di halaman
yang sama-sama berbahasa Indonesia di sekitarnya:

| Berkas | Sebelum | Sesudah |
|---|---|---|
| `auth/login` | `<title>Login – Berkah Kreatif</title>` | `Masuk – Berkah Kreatif` |
| `roles/admin/notifications` | “Belum ada aktivitas order.” | “Belum ada aktivitas pesanan.” |
| `customer/addresses/index` | tombol “Update” (form PUT) | “Simpan” |
| `roles/admin/dashboard` (2×) | “Admin Panel” | “Panel Admin” |
| `roles/admin/profile_edit` | “SIMALUN Admin Panel” | “SIMALUN · Panel Admin” |
| `roles/admin/audit/index` | label filter “Action” | “Aksi” |

“Panel Admin” bukan sekadar urutan kata: tiga halaman admin lain sudah menulis
“Panel admin”, jadi halaman ini satu-satunya yang berbeda. Pola pencocokan
kata di penjaga kini memakai `(?<![A-Za-z$>.-])` supaya variabel seperti
`$order->` dan `$update` tidak dianggap label.

### 3. Tiga kamus bahasa yang tidak punya pemakai

`lang/id/messages.php`, `orders.php`, dan `finance.php` tidak pernah dirujuk
(`__('`, `trans('`, `@lang('` dengan prefix itu → nol di app, views, dan
routes) dan isinya masih domain **laundry**: status “Sedang Dicuci” /
“Disetrika”, pesan “Minimum order Rp :amount untuk pakai voucher ini.”,
“Pesanan tidak bisa dibatalkan karena sudah dalam proses pencucian.”
Ketiganya dihapus; `validation.php` tetap.

Penjaga baru `UiAccessibilityTest::test_setiap_kamus_bahasa_punya_pemakai`
memastikan setiap berkas baru di `lang/id/` dipakai lewat
`__('nama.kunci')`/`trans(...)`/`@lang(...)`, dengan pengecualian
`validation.php` (dimuat Laravel berdasarkan nama berkas). Diuji dengan
menaruh `lang/id/zzz.php` sementara: tes gagal menyebut berkas itu, lalu hijau
setelah dihapus.

`docs/kl/i18n.md` ditulis ulang: struktur yang benar (satu kamus), tabel
istilah baku, alasan kamus mati dihapus, dan panduan “rapikan istilah saat
menyentuh view” — menggantikan rujukan ke berkas yang sudah tidak ada.

---

## 9.45 — Tautan header katalog untuk admin: 38 px, bukan 44 px

`catalog/layout.blade.php` punya dua header. Untuk tamu dan pelanggan ia
memuat `catalog.customer-navigation` (header kaca yang sudah dirapikan di
gelombang C–G). Untuk **admin yang meninjau halaman publik** ia memakai
cabang `else` berisi header lama `.hero > .top > .nav`.

Diukur pada aturan yang berlaku:

| Aturan | Tinggi tautan |
|---|---|
| `.nav a` (dasar) | hanya tinggi baris ≈ 22 px |
| `@media(max-width:650px) .nav a{padding:8px 0}` | 8 + 22 + 8 ≈ **38 px** |

Jadi tepat di lebar ponsel — saat jari yang dipakai, bukan kursor — tautan
header admin justru turun ke 38 px, di bawah sasaran 44 px yang dipakai
seluruh header lain. `.nav a` kini `min-height:44px;display:inline-flex;
align-items:center`, dan aturan ≤650 px yang membungkus nav selebar header
tetap berlaku. Dikunci
`ResponsiveLayoutTest::test_header_katalog_untuk_admin_punya_tautan_setinggi_sasaran_sentuh`.

Satu hal yang **tidak** perlu ditambal: header ini memang tidak punya slot
`data-bk-theme-host`. Bukan celah — `berkah-ui-kit.js` sudah punya jalur
cadangan `.bk-theme-dock` yang menempelkan tombol tema ke `body` bila tidak
ada slot yang terlihat, jadi admin tetap bisa berganti tema dari halaman
katalog. Menambahkan slot kedua hanya akan membuat pemilihan host berebut.

---

## 9.46 — Peta label yang meledak saat ada nilai baru

Label status ditulis sebagai peta di dalam view, bukan lewat satu kelas.
Saat menyisirnya ketemu delapan peta di enam berkas: `progressLabels` (tiga
salinan, lima nilai), `nextLabels` (dua salinan), `unitStatusLabels`,
`paymentStatusLabels`, peta pembayaran dan peta pratinjau di `rental/unit`,
serta peta status di `rental/show`, `rental/receipt`, dan `rental/daily-sheet`.

Semua peta **lengkap hari ini** — saya cocokkan dengan enum di migrasi
(`rental_orders.status`: pending/confirmed/cancelled; `rental_payments.status`:
pending/verified/rejected; `rental_design_previews.status`:
pending/revision/approved/superseded) dan dengan rantai `progress` di
`RentalProgressController` (waiting → production → ready → handed_over →
returned). Yang jadi masalah bukan isinya, tapi bentuk indeksnya:

```blade
{{ ['cancelled'=>'Dibatalkan'][$unit->status] }}
```

Enam indeks seperti itu tidak punya cadangan. Tambah satu nilai status di
kemudian hari — mis. unit `expired` — dan halaman **pelanggan** (`rental/show`,
`rental/unit`) serta **struk** (`rental/receipt`) langsung 500 karena
`Undefined array key`, bukan sekadar menampilkan teks yang belum diterjemahkan.
Enam indeks itu kini berakhiran `?? $unit->status` (dan `?? $payment->status`,
`?? $preview->status`), sejalan dengan yang sudah dipakai di `daily-sheet`
dan halaman admin.

Dikunci `UiAccessibilityTest::test_peta_label_selalu_punya_cadangan`: setiap
`][$…->status]`/`][$…->progress]` di seluruh view wajib diikuti `??`. Diuji
dengan menghapus satu cadangan: tes gagal menunjuk berkas dan potongan
ekspresinya, lalu hijau setelah dipulihkan.

---

## 9.47 — Tujuh belas berkas yang tidak pernah tampil

Sapuan ini berangkat dari warna, bukan dari halaman. Saat memeriksa heksa
terang di dalam blok `<style>` tiap view, muncul warna yang tidak pernah ada
di palet aplikasi: `#f4f8fc`, `#ddeeff` (biru), `#ecfeff`, `#bae6fd`,
`#0e7490` (cyan/sky) — sisa era Tailwind/Breeze. Penelusuran pemakainya
menunjukkan sebabnya: halaman-halaman itu **tidak pernah dirender**.

Dihapus (13 berkas view + 4 komponen):

| Berkas | Kenapa mati |
|---|---|
| `auth/reset-password.blade.php` | `ResetPasswordController::showForm` justru mengembalikan `auth.forgot-password` (yang punya cabang `@elseif(isset($token))`) |
| `profile/edit.blade.php` + `profile/partials/{delete-user-form,update-password-form,update-profile-information-form}` | `ProfileController::edit` memetakan peran ke `roles.admin.profile_edit` / `roles.customer.profile.edit` |
| `customer/addresses/index.blade.php` | `CustomerAddressController` merender `roles.customer.addresses.index` |
| `rental/help-layout.blade.php` | halaman bantuan memakai `layouts.admin` dan `catalog.layout` |
| `layouts/component/_form_loading.blade.php` | hanya disebut di komentar pemakaiannya sendiri |
| `components/{auth-session-status,wa-button,danger-button,modal,secondary-button}` | nol `<x-…>` setelah berkas di atas hilang |

Dua tes ikut disesuaikan karena menyebut layout yang sudah tidak ada
(`UiDefaultsTest::LAYOUTS_WITH_CONTROLS`, `UiKitTest` daftar `bk-back` dan
pola `@extends`). Komentar di alias `layouts/component/customer/_confirm_modal`
diperbarui: aliasnya tetap dipakai `roles/customer/profile/edit.blade.php`,
tetapi tidak lagi oleh halaman daftar alamat yang sudah dihapus.

### Penjaga: setiap view harus bisa dijangkau

`UiAccessibilityTest::test_setiap_view_bisa_dijangkau` mengumpulkan seluruh
teks `app/`, `routes/`, dan `resources/views/` — **komentar dibuang lebih
dulu** — lalu menuntut setiap `*.blade.php` disebut sebagai string literal
(`'nama.view'`) atau, untuk komponen, sebagai `<x-nama>`. `errors.*` dan
`vendor.*` dikecualikan karena dimuat Laravel sendiri; `.php` non-Blade di
dalam `views/` (peta ikon bangkitan) dilewati.

Dua pelajaran yang mengubah penjaga itu saat diuji:

1. **Komentar menipu.** Versi pertama menghitung semua teks, sehingga alias
   `customer/_confirm_modal` dan `_form_loading` dianggap hidup karena
   namanya muncul di komentar dokumentasi. Setelah komentar dibuang,
   `_form_loading` langsung terungkap sebagai yatim.
2. **Tes bukan pemakai.** `rental/help-layout` disebut dua berkas tes, jadi
   pemindaian yang menyertakan `tests/` akan menganggapnya hidup. Tes justru
   bisa membuat view mati tampak wajar; karena itu `tests/` tidak dihitung.

Diuji dengan menaruh `resources/views/zzz/orphan.blade.php` dan
`resources/views/components/zzz.blade.php` sementara: keduanya dilaporkan,
lalu hijau setelah dihapus. `docs/AUDIT-ALUR-DAN-JALUR.md` baris 10–12
diperbarui: baris 10 selesai (dengan daftar sebenarnya), baris 11 ditandai
keliru (halaman alamat sudah bertoken hangat), baris 12 selesai (`roles/driver`
sudah tidak ada).

---

## 9.48 — Netral dingin yang tertinggal di tiga halaman hidup

Pemindaian rona (hue) pada blok `<style>` semua view menyisakan empat halaman
yang masih memakai netral dari tema biru lama, padahal latarnya sudah krem
hangat:

| Variabel | Nilai lama (dingin) | Nilai baru (hangat) | Berkas |
|---|---|---|---|
| `--ink` | `#112030` / `#1a2332` (navy) | `#321d24` | dashboard admin, alamat (create/edit), profil pelanggan |
| `--ink-mid` | `#46586c` / `#3d5066` (slate) | `#6f5960` | idem |
| `--ink-lt` | `#5f7082` (slate) | `#806975` | idem |
| `--border` | `#e4ecf4` (biru-kelabu) | `#f0e4e0` | dashboard admin |
| `--line-soft` | `#f0f5fa` | `#f1e6e3` | dashboard admin |
| `--shadow(-md)` | `rgba(0,47,92,…)` (navy) | `rgba(84,5,31,…)` | dashboard, alamat, profil |
| gradien avatar | `#f9e8ec → #d3ecfb` (krem → biru langit) | `#f9e8ec → #f2dfe4` | dashboard admin |

Kontras tidak turun di bawah AA, hanya sedikit bergeser:

| Token | Lama (krem / putih) | Baru (krem / putih) |
|---|---|---|
| tinta redup | 4,68 / 5,09 | 4,60 / 5,01 |
| tinta kedua | 6,72 / 7,31 | 5,90 / 6,41 |
| tinta utama | 15,16 / 16,49 | 14,44 / 15,71 |

Nama variabelnya tidak diubah — justru itu yang membuat tema gelap bekerja:
`berkah-theme.css` mendefinisikan ulang `--ink`, `--ink-mid`, `--ink-lt`,
`--border`, `--line-soft`, `--card`, `--surface` di `html[data-theme="dark"]`,
sehingga halaman-halaman ini ikut gelap tanpa blok tema sendiri.

### Variabel mati ikut dibuang

Dashboard admin mendeklarasikan delapan variabel yang tidak dipakai di mana pun
(`--sky`, `--teal`, `--teal-lt`, `--violet`, `--violet-lt`, `--amber`, `--red`,
`--red-lt`). Dua di antaranya (`#0d9488` teal, `#6366f1` indigo) satu-satunya
alasan pemindaian rona ini terus menunjuk halaman tersebut.

### Penjaga

`UiAccessibilityTest::test_tidak_ada_netral_dingin_sisa_tema_biru` menolak
sembilan nilai lama itu muncul kembali di view mana pun, dengan pesan yang
menyebut berkas dan heksanya. Diverifikasi dengan menyuntikkan
`.sisa-biru{color:#5f7082}` ke satu view: tes gagal menyebut berkas itu, lalu
hijau setelah dipulihkan.

### Token `-lt` yang tidak pernah dibaca

`berkah-theme.css` mendeklarasikan lima pasang token `-lt` tanpa satu pun
pembaca: `--accent-lt`, `--teal-lt`, `--red-lt`, `--violet-lt`, `--yellow-lt`
(sepasang di blok gelap, sepasang di blok terang). Pemindaian `var(--nama)` di
`public/css`, `public/js`, `resources/views`, `resources/css`, `resources/js`,
dan `app/` menemukan nol rujukan untuk kelimanya, sementara `--blue-lt` (4),
`--orange-lt` (2), dan `--green-lt` (2) memang dipakai halaman admin. Sepuluh
baris itu dibuang; sisanya tetap.

Blok alias "nama lama, nilai baru" (`--bg`, `--text`, `--muted`, `--paper`,
`--surface-300`, `--danger-tint`, `--success-tint`, `--warning-tint`) sengaja
**tidak** disentuh: blok itu mendokumentasikan pemetaan tema untuk halaman yang
memakai nama variabel lama, jadi menghapus per entri akan mengaburkan maksud
bloknya, bukan merapikannya.

---

## 9.50 — Huruf merek lepas dari CDN

Tujuh halaman masih meminta Plus Jakarta Sans ke `fonts.googleapis.com`
(25 tautan, termasuk `preconnect` yang di `auth/login` terpasang dua kali),
padahal catatan di `layouts/guest.blade.php` sendiri sudah menyatakan "tidak
ada CDN pihak ketiga". Akibatnya huruf hilang saat perangkat luring, saat CDN
diblokir jaringan kantor/sekolah, dan `PreviewRadialTest` sudah lebih dulu
mengunci "tidak ada `fonts.googleapis`" pada pratinjau — kunci itu tidak
berlaku untuk halaman lain.

Sekarang:

| Berkas | Isi |
|---|---|
| `public/fonts/plus-jakarta-sans-latin-wght-normal.woff2` | 27 KB, variabel 200–800 |
| `public/fonts/plus-jakarta-sans-latin-ext-wght-normal.woff2` | 22 KB |
| `public/css/berkah-fonts.css` | dua `@font-face`, `font-display: swap`, `unicode-range` per subset |

Dipasang dari `layouts/component/_theme_head.blade.php` — satu tempat, dan
semua dokumen (tamu, pelanggan, admin, katalog, masuk/daftar/lupa sandi)
memang meng-`@include` berkas itu; hanya `emails/layout` yang tidak, dan surel
memang tidak boleh memuat huruf eksternal. `preload` hanya untuk berkas latin
karena seluruh teks Indonesia ada di subset itu.

Dua tautan mati ikut dibuang: `resources/css/splash.css` (memuat Poppins dari
Google Fonts, tidak pernah ikut build Vite maupun dirujuk halaman) dan
`_head_meta` yang tersisa hanya tautan favicon.

Penjaga: `UiAccessibilityTest::test_tidak_ada_aset_pihak_ketiga_di_view`
(komentar blade dibuang lebih dulu, jadi catatan yang menyebut nama domain
tidak dihitung) dan `test_huruf_merek_dihosting_sendiri` (dua `@font-face`,
`swap`, dan setiap `url()` harus menunjuk woff2 yang benar-benar ada — dicek
lewat empat bita pertama `wOF2`). Keduanya diuji dengan menyuntikkan ulang
tautan Google Fonts ke satu view: tes gagal menyebut berkasnya, lalu hijau
setelah dipulihkan.

---

## 9.51 — "Dashboard" jadi "Dasbor", judul seragam

`app/Support/AdminNav.php` sudah lama memakai **Dasbor** untuk butir pertama
panel admin, tetapi teks lain belum ikut:

| Letak | Sebelum | Sesudah |
|---|---|---|
| `errors/403`, `errors/404` | tombol "Ke Dashboard" | "Ke Dasbor" |
| `catalog/layout` (nav hero, 2 tautan) | "Dashboard" | "Dasbor" |
| `<title>` dashboard admin | "Dashboard Admin – Berkah Kreatif" | "Dasbor Admin · Berkah Kreatif" |

Pemisah judul juga bercampur tiga gaya: ` – ` (admin, login, daftar, alamat,
jejak audit), ` · ` (tamu, katalog, pelanggan, ringkasan pesanan, lembar
harian, apresiasi), dan ` | ` (halaman galat). Sekarang semuanya ` · `,
termasuk `{{ $code }} · {{ $title }} · Berkah Kreatif` di `errors/layout`.

Dua penjaga: kata `dashboard` masuk daftar label Inggris yang ditolak
(`test_tidak_ada_label_inggris_tersisa_di_teks_atau_kamus`), dan
`test_judul_halaman_memakai_pemisah_titik_tengah` menolak `<title>` yang
memakai ` – `/` | ` atau tidak berpemisah sama sekali (surel dikecualikan —
klien surel memakai nama merek saja).

Sapuan "Menu" tidak diubah: `aria-label="Menu lainnya"`, "Menu admin", label
radial "Menu" dan judul panel "Menu pelanggan/tamu" semuanya berbahasa
Indonesia dan sudah menyebut isinya pada `aria-label`, jadi tidak ada istilah
asing yang perlu diterjemahkan.

### Aset tanpa pemakai ikut dikunci

`resources/css/splash.css` baru ketahuan yatim saat tema huruf dibongkar:
berkas itu masih memuat Poppins dari Google Fonts padahal tidak pernah masuk
daftar input Vite maupun dirujuk halaman. Supaya tidak terulang,
`UiAccessibilityTest::test_setiap_aset_css_dan_js_punya_pemakai` menelusuri
setiap berkas di `public/css` dan `public/js` dan menuntut namanya disebut
minimal sekali oleh berkas lain (isi berkas itu sendiri tidak dihitung, jadi
komentar yang menyebut nama sendiri tidak menyelamatkan aset yatim).
Diverifikasi dengan membuat `public/css/zzz-orphan.css`: tes gagal menyebut
berkas itu, lalu hijau setelah dihapus. Kesebelas CSS dan kesebelas JS yang ada
sekarang semuanya punya pemakai.

---

## 9.53 — Item tambahan & foto referensi desain

Dua permintaan pemilik dikerjakan bersamaan karena keduanya menyentuh form
checkout: pelanggan boleh **mengunggah beberapa foto contoh desain** (karena
menjelaskan selera lewat teks sering tidak cukup), dan admin boleh menyiapkan
**katalog item tambahan** yang dipilih pelanggan per unit, dengan harga masuk
tagihan.

Keputusan yang dipakai (dari pemilik): foto berlaku **per pesanan** (maks 5),
daftar item tambahan **global untuk semua papan**, harga masuk **total unit**
sehingga ikut DP/pelunasan, dan pilihan **terkunci setelah pesanan dikirim**.

Catatan visual singkat:

| Bagian | Catatan |
|---|---|
| `.bk-addon-list` / `.bk-addon` | Satu baris per item: foto 56 px, nama + harga, kotak jumlah 44 px. Di bawah 420 px kotak jumlah turun ke baris sendiri dan melebar penuh. |
| `.bk-addon__text small` | Keterangan memakai `--theme-ink-mid` dengan cadangan `#6f5960`, jadi terbaca di tema gelap tanpa aturan tambahan. |
| Unggahan foto | Kotak putus-putus setinggi `--bk-touch`, tombol `::file-selector-button` 36 px di dalamnya. |
| `.bk-reference-list` | Galeri 96 px (jauh di atas sasaran ketuk), garis `--theme-line`. |

Halaman yang ikut berubah: checkout (dua bagian baru + `enctype`), detail
pesanan pelanggan, detail unit, ringkasan cetak, dan detail pesanan admin.

Rincian alur, tabel, dan batas perilaku ada di
`docs/FITUR-ITEM-TAMBAHAN-DAN-FOTO-REFERENSI.md`.

**Istilah ikut dirapikan:** menu admin "Audit trail" → **Jejak audit** (di
`AdminNav` dan kartu menu profil admin), sesuai tabel istilah `docs/kl/i18n.md`
yang sudah lebih dulu menetapkan "jejak audit".

### 9.54 Penjaga sintaks ekspresi Blade

Satu ekspresi di `rental/unit.blade.php` kehilangan satu kurung tutup pada
`map(...)->implode(...)`. Karena Blade baru mengompilasi view saat halaman
diminta, `php -l` pada sumbernya tetap bersih dan pemeriksaan statis tidak
menangkapnya — yang muncul justru `ParseError` pada view terkompilasi dan
halaman 500.

Penjaga baru `UiAccessibilityTest::test_semua_ekspresi_blade_bisa_diurai`
memindai semua view dan mengumpankan isi `{{ }}`, `{!! !!}`, serta blok `@php`
ke `token_get_all(..., TOKEN_PARSE)`; galat menyebut nama berkas (pemisah path
dinormalkan agar sama di Windows dan Linux). Penjaga ini terbukti menangkap
kesalahan tersebut saat diuji dengan menyuntikkan ulang bug yang sama, lalu
dikembalikan.

Ikut dirapikan: tulisan item tambahan kini seragam di semua halaman
(`Lilin × 2`, jumlah 1 tanpa pengali) lewat `RentalOrderUnitAddon::label()`, dan
baris item di halaman unit dipindahkan keluar dari blok potongan voucher agar
tetap tampil pada pesanan tanpa voucher.

Pesan galat item tambahan dan foto referensi kini berbahasa Indonesia penuh
(`lang/id/validation.php`), dan batas "5 jenis item per unit" yang sebelumnya
hanya tertulis di dokumentasi kini benar-benar ditolak server dengan pesan
per unit.

Batas foto referensi dinaikkan dari 5 MB menjadi **25 MB per foto** (total 60 MB
per pesanan) karena foto kamera ponsel/DSLR modern sering 8–20 MB. Agar
pelanggan tidak menabrak batas PHP tanpa penjelasan, checkout membaca
`upload_max_filesize`/`post_max_size` lewat `App\Support\UploadLimit` dan
menampilkan peringatan bila server masih lebih ketat; `public/.user.ini` dan
panduan deploy ikut disamakan.

### 9.55 Gulir terasa "patah" saat digeser cepat atau miring

Laporan: digulir cepat di ponsel, terutama dengan arah miring, halaman kadang
terasa patah — kadang sama sekali tidak mau bergulir.

Empat sebab yang ditemukan, semuanya di lapisan gulir:

1. **Lenis memegang gestur jari.** `syncTouch: true` membuat Lenis
   mem-`preventDefault` `touchmove` lalu menggulir sendiri dari selisih jari.
   Bila peramban sudah memulai gulir aslinya sendiri (umum pada gestur cepat,
   karena `touchmove` berikutnya menjadi tak-bisa-dibatalkan), dua mesin
   menggulir bersamaan → posisi saling tarik. Arah miring memperburuknya:
   peramban memilih satu sumbu, Lenis menghitung sumbu lain.
2. **Halaman membeku setelah Lenis berhenti.** `lenis.css` bawaan memuat
   `.lenis-stopped { overflow: clip }` pada `<html>`, dan selama berhenti
   Lenis juga mem-`preventDefault` sentuhan. Kalau `stop()` pernah terjadi
   (sheet, dialog, busur menu radial) dan `start()` tidak menyusul, tidak ada
   satu pun jalur gulir yang tersisa selain muat ulang.
3. **Strip mendatar menahan sumbu Y.** `lenis.css` memasang
   `overscroll-behavior: contain` pada semua `[data-lenis-prevent]`, sedangkan
   `berkah-motion.css` mengulanginya. Strip mendatar (`.bk-how`,
   `.bk-category-strip`, `.bk-jump div`, tabel lebar) ikut mendapatkannya,
   sehingga geser jari yang dimulai di atas strip tidak bisa menyambung ke
   halaman — persis di bagian yang paling sering digulir di katalog.
4. **Delta frame tidak dibatasi.** `gsap.ticker.lagSmoothing(0)` meneruskan
   jeda nyata ke Lenis; satu frame panjang membuat posisi gulir melompat ke
   target alih-alih meluncur.

Perbaikan:

- `berkah-motion.js`: `syncTouch: false` (gulir sentuh milik peramban),
  `lagSmoothing(500, 33)`, dan jaring pengaman `reviveIfStuck` yang
  menyalakan kembali Lenis pada sentuhan/klik berikutnya bila ia tertinggal
  dalam keadaan berhenti sementara tidak ada kunci gulir.
- Kunci gulir busur radial kini memakai penanda bersama `bk-scroll-frozen`;
  `berkah-motion.js` menghitungnya di `scrollLocked()` sehingga pengamat kelas
  tidak menyalakan Lenis di tengah busur terbuka. Penanda mati
  `lenis-stopped-lock` dibuang.
- `berkah-motion.css`: strip mendatar hanya mengunci sumbu X
  (`overscroll-behavior-x: contain` + `overscroll-behavior-y: auto`) sehingga
  geser miring tetap menggulir halaman; `.bk-jump div` ikut masuk
  `SCROLL_REGIONS` agar roda mendatar tidak direbut Lenis.
- `[data-bk-tilt]` tidak lagi memakai `will-change` permanen; lapisan
  compositor hanya dibuat saat kursor presisi berada di kartu, supaya kartu
  katalog tidak memakan memori GPU di ponsel.

Yang tetap sama: pengunci gulir sheet (`body.fc-scroll-lock`), gulir halus roda
di desktop, reveal/parallax berbasis ScrollTrigger, dan seluruh jalur
`prefers-reduced-motion` serta `saveData`.

Dijaga `MotionAssetsTest::test_gulir_sentuh_tetap_milik_peramban`.

Cara memeriksa di ponsel (Chrome/Safari):

1. Katalog: geser cepat naik-turun berulang kali — tidak boleh ada jeda patah
   atau posisi yang melompat.
2. Mulai geser miring ±45° tepat di atas deret chip kategori atau strip
   "Cara pesan" — halaman harus ikut bergulir, bukan diam.
3. Buka sheet filter lalu tutup, langsung geser lagi — halaman langsung hidup.
4. Buka busur menu lalu tutup, lalu geser — gulir kembali normal tanpa perlu
   muat ulang.
5. Gulir sampai header berubah menjadi pil ringkas, lanjutkan cepat — pil
   tetap menempel tanpa membuat gulir tersendat.

### 9.56 Morph navbar penuh ⇄ pil ringkas

Dulu perpindahan navbar ke pil hanya terlihat sebagai potongan: kelas
`is-compact` memasang `display: none !important` pada isi lebar, dan yang bisa
dianimasikan CSS hanya `top`. Tidak ada yang bisa ditransisikan karena ukuran
header ditentukan isinya.

Sekarang berkas `berkah-experience.js` membungkus perubahan kelas dengan pola
FLIP sederhana (`morphHeader`): ukuran kotak diukur sebelum kelas berubah,
diukur lagi sesudahnya, lalu GSAP menganimasikan `width`/`height` selama
0,34 detik. Isi menyusul di lapisan gerak:

- `.bk-header--morphing .bk-header-inner` — `padding`, `border-radius`, dan
  `gap` ikut menyusut/menebar.
- `.bk-header--morphing.is-compact :is(.bk-expand, .bk-header-filter)` —
  logo pil (dan tombol filter katalog) tumbuh dari 80%.
- `.bk-header--morphing:not(.is-compact) :is(.bk-wordmark, .bk-desktop-nav,
  .bk-desktop-actions, .bk-command-trigger, .bk-menu-trigger)` — isi lebar
  turun 4 px ke tempatnya.

Pengaman:

1. Tanpa GSAP, saat `prefers-reduced-motion: reduce`, atau bila ukurannya tidak
   berubah, kelas dipasang langsung seperti sebelumnya — kelas `is-compact`
   tetap jalur yang sama, jadi selektor teruji tidak berubah.
2. `transition: width` dilepas dari `.bk-header` (kini hanya `top`); transisi
   CSS per frame justru membuat header tertinggal satu langkah.
3. Kunci tetap dipegang tiket (`morphTicket`) + jaring waktu 700 ms, sehingga
   tween yang terputus tidak meninggalkan ukuran inline atau kelas penanda.
4. Tidak ada `opacity: 0` baru: seluruh efek memakai transform/transisi,
   sehingga penjaga "konten tidak boleh tersembunyi tanpa JavaScript" tetap
   apa adanya.

Dijaga `ResponsiveLayoutTest::test_header_morphs_between_full_navbar_and_compact_pill`.

### 9.57 Zona mati header, morph saat gulir cepat, dan ikon menu

Empat keluhan setelah morph 9.56, satu akar per keluhan.

**a. Selama ±94 px gulir tidak ada header sama sekali.**
`syncHeader` memakai ambang tetap `window.scrollY > 180`, padahal tepi bawah
navbar penuh (`.bk-header-space` 96 px, header 70 px di `top:16px`) sudah lewat
dari atas layar pada ±86 px. Di antara 86 px dan 180 px navbar sudah pergi dan
pil belum lahir — persis jeda "navbar tidak terlihat" yang dilaporkan.

Ambang sekarang diturunkan dari kotak header itu sendiri:

- `headerExitPoint()` mengukur `rect.bottom + window.scrollY` **hanya saat
  header masih penuh**. Setelah ringkas/pinned header `fixed`, dan `rect`-nya
  tidak lagi mencerminkan posisi dokumen; nilai itu di-cache.
- `compactAt()` = titik keluar − 2 px (pil muncul begitu tepi bawah navbar
  lewat), `expandAt()` = titik keluar − 10 px (histeresis 8 px supaya pil tidak
  berkedip ketika gulir berhenti tepat di ambang). Saat resize cache dibuang.
- Handler scroll dijadwalkan satu kali per frame (`queueHeaderSync` +
  `requestAnimationFrame`), jadi bacaan `getBoundingClientRect` tidak memaksa
  tata letak ulang puluhan kali per frame.

**b. Gulir cepat bolak-balik terasa patah / menunggu.**
Dua sumber:

1. Lenis memakai `duration: 1.05` dengan easing eksponensial. Setiap putaran
   roda memulai tween baru yang panjang, sehingga halaman selalu tertinggal di
   belakang jari dan gulir cepat berulang terasa menunggu tween selesai.
   Sekarang `lerp: 0.1`: target dikejar proporsional — respons pertama langsung
   terasa, sisa jarak menyusul makin cepat, tanpa antrean tween.
2. Morph beruntun. Gulir cepat memicu `is-compact` ⇄ penuh dalam hitungan
   milidetik; tween yang saling menyusul membuat header tertinggal dan bisa
   menahan ukuran inline lama. `morphStamp` mencatat morph terakhir: bila morph
   berikutnya datang dalam 320 ms, tween dibatalkan, ukuran inline dibersihkan,
   dan kelas dipasang langsung. Selama `--morphing`, `backdrop-filter` dilepas
   (kaca 18 px harus digambar ulang setiap frame dan itu mahal di ponsel;
   latarnya sudah 94% pekat) dan `transition: top` dimatikan karena posisi
   dipegang tween.

FLIP kini juga menutup **posisi**, bukan hanya ukuran: `x/y` dari selisih
`getBoundingClientRect()` sebelum/sesudah kelas berubah, dengan
`xPercent: -50` untuk mengembalikan `translateX(-50%)` milik CSS yang ditimpa
GSAP. Tanpa itu header melawan 70 px saat berganti `absolute` ⇄ `fixed` tepat
di tepi atas layar. `clearProps` membersihkan `width,height,transform`.

**c. Ikon garis-3 tampak tidak simetris.**
Geometri Lucide `menu` simetris (`M4 5h16`, `M4 12h16`, `M4 19h16`). Pemindaian
piksel pil ringkas menunjukkan tiga garis berjarak sama (7 px pada 22 px) dan
berpusat vertikal, tetapi **berpusat 11 px di kanan pusat lingkaran** — ikon
menempel ke tepi kanan tombol. Penyebabnya tata letak wadah: kotak ikon adalah
grid kecil di dalam `<summary>`, dan ikon 22 px diskalakan 0,917 sehingga
goresan 2 px jatuh di piksel pecahan (garis tengah terbaca 1 px lebih panjang).

Perbaikan di `_radial_customer.blade.php`:

- `.customer-radial__trigger` menjadi kotak tombol itu sendiri: `position:
  absolute` dengan empat sisi `0`, `width/height: auto`, `display: flex` +
  `align-items/justify-content: center`. Tidak ada jalur tata letak yang bisa
  menggesernya lagi.
- `.customer-radial__face` memakai empat sisi eksplisit + flex center (bukan
  `inset: 0` + grid).
- Ikon digambar pada **24 px** (kelipatan bulat viewBox 24) sehingga garis
  2 px jatuh di piksel bulat: tiga garis sama panjang, sama jarak, sama tebal.
- Override `width: 44px/100%` pada trigger dihapus — ukuran itu membuat kotak
  ikon melewati padding `<summary>` dan menggeser ikon 1 px.

**d. Scrollbar mendatar di halaman pelanggan.**
Dekorasi (bunga besar, pita, strip) bisa lebih lebar dari kolomnya; tanpa batas
peramban menampilkan scrollbar mendatar dan seluruh halaman bisa digeser
sedikit ke kiri — pil header ikut bergeser. `body.bk-experience{overflow-x:
clip}` memotong luapan itu tanpa menjadikan `<body>` kotak gulir (berbeda dari
`hidden`), jadi `position: sticky` dan Lenis tetap bekerja.

Dijaga `ResponsiveLayoutTest::test_header_never_disappears_between_full_navbar_and_pill`,
`::test_radial_menu_icon_sits_in_the_middle_of_its_button`,
`::test_customer_pages_cannot_scroll_sideways`, dan
`MotionAssetsTest::test_gulir_roda_memakai_lerp_bukan_durasi_tetap`.

### 9.58 Busur keluar layar, kolom tanpa contoh, dan ritme tipografi

**a. Busur radial bisa keluar layar — dan itu yang membuat halaman bergeser.**
`applyArc()` menghitung bentuk busur sekali saat menu dibuka, dari posisi
tombol saat itu. Header bisa berganti bentuk tepat sesudahnya (pil ringkas ⇄
penuh/pinned, morph 9.56) sehingga tombol bergeser ke tepi kanan, tetapi
tujuan tetap memakai titik tumpu lama: lingkaran terakhir terpotong tepi
layar. Karena tujuan itu `position: absolute` di dalam header yang juga
`absolute` (belum ringkas), ia ikut melebarkan dokumen — seluruh halaman jadi
bisa digeser mendatar, pil header tampak tidak di tengah, dan tepi kiri kartu
terpotong.

Perbaikan di `berkah-radial.js`:

- `arcViewport()` menghitung area aman dari titik tumpu tombol (jarak ke tiap
  tepi pandangan dikurangi jari-jari lingkaran 23 px + 10 px napas).
- `clampPoints()` menjepit setiap titik ke area itu, dan **semua** jalur bentuk
  (busur U, cermin satu sisi, busur server) menulis lewat `commitArc()` yang
  sama — jadi tidak ada jalur yang bisa lolos keluar layar.
- `followPivotFor(550)` mengikuti titik tumpu selama ±0,55 dtk setelah menu
  dibuka: setiap kali `getBoundingClientRect()` tombol berubah, bentuk busur
  dihitung ulang. Berhenti sendiri, dan dihentikan di `close()`.

**b. Busur tidak ada di tablet 701–880 px.**
Tautan header disembunyikan mulai `max-width: 880px`, sedangkan tombol busur
baru muncul di `max-width: 700px`. Di pita tablet itu tidak ada menu sama
sekali. Ambang busur disamakan dengan ambang nav (880 px), sehingga
Pesanan/Bantuan tetap terjangkau di tablet; slot tema di busur hanya terisi di
ponsel (≤700 px), jadi di tablet busur berisi tujuan saja.

**c. Kolom isian tidak lagi kosong tanpa petunjuk.**
44 kolom teks di formulir pelanggan dan admin sebelumnya hanya kotak kosong:
pelanggan tidak tahu format alamat/ucapan, admin tidak tahu format tarif.
Semuanya kini membawa contoh nyata (`Contoh: Jl. Merdeka No. 12, RT 03,
Palembang`, `Contoh: 15000`, `Contoh: Ibu Sari Wulandari`, …). Kolom yang
memang tidak butuh contoh — hidden, submit, pilihan, berkas, tanggal, dan
kolom jumlah yang sudah bernilai — tidak disentuh. Salah ketik
"Universitas Sriwjaya" juga diperbaiki.

**d. Satu ritme tipografi untuk judul halaman, paragraf, dan kartu.**
Jarak di dalam kartu dulu mengikuti margin bawaan peramban (`p` 1em
atas-bawah): paragraf pertama menambah ±16 px di bawah judul, paragraf
terakhir menambah ±16 px di atas padding kartu. Ukuran `h1` halaman pun
berbeda-beda — 32 px bawaan di halaman unit, 52 px di dasbor.

- `.bk-experience :where(.wrap > h1)` — satu skala judul halaman
  `clamp(1.55rem, 1.15rem + 1.7vw, 2.2rem)`. `:where()` membuat specificity
  nol, jadi judul hero/dasbor yang punya aturan berkelas tetap menang.
- Paragraf di dalam `.card`, `.unit-section`, `.bk-row-card`, `.notice`,
  `.bk-profile-card`: `margin: 0 0 12px`, `line-height: 1.65`, dan margin
  bawah paragraf terakhir dinolkan. Kartu pesanan/alamat sengaja tidak ikut
  karena di sana jarak diatur `gap` flex.
- `overflow-wrap: break-word` pada teks kartu: kata panjang tanpa spasi
  (nomor referensi, alamat, nama berkas) tidak melebarkan kartu di ponsel.

Ditambah `body.bk-experience{overflow-x: clip}` (§9.57) sebagai jaring
pengaman: kalau ada elemen dekoratif yang tetap lebih lebar, halaman tidak
bisa digeser mendatar.

Dijaga `RadialMenuTest::test_busur_tidak_keluar_layar_dan_mengikuti_titik_tumpu`,
`FormPlaceholderTest` (dua tes), dan
`ResponsiveLayoutTest::test_ritme_tipografi_kartu_sama_di_semua_halaman`.

---

### 9.59 Luapan mendatar yang masih tersisa, dan cara membuktikannya

**Temuan dari laporan 2026-11-25 (halaman `sewa/{unit}/unit/{unit}`, 437–440 px).**

Dua lapis penyebab, keduanya ditutup di commit ini.

**a. `clip` pada `<body>` ikut diwariskan ke viewport, jadi `<body>` berhenti
menjadi kotak pemotong.** Menurut spesifikasi, bila `overflow` milik `<html>`
masih `visible`, nilai `overflow` milik `<body>` **dipindahkan ke viewport** dan
`<body>` sendiri memakai `visible`. Akibatnya `body.bk-experience{overflow-x:
clip}` (§9.57) tidak selalu memotong apa pun selain membuat viewport berhenti
menggulir — dan di sebagian mesin pemindahan itu tidak berlaku untuk `clip`,
sehingga scrollbar tetap muncul.

Perbaikan:

```css
@supports (overflow: clip) {
    html.bk-enhanced:has(> body.bk-experience) { overflow-x: clip; }
}
```

`clip` (bukan `hidden`) dipilih supaya `position: sticky` pada header dan
`position: fixed` pada busur radial serta navigasi bawah tetap bekerja: `clip`
tidak membuat kotak gulir dan tidak menjadi containing block untuk `fixed`.
`:has()` menjaga aturan ini hanya untuk halaman pelanggan; tabel lebar di admin
tetap bisa digulir di dalam pembungkusnya sendiri.

**b. Anak grid/flex memakai `min-width: auto` = min-content.** Satu baris
panjang (deretan pil "Lihat bagian", nomor referensi, sederet tombol) dapat
memaksa kolom grid melebar melewati layar — kolom tidak boleh menyusut di bawah
min-content anaknya. Ini yang membuat satu kartu terlihat lebih lebar dari
layar pada 437–440 px sementara 465 px tampak normal (selisihnya kecil).

Perbaikan:

```css
.bk-experience :is(.wrap, .items, .grid, .stack) > * { min-width: 0; }

.bk-experience .wrap { max-width: 100%; overflow-x: clip; }

.bk-experience .bk-jump { max-width: min(820px, 100%); }
.bk-experience .bk-unit-jump { max-width: 100%; }
.bk-experience .bk-jump > div {
    flex: 1 1 auto; min-width: 0; max-width: 100%;
    scrollbar-width: thin; -webkit-overflow-scrolling: touch;
}
```

- `min-width: 0` di anak grid/flex: kolom boleh menyusut; isi yang tetap
  panjang dipotong kartu (`overflow: hidden`) atau digulir scroller-nya.
- `.wrap{overflow-x: clip}`: jaring terakhir untuk isi halaman. Apa pun yang
  lebih lebar dipotong di tepi `.wrap`, bukan mendorong seluruh dokumen.
  Scroller di dalam tetap menggulir karena pemotongan hanya pada sumbu X kotak
  itu.
- `.bk-jump > div` dulu hanya `max-width: 100%`; `max-width` tidak selalu cukup
  pada flex item yang boleh wrap, jadi ditambah `flex: 1 1 auto` +
  `min-width: 0`. Deretan pil kini benar-benar scroller sendiri.

**c. Grid formulir admin rata dari label.** `.adm-field-grid` memakai
`align-items: end`, sehingga kolom yang punya teks bantuan ("Angka kecil tampil
lebih dulu.") naik lebih tinggi dan label sebaris tidak sejajar (laporan
`image-112`). Sekarang `align-items: start`: label rata atas, input tetap
sejajar karena tingginya seragam (44 px).

**d. Bukti build yang diuji belum memuat perbaikan sebelumnya.** Dua kolom pada
`image-113` ("Tarif antar (Rp)", "Tarif jemput kembali (Rp)") tampil kosong,
padahal di `HEAD` keduanya sudah membawa `placeholder="Contoh: 15000"` dan
`placeholder="Contoh: 20000"` (`rental/universities.blade.php`). Artinya
tangkapan layar dibuat dari kode/cache lama (view terkompilasi atau CSS di
cache peramban), bukan dari commit terbaru.

Urutan verifikasi yang benar:

```powershell
cd D:\download\berkah-preview
git pull
php artisan view:clear
php artisan config:clear
php artisan test
```

Lalu **hard reload** (Ctrl+Shift+R) agar CSS `?v=filemtime` baru terpakai.

**Cara menunjuk elemen penyebab bila luapan masih ada.** Tempel di Console saat
halaman terbuka pada lebar bermasalah:

```js
const vw = document.documentElement.clientWidth;
console.log('dokumen/ layar:', document.documentElement.scrollWidth, '/', vw,
            '| body overflow-x:', getComputedStyle(document.body).overflowX,
            '| html clip:', getComputedStyle(document.documentElement).overflowX);
[...document.querySelectorAll('body *')]
  .filter(el => el.getBoundingClientRect().right > vw + 1)
  .slice(0, 10)
  .forEach(el => console.log(el.tagName, el.className,
                             Math.round(el.getBoundingClientRect().right)));
```

`dokumen/layar` harus sama (mis. `440 / 440`) dan `html clip` harus `clip`.
Bila masih ada selisih, baris `TAG kelas kanan` menunjuk elemen tepatnya —
kirimkan 3–5 baris teratas.

**Hasil yang diharapkan di setiap ukuran.** Lebar `scrollWidth` = lebar layar,
tanpa scrollbar mendatar, judul tidak terpotong, kartu tidak melewati tepi:

| Lebar | Yang diperiksa |
| --- | --- |
| 320 px | header pil penuh, pil "Lihat bagian" menggulir sendiri, tombol 44 px |
| 360/390/414/430 px | tanpa geser mendatar; paragraf tidak menempel tepi |
| 437–480 px | kasus laporan; dokumen = layar |
| 600–700 px | dua kartu per baris di katalog, busur radial muncul |
| 701–880 px | nav tablet + busur radial (slot tema kosong di busur) |
| 881–1024 px | nav tablet tanpa busur, kolom formulir 3–4 sejajar rata atas |
| 1280 px | `.wrap` 1200 px di tengah, tidak ada elemen melewati tepi |

Dijaga `ResponsiveLayoutTest::test_elemen_lebar_tidak_bisa_mendorong_halaman_ke_samping`
dan `ResponsiveLayoutTest::test_kolom_formulir_admin_sejajar_dari_label`.

---

### 9.60 Sapuan semua peran: tamu, auth, pelanggan, admin

**Cakupan.** Seluruh 82 berkas blade diperiksa, bukan hanya halaman yang
dilaporkan. Peta layout dan pengaman yang berlaku sekarang:

| Kelompok halaman | Contoh route | Layout / `<head>` | Pengaman luapan |
| --- | --- | --- | --- |
| Tamu & pelanggan | `/`, `/katalog/{produk}`, `/keranjang`, `/checkout`, `/sewa…` | `catalog.layout` | `berkah-experience.css` §9.57–9.59 |
| Pelanggan (mandiri) | `/customer/addresses/create`, `/profile/edit` | `<head>` sendiri + header/nav pelanggan | `berkah-page-shell.css` (baru) |
| Auth & verifikasi | `/login`, `/register`, `/forgot-password`, `/confirm-password` | `<head>` sendiri atau `layouts/guest` | `berkah-page-shell.css` (baru) |
| Admin (ber-layout) | `/admin/pesanan`, `/admin/buku-kas`, `/admin/paket`, … | `layouts.admin` | `berkah-admin.css` (§9.60b) |
| Admin (mandiri) | `/dashboard`, `/admin/notifikasi`, `/admin/profil`, `/admin/audit` | `<head>` sendiri | `berkah-page-shell.css` (baru) |
| Lembar cetak | `/ringkasan-sewa/{order}`, `/admin/lembar-harian` | `<head>` sendiri | `berkah-page-shell.css`, khusus `@media screen` |
| Halaman galat | 403/404/413/419/500/503 | `errors.layout` | `berkah-page-shell.css` (baru) |

**a. Kolom tanpa contoh di admin dan pelanggan (16 kolom).**
Sapuan ulang menemukan isian yang masih benar-benar kosong saat halaman dibuka
— semuanya di alur kerja admin dan satu di halaman unit pelanggan:

- `rental/admin-detail`: rincian ongkos, alasan melanjutkan (dua tempat), alasan
  koreksi, nominal + alasan pengembalian dana, alasan penolakan bukti, alasan
  lanjut tanpa persetujuan, catatan admin pada pratinjau desain, alasan
  pembatalan.
- `rental/admin-progress`: catatan perkembangan.
- `rental/cashbook-fields`: kategori bebas, nominal, catatan.
- `catalog/admin`: tiga kolom "paket baru" (nama, durasi, harga).
- `rental/unit`: catatan revisi desain milik pelanggan.

Semua kini berisi contoh konkret, mis. `Contoh: 150000`,
`Contoh: Pelanggan belum membalas sejak kemarin, pengerjaan dimulai sesuai
tenggat.`, `Contoh: Tolong ganti pita jadi hitam dan tambahkan nama "Bapak
Ahmad".`

Contoh isian di halaman auth juga diperjelas (sebelumnya berupa perintah):
`Nama sesuai identitas` → `Contoh: Sari Wulandari`, `8xxxxxxxxxx` →
`Contoh: 81234567890`, `Boleh dikosongkan` →
`Contoh: sari@email.com (opsional)`, `Nama jalan, nomor rumah, RT/RW…` →
`Contoh: Jl. Merdeka No. 12, RT 03/RW 05, Kel. Talang`, `Warna pagar…` →
`Contoh: Pagar hijau, sebelah warung Bu Ani`, dan email login menjadi
`Contoh: nama@email.com atau 081234567890`.

**b. Halaman mandiri punya pengaman sendiri.**
Enam belas halaman punya `<head>` sendiri sehingga tidak ikut
`berkah-experience.css`/`berkah-admin.css`. Selama ini satu-satunya penahan
luapan adalah `overflow-x: hidden` pada `<body>` — yang justru (i)
menjadikan `<body>` kotak gulir sehingga acuan `position: sticky` (header
formulir) dan `fixed` (laci admin, navigasi bawah) bergeser, dan (ii) ikut
diwariskan ke viewport sehingga tidak memotong apa pun.

Berkas baru `public/css/berkah-page-shell.css` (dimuat lewat partial
`layouts/component/_page_shell.blade.php`) hanya berisi aturan keselamatan,
tanpa mengubah warna/radius/tipografi:

```css
@supports (overflow: clip) {
    @media screen { html { overflow-x: clip; } }
}
html, body { max-width: 100%; }
body { overflow-wrap: break-word; }
img, video, canvas, iframe { max-width: 100%; }
:where(main, .page-body, .page-main, .page-shell, .wrap, .container,
       form, .card, .grid, .row, .adm-field-grid) > * { min-width: 0; }
:where(.table-wrap, .table-scroll, .adm-scroll-x) { overflow-x: auto; }
```

Sepuluh halaman yang tadinya `overflow-x: hidden` kini memakai `clip`
(login, register, dasbor admin, notifikasi, profil, ubah profil, audit, alamat
buat/ubah, ubah profil pelanggan). `@media screen` menjaga lembar cetak
(ringkasan pesanan, lembar harian) tidak terpotong saat dicetak.

**c. Area admin ber-layout juga punya pengaman.** `layouts/admin` tidak memuat
`berkah-experience.css`, jadi `berkah-admin.css` mendapat:

```css
@supports (overflow: clip) {
    @media screen { html:has(> body.adm-app) { overflow-x: clip; } }
}
.adm-app :where(.adm-content, .adm-toolbar, .adm-field-grid, .adm-card, .adm-stack) > * { min-width: 0; }
```

**d. Tes penjaga.** `FormPlaceholderTest` kini memindai **seluruh** view (82
berkas), bukan 14 halaman pilihan: setiap `input`/`textarea` yang tampil
kosong wajib punya contoh, sedangkan kolom `readonly`, bertipe non-teks, dan
yang sudah berisi nilai dikecualikan. Tes memotong dulu ekspresi `{{ … }}`
karena operator `->` membuat regex tag sederhana terpotong di tengah
(inilah sebabnya versi lama lolos padahal kolomnya kosong).
`StandalonePageShellTest` (4 tes) menjaga: setiap halaman yang punya
`<!DOCTYPE` memuat `_page_shell`, tidak ada lagi `overflow-x: hidden`, berkas
pengaman memuat aturan kuncinya, dan admin ber-layout punya pengaman sendiri.

**e. Temuan yang dilaporkan, belum diubah (butuh keputusan).**
Lima halaman admin — dasbor, notifikasi, profil, ubah profil, audit — memakai
`<head>` dan header sendiri (gaya "hero" dengan tombol kembali), sedangkan
halaman admin lain memakai `layouts.admin` dengan sidebar + topbar. Keduanya
sudah punya jalan kembali dan sudah dijaga tes; yang berbeda hanya chrome-nya.
Menyatukannya ke `layouts.admin` berarti menulis ulang ±1.800 baris CSS/JS
per halaman dan tidak bisa diverifikasi tanpa peramban, jadi menunggu
persetujuan arah desain.
`rental/daily-sheet` sengaja tidak memuat `_theme_head` karena hanya dipakai
untuk dicetak.

**f. Periksa manual per peran** (lebar 320 → 1280, satu halaman per kelompok):

1. Tamu: `/`, `/katalog/{produk}`, `/keranjang`, `/login`, `/register`,
   `/forgot-password`, 404.
2. Pelanggan: `/customer/dashboard`, `/sewa`, `/sewa/{order}`,
   `/checkout`, `/customer/addresses/create`, `/customer/profile`,
   `/profile/edit`, `/customer/notifications`, `/ringkasan-sewa/{order}`.
3. Admin: `/dashboard`, `/admin/pesanan`, `/admin/pesanan/{unit}`,
   `/admin/jadwal`, `/admin/buku-kas`, `/admin/paket`, `/admin/universitas`,
   `/admin/voucher`, `/admin/pengaturan-pembayaran`, `/admin/audit`,
   `/admin/profil`, `/admin/notifikasi`, `/admin/lembar-harian`.
4. Untuk tiap halaman: `scrollWidth == clientWidth` (skrip di §9.59d),
   judul tidak terpotong, tombol ≥44 px, dan setiap kolom isi memperlihatkan
   contoh saat kosong.

---

### 9.61 Sapuan detail kedua: matriks periksa dan temuan

Sapuan pertama (§9.60) menyelesaikan luapan mendatar, contoh isian, dan
pengaman halaman mandiri. Sapuan kedua memeriksa **detail kecil yang tidak
terlihat dari screenshot** — semuanya statis dan bisa diuji ulang tanpa
peramban.

| # | Yang diperiksa | Cara | Hasil |
| --- | --- | --- | --- |
| 1 | `label for` menunjuk kolom yang ada | pindai semua `<label for>` vs `id`/`name` per view | 0 temuan |
| 2 | `id` duplikat dalam satu view | hitung `id` per view | 1 temuan semu (`auth-title` di tiga cabang `@if` yang saling eksklusif — aman) |
| 3 | `<img>` tanpa `alt` | pindai seluruh view | 0 temuan |
| 4 | `<button>` di dalam `<form>` tanpa `type` | pindai seluruh view | 0 temuan |
| 5 | `target="_blank"` tanpa `rel="noopener"` | pindai seluruh view | 0 temuan |
| 6 | Tautan mati `href="#"` | pindai seluruh view | 0 temuan |
| 7 | Form POST tanpa `@csrf` | pindai `<form>` + isinya | 0 temuan |
| 8 | Form PUT/PATCH/DELETE tanpa `@method` | idem | 0 temuan |
| 9 | Form berisi `<input type="file">` tanpa `enctype` | idem | 0 temuan |
| 10 | `name` ikon dipakai vs tersedia | bandingkan dengan `$aliases` + `_icon_glyphs.php` (75 glyph) | 55 nama dipakai, 0 tidak dikenal |
| 11 | Aset `asset('css|js|images|vendor/…')` benar-benar ada | cek `public/` | 0 hilang |
| 12 | Berkas CSS yang tidak pernah dimuat | cek rujukan di seluruh view | 0 (12/12 dimuat) |
| 13 | `data-*` hook punya penangan skrip | cari literal, camelCase `dataset`, dan skrip inline | 0 hook menganggur |
| 14 | `viewport` + `lang="id"` + `<title>` | semua view ber-`<!DOCTYPE>` | 0 temuan |
| 15 | Jumlah `<h1>` per halaman | semua view ber-`<!DOCTYPE>` | tepat satu (kecuali layout, yang `<h1>`-nya dari konten) |
| 16 | `prefers-reduced-motion` pada halaman beranimasi | hitung `@keyframes`/`transition` vs blok media | 2 temuan, diperbaiki |
| 17 | `inputmode` untuk kolom uang/telepon | nama kolom vs atribut | 0 temuan |
| 18 | Tombol ikon tanpa nama aksesibel | teks/`aria-label`/`title` | 0 temuan |
| 19 | Tautan lompat ke isi + target fokus | semua view ber-`<!DOCTYPE>` | 13 temuan, diperbaiki |
| 20 | Cincin fokus seragam | aturan `:focus-visible` per halaman | 2 halaman tanpa aturan → ditambah di pengaman |

**a. Satu bentuk untuk semua contoh isian.** Placeholder kini wajib berbentuk
`Contoh: …` (isian) atau `Cari …` (kotak pencarian). Delapan placeholder lama
dirapikan:

- `auth/forgot-password`: `nama@contoh.com` → `Contoh: nama@email.com`.
- `catalog/index`: `Nama papan bunga` → `Cari nama papan bunga`.
- `rental/payment-settings`: `6281234567890` → `Contoh: 6281234567890`.
- `roles/admin/audit`: `contoh: voucher, pesanan.` → `Contoh: voucher, pesanan.`
- `roles/customer/addresses/create`: `08xxxxxxxxxx` →
  `Contoh: 081234567890`; `Warna pagar, patokan, titipkan di mana, dll.` →
  `Contoh: Pagar hijau, titip di pos keamanan.`
- `roles/customer/addresses/edit`: `Warna pagar, patokan, dll.` → idem.

`FormPlaceholderTest::test_contoh_isian_memakai_kata_contoh` sekarang menolak
placeholder yang bukan `Contoh`/`Cari`, jadi bentuk lama tidak bisa kembali.

**b. Halaman mandiri bisa dilewati ke isi.** Hanya halaman ber-layout yang
punya tautan "Langsung ke isi". Tiga belas halaman mandiri memaksa pengguna
papan ketik menekan Tab melewati header lebih dulu. Ditambahkan:

- Partial baru `layouts/component/_skip_link.blade.php` — tautan `#main-content`
  (atau `#page` untuk login/register) dengan gaya sendiri, muncul saat fokus,
  disembunyikan saat dicetak, dan menghormati `prefers-reduced-motion`.
- Setiap halaman mandiri kini punya target `id="main-content"`/`id="page"` +
  `tabindex="-1"` sehingga fokus benar-benar berpindah. Tiga halaman
  (`auth/register`, `roles/admin/profile`, `roles/admin/profile_edit`) memakai
  `<div role="main">` — sengaja tidak diubah menjadi `<main>` agar pohon
  flex/grid halaman tidak berubah; `role="main"` memberi landmark yang sama.

**c. Cincin fokus seragam.** `berkah-page-shell.css` menambah
`:where(a, button, input, select, textarea, summary, [tabindex]):focus-visible
{ outline: 3px solid #bc5b75; outline-offset: 2px; }`. Specificity nol, jadi
halaman yang punya gaya fokus sendiri tetap menang.

**d. Kurangi gerak di halaman alamat.** `roles/customer/addresses/create` dan
`edit` beranimasi (5 transisi) tanpa blok `prefers-reduced-motion`. Ditambah
blok yang menetapkan `transition-duration`/`animation-duration` ke `.01ms`.

**e. Desktop tidak lagi berupa kolom ponsel.** Empat halaman admin mandiri
memakai `max-width: 520px` di semua ukuran, sementara
`layouts.component.admin._navbar_admin` sudah berubah menjadi baris menu di
≥900 px — hasilnya kolom sempit di tengah layar lebar. Ditambah:

```css
@media (min-width: 900px) { .appbar, .wrap { max-width: 960px; } }
```

(`roles/admin/profile` memakai `.profile-header, .page-body`; dasbor sudah
melebar ke 1120 px sejak awal.)

**f. Catatan perbaikan.** Saat menyisipkan tautan lompat ke `auth/login`, tag
`<body … @if($errors->any() …)>` terpotong karena operator `->` mengandung
`>` (jebakan yang sama seperti §9.60d). Sudah diperbaiki dan dijaga
`UiDefaultsTest::test_login_has_a_branded_splash_with_an_escape_hatch`, yang
memastikan `$errors->any()` tetap ada di tag `<body>`.

**Hasil harness:** 118 lulus, 0 gagal, 216 dilewati; `blade-scan` 83 view,
0 direktif menempel, 0 blok tidak seimbang; `.tmp-verify.php` 26 lulus.

#### 9.61 lanjutan — tema gelap, paginasi, dan status migrasi

**g. Tema gelap di halaman pelanggan mandiri.**
`roles/customer/addresses/create`, `addresses/edit`, dan `profile/edit` memakai
`<style>` sendiri dengan variabel lokal (`--surface`, `--card`, `--ink`,
`--border`) dan latar `white` lepas. Saat pengguna memilih tema gelap di header,
halaman ini tetap menyala terang. Ditambahkan pemetaan per halaman (bukan di
berkas tema, karena nama variabel yang sama dipakai palet terang halaman lain):

```css
html[data-theme="dark"] {
    --surface: #140f12; --card: #1d1619; --ink: #f6eef0;
    --ink-mid: #cbb9bf; --ink-lt: #ab989f; --border: #3a2b31;
}
html[data-theme="dark"] .section-card,
html[data-theme="dark"] .field-input,
html[data-theme="dark"] .zone-pill { background: var(--card); color: var(--ink); border-color: var(--border); }
```

Tombol geser (`.toggle-switch::after`) sengaja tetap putih karena ia "knob" di
atas lintasan berwarna merek.

**h. Halaman admin mandiri dipatok terang (sementara).**
Kelimanya memakai palet terang `_tokens` + kartu putih, sedangkan tema global
tetap mengubah `body` dan judul (`html[data-theme="dark"] :is(h1…)`) — akibatnya
judul menjadi terang di atas kartu putih. Karena migrasi ke `layouts.admin`
belum dikerjakan, halaman diberi `class="adm-standalone"` dan
`--theme-bg`/`--theme-ink` dipatok ke nilai terang **hanya** di halaman itu:

```css
html[data-theme="dark"] body.adm-standalone {
    --theme-bg: var(--surface-50);
    --theme-ink: var(--brand-900);
}
```

Setelah migrasi ke `.adm-app` (yang sudah punya aturan gelap di
`berkah-admin.css`), pinning ini dihapus. Dijaga
`StandalonePageShellTest::test_halaman_admin_mandiri_dipatok_terang`.

**i. Paginasi turun baris di layar sempit.** `.bk-pagination` memakai
`width:max-content; min-width:100%` tanpa `flex-wrap`. Dengan sasaran sentuh
44 px, tujuh tombol sudah melewati 320 px sehingga nomor terakhir hanya bisa
dicapai dengan menggulir kotak paginasi. Sekarang `.bk-pagination` dan
`.bk-pagination__pages` memakai `flex-wrap: wrap`, `width: auto`,
`max-width: 100%`, dan `justify-content: center`. Dijaga
`ResponsiveLayoutTest::test_navigasi_halaman_turun_baris_di_layar_sempit`.

**Hasil harness akhir:** **121 lulus, 0 gagal, 216 dilewati**; `blade-scan`
83 view 0/0; `.tmp-verify.php` 26 lulus; `node --check` seluruh `public/js`
bersih.

### 9.62 Migrasi lima halaman admin ke shell `.adm-app`, dan empat cacat yang tersisa

**a. Lima halaman admin mandiri pindah ke `layouts.admin` (`.adm-app`).**
`roles/admin/{dashboard,notifications,profile,profile_edit,audit/index}` dulu
membawa `<head>`, `<style>` sendiri, `_navbar_admin`, dan pinning tema terang.
Sekarang kelimanya `@extends('layouts.admin')` dengan pola yang sama seperti
halaman admin lain (`rental/addons`, `rental/orders`, …):

* `@section('back', route('dashboard.admin'))`, `@section('title', …)`,
  `@section('crumbs')<strong>…</strong>@endsection`, `@section('content')`.
* Kepala halaman `.adm-page-head` + `.adm-kicker` + `.adm-page-sub` + `.adm-actions`
  (ritme tipografi sama dengan halaman admin lain: satu `<h1>` per halaman).
* Kartu `.adm-card` + `.adm-card__head` + `<h2>` ber-id, chip `.adm-chip`,
  tombol `.adm-btn` / `.adm-btn--ghost` / `.adm-btn--sm`, catatan `.adm-note`
  (`--ok`/`--warn`), keadaan kosong `.adm-empty`, lencana `.adm-badge`.
* Dasbor: `.adm-stats` (angka tetap beranimasi lewat `data-adm-count` yang
  sudah ada di `berkah-admin.js`), baris tautan baru `.adm-listlink`.
* Jejak audit: chipbar filter `.adm-chipbar`/`.adm-chip--link`, formulir
  `.adm-toolbar` + `.adm-field-grid` (label "Dari tanggal"/"Sampai tanggal"
  kini satu baris grid, tidak lagi dua label bertumpuk di dalam satu kolom),
  diff sebelum→sesudah tetap ditampilkan sebagai chip.
* Profil admin: kartu identitas, `.adm-stats`, pintasan `.adm-grid--auto`,
  keluar lewat `showConfirmModal` dari `_ui_kit` (sebelumnya `onclick`
  sebaris). Ubah profil: pratinjau avatar dipindah dari `onchange="previewImage(this)"`
  ke pendengar di `@push('scripts')` — tanpa fungsi global.

Akibat migrasi, pinning `html[data-theme="dark"] body.adm-standalone` **dicabut**
(tidak ada lagi view yang memuatnya) sehingga tema gelap admin benar-benar
berlaku di lima halaman itu. Dijaga
`StandalonePageShellTest::test_halaman_admin_tidak_lagi_memandiri_dari_shell`
dan `test_tidak_ada_view_yang_mematok_tema_terang`.

**b. Dua view mati dicabut.** Setelah lima halaman pindah,
`layouts/component/admin/_navbar_admin.blade.php` (bilah bawah + lembar
"Lainnya") dan `components/back-button.blade.php` tidak lagi dirujuk di mana
pun. Navigasi ponsel kini memakai drawer `_sidebar` (`data-adm-nav-toggle` di
topbar, `#adm-sidebar`, `data-adm-close`) yang sudah ada di shell. Keduanya
dihapus bersama 93 baris pengendali `admin-sheet` di `berkah-admin.js`;
penjaganya dipindah ke `UiDefaultsTest::test_admin_mobile_drawer_has_a_controller`.
Ambang `UiKitTest` (halaman bertema) 25 → 20 karena lima halaman itu tidak lagi
memuat `_theme_head` sendiri, dan ambang `StandalonePageShellTest` 16 → 11.

**c. `placeholder` ganda di profil pelanggan.** Dua isian memasang
`placeholder` dua kali:

```html
<input placeholder="Contoh: nama@email.com" … placeholder="Boleh dikosongkan">
<input placeholder="Contoh: 0812 3456 7890" … placeholder="08xxxxxxxxxx">
```

Peramban memakai yang terakhir, jadi contoh isian yang benar hilang — dan tes
placeholder lama tetap hijau karena hanya membaca atribut pertama. Keduanya
dibetulkan, `autocomplete="name"` ditambahkan pada isian nama. Dijaga
`FormPlaceholderTest::test_tidak_ada_atribut_placeholder_ganda` (memindai
seluruh tag `input`/`textarea`).

**d. Pesan galat yang bisu.** `.field-error` dan `.bk-field-error` dirender
server setelah validasi gagal, tetapi tidak satu pun punya `role="alert"` atau
`aria-live` di profil/alamat pelanggan, katalog, maupun halaman unit sewa.
Pengguna pembaca layar kembali ke atas formulir tanpa tahu kolom mana yang
salah. 23 wadah di 6 view diberi `role="alert"`; `aria-invalid` +
`aria-describedby` yang sudah ada tetap dipertahankan. Dijaga
`UiAccessibilityTest::test_pesan_galat_isian_selalu_diumumkan` (pemindai
menetralkan `->` Blade lebih dulu agar tag tidak terpotong di tengah ekspresi).

**e. Target sentuh admin di bawah 44 px.** `.adm-btn` sudah 44 px, tetapi
`.adm-btn--sm` (38 px), `.adm-btn--quiet` (40 px), `.adm-chip--link` (40 px),
dan `.adm-iconbtn` (40 px) belum. Blok baru di `berkah-admin.css`:

```css
@media (pointer: coarse), (max-width: 700px) {
    .adm-btn--sm, .adm-btn--quiet, .adm-chip--link, .adm-nav__link, .adm-tab { min-height: 44px; }
    .adm-iconbtn { width: 44px; height: 44px; }
}
```

Dijaga `ResponsiveLayoutTest::test_target_sentuh_admin_minimal_44_px`.

**f. Halaman galat di 320 px.** Baris merek di bawah kartu (`BERKAH KREATIF •
SIMALUN`) memakai `letter-spacing: 2.4px` tanpa `flex-wrap`; di 320 px ruang
kartu hanya ~236 px sehingga baris itu mendorong keluar. Sekarang
`flex-wrap: wrap`, dan di bawah 360 px `letter-spacing` dirapatkan ke 1.6 px
(chip kode ikut 1 px). Dijaga
`ResponsiveLayoutTest::test_halaman_galat_utuh_di_320_px`.

**g. Komponen baru.** `.adm-listlink` (baris tautan daftar dasbor: 44 px,
grid `auto minmax(0,1fr) auto`, menyusut di 420 px) dan `.adm-dot` (titik
status sebaris, menggantikan `.adm-iconbtn__dot` yang berposisi absolut dan
salah tempat bila dipakai di dalam tombol/lencana).

**h. Tombol ikon header katalog 42 px.** `.bk-desktop-action` (keranjang,
notifikasi, pesanan) berukuran 42×42, dan di rentang 701–880 px menyusut lagi
menjadi 38×42 — di tablet berujung sentuh sasaran itu terlalu kecil. Kini
44×44 di semua rentang, dan tautan `.bk-desktop-nav` ikut 44 px agar sederajat.
Dijaga `ResponsiveLayoutTest::test_tombol_ikon_header_katalog_minimal_44_px`.

**i. Tautan "Langsung ke isi" hanya di satu cabang peran katalog.**
`catalog.layout` memilih header menurut peran: cabang pelanggan/tamu memuat
`catalog.customer-navigation` yang berisi `.bk-skip`, sedangkan cabang lain
(admin membuka katalog publik) memakai header `.top` **tanpa** tautan lompat —
pengguna papan ketik harus menekan Tab melewati seluruh header di 12 halaman
yang mewarisi layout itu. Tautan yang sama kini dipasang juga di cabang
`@else`. Dijaga
`StandalonePageShellTest::test_katalog_publik_punya_tautan_lompat_di_semua_cabang`.

**Hasil harness akhir:** **129 lulus, 0 gagal, 216 dilewati**; `blade-scan`
81 view 0/0 (dua view mati dihapus); `.tmp-verify.php` 26 lulus; `node --check` seluruh `public/js`
bersih.

### 9.63 Putaran ketiga: yang hanya terlihat dari token, atribut, dan kontras

**a. Kontrol form bersama "meminjam" palet katalog di panel admin.**
`_form_controls` mengganti setiap `<select>`, `<input type="date">`, dan
`<input type="month">` menjadi `.fc-trigger`, dan komponen itu di-`@include`
juga oleh `layouts.admin`. Akibatnya di semua halaman admin tombol kontrol
tetap berukuran **48 px**, radius `--fc-radius` (14 px), latar putih, dan
bayangan katalog, berdiri di samping label `.adm-field` 12 px serta input
admin 44 px — satu baris formulir tampak meminjam dua sistem. Popover, bottom
sheet, dan kalender juga masih memakai `--fc-cream: #fffaf8` dan
`--fc-muted: #7a626a`. Dua perbaikan:

* `.adm-app` memetakan seluruh token `--fc-*` ke `--adm-*` (warna, garis,
  bayangan, fokus, radius 12 px), sehingga lapisan yang ditempel ke `<body>`
  ikut admin — termasuk di tema gelap, yang sebelumnya mempertemukan dua
  palet gelap berbeda di satu layar.
* `.adm-app .fc-trigger` diselaraskan: 44 px, radius 12 px, latar
  `--adm-surface-2`, bobot 500, cincin fokus `--adm-rose-soft`.

Dijaga `ResponsiveLayoutTest::test_kontrol_form_bersama_mengikuti_palet_admin`
(yang juga menolak warna katalog tertinggal di aturan trigger admin).

**b. `var(--adm-line-2)` — token yang tidak pernah ada.** Blok `.adm-listlink`
memakai `var(--adm-line-2)`; token itu tidak dideklarasikan sehingga
`border-color` jatuh ke `currentColor` dan garis hover berubah warna mengikuti
teks. Diganti `--adm-line-strong`. Dijaga
`UiKitTest::test_token_admin_yang_dipakai_selalu_terdefinisi` — memindai
seluruh `var(--adm-*)` dan hanya membolehkan yang punya nilai cadangan (dipakai
`--adm-i` dan `--adm-toast-life` yang disetel JS).

**c. Kontras `--adm-ink-3` gagal AA.** `#93787f` hanya **3,6–3,9:1** di atas
permukaan admin, padahal token itu dipakai `.adm-help`, `.adm-stat__hint`,
`.adm-timeline__time`, `.adm-notif__time`, dan placeholder kontrol bersama —
semuanya teks 11–12 px. Digelapkan ke `#80626a` (4,9:1 di `--adm-surface-3`,
5,4:1 di `--adm-surface-2`). Dijaga
`UiKitTest::test_kontras_teks_admin_minimal_aa`, yang menghitung rasio WCAG
dari CSS itu sendiri (bukan dari nilai yang disalin ke tes) untuk 13 pasangan
token di tema terang **dan** gelap. Palet pelanggan diperiksa dengan cara sama
dan semua ≥ 4,6:1, jadi tidak diubah.

**d. `inputmode` untuk isian angka dan telepon.** 28 isian
`type="number"`/`type="tel"` di 11 view tidak punya `inputmode`, sehingga di
ponsel papan ketik penuh (dengan `e`, `+`, `-`) muncul untuk nominal rupiah.
Ditambahkan `inputmode="numeric"` untuk nominal dan `inputmode="tel"` untuk
nomor telepon — nama field, validasi, dan `min`/`max` tidak berubah. Dijaga
`UiAccessibilityTest::test_isian_angka_dan_telepon_punya_inputmode`.

**e. Pesan galat halaman lupa sandi bisu.** Tiga `<p class="auth-error">` di
`auth/forgot-password` tidak punya `role="alert"` (ringkasannya punya, pesan
per-kolom tidak). Ketiganya diberi `role="alert"`, dan penjaga
`UiAccessibilityTest::test_pesan_galat_isian_selalu_diumumkan` sekarang juga
mencakup kelas `auth-error` di samping `field-error`/`bk-field-error`.

**f. Skrip audit yang dipakai.** Selain tes, putaran ini memakai pemindai
sementara untuk: `data-*` tanpa pembaca di JS mana pun (ditemukan penanganan
`dataset.camelCase` yang benar, sisa `data-bk-like-form` dan `data-fc-select`
yang aman), kelas CSS yang dipakai tanpa definisi, `var()` tanpa definisi,
`aria-describedby`/`labelledby`/`controls` tanpa target, `placeholder` ganda,
`<img>` tanpa `alt`, `target="_blank"` tanpa `rel`, serta `<button>` tanpa
`type`. Semua temuan yang berdampak sudah ditutup di atas; sisanya terbukti
positif palsu (ekspresi Blade `->` memotong regex) atau aman.

**Hasil:** harness **133 lulus, 0 gagal, 216 dilewati**; `blade-scan` 81 view
0/0; `node --check` bersih; `.tmp-verify.php` 26 lulus.

### 9.64 Putaran keempat: sisa Breeze di komponen, label, dan $type

**a. Komponen Breeze masih membawa kelas Tailwind yang CSS-nya tidak ada.**
`x-text-input` (`border-gray-300 focus:border-indigo-500 focus:ring-indigo-500
rounded-md shadow-sm`), `x-input-label` (`block font-medium text-sm
text-gray-700`), `x-primary-button` (`bg-gray-800`, `text-gray-800`), dan
`x-input-error` (`text-red-600 space-y-1`) adalah sisa dari build Tailwind yang
sudah dicabut. Kalau CSS-nya tidak ada, atribut `class` itu hanya keluaran mati
yang membingungkan: pembaca mengira kontrol masih ditata oleh kelas, padahal
`layouts.guest` menatanya lewat selektor elemen (`.guest-card input`,
`.guest-card label`, `.guest-card button[type="submit"]`, `.guest-card ul`).
Keempat komponen kini mengeluarkan elemen bersih; `x-primary-button` tetap
memaksa `type="submit"`. `x-input-error` mempertahankan `role="alert"` supaya
galat konfirmasi sandi dan verifikasi email tetap diumumkan.

**b. Label isian yang Judul Kapital di tengah formulir huruf kecil.**
Konvensi yang dipakai login dan daftar: label isian **huruf kecil** ("Kata
sandi", "Email"), sedangkan judul kartu dan tombol utama Judul Kapital ("Masuk
Sekarang", "Ganti Kata Sandi"). Dua halaman melanggar:

* `auth/confirm-password` — label `Kata Sandi` → `Kata sandi`.
* `roles/customer/profile/edit` — label `Kata Sandi Saat Ini`, `Kata Sandi
  Baru`, `Konfirmasi Kata Sandi Baru` → huruf kecil; judul kartu "Ganti Kata
  Sandi" tetap.
* `auth/verify-email` — h1 `Verifikasi email Anda` → `Verifikasi Email Anda`
  agar sejalan dengan judul halamannya (`Verifikasi Email`).

Dijaga `UiDefaultsTest::test_label_isian_memakai_huruf_kecil_sesuai_kalimat`.

**c. Pemeriksaan tambahan yang bersih tanpa perubahan.** Seluruh `<img>` punya
`alt`; tidak ada `target="_blank"` tanpa `rel="noopener"`; tidak ada `<form>`
POST tanpa `@csrf`, unggahan tanpa `enctype`, atau metode palsu tanpa
`@method`; tidak ada `<h1>` ganda maupun halaman tanpa `<h1>`; rujukan
`aria-describedby`/`labelledby`/`controls` semuanya punya target; 15 halaman
admin memakai `.adm-page-head` + tepat satu `<h1>`; 12 halaman pelanggan juga
satu `<h1>`; tabel email satu-satunya `<table>` tanpa pembungkus gulir dan itu
memang benar (HTML surel).

**Hasil:** harness **134 lulus, 0 gagal, 216 dilewati**.

### 9.65 Putaran kelima: paginasi, kolom grid, dan jam yang tidak berlabel

**a. Paginasi: dua rupa, satu di antaranya tanpa CSS sama sekali.**
Lima halaman memanggil `->links()` **tanpa argumen** (`catalog/index`,
`catalog/admin`, `rental/admin`, `rental/cashbook`). Tidak ada
`Paginator::defaultView` di provider mana pun, jadi Laravel memakai tampilan
bawaan berbasis kelas Tailwind — CSS-nya tidak pernah dibangun di proyek ini.
Nomor halaman di empat halaman itu tampil sebagai teks polos tanpa kotak,
tanpa sasaran sentuh 44 px, dengan label berbahasa Inggris. Sementara itu
halaman pelanggan memakai `.bk-pagination` dan dua halaman admin memakai
`vendor.pagination.token-simple` dengan gaya sebaris berisi token
`--brand-*`/`--surface-*` **yang hanya didefinisikan di halaman mandiri** —
di panel admin token itu tidak ada, sehingga tautan halaman kehilangan warna
dan latar dan tingginya cuma ~32 px.

Perbaikan:

* `AppServiceProvider::boot()` menyetel
  `Paginator::defaultView('vendor.pagination.token-simple')` dan
  `defaultSimpleView` yang sama, jadi setiap `->links()` di aplikasi ini
  memakai satu tampilan.
* `token-simple` ditulis ulang: tanpa `style="…"` sama sekali, memakai kelas
  `.bk-page-nav`, `.bk-page-nav__link`, `__current`, `__edge`, `__gap`, dengan
  `aria-current="page"`, `rel="prev"`/`rel="next"`, dan teks "Sebelumnya"/
  "Berikutnya" yang dipertahankan.
* Gaya di `berkah-ui-kit.css` (dimuat semua peran): butir `min-height`
  `min-width` **44 px**, radius `--bk-r-control`, fokus
  `--bk-focus-ring`, token paginasi baru (`--bk-page-nav-*`) dengan pemetaan
  gelap di `berkah-theme.css`.
* Varian panel admin di `berkah-admin.css` (`.adm-app .bk-page-nav*`) memakai
  `--adm-*`; halaman aktif = putih di atas `--adm-solid`.
* `vendor/pagination/berkah.blade.php` kini alias satu baris ke tampilan yang
  sama; aturan `.bk-pagination` (terang di `berkah-experience.css`, gelap di
  `berkah-theme.css`) dicabut seluruhnya sehingga tidak ada dua paginasi.

Dijaga `ResponsiveLayoutTest::test_paginasi_bersama_punya_sasaran_sentuh_44_px`
dan `test_paginasi_bawaan_memakai_tampilan_bertema`, serta
`test_navigasi_halaman_turun_baris_di_layar_sempit` yang kini memeriksa
`.bk-page-nav`.

**b. `grid-column: span 2` di grid satu kolom.** `.adm-field-grid` memakai
`repeat(auto-fit, minmax(min(100%, 170px), 1fr))`; di 320 px hanya ada **satu**
kolom. Lima isian pencarian (pesanan, universitas ×2, item tambahan ×2)
menulis `style="grid-column: span 2"`, sehingga grid membuat kolom implisit
kedua yang selebar isinya — halaman melebar dan kolom terpotong. Semuanya
diganti kelas `.adm-field--wide` yang jatuh ke `grid-column: 1 / -1` di
≤700 px dan tetap `span 2` di layar lebar. Dijaga
`ResponsiveLayoutTest::test_isian_lebar_tidak_memaksa_kolom_implisit`.

**c. Jejak audit satu-satunya jam tanpa "WIB".** Semua halaman lain menulis
`d/m/Y H:i` + "WIB" (pesanan, jadwal, pembatalan, penawaran ongkos, waktu
bayar). Jejak audit mencetak `d/m/Y H:i` tanpa label zona dan tanpa konversi
eksplisit. Sekarang memakai `timezone('Asia/Jakarta')` + "WIB", dengan
`datetime` ISO tetap dipertahankan untuk mesin dan pembaca layar. Dijaga
`UiDefaultsTest::test_semua_waktu_tampil_menyebut_wib`.

**Hasil:** harness **138 lulus, 0 gagal, 216 dilewati**; `blade-scan` 81 view
0/0; kurung CSS seimbang; `node --check` bersih.

### 9.66 Putaran keenam: pesan galat yang bisu dan paginasi ketiga

**a. Pesan galat yang tidak diumumkan.** Tes
`test_pesan_galat_isian_selalu_diumumkan` hanya mengenal kelas
`field-error`, `bk-field-error`, dan `auth-error`. Sapuan kelas di seluruh
view menemukan empat wadah galat lain yang lolos dari jaring itu:
`alert-error` (pendaftaran + dua halaman alamat pelanggan),
`alert alert-error` (profil pelanggan), `adm-err` (12 kali di panel admin:
profil, item tambahan, voucher), `form-error show` (tujuh galat server di
pendaftaran), `error-msg` (login — sudah benar), dan `bk-unit-errors`
(ringkasan galat per unit di checkout). Pola tes diperluas ke semua kelas itu,
lalu:

* `auth/register` — alert sesi diberi `role="alert"`, dan tujuh
  `form-error show` yang dirender server ikut diumumkan (wadah JS di
  sebelahnya sudah `aria-live="polite"`).
* `roles/customer/addresses/{create,edit}` dan
  `roles/customer/profile/edit` — `role="alert"`.
* 10 `adm-err` di `roles/admin/profile_edit`, `rental/addons`,
  `rental/vouchers` — `role="alert"`.
* `rental/checkout` — ringkasan galat unit (`bk-unit-errors`) diberi
  `role="alert"`; kotak ini hanya dirender kalau ada galat, jadi tidak
  muncul kosong pada checkout yang bersih.

**b. Formulir ganti kata sandi admin tidak pernah menampilkan galat.**
`PasswordController::update` memvalidasi dengan
`validateWithBag('updatePassword')`. Formulir admin menulis
`@error('password')` (kantong bawaan) sehingga pesan "kata sandi saat ini
salah" tidak pernah muncul — formulir kembali kosong tanpa penjelasan.
Sekarang kedua isian memakai kantong `updatePassword`, punya `aria-invalid`
dan `aria-describedby`, dan paragraf galatnya ber-`role="alert"`. Dijaga
`UiAccessibilityTest::test_galat_ganti_kata_sandi_memakai_kantong_update_password`
yang memindai setiap view ber-`route('password.update')`.

**c. Tampilan paginasi ketiga.** `vendor/pagination/customer-simple.blade.php`
ternyata tidak dipanggil dari mana pun, tetapi membawa `<style>` sebaris
sendiri (`.pg*`, butir ~32 px) dan lima aturan tema gelap `.pg-*` di
`berkah-theme.css`. Keduanya dicabut; berkas itu kini alias satu baris seperti
`berkah.blade.php`, sehingga direktori paginasi hanya punya satu rupa
(`.bk-page-nav*`). Tampilan bersama juga dijaga untuk `simplePaginate()`:
`@foreach ($elements ?? [] as $element)` — `Paginator::defaultSimpleView`
menunjuk berkas yang sama, dan paginator sederhana tidak punya `$elements`.

**Hasil:** harness **139 lulus, 0 gagal, 216 dilewati**.

### 9.67 Putaran ketujuh: sasaran sentuh, `hidden`, label validasi, dan sapaan

Sapuan ini memakai pemindai, bukan mata: setiap temuan di bawah datang dari
pemeriksaan menyeluruh atas semua view/CSS/JS, lalu dijaga tes.

**a. Kendali sentuh yang menyusut.** Empat pelanggaran 44 px yang lolos dari
tes sebelumnya: (1) bintang penilaian menyusut **40 px** di `@media
(max-width:420px)` — tepat rentang ponsel; sekarang tetap 44 px, hanya
hurufnya yang mengecil; (2) chip filter aktif (`Hapus filter`) 36 px dan
tidak tercakup aturan `pointer: coarse`; (3) tombol "Pilih berkas" 36 px di
checkout dan di kontrol bersama; (4) ringkasan "Catatan halaman" 30 px di
panel admin (44 px di perangkat sentuh). Selain itu `.adm-app .pagination a`
(40 px) **mengalahkan** `.bk-page-nav__link` karena spesifisitasnya lebih
tinggi — blok paginasi lama dicabut sehingga 44 px benar-benar berlaku.

**b. `hidden` yang tidak menyembunyikan.** `.btn`, `.bk-header-filter`, dan
`.bk-expand` (kepala ringkas) memberi `display` dengan spesifisitas yang
mengalahkan gaya bawaan peramban, sehingga tombol bertanda `hidden` — mis.
"Salin alamat pesanan sebelumnya" dan "Pesan untuk hari yang sama" di
checkout — tetap tampil sebelum JavaScript siap. Satu aturan
`[hidden] { display: none !important; }` di `berkah-ui-kit.css` (dimuat
katalog, pelanggan, admin, dan halaman tamu) mengembalikan perilaku standar.

**c. Label validasi yang bocor ke pengguna.** `lang/id/validation.php` hanya
memetakan 31 kolom; **22 kolom** lain tampil sebagai kunci mentah, termasuk
semua kolom checkout bertingkat: pelanggan membaca "units.0.event_on wajib
diisi." dan admin membaca "entry_date wajib diisi.". Semua kunci
`validate()`/`validateWithBag()` di `app/` kini punya label Indonesia,
termasuk `units.*.<field>`, `terms`, `code`, `period`, dan `expected_amount`.

**d. Sapaan yang berganti di tengah halaman.** "kamu" 30 kali, "Anda" 13
kali, dan tiga berkas mencampur keduanya dalam satu halaman (`rental/unit`,
`rental/show`: "Anda dapat membayar bertahap" lalu "Kamu masih dapat
mengirim bukti"). Semua diseragamkan ke "kamu", termasuk halaman verifikasi
email, konfirmasi sandi, dan dialog konfirmasi.

**e. Atribut `placeholder` ganda.** Tiga halaman isian alamat (buat, ubah,
profil) menulis `placeholder` dua kali per kolom — "Contoh: Rumah" lalu
"Rumah, Kantor, Kos, dll.". Peramban memakai yang pertama dan membuang
sisanya, jadi niat penulis kedua tidak pernah terlihat. Duplikat dibersihkan;
yang dipertahankan selalu varian berawalan "Contoh:" sesuai aturan petunjuk
isian.

**f. Rincian kecil yang ikut diperbaiki.**
`rental/unit` melompat h2 → h4 (dua subjudul kini h3, dan ada tes urutan
judul untuk seluruh view); `.fc-opt:focus-visible` hanya berbeda latar dari
hover (kini cincin dalam); `berkah-ui-kit.js` masih mencari
`#bk-theme-hand-1/2` yang sudah tidak ada (kait mati dicabut, ada tes yang
memastikan setiap `querySelector('#id')` benar-benar dirender);
`.adm-drawer` memakai `100vw` (kini `100%`); tabel kas satu-satunya tabel data
tanpa `<caption>` (kini ada, tersembunyi secara visual); wadah gulir tabel kas
belum bisa difokus papan ketik (kini `tabindex="0"`, `role="region"`, label,
dan cincin fokus); `<meta name="theme-color">` baru berubah setelah
`berkah-ui-kit.js` dimuat — halaman masuk/daftar tidak pernah menyesuaikan
warna bilah peramban (kini skrip pra-lukis `_theme_head` yang mengatur);
notifikasi admin satu-satunya waktu berformat `d M Y H:i` di antara
`d/m/Y H:i` (kini seragam, dengan tes format).

**Hasil:** harness **151 lulus, 0 gagal, 216 dilewati**; `blade-scan` 81 view
0/0; `node --check` bersih; `.tmp-verify.php` 26 lulus; kurung CSS seimbang.

### 9.68 Putaran kedelapan: aset, ikon situs, dan bahasa kerangka

**a. Logo 564 KB untuk kotak 44 px.** `berkah-kreatif-logo.jpeg` berukuran
3460×1892 dan **564 KB**, tetapi tidak pernah digambar lebih dari 108 px:
logo header 44–52 px (di header pelanggan muncul **dua kali** — logo dan
tombol kembali-ke-atas), avatar admin 64–72 px, splash login maksimal 108 px.
Setiap halaman memuatnya, jadi di jaringan seluler satu gambar ini menghabiskan
sebagian besar muatan awal. Diganti turunan dari berkas yang sama:
`logo-104.png` (7 KB), `logo-216.png` untuk splash (22 KB), `icon-32.png`
(1 KB), `icon-180.png` (13 KB). Semua `<img>` logo kini punya `width`/`height`
sehingga ruangnya sudah dipesan sebelum CSS/gambar selesai dimuat.

**b. Ikon situs tidak seragam.** Hanya halaman login/daftar, halaman lupa
sandi, dan tiga halaman pelanggan yang mendeklarasikan `<link rel="icon">` —
itu pun SVG data-URI berbeda-beda; halaman lain jatuh ke `/favicon.ico`.
Sekarang `_theme_head` (dipakai 12 halaman) mendeklarasikan ikon 32 px dan
`apple-touch-icon` 180 px sekaligus, dan deklarasi per-halaman dicabut
(`_head_meta` dihapus, berkasnya hanya berisi satu baris ikon). Lembar harian
dan lembar pesanan (halaman mandiri tanpa `_theme_head`) mendapat ikon 32 px
sendiri.

**c. Kalimat Inggris dari kerangka Laravel.** `lang/id` hanya berisi
`validation.php`. Akibatnya `__('auth.password')` di
`ConfirmablePasswordController` jatuh ke `fallback_locale` (en) sehingga
halaman konfirmasi kata sandi menampilkan **"This password is incorrect."**,
dan `ResetPasswordController` (`__($status)` dari broker) bisa menampilkan
kunci mentah `passwords.token`. Ditambahkan `lang/id/auth.php` dan
`lang/id/passwords.php`; `lang/id/pagination.php` tidak dibuat karena tampilan
paginasi memakai teks Indonesia langsung (dijaga
`test_setiap_kamus_bahasa_punya_pemakai`).

**d. Nama kolom mentah di pesan validasi.** 47 kolom yang divalidasi belum
punya entri `attributes`, sehingga pesan berbunyi "kolom proof wajib diisi"
atau "kolom gender wajib diisi". Ditambahkan 36 entri untuk kolom yang benar-
benar tampil ke pengguna (gender, avatar, proof, greeting, design_notes,
delivery/return address, latitude/longitude, voucher_code, dan kolom panel
admin: harga, stok, voucher, QRIS, WhatsApp). Sisa aplikasi lama (laundry)
dicabut: `pickup_date`, `pickup_time`, `weight_estimate`, `service_id`,
`driver_id`, `assignment_type`, `weight_actual`, `proof_image`, `item_lines`,
beserta blok `custom`-nya — pesan "Layanan laundry wajib dipilih" tidak ada
hubungannya dengan pemesanan papan bunga. Juga `'Password wajib diisi'` di
`LoginController` menjadi `'Kata sandi wajib diisi'`.

**e. Halaman galat.** Status 429 belum punya tampilan sendiri (pengguna yang
kena batas percobaan masuk melihat halaman bawaan Laravel tanpa merek);
judul galat berbahasa Inggris ("Server Error") dan berjudul kapital ("Akses
Ditolak", "Sesi Kedaluwarsa"). `errors/429.blade.php` ditambahkan, semua judul
jadi kalimat biasa dan berbahasa Indonesia, dan `public/robots.txt` menutup
`/admin`, `/dashboard`, `/customer`, `/keranjang`, `/checkout`, `/pesanan`,
`/profile`, `/addresses`, `/notifications`, `/pengaturan-pembayaran-sewa`
sementara etalase tetap `Allow: /`.

**Hasil:** harness **161 lulus, 0 gagal, 217 dilewati**; `blade-scan` 81 view
0/0; `node --check` bersih; kurung CSS seimbang (518/649/242/204).

### 9.69 Putaran kesembilan: sapaan notifikasi, tinggi kolom teks, dan kolom pencarian

**a. Dua "Anda" yang tersisa di notifikasi.** Seluruh halaman sudah memakai
"kamu" (§9.67d), tetapi dua pesan notifikasi di controller masih menyapa
"Anda": "dibatalkan atas permintaan Anda" dan "menunggu respons Anda".
Notifikasi adalah tempat pelanggan paling sering membaca kalimat panjang, jadi
campuran sapaan justru paling terlihat di sana. Dijaga
`test_sapaan_seragam_kamu` (memindai `resources/views` **dan** `app`).

**b. `<textarea>` setinggi dua baris.** 22 kolom teks panjang (ucapan, catatan
desain, alamat lengkap, alasan pembatalan, petunjuk pembayaran) tidak punya
`rows`, jadi peramban memberi tinggi bawaan dua baris. Di ponsel, kotak
setinggi itu membuat pelanggan menggulir di dalam kotak kecil — dan gulir
dalam kotak mudah tertukar dengan gulir halaman. Semua kini `rows="4"`.
Dijaga `test_kolom_teks_panjang_punya_tinggi_awal`.

**c. Kolom pencarian bertipe teks.** `#adm-order-q` dan `#order-search`
adalah kolom pencarian tetapi tidak bertipe `search`, sehingga papan ketik
ponsel tidak menampilkan tombol "Cari" dan peramban tidak memberi tombol
bersihkan. Keduanya kini `type="search" autocomplete="off"`. Dijaga
`test_kolom_pencarian_memakai_type_search`.

**d. Yang diperiksa dan sudah bersih** (supaya tidak diaudit ulang):
114 rujukan `route()` semuanya ada; 15 rujukan aset statis ada di `public/`;
tidak ada token CSS tanpa definisi (tiga sisanya sengaja `var(--x, fallback)`
yang diisi JS); tidak ada `for`/`aria-describedby` yang menunjuk id hilang;
tidak ada `tabindex` positif; tidak ada tag berpasangan yang tidak seimbang;
tidak ada tabel data tanpa wadah gulir atau `<caption>`; tidak ada `<img>`
tanpa `alt`; tidak ada `<a>` tanpa `href` atau `<form>` POST tanpa `@csrf`;
24 `<select>` semuanya ditingkatkan `initFormControls` (atribut
`data-fc-select` hanya penanda tambahan); `localStorage`/`matchMedia` selalu
dibungkus `try`.

**Hasil:** harness **164 lulus, 0 gagal, 217 dilewati**.

### 9.70 Putaran kesembilan (lanjutan): sisa Judul Kapital dan pemeriksaan yang terbukti aman

**a. Empat sisa Judul Kapital.** `test_judul_dan_tombol_memakai_huruf_kecil_kalimat`
(§9.62) hanya memindai `<h1>`–`<h4>`, `<button>`, dan `<summary>`. Akibatnya
empat teks lolos: tautan **"Buat Akun"** di login, **"Masuk Sekarang →"** di
daftar, judul **"Verifikasi Email Kamu"**, dan tombol **"Atur Ulang Kata
Sandi"** di surel atur ulang sandi. Semua kini kalimat biasa — "Buat akun",
"Masuk sekarang →", "Verifikasi email kamu", "Atur ulang kata sandi".
"Email" ditulis huruf kecil karena ia kata biasa dalam bahasa Indonesia.

Penjaga barunya `test_tautan_tombol_dan_teks_terjemahan_memakai_kalimat_biasa`
memindai tautan berkelas `btn*` dan setiap string `__('…')`, jadi kelas teks
yang tadinya lolos sekarang ikut diperiksa. Nama merek, akronim, dan nama
bank/brand tetap dikecualikan.

**b. Pemeriksaan yang terbukti aman** (jangan diulang):
116 rujukan `[Controller::class, 'method']` di `routes/web.php` semuanya
menemukan kelas **dan** metodenya (termasuk lewat kelas induk); tidak ada
`console.log`, `dd()`, `dump()`, `ray()`, `TODO`, atau `FIXME` di `app/`,
`routes/`, `config/`, `public/js`, `public/css`, dan view; `localStorage` dan
`matchMedia` di `berkah-ui-kit.js` selalu dibungkus `try`; tabel
`notifications` memakai `text` untuk `data` sehingga pesan panjang tidak
terpotong; 24 `<select>` seluruhnya ditingkatkan `initFormControls` (atribut
`data-fc-select` hanya penanda, bukan syarat); semua `env()` yang dipakai
punya nilai bawaan di `config/`, dan `LAUNDRY_*` sudah terdokumentasi di
`.env.example`.

**c. Jangan hapus `@section('back')` dan `@section('crumbs')`.** Pemindai
"section yatim" yang hanya melihat rantai `@extends` sempat menandai keduanya
mati di 15 halaman admin — padahal `layouts/component/admin/_topbar.blade.php`
mengisinya lewat `yieldContent('back')` dan `@yield('crumbs')`. Yang benar:
hanya `@section('title')`, `back`, dan `crumbs` yang dipakai shell admin;
section lain di halaman anak tidak akan tampil.

**Hasil:** harness **165 lulus, 0 gagal, 217 dilewati**.

**d. Kunci `@error` sudah sepadan.** Setiap `@error('…')` di view punya sumber
pesan: kunci field biasa berasal dari `name="…"` di formulir yang sama, sedangkan
`unit`, `cancel`, dan `transport` di halaman detail admin memang dikirim
`ValidationException::withMessages([...])` dari `RentalOrderController`.
Pemeriksaan ini pernah menandai kunci `units.` di checkout, tetapi itu kunci
dinamis (`@error('units.'.$key.'.…')`) — bukan temuan.


### 9.71 Putaran kesepuluh: label Judul Kapital dan teks kapital di markup

**a. Label isian yang masih Judul Kapital.** Aturan "label isian huruf kecil
kalimat" (§9.62) baru dijaga untuk profil dan konfirmasi sandi, jadi formulir
alamat, pendaftaran, dan masuk masih memakai **"Nama Lengkap"**, **"Alamat
Lengkap"**, **"Label Alamat"**, **"Nama Penerima"**, **"Catatan Tambahan"**,
**"Ubah Foto"**, **"Konfirmasi Kata Sandi"**, dan **"Email / No. HP"**. Satu
pelanggan bisa melihat dua gaya label dalam satu alur checkout → alamat.
Semua kini kalimat biasa, dan judul dokumen `confirm-password` ikut dirapikan
("Konfirmasi kata sandi"). Dijaga
`test_semua_label_isian_memakai_huruf_kecil_kalimat`.

**b. Teks kapital ditulis langsung di markup.** Enam belas label kecil
("RUANG PELANGGAN", "DATA PRIBADI", "KABAR UNTUKMU", "PILIHANMU", "SERAH
TERIMA", "DETAIL PESANAN", "APRESIASI", "Papan bunga · Berkah Kreatif", dst.)
ditulis huruf besar di markup. Pembaca layar kadang mengejanya huruf per huruf
dan teks itu tidak bisa dicari sebagai huruf biasa. Markup kini kalimat biasa;
kapital datang dari `text-transform:uppercase` pada `.bk-kicker`,
`.bk-atelier-label`, `.bk-order-card__label`, `.auth-eyebrow`, dan `.wm-sub`
(dua halaman splash). **Tampilan tidak berubah.** Dijaga
`test_teks_kapital_dibuat_oleh_css_bukan_markup`.

**c. Pemeriksaan keamanan & alas hak yang terbukti aman** (jangan diulang):
setiap aksi pelanggan memeriksa pemilik — `abort_unless($unit->order->customer_id
=== $request->user()->id)`, `abort_if($address->customer_id !== Auth::id())`,
`Auth::user()->notifications()->findOrFail($id)`; berkas pribadi (bukti bayar,
pratinjau desain, gambar referensi, ringkasan sewa) memakai pola
`admin || (customer && pemilik)` + `Cache-Control: private, no-store` +
`nosniff`; grup rute `role:admin`/`role:customer` menutup aksi admin; tidak ada
pengecualian CSRF; semua model punya `$fillable` eksplisit dan `User` sengaja
tidak memuat `role`; tidak ada `{!! !!}` berisi data pengguna, tidak ada
`whereRaw`/`selectRaw` bersisipan variabel, tidak ada `console.log`/`dd()`.
Header keamanan (`nosniff`, `DENY`, `Referrer-Policy`, `Permissions-Policy`,
`no-store` untuk sesi masuk) terpasang lewat middleware.

**Hasil:** harness **166 lulus, 0 gagal, 218 dilewati**.

### 9.72 Putaran kesepuluh (lanjutan): tautan lompat, `aria-invalid`, dan batas voucher

**a. Tiga halaman tanpa tautan lompat ke isi.** `roles/customer/addresses/create`,
`…/addresses/edit`, dan `roles/customer/profile/edit` punya
`<main id="main-content">` tetapi tidak punya tautan lompat. Akibatnya Tab
pertama di halaman itu berhenti di menu, bukan di isi. Ketiganya kini
menyertakan `layouts.component._skip_link`. Dijaga
`test_setiap_halaman_punya_tautan_lompat_ke_isi`, yang juga memastikan id
tujuan tautan benar-benar ada di halaman.

**b. Sembilan halaman menampilkan pesan galat tanpa menandai kolomnya.**
`rental/addons`, `rental/unit`, `rental/show`, `catalog/index`, `catalog/show`,
`roles/admin/profile_edit`, `roles/customer/profile/edit`, dan dua formulir
alamat menampilkan `<p class="…-error" role="alert">` tanpa `aria-invalid` pada
isiannya — 27 kolom. Pengguna papan ketik yang kembali ke kolom salah tidak
mendapat tanda apa pun. Semua kolom bergalat kini bertanda; halaman daftar
menambahkan penanda yang sama dari sisi klien (`markInvalid()`), sebab
validasinya berjalan sebelum halaman dikirim. Dijaga
`test_kolom_bergalat_ditandai_aria_invalid`.

**c. Batas kode voucher tidak sesuai kolom.** `vouchers.code` bertipe
`string(30)`, tetapi validasi penyimpanan mengizinkan `max:40` sementara
formulir memotong di 30. Kode 31–40 huruf akan lolos validasi lalu gagal saat
disimpan (`Data too long`) atau terpotong diam-diam. Validasi kini `max:30`,
sama dengan kolom, formulir, dan aturan pembaruan.

**d. Sisa Judul Kapital di teks pendamping.** "Daftar Sekarang", "Akun Berhasil
Dibuat!", "Jenis Kelamin", "Papan Bunga & Hadiah", "Jadikan Alamat Utama",
"Titik Lokasi di Peta (Opsional)", "Simpan Perubahan", "Simpan Perubahan
Alamat", dan label bawaan "Alamat Utama" dari pendaftaran. Semua kini kalimat
biasa; label alamat bawaan pelanggan baru berbunyi "Alamat utama". Dijaga
`test_teks_pendamping_tidak_memakai_judul_kapital`.

**e. Yang diperiksa dan terbukti aman** (jangan diulang): semua `<form
method="post">` punya `@csrf` (0 temuan); semua `<th>` punya `scope`; semua
pesan sesi berada di wilayah `aria-live`/`role="status"`; batas `maxlength`
formulir sepadan dengan `max:`/tipe kolom untuk nama, deskripsi, kategori,
universitas, kas, dan alamat; kolom teks panjang memakai `text` di basis data
jadi `max:1000`–`max:3000` tidak meluap; `<fieldset class="bk-star-rating">`
membawa `aria-invalid`/`aria-describedby` untuk grup bintang.

**Hasil:** harness **169 lulus, 0 gagal, 218 dilewati**.

**f. Putaran kesepuluh, catatan kecil terakhir.** Gambar QRIS (halaman unit
pelanggan dan pengaturan pembayaran admin) kini membawa `width="320"
height="320"` sehingga ruangnya dipesan sebelum gambar termuat — CSS tetap
`height:auto`, jadi rasionya tidak berubah. Gambar produk tidak perlu:
`.photo` sudah memakai `aspect-ratio`, avatar profil sudah `96×96` tetap, dan
`.cart-image` duduk di jalur grid yang sama dengan wadah placeholder.
Kelas `js-card` yang tidak dirujuk skrip mana pun dibuang dari tiga kartu di
formulir alamat. Sisa kelas tanpa definisi hanyalah `registration-progress`
(digayai lewat `#registration-progress`), `bk-rating-total` dan
`bk-share-link` (mewarisi gaya wadahnya) — semuanya bukan temuan.

### 9.73 Putaran kesebelas: bahasa pesan galat dan satu sumber label status

**a. Pesan galat kini kalimat utuh dan seistilah.** Sebelumnya ada pesan tanpa
titik ("Kata sandi wajib diisi"), kata **"Password"** di tengah kalimat
berbahasa Indonesia ("Email/No. HP atau password yang kamu masukkan salah"),
**"No. HP"** bercampur "Nomor HP" di layar yang sama, dan pesan yang membocorkan
istilah teknis ("Kunci pencatatan sudah digunakan untuk pengembalian lain").
Semua dirapikan — termasuk contoh nomor HP yang kini seragam
(`0812 3456 7890`, sesuai placeholder formulir) dan pesan "Catatan kas tidak
cocok" yang kini menyebut langkah berikutnya. Pesan yang terpotong karena
disambung variabel ("… lebih dari 7 hari; minimal satu unit tidak dibatalkan")
ditulis ulang jadi satu kalimat utuh. Dijaga
`test_pesan_galat_berbentuk_kalimat_utuh`.

**b. Satu sumber label status.** Peta label status unit, progres pengerjaan,
status bukti pembayaran, dan status pratinjau desain ditulis ulang di delapan
view dengan ejaan berbeda — **"Preview menunggu respons"** di filter admin,
"Pratinjau" di halaman pelanggan. Notifikasi pengingat bahkan menampilkan nilai
mentah basis data: `masih berstatus "waiting"`. Semua kini memakai
`app/Support/RentalLabels.php`; ejaan diseragamkan ke "Pratinjau", dan
notifikasi menampilkan label manusia. Daftar filter tetap dibatasi sesuai
validasi controllernya: 3 status untuk pesanan pelanggan, 2 status untuk papan
jadwal, 11 status untuk pesanan admin. Dijaga
`test_label_status_dan_progres_hanya_di_satu_tempat`.

**c. Yang diperiksa dan aman.** Seluruh 110 pesan galat kustom di controller,
perintah artisan, dan layanan sudah menyapa "kamu" (0 "Anda"); teks tautan
WhatsApp siap kirim sengaja tidak memuat nominal atau alamat lengkap, dan
potongan statusnya ditulis huruf kecil supaya menyambung kalimat.

**Hasil:** harness **171 lulus, 0 gagal, 218 dilewati**.

### 9.74 Putaran kesebelas (lanjutan): pratinjau tautan dan tautan folder unggahan

**a. Tautan yang dibagikan tampil kosong.** Tidak ada satu pun tag Open Graph
di aplikasi. Pelanggan membagikan tautan katalog dan halaman apresiasi lewat
WhatsApp — yang muncul hanya kotak kosong tanpa judul dan gambar. Kini:

- `catalog/layout.blade.php` memuat `og:type/site_name/locale/title/description/url/image`
  dan pasangan `twitter:*`;
- halaman produk memakai **foto papannya sendiri** sebagai gambar pratinjau
  (`@section('og_image')`) dan deskripsi produk sebagai ringkasan (dipotong
  155 karakter);
- halaman apresiasi (`rental/public-like`) ikut punya tag meski tetap
  `noindex`;
- kartu bawaan baru `public/images/og-default.jpg` (1200×630, 45 KB): monogram
  di kiri, nama toko, tagline, dan kota. Dibuat dari logo yang sudah ada
  (watermark kecil di sudut logo dihapus dengan mengisi putih).

Dijaga `test_pratinjau_tautan_punya_open_graph` (termasuk batas 300 KB supaya
pratinjau WhatsApp tidak putus).

**b. Foto unggahan tidak tampil setelah pemasangan baru.** Foto produk dan item
tambahan disimpan di disk `public` dan ditautkan ke `public/storage`; tautan
itu tidak ikut Git dan **`composer setup` tidak membuatnya**, jadi semua foto
tampil rusak tanpa pesan apa pun. Sekarang: `composer setup` menjalankan
`php artisan storage:link`, README menjelaskan langkah itu, dan panel katalog
menampilkan pemberitahuan berisi perintahnya bila tautan belum ada. Dijaga
`test_unggahan_publik_punya_tautan_storage`.

**c. Sisa konfigurasi laundry yang mati.** `config/laundry.php` masih memuat
`driver_assignment_strategy` (strategi penugasan kurir era modul laundry) yang
tidak dibaca kode mana pun — dibuang. Bagian `social` tetap disimpan karena
akan dipakai saat tautan Instagram/Facebook diisi.

**d. Alamat pengirim email.** `.env.example` memakai `hello@example.com` tanpa
penjelasan; email pemulihan kata sandi dari alamat contoh masuk folder spam
atau ditolak. Ditambahkan catatan agar diganti dengan alamat domain sendiri.

**Hasil:** harness **174 lulus, 0 gagal, 218 dilewati**.

### 9.75 Putaran kesebelas, catatan penutup: satu hitungan untuk dua navigasi

Halaman pelanggan memuat **dua** navigasi — bilah atas
(`catalog/customer-navigation`) dan bilah bawah
(`layouts/component/customer/_navbar_customer`) — dan keduanya menghitung
notifikasi belum dibaca serta isi keranjang dengan caranya sendiri. Satu
halaman karenanya membuka kueri notifikasi yang sama **dua kali**, dan `try`
penahannya ditulis dua kali dengan cakupan berbeda (`\Exception` di satu
tempat, tanpa penahan di tempat lain). Semua kini lewat
`App\Support\NavCounts`, yang menyimpan hasil per permintaan dan menahan
gangguan basis data supaya lencana notifikasi tidak pernah menggagalkan
halaman. Halaman profil admin memakai penampung yang sama. Dijaga
`test_angka_navigasi_dihitung_sekali`.

Pemeriksaan lain pada putaran ini: semua daftar yang memuat relasi sudah
memakai *eager loading* (`RentalProduct::with('category')`, pesanan admin,
papan jadwal, lembar harian, notifikasi admin), jadi tidak ada kueri berulang
di dalam perulangan; `.photo` memakai `aspect-ratio` sehingga tidak ada
lonjakan tata letak saat foto termuat.

**Hasil:** harness **175 lulus, 0 gagal, 218 dilewati**.

### 9.76 Putaran kedua belas, catatan penutup: pembatalan admin berhenti di serah terima

Alur pesanan ditelusuri ujung ke ujung (katalog dan filter tanggal → keranjang
→ checkout → konfirmasi → ongkos → pratinjau desain → pembayaran → progres →
penutupan dan rating → pembatalan dan pesan ulang). Satu celah ditemukan di
titik pembatalan oleh admin.

`RentalOrderController::cancel()` hanya menolak unit yang **sudah** berstatus
`cancelled` (`app/Http/Controllers/RentalOrderController.php:556`). Akibatnya
unit yang sudah **diserahkan** (`progress = handed_over`) masih bisa
dibatalkan lewat POST langsung, padahal form di halaman admin pun tidak
menyaringnya: `resources/views/rental/admin-detail.blade.php:415` dahulu hanya
memeriksa `$unit->status !== 'cancelled'`. Pembatalan melepas reservasi
sekaligus mengeluarkan unit dari tiga penjaga yang semuanya menyaring
`status = confirmed` dan `progress = handed_over`:
`RentalReminders::remindReturns()` (pengingat papan belum kembali),
`RentalAutoClose::run()` (penutupan otomatis setelah `GRACE_DAYS`), dan
antrean "aktif" dasbor. Papan yang masih di tangan pelanggan karena itu hilang
dari pelacakan tanpa jejak apa pun.

Perbaikan dua sisi. Server kini menolak dengan pesan yang menjelaskan langkah
berikutnya (`RentalOrderController.php:557–561`): *"Papan sudah diserahkan.
Tandai pengembalian lebih dulu sebelum membatalkan unit; pembatalan dapat
dilakukan setelah unit tercatat kembali."* Halaman admin mengganti form itu
dengan satu paragraf penjelas (`admin-detail.blade.php:415–416`), sehingga
tidak ada tombol yang berujung galat. Pesan galatnya tetap tampil lewat kotak
galat global tata letak admin (`layouts/admin.blade.php:46–52`) bila ada yang
mengirim POST langsung.

Dijaga `tests/Feature/RentalAdminCancellationTest.php`: (1) POST pada unit
`handed_over` menghasilkan galat `unit`, status tetap `confirmed`, progres
tetap `handed_over`, dan **tidak** ada catatan pembatalan; (2) halaman detail
admin tidak lagi memuat tautan `admin.rental.cancel` untuk unit itu, tetapi
masih memuatnya untuk unit `waiting`; (3) unit `production` tetap bisa
dibatalkan dan tanggalnya benar-benar kembali tersedia
(`RentalAvailability::minimumAvailable` naik dari 1 ke 2).

### 9.77 Stok papan adalah kapasitas, bukan sisa yang dikurangi

`RentalProduct::stock` tidak pernah didekrementasi saat checkout — dan itu
memang disengaja. Ketersediaan dihitung dari baris unit yang menahan tanggal,
bukan dari kolom stok: `RentalOrderUnit::RESERVING_STATUSES`
(`app/Models/RentalOrderUnit.php:22`) berisi `pending` dan `confirmed`, lalu
`RentalAvailability::minimumAvailable()` menarik semua unit yang beririsan
(`app/Services/RentalAvailability.php:64`) dan `minimumFromOverlaps()`
menghitung puncak tumpang tindih dengan sapuan peristiwa
(`app/Services/RentalAvailability.php:95`). Pola ini membuat pembatalan,
kedaluwarsa konfirmasi, dan hangusnya DP otomatis melepas tanggal tanpa
perlu mengembalikan angka apa pun — satu sumber kebenaran, tanpa risiko
"stok bocor" karena satu jalur lupa menaikkan kembali.

Karena itu pemeriksaan `stock < 1` di checkout
(`RentalOrderController.php:35`, `:188`) dan di keranjang
(`RentalCartController.php:28`, `:61`) bukan pengganti pemeriksaan tanggal;
keduanya hanya menolak papan yang dinonaktifkan admin. Catatan ini
ditambahkan ke `docs/ALUR-PEMESANAN.md` supaya tidak ada yang "memperbaiki"
kolom stok di kemudian hari.

### 9.78 Matematika tenggat: semua batas dihitung di WIB, bukan zona peramban

Empat batas waktu pada alur sewa diperiksa satu per satu dan semuanya memakai
`Asia/Jakarta` secara eksplisit:

- tenggat pelunasan `plannedPaymentDeadline()`
  (`app/Models/RentalOrderUnit.php:34–38`) = 23.59.59 WIB pada H-1;
- `cutoff()` (`app/Services/RentalPaymentDeadline.php:10`) = 00.00.00 WIB pada
  hari penerimaan, dipakai sebagai batas **eksklusif** supaya bukti yang
  dikirim pukul 23.59 masih dihitung tepat waktu;
- toleransi `graceDeadline()` (`app/Services/RentalPaymentDeadline.php:30`) =
  H+1 pukul 12.00 WIB, batas terakhir unit yang dibatalkan karena tenggat
  masih bisa dilanjutkan sebelum DP hangus;
- tenggat desain `deadline()` (`app/Services/RentalDesignDeadline.php:34`) =
  `min(unggah + 48 jam, H-3)`, dengan lantai `unggah` supaya pesanan mendadak
  tidak langsung melewati tenggat saat pratinjau baru tersedia.

`config('app.timezone')` bernilai `Asia/Jakarta` dan `.env.example` ikut
menyetel `APP_TIMEZONE` (`config/app.php:68`, `.env.example:10`), jadi
`submitted_at` yang dibandingkan `timelyCoverage()` berada di zona yang sama
dengan batasnya. Tidak ada lagi perbandingan yang bergantung pada zona
peramban.

### 9.79 Pembekuan ubah data unit: view dan server memakai ukuran yang sama

Kekhawatiran lama bahwa halaman unit menyembunyikan form lebih awal daripada
aturan server ternyata tidak terbukti. Halaman memakai
`$netPaid` = pembayaran `verified` dikurangi pengembalian
(`resources/views/rental/unit.blade.php:8`) dengan syarat
`status = confirmed`, `progress = waiting`, dan `$netPaid === 0`
(`unit.blade.php:47`). Server memakai `verifiedNet()` — rumus yang sama
(`app/Models/RentalOrderUnit.php:74`) — dengan dua syarat yang sama
(`RentalOrderController.php:501–502`). Bukti yang masih `pending` tidak
membekukan apa pun di kedua sisi, jadi pelanggan tidak pernah melihat form
yang pasti ditolak. Tidak ada perubahan kode di sini; hanya dicatat agar
pemeriksaan ini tidak diulang.

### 9.80 Checkout: satu pesanan per keranjang, dan semua angka dikunci

Pengiriman ulang formulir checkout tidak membuat pesanan ganda: baris
pelanggan dikunci lebih dulu (`User::whereKey(...)->lockForUpdate()`), lalu
`checkout_key` dicari dan `checkout_payload_hash` dibandingkan; isi keranjang
yang berbeda ditolak dengan pesan yang menyuruh memeriksa "Pesanan saya"
(`RentalOrderController.php:147–151`). Harga tidak pernah diambil dari
formulir: produk, item tambahan, paket durasi, universitas antar/jemput, dan
voucher semuanya dibaca ulang di dalam transaksi dengan `lockForUpdate`, dan
ketersediaan tanggal diperiksa dengan `canFitRequested(..., true)` yang ikut
mengunci. Foto referensi sengaja disimpan **di luar** transaksi dan dihapus
bila transaksi gagal atau pesanan ternyata sudah ada, sehingga percobaan
ulang karena *deadlock* tidak meninggalkan berkas yatim.

### 9.81 Voucher di halaman checkout memang hanya perkiraan

Halaman checkout menyebut dirinya sendiri dengan jujur: *"Potongan dihitung
dari harga sewa unit dan diperiksa kembali saat pesanan dikirim"* dan status
tombol cek berbunyi *"Kode diperiksa saat pesanan dikirim; tombol cek hanya
menampilkan perkiraan potongan."*
(`resources/views/rental/checkout.blade.php:91`, `:95`). Selisih antara
perkiraan dan potongan final memang mungkin terjadi karena pilihan paket
durasi baru diketahui saat formulir dikirim
(`app/Services/RentalVouchers.php:estimateRentalBase`), dan aturan final
memakai basis sewa yang sebenarnya. Karena antarmuka sudah menyebutnya
perkiraan, ini bukan cacat; dicatat agar teks itu tidak "dirapikan" menjadi
janji potongan pasti.

**Hasil:** harness **175 lulus, 0 gagal, 218 dilewati**; `blade-scan.py`
81 view / 0 / 0; `node --check` 11 berkas JS lolos; `.tmp-verify.php` 26/0.

### 9.82 Pembersih masukan memotong kalimat biasa

`app/Http/Middleware/SanitizeInput.php` membersihkan kolom teks bebas sebelum
validasi: `address`, `notes`, `label`, `recipient_name`, `name`, dan kawan-
kawan. Masalahnya, semua nilai dilewatkan **tiga** pembersih HTML sekaligus —
`strip_tags()`, pembersih atribut `on…=`, dan pembersih protokol `href/src` —
tanpa memeriksa apakah nilainya memang memuat tag. Diukur dengan menjalankan
metode aslinya:

| Masukan | Sebelum | Sesudah |
| --- | --- | --- |
| `Papan Bunga <3 untuk Ibu Sari` | `Papan Bunga` | utuh |
| `ongkir=25000 ditanggung toko` | `ditanggung toko` | utuh |
| `Kakak <Budi> yang mengambil papan` | `Kakak` | utuh |
| `Halo <b>dunia</b> ongkir=25000` | `Halo dunia` | `Halo dunia ongkir=25000` |

Dua kerusakan nyata: `<3` dianggap tag sehingga sisa kalimat hilang, dan kata
berawalan `on` yang diikuti `=` (ongkir, ongkos, online) dianggap penangan
event. Untuk nama penerima atau catatan kas, ini kehilangan data yang tidak
bisa dipulihkan.

Logika dipindah ke `App\Support\InputSanitizer` dengan jalur cepat: nilai yang
tidak memuat tag yang dikenal browser dikembalikan apa adanya (hanya byte nol
dibuang dan spasi tepi dipangkas). Daftar tag sengaja eksplisit
(`a|b|div|script|img|iframe|…`), bukan "<" diikuti huruf apa pun, supaya nama
`Kakak <Budi>` tidak dianggap markup. Untuk nilai bertag, urutannya kini:
buang `<script>…</script>` beserta isinya, buang `<iframe|object|embed|link|
meta …>`, lalu `strip_tags()` membuang seluruh tag **bersama atributnya** —
pembersih atribut terpisah tidak diperlukan lagi, dan justru dialah yang dulu
mencacat teks biasa. Middleware tinggal memanggil `InputSanitizer::clean()`.

Dijaga `tests/Feature/InputSanitizerTest.php` (4 tes, 13 pemeriksaan): teks biasa
utuh, spasi tepi dipangkas, byte nol dibuang, `<script>`/`<img onerror>`/
`<iframe>`/`href="javascript:"` tetap bersih, dan spasi sisa tag dirapikan.
Tes ini berjalan di harness karena `InputSanitizer` tidak bergantung pada
aplikasi.

### 9.83 "No. HP" masih tertinggal di empat layar dan di perintah terminal

Aturan istilah sudah "nomor HP", tetapi pemeriksa lama
(`UiDefaultsTest::test_pesan_galat_berbentuk_kalimat_utuh`) hanya memindai
**controller** (`withMessages`/`withErrors`). Berkas Blade dan perintah
terminal tidak tersentuh, sehingga empat teks masih lolos:

- `resources/views/auth/login.blade.php:322` — pesan JS "Email atau No. HP
  wajib diisi" saat kolom identitas kosong;
- `resources/views/roles/customer/addresses/create.blade.php:288` dan
  `edit.blade.php:167` — label "No. HP / WhatsApp";
- `resources/views/roles/customer/profile/edit.blade.php:374` — label "No. HP
  penerima".

Semua diganti menjadi "nomor HP"/"Nomor HP". Perintah terminal ikut
diseragamkan (`CreateUser`, `EditUser`): prompt "Nomor HP (contoh: …)", judul
tabel "Nomor HP", serta "Password"/"password" pada teks yang terlihat menjadi
"kata sandi" (nama variabel dan kunci validasi tidak diubah).

Celahnya ditutup `tests/Feature/ViewCopyTest.php`: menelusuri rekursif seluruh
`resources/views/**/*.blade.php` (termasuk `roles/admin/audit/…` yang lebih
dalam), `app/Console/Commands/*.php`, dan `lang/id/*.php` untuk tiga bentuk
terlarang — `No. HP`, sapaan `Anda`, dan ejaan `Silahkan`. Tes ini
dibuktikan gagal saat satu baris "No. HP" disisipkan sementara ke sebuah view,
lalu lulus setelah berkas uji itu dihapus.

### 9.84 Akun yang sudah dihapus: pesan menyesatkan dan jalan pulang yang hilang

Penghapusan akun memakai *soft delete* (`app/Models/User.php:16`), sehingga
baris pengguna tetap ada dan nomor HP serta emailnya **masih menempati aturan
unik**. Pelanggan yang pernah menghapus akun lalu ingin kembali akan ditolak
saat mendaftar dengan pesan lama: *"Nomor HP tidak tersedia. Silakan masuk
atau hubungi admin…"* — menyuruh masuk ke akun yang justru sudah tidak bisa
dipakai. Admin pun tidak punya jalan untuk memulihkannya: belum ada halaman
kelola pengguna, dan `user:edit` hanya mencari akun aktif.

Dua perbaikan:

- `RegisterController::store()` memeriksa `User::onlyTrashed()` untuk nomor HP
  dan email yang dipakai. Bila cocok, pesannya menjadi *"Nomor HP ini tercatat
  pada akun yang sudah dihapus. Hubungi admin untuk memulihkan akunmu."*
  (begitu pula untuk email).
- Perintah baru `php artisan user:restore {identifier?}` mencari akun terhapus
  lewat email, nomor HP (dinormalkan seperti pendaftaran: `+62`/`62`/`0`
  dibuang), atau ID; menampilkan tabel nama/email/nomor/role/waktu dihapus;
  lalu meminta konfirmasi sebelum memulihkan. Bila data yang dicari justru
  milik akun aktif, perintah berhenti dengan penjelasan, bukan memulihkan
  akun yang salah. `README.md` menyebut perintah ini di bagian akun demo.

Dijaga `tests/Feature/UserRestoreCommandTest.php` (pulih lewat nomor HP dengan
format `08…`, akun aktif ditolak) dan pemeriksaan pesan baru di
`tests/Feature/Auth/RegistrationTest.php`.

### 9.85 `orWhere` tanpa grup melepas scope soft delete

Dua perintah terminal mencari pengguna dengan rantai
`where('email', …)->orWhere('phone', …)->orWhere('id', …)`. Scope soft delete
menambahkan `deleted_at is null` sebagai syarat pertama, sehingga SQL-nya
menjadi `(deleted_at is null AND email = ?) OR phone = ?` — cabang `phone`
**tidak** lagi mensyaratkan akun aktif. Akibatnya `user:edit` bisa menemukan
akun yang sudah dihapus, lalu validasi uniknya (`whereNull('deleted_at')`)
mengizinkan nomor HP yang sama dengan baris terhapus itu, dan penyimpanan
berakhir dengan pelanggaran indeks unik.

Pencarian di `EditUser` dan `RestoreUser` kini dibungkus satu closure, jadi
syarat aktif/terhapus berlaku untuk ketiga cabang. Di seluruh controller pola
sejenis sudah dibungkus dengan benar (`DashboardController:56`,
`RentalOrderController:371`, `RentalScheduleController:51`,
`RentalReminders:69`), sehingga tidak ada yang perlu diubah.

**Hasil:** harness **181 lulus, 0 gagal, 223 dilewati**; `blade-scan.py`
81 view / 0 / 0; `.tmp-verify.php` 26/0; `php -l` bersih untuk semua berkas
yang disentuh.

### 9.86 Contoh isian seragam di semua formulir

Satu medan yang sama ditampilkan dengan contoh berbeda-beda, sehingga
pelanggan harus menebak format mana yang diterima:

| Medan | Sebelum | Sesudah |
| --- | --- | --- |
| Nomor HP | `0812 3456 7890` (3 layar) dan `081234567890` (2 layar, termasuk kolom identitas di halaman masuk) | `0812 3456 7890` di semua layar |
| Email opsional (daftar) | `Contoh: sari@email.com (opsional)` | `Contoh: nama@email.com (opsional)` |
| Panjang kata sandi | `Min. 8 karakter` dan `Minimal 8 karakter` | `Minimal 8 karakter` |

Contoh di prompt perintah `user:create` ikut diseragamkan, dan contoh pemakaian
`user:restore` memakai nomor ber-spasi: `php artisan user:restore "0812 3456
7890"`. `ViewCopyTest` menambahkan satu pola penjaga: bentuk tanpa spasi
`081234567890` tidak boleh muncul lagi di view, perintah, maupun `lang/id`.

### 9.87 Wildcard LIKE di kotak pencarian pesanan

Kotak pencarian pesanan pelanggan dan admin menyusun `like '%'.$term.'%'`
tanpa meng-escape `%` dan `_`. Mengetik `%` membuat kata kunci berubah arti
menjadi "semua data", dan `_` mencocokkan satu karakter apa pun — hasil
pencarian jadi menyesatkan tanpa sebab yang terlihat. Keduanya kini memakai
`addcslashes(trim($q), '%_\\')`, sehingga `%` dan `_` dicari sebagai karakter
biasa. Tidak ada perubahan pada rute, kolom, atau tampilan hasil.

### 9.88 Dasbor admin menghitung notifikasi tanpa memuat isinya

`DashboardController::admin()` memakai `Auth::user()->unreadNotifications`
(properti) sehingga seluruh baris notifikasi diambil ke memori hanya untuk
dihitung. Diganti `unreadNotifications()->count()`, sama seperti dasbor
pelanggan (`DashboardController::customer()`), dan sejalan dengan `NavCounts`
yang dipakai lencana navigasi. Angka yang tampil tidak berubah.

Pemeriksaan lain pada putaran ini yang tidak menghasilkan perubahan:
`RentalScheduleController` sudah membatasi parameter `month`/`status`/
`activity` dengan `date_format` dan `Rule::in`, serta memakai
`Asia/Jakarta`; `RentalAddonController::destroy()` sudah menolak item yang
dipakai pesanan dan mengunci barisnya di dalam transaksi;
`RentalCashBookController::export()` sudah menetralkan sel CSV yang diawali
`=`, `+`, `@`, atau `-` (`csvText()`); `Email/reset-password` dan tata
letaknya memakai "kamu" dan `Laundry::name()`; `vendor/pagination/
customer-simple.blade.php` memang alias lama yang masih diuji
`ResponsiveLayoutTest:311`, jadi tidak dihapus.

**Hasil:** harness **181 lulus, 0 gagal, 223 dilewati**; `blade-scan.py`
81 view / 0 / 0; `.tmp-verify.php` 26/0; `node --check` 11 berkas JS lolos;
`php -l` bersih.

### 9.89 Komponen tombol kembali tercabut setengah, dan pemindai rujukan baru

Putaran sebelumnya menghapus `resources/views/components/back-button.blade.php`
karena tidak lagi dirujuk. Yang tertinggal: kelas
`App\View\Components\BackButton` yang `render()`-nya masih menunjuk view yang
sudah tidak ada, plus 55 baris `.back-btn*` di `resources/css/app.css` dengan
komentar "used by x-back-button component". Komponen seperti ini tidak
menimbulkan galat sampai ada yang memakainya — lalu halaman yang memuatnya
gagal 500 dengan pesan "View [components.back-button] not found", bukan pesan
yang bisa dimengerti. Kelas dan blok CSS-nya dihapus; contoh pakai di docblock
`App\Support\BackUrl` diperbaiki agar menunjuk pola yang benar-benar dipakai
(`data-smart-back` + `href`, penanganannya di
`public/js/berkah-smart-back.js`).

Agar kelas masalah ini tertangkap lebih awal, `tests/Feature/RouteReferenceTest.php`
memindai seluruh `app/`, `resources/views/`, dan `public/js/`:

- setiap `route('…')` literal harus ada di `routes/*.php`, termasuk nama yang
  dibentuk awalan grup (`admin.`, `customer.`);
- setiap `@include`/`@extends`/`@each`/`view('…')` literal harus punya berkas
  `resources/views/…blade.php`.

Pemindai ini yang menemukan sisa `BackButton`. Pada pemindaian seluruh
proyek: 115 nama rute terdaftar dibaca dan 332 pemanggilan `route('…')`
diperiksa — tidak ada satu pun yang menunjuk rute tak dikenal. Pemeriksaan pendamping lain juga bersih: tidak ada `<form>` non-GET
tanpa `@csrf` (tujuh `<form>` yang tertangkap pemindai awal semuanya form
filter `method="get"`), dan seluruh kurawal `public/css/*.css` seimbang.

**Hasil:** harness **183 lulus, 0 gagal, 223 dilewati**; `blade-scan.py`
81 view / 0 / 0; `.tmp-verify.php` 26/0; `node --check` 11 berkas JS lolos.

### 9.90 `robots.txt` menutup tautan berbagi, dan alias `/masuk` serta `/daftar`

`public/robots.txt` hanya menutup lima jalur (`/admin`, `/dashboard`,
`/customer`, `/keranjang`, `/checkout`). Yang terlewat justru yang paling
pribadi:

- `/apresiasi/{token}` — halaman berbagi yang **bisa dibuka tanpa masuk**;
  isinya nama penerima dan foto acara. Bila terindeks, data itu muncul di
  hasil pencarian meski tautannya tidak pernah dibagikan ke mesin pencari.
- `/sewa`, `/ringkasan-sewa`, `/pembayaran-sewa`, `/preview-desain`,
  `/referensi-pesanan` — pesanan, struk, bukti bayar, dan berkas desain.
- `/forgot-password`, `/reset-password`, `/confirm-password`, `/verify-email`
  — halaman autentikasi yang sebenarnya sudah ber-`noindex`, kini ditutup juga
  di tingkat perayapan.

Berkasnya disusun ulang dengan komentar per kelompok, dan
`UiDefaultsTest` memperluas daftar jalur yang wajib tertutup dari 5 menjadi
13 jalur.

Sekalian ditemukan bahwa `/daftar` dan `/masuk` — bentuk yang paling wajar
diketik pengguna berbahasa Indonesia — tidak punya rute dan berakhir 404.
Ditambahkan dua alias pengalihan di dalam grup `guest`
(`Route::redirect('/masuk', '/login')`, `Route::redirect('/daftar',
'/register')`), sejalan dengan alias `/profile` yang sudah ada, dan keduanya
ikut ditutup di `robots.txt`. Dijaga `tests/Feature/RouteAliasTest.php`.

**Hasil:** harness **183 lulus, 0 gagal, 224 dilewati**; `blade-scan.py`
81 view / 0 / 0; `.tmp-verify.php` 26/0; `node --check` 11 berkas JS lolos.

### 9.91 Nama isian yang belum punya nama Indonesia

Pesan galat Laravel dibentuk dari dua bagian: aturan (`required`, `max:1000`,
…) dan nama kolom. Nama kolom itu diambil dari daftar `attributes` di
`lang/id/validation.php`; kalau tidak terdaftar, yang muncul ke layar adalah
nama variabel mentah. Blok validasi di controller sebelumnya belum tercakup
pemeriksaan apa pun, jadi ejekan seperti "ends on harus berformat Y-m-d"
hanya menunggu waktu.

Sepuluh kolom ternyata belum terdaftar:

| Kolom | Muncul di | Kalimat yang akan dilihat pelanggan |
| --- | --- | --- |
| `ends_on` | `RentalCatalogController`, `RentalCartController` | "tanggal pengembalian harus berformat …" (sebelumnya "ends on …") |
| `from`, `to` | `RentalOrderController` | "tanggal awal"/"tanggal akhir" |
| `date` | `RentalCashBookController`, `RentalScheduleController` | "tanggal" |
| `next` | `RentalCartController` | "langkah berikutnya" |
| `base` | `RentalVoucherController` | "nilai belanja" |
| `image` | katalog, item tambahan, desain | "foto" |
| `role` | `user:create` | "peran akun" |
| `confirmed` | centang persetujuan desain/pesanan | "persetujuan" |
| `request_key` | bukti pembayaran (kunci idempotensi) | "kunci permintaan" |

Sekaligus dibuang tiga sisa fitur lama yang sudah tidak dipakai lagi
(`grep` = 0 pemakaian): pesan `custom.proof_image`, atribut
`customer_address_id`, dan `status_note` — yang terakhir bahkan hanya
tersisa sebagai nama kolom di `InputSanitizer::FIELDS` tanpa satu pun isian
yang mengirimnya.

Supaya tidak terulang, `tests/Feature/ValidationAttributeTest.php`
membaca setiap blok `->validate([...])` dan `Validator::make(...)` di
controller serta perintah artisan (kurung siku diimbangi, string dilewati),
mengumpulkan 80+ nama kolom, lalu memastikan semuanya terdaftar. Tes yang
sama gagal pada sepuluh kolom di atas sebelum diperbaiki — jadi daftarnya
bukan tebakan.

### 9.92 Alamat tersimpan tanpa batas atas, hilang diam-diam saat checkout

Kolom alamat di checkout dibatasi `max:1000`
(`RentalOrderController:74,76,499,500`), dan halaman checkout hanya
menawarkan alamat tersimpan yang panjangnya ≤ 1000 karakter
(`->filter(fn ($address) => mb_strlen($address->full_address) <= 1000)`,
`RentalOrderController:40`). Masalahnya, tiga tempat yang menyimpan alamat
tidak punya batas atas sama sekali: `CustomerAddressController::store`,
`CustomerAddressController::update`, dan `ProfileController::savePrimaryAddress`
semuanya berhenti di `'min:10'`.

Akibatnya pelanggan bisa menyimpan alamat 1.400 karakter lewat halaman
alamat, melihatnya tersimpan rapi di daftar, lalu tidak menemukannya di
daftar pilihan saat checkout — tanpa pesan apa pun. Batas atas `max:1000`
kini dipasang di ketiga validasi itu sehingga pesan galat muncul di tempat
yang benar. `RegisterController` tidak perlu diubah karena kolom `address`
di formulir pendaftaran sudah dibatasi `max:300`.

Dijaga `tests/Feature/CustomerAddressLimitTest.php`: satu tes statis
(kedua controller memakai aturan baru) dan satu tes perilaku (alamat
1.380 karakter ditolak, tidak ada baris `customer_addresses` yang dibuat).

### 9.93 Kaki halaman tanpa alamat outlet

Footer katalog hanya berbunyi `© 2026 Berkah Kreatif` — nama toko ditulis
mati di Blade, bukan dari `config/laundry.php`. Alamat outlet tidak pernah
muncul di satu halaman publik pun; satu-satunya tempat alamat tampil adalah
kaki email reset kata sandi. Padahal checkout menawarkan "Diambil di
outlet" dan struk mencetak kalimat yang sama: pelanggan diberi tahu bahwa
barang diambil di outlet, tanpa pernah diberi tahu di mana outletnya.

Footer kini memuat nama dari `Laundry::name()`, alamat dari
`Laundry::address()`, tautan **Lokasi di Google Maps** dari
`Laundry::googleMaps()`, dan **WhatsApp** dari
`WhatsApp::link(Laundry::whatsapp(), …)`. Nilai `LAUNDRY_GMAPS` sudah lama
ada di `config/laundry.php:22` dan `.env.example`, tetapi tidak pernah
dipakai di view — sekarang tautannya benar-benar bisa diklik, dan tetap
disembunyikan bila pemilik mengosongkannya.

Rapikan sekalian: `App\Support\Laundry` menyimpan lima pembungkus yang
tidak pernah dipanggil satu view pun — `version()`, `hours()`, `social()`,
`receiptHeader()`, dan `waLink()` (duplikat `WhatsApp::link()`). Semuanya
dibuang bersama blok `version`, `hours`, dan `social` di
`config/laundry.php`. Blok `social` memang mustahil diisi: kunci
`LAUNDRY_IG`/`LAUNDRY_FB` tidak pernah ada di `.env.example`.

Dijaga `tests/Feature/CatalogFooterTest.php`: satu tes statis (footer
memakai data config, bukan nama tertulis mati) dan satu tes halaman yang
memastikan alamat, "Lokasi di Google Maps", dan "WhatsApp" tampil di `/`.

**Catatan keputusan:** nama merek "Berkah Kreatif" di judul halaman, meta
Open Graph, dan teks sambutan tetap ditulis harfiah. Itu bagian dari naskah,
bukan data operasional yang berubah; memindahkannya ke config hanya
menambah lapisan tanpa mengubah apa pun bagi pemilik toko.

### 9.94 Daftar proxy tepercaya yang tertulis di kode

`bootstrap/app.php` memanggil `trustProxies(at: '*')`. Alasannya masuk
akal — hosting menerminasi TLS di proxy mereka, dan tanpa mempercayai
proxy, Laravel mengira koneksi masih HTTP (muncul "Mixed Content" atau
redirect berulang saat HTTPS dipaksa). Tetapi `*` juga berarti siapa pun
yang menghubungi server langsung boleh mengaku sebagai IP apa saja, dan IP
itu dipakai untuk kunci pembatasan percobaan masuk (5 kali per email+IP,
`LoginRequest::throttleKey()`) serta kolom `ip` pada catatan audit.

Nilai itu sekarang bisa diatur tanpa menyentuh kode:
`App\Support\TrustedProxies::parse()` membaca `TRUSTED_PROXIES`
(dipisah koma, kosong jatuh ke `'*'`), dipakai lewat
`config('app.trusted_proxies')` di `bootstrap/app.php`. Bawaannya sengaja
**tetap** `'*'` supaya tidak ada perubahan perilaku pada pemasangan yang
sudah jalan; pemilik yang servernya punya TLS sendiri cukup mengisi
`TRUSTED_PROXIES=127.0.0.1,::1` (atau daftar IP proxy) dan IP palsu tidak
lagi dipercaya. Konsekuensinya ditulis di docblock kelas, di komentar
config, dan di `.env.example`.

Dijaga `tests/Feature/TrustedProxyTest.php` (3 tes): penguraian daftar
koma, nilai kosong/bawaan, serta pemeriksaan bahwa `bootstrap/app.php`
tidak lagi menuliskan `trustProxies(at: '*')` langsung di kode.

### 9.95 `last_used_at` yang tidak pernah diisi

Daftar alamat pelanggan diurutkan dengan
`orderByDesc('last_used_at')` (dua tempat: `index()` dan promosi alamat
utama setelah `destroy()`). Kolom itu warisan modul lama: `grep` seluruh
repo menunjukkan tidak ada satu baris pun yang **menulis** nilainya —
hanya membaca, mengurutkan, dan mencantumkannya di `$fillable`/`$casts`.
Artinya pengurutan "terakhir dipakai" tidak pernah berpengaruh, dan
komentar di `destroy()` ("promote alamat lain (yang terbaru dipakai)")
menjanjikan sesuatu yang tidak terjadi.

Pengurutan diganti `updated_at` lalu `id` — kolom yang selalu terisi —
sehingga alamat yang baru diperbarui memang naik ke atas. `last_used_at`
dihapus dari `$fillable` dengan catatan; kolomnya sengaja **tidak**
dihapus lewat migrasi supaya tidak ada operasi destruktif pada basis data
yang sudah berjalan.

### 9.96 Yang sudah diperiksa dan tidak perlu diubah (putaran 16)

Diperiksa ulang dan dinyatakan benar; dicatat supaya tidak diaudit lagi:

- **`RentalPaymentController::store`** — kunci baris unit (`lockForUpdate`),
  penjagaan status unit termasuk jalur toleransi `eligibleForLatePayment`,
  penolakan saat penawaran ongkos belum disetujui, penjagaan overdraft
  memakai `allocatedNet()` (menghitung bukti yang masih `pending`), dan
  berkas bukti dihapus kembali bila transaksi gagal. Tidak ada temuan.
- **Struk (`rental/receipt.blade.php`)** — "Pembayaran terverifikasi
  bersih" = pembayaran `verified` − seluruh refund. Refund tidak mungkin
  melebihi pembayaran induknya (`RentalPaymentController::correct` menolak
  nominal di bawah jumlah refund, baris 95), jadi angkanya tidak bisa
  negatif. Bukti `pending` ditampilkan terpisah dan diberi label jelas.
- **`ProfileController::savePrimaryAddress`** — `zone` dipaksa `'A'` memang
  disengaja: zona sudah tidak dipakai untuk harga (biaya penanganan flat),
  begitu pula di `CustomerAddressController`. Bukan bug.
- **Format tanggal/waktu** — seluruh view memakai `d/m/Y` (31) dan
  `d/m/Y H:i` (28); tidak ada campuran format Amerika.
- **Biaya `fullyBookedDates()`** di halaman checkout — satu kueri per produk
  di keranjang; jumlah produk berbeda jarang lebih dari tiga, jadi masih
  wajar. Dicatat sebagai catatan performa, bukan temuan.

**Hasil putaran 16:** harness **189 lulus, 0 gagal, 226 dilewati**
(naik dari 183 karena 6 tes baru); `blade-scan.py` 81 view / 0 / 0;
`.tmp-verify.php` 26/0; `php -l` pada 15 berkas yang diubah bersih.

### 9.97 "Error 404" yang tersisa di halaman galat

Enam halaman galat (`403`, `404`, `413`, `419`, `429`, `500`, `503`) sudah
berjudul Indonesia dan sudah diuji, tetapi kartu galatnya masih memakai
label kecil berbahasa Inggris dari layout bersama:

```blade
<span class="code-pill">Error {{ $code }}</span>
```

Label itu tampil di setiap halaman galat, persis di atas angka besar kode
status. Diganti menjadi `Galat {{ $code }}`. Sekalian, tombol bawaan layout
menulis "Kembali ke Beranda" dengan huruf besar di tengah, sementara semua
halaman galat lain memakai kalimat yang sama dengan huruf kecil ("Kembali
ke beranda", "Ke beranda"). Diseragamkan ke bentuk huruf kecil supaya satu
jenis tombol tidak punya dua ejaan.

`UiDefaultsTest` diperluas: label kartu harus "Galat {{ $code }}", bukan
">Error {{ $code }}", dan tombol bawaan harus `'Kembali ke beranda'`.

Pemeriksaan sejenis untuk teks Inggris lain di view (kata seperti "Save",
"Submit", "Cancel", "Loading") hanya menyisakan sisa yang sah: nama atribut
HTML (`type="date"`), komentar Blade, dan kata serapan yang memang dipakai
sehari-hari ("Email", "Status", "Total", "No. HP").
