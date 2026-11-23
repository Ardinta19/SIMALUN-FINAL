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
| Tipografi | `.card-sub`, `.form-label` konsisten; pesan galat memakai `.alert` dengan ikon emoji ⚠️/✅. | Emoji status **belum diganti** ikon Lucide (menyusul bersama pembersihan emoji sisa). |
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
| **Lenis 1.3.11** | `public/vendor/lenis/lenis.min.js` + `lenis.css` | Gulir halus berbobot di mouse, trackpad, **dan layar sentuh** (`syncTouch` dengan lerp .09); disinkronkan ke ScrollTrigger; berhenti otomatis saat sheet mengunci gulir. | Gulir adalah interaksi paling sering di semua perangkat. Di sentuh, lerp dibuat lebih rapat supaya gulir jari tidak terasa melayang. |
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

## 5. Sisa pekerjaan (urutan yang disarankan)

1. **Customer**: skeleton setinggi kartu pada pratinjau katalog (mencegah lompatan tata letak saat `dashboard-preview.js` mengisi data).
2. **Auth**: ganti emoji ⚠️/✅ pada `.alert` dengan ikon Lucide agar setara dengan admin.
4. **Admin**: densitas tabel (`td` padding) dan `data-bk-count` pada kartu statistik dasbor.
5. **Semua peran**: adopsi `--bk-fs-*`/`--bk-space-*` di view yang masih menulis angka lepas; audit tablet 768–1024 px per halaman.
6. **Gerak lanjutan (opsional)**: `SplitText` untuk judul hero katalog; `Flip` untuk transisi daftar pesanan saat filter berubah.

---

## 6. Cara memeriksa

```bash
# pustaka lokal & tanpa CDN
grep -rn "cdnjs\|unpkg\|jsdelivr" resources/views | wc -l     # harus 0
ls public/vendor/gsap public/vendor/lenis

# kontrak gerak
php artisan test --filter=UiDefaultsTest
php artisan test --filter=MotionAssetsTest

# keterbacaan: tidak boleh ada teks di bawah 12 px
grep -o "font-size: *\.[0-9]*rem" public/css/berkah-admin.css | sort -u
```

Di peramban: matikan JavaScript → konten harus tetap utuh; aktifkan
`prefers-reduced-motion: reduce` → tidak boleh ada gerak; buka sheet filter →
gulir halaman di belakangnya harus terkunci.


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
(syncTouch), `public/css/berkah-motion.css` (token sentuh pindah ke ui-kit),
`public/css/berkah-ui-kit.css`.
