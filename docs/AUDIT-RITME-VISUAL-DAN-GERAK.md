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
(syncTouch), `public/css/berkah-motion.css` (token sentuh pindah ke ui-kit),
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
| Font | `fonts.bunny.net` (CDN pihak ketiga), Figtree. | Plus Jakarta Sans dari Google Fonts, sama dengan halaman auth lain. |
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
  dengan sebelumnya.
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
