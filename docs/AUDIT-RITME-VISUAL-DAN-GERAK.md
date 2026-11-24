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
| Pill nav / liquid glass / pill ringkas saat digulir | `berkah-experience.css` `.bk-header`, `.bk-header.is-compact`, `berkah-experience.js` (`scrollY > 180`) | Kaca + blur, pil mengambang, keadaan ringkas menahan tombol tema |
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

Catatan: `_radial_header.blade.php` kini tidak di-`@include` di mana pun —
halaman katalog memakai `catalog/customer-navigation.blade.php` dan halaman
pelanggan memakai header biasa. Berkasnya dibiarkan karena hanya membungkus
partial yang sama, jadi tidak ada rintangan versi bila dipakai lagi. Karena
busur kedua mungkin ada, panel tetap memakai satu `id="bk-radial-panel"`
(selektor teruji) — satu halaman praktiknya hanya merender satu busur.

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
