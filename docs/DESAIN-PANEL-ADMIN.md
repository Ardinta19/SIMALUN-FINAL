# Desain Panel Admin — Berkah Kreatif

Tanggal: 22 November 2026 · Branch `arena/abd2ecb2-simalun-final`
Status: **statis, belum dijalankan di runtime Laravel** (sandbox tanpa `vendor/` & database).
Semua tes di bawah ini dijalankan sebagai pemeriksaan *teks/struktur*, bukan render.

---

## 1. Tujuan

Satu panel admin untuk **satu alur kerja penuh**: pesanan sewa masuk → dikonfirmasi →
ongkos → desain → pembayaran → pengerjaan → serah terima → pengembalian → uang tercatat.
Sebelumnya halaman admin tersebar di dua bahasa desain yang berbeda (halaman `rental/*`
memakai `catalog.layout`, halaman `roles/admin/*` memakai dokumen sendiri). Sekarang
halaman transaksi memakai **satu shell**; halaman lama yang belum diseragamkan tetap
dapat dijangkau dan ditandai jelas.

## 2. Berkas baru

| Berkas | Isi |
|---|---|
| `resources/views/layouts/admin.blade.php` | Shell admin: `<head>`, token, sidebar, topbar, isi, toast, skrip |
| `resources/views/layouts/component/admin/_sidebar.blade.php` | Merek, menu, kartu "mode uji coba", identitas admin |
| `resources/views/layouts/component/admin/_nav.blade.php` | Daftar menu berkelompok + penanda halaman aktif |
| `resources/views/layouts/component/admin/_topbar.blade.php` | Tombol menu (HP), remah roti, pintasan, avatar |
| `public/css/berkah-admin.css` | 19 bagian: token → kerangka → komponen → gerak → responsif → preferensi |
| `public/js/berkah-admin.js` | 7 perilaku: reveal, hitung angka, bar, drawer, toast, topbar, keadaan memuat |

Shell **memuat aset yang sudah ada**, tidak membuat pustaka baru:
`_theme_head` (tema gelap + UI kit), `_ui_kit` (tombol tema), `_form_controls`
(select/tanggal/bulan/stepper + GSAP lokal), `_icon` (Lucide), `page-transitions.css/js`.

## 3. Token desain

Seluruh warna, radius, bayangan, dan durasi gerak berasal dari `--adm-*` pada `:root` dan
`:root[data-theme="dark"]` (berkah-admin.css bagian 1). Tidak ada nilai warna liar di
halaman: setiap permukaan memakai `var(--adm-surface*)`, garis `var(--adm-line*)`,
teks `var(--adm-ink*)`, aksen `var(--adm-wine)`/`--adm-rose`.

Aturan keras: **semua aturan CSS admin di-scope** ke `.adm-app`, `.adm-*`, `html`, atau
`:root`. Diperiksa otomatis: 0 selektor telanjang (`body`, `a`, `input`) sehingga memuat
berkas ini di halaman lama tidak mengubah tampilannya.

## 4. Komponen

| Kelas | Guna |
|---|---|
| `.adm-page-head`, `.adm-kicker`, `.adm-page-sub` | Kepala halaman + konteks |
| `.adm-card`, `--flat`, `--pad-0`, `__head`, `__foot` | Panel |
| `.adm-grid`, `--auto`, `--wide` | Kolom adaptif |
| `.adm-stat`, `__label`, `__value`, `__hint` | Angka ringkas |
| `.adm-badge` + `--confirmed/pending/cancelled/info/warn/danger/live` | Status |
| `.adm-chip`, `.adm-seg`, `.adm-actions` | Label, pemilih, baris tombol |
| `.adm-table` (+ `__num`, `__wrap`) | Tabel data; di HP berubah jadi kartu berlabel |
| `.adm-field`, `.adm-field-grid`, `.adm-switch`, `.adm-toolbar` | Formulir & filter |
| `.adm-note` + `--danger/--warn/--ok/--info`, `.adm-help` | Catatan |
| `.adm-empty`, `.adm-skeleton` | Kosong & pemuatan |
| `.adm-timeline`, `__item` + `is-done/is-current`, `__time`, `__title`, `__note` | Riwayat & tahapan |
| `.adm-drawer`, `.adm-backdrop`, `.adm-toasts`, `.adm-toast` | Lapisan mengambang |
| `.adm-btn` + `--primary/--ghost/--danger/--sm` | Tombol admin |

Halaman detail pesanan memakai ulang komponen ini untuk seluruh aksi yang sudah ada
(konfirmasi, penawaran ongkos, desain, verifikasi/tolak/koreksi/refund pembayaran,
selesaikan tinjauan, batalkan, lanjutkan, majukan progres) — nama field, rute, dan teks
tidak berubah, hanya wadahnya.

## 5. Inventaris animasi

Semua efek hanya menyentuh `transform`, `opacity`, dan `filter`; tidak ada animasi
`width/height/top/left` yang memicu tata letak ulang.

| Efek | Pemicu | Berkas |
|---|---|---|
| Reveal bertahap (IntersectionObserver, indeks `--adm-i` maks 12) | `data-adm-reveal="grup"` | JS §1 + CSS §13 |
| Hitung angka naik (GSAP, cadangan tanpa animasi) | `data-adm-count` + `data-to` | JS §2 |
| Bar kemajuan tumbuh | `data-adm-progress="0…100"` | JS §3 |
| Drawer & sidebar geser, fokus terkurung, Esc, klik latar | `data-adm-nav-toggle` | JS §4 |
| Toast masuk/keluar + bar waktu, jeda saat hover/fokus | `data-adm-toast` + `data-adm-life` | JS §5 |
| Topbar berubah saat digulir; pintasan `/` ke pencarian | `.adm-topbar`, `[data-adm-search]` | JS §6 |
| Tombol kirim terkunci + spinner saat form berjalan | `form` + tombol submit | JS §7 |
| Chevron `<details>` berputar | `details.adm-card` | CSS §18 |
| Denyut halus badge "live" | `.adm-badge--live` | CSS §11 |
| Transisi antarhalaman (View Transition API) | `@view-transition` | page-transitions.css |
| Sidebar & topbar **diam** saat halaman berganti | `view-transition-name` | CSS §19 |
| Gulir halus untuk tautan "Langsung ke isi" | `html` | CSS §19 |
| Splash & wobble halaman galat | `errors/layout` | inline |

Penjaga:
1. **Gerak minimal** — `@media (prefers-reduced-motion: reduce)` mematikan seluruh
   animasi & transisi admin (`animation: none !important`), memaksa elemen reveal
   terlihat, dan membatalkan `transform` saat hover/tekan.
2. **Tanpa JavaScript** — keadaan tersembunyi hanya aktif bila `html.adm-js`; skrip
   terpasang `defer`, jadi konten tidak pernah hilang bila skrip gagal.
3. **Kontras tinggi** — `@media (prefers-contrast: more)` menebalkan garis.
4. **Cetak** — bagian 15 membuang sidebar/topbar dan memaksa kontras hitam-putih.

## 6. Alur admin dari awal sampai akhir

| # | Momen | Halaman | Aksi admin | Umpan balik di layar |
|---|---|---|---|---|
| 1 | Masuk | `login` | — | splash merek, transisi ke dasbor |
| 2 | Orientasi | `dashboard.admin` | melihat antrean "Perlu perhatian" | angka berhitung, tautan langsung ke antrean |
| 3 | Pesanan masuk | `admin.rental.orders` | cari/filter status, tanggal, pelanggan | tabel jadi kartu di HP, badge status |
| 4 | Konfirmasi | `…orders.show` | setujui (termasuk *konfirmasi khusus*) / batalkan | toast sukses, badge berubah, audit tercatat |
| 5 | Ongkos | detail unit | kirim penawaran, lihat versi, tunggu persetujuan | badge "menunggu persetujuan ongkos versi n" |
| 6 | Desain | detail unit | unggah preview, tetapkan versi, *override* setelah tenggat | timeline versi + tenggat |
| 7 | Pembayaran | detail unit | verifikasi, tolak, koreksi, refund, selesaikan tinjauan | ringkasan tagihan, tenggat & toleransi |
| 8 | Pengerjaan | detail unit | majukan tahap (`waiting → production → ready`) | timeline bertahap, catatan tercatat |
| 9 | Serah terima | detail unit | tandai diserahkan | timeline + status pesanan |
| 10 | Pengembalian | detail unit / `rental:close-events` | tandai dikembalikan | antrean "terlambat kembali" kosong |
| 11 | Uang | `admin.rental.cashbook.index` | catat entri manual, edit, ekspor CSV | total berhitung, entri nonmanual terkunci |
| 12 | Jual | `admin.catalog.index`, `…universities.index`, `…vouchers.index` | kelola papan, tarif antar/jemput, voucher | kartu ringkas + bar kuota voucher |
| 13 | Terima uang | `admin.rental.payment-settings.edit` | atur QRIS, nomor WhatsApp, petunjuk | pratinjau QRIS |
| 14 | Awasi | `admin.notifications`, `admin.audit.index` | baca notifikasi, telusuri jejak | badge jumlah belum dibaca |
| 15 | Bantu diri | `admin.help` | panduan langkah demi langkah | tertaut dari sidebar & topbar |

Jadwal (`admin.rental.schedule`) dan lembar serah terima harian
(`…schedule.daily-sheet`) menjadi jalur pendamping langkah 8–9: admin melihat hari
penerimaan/pengembalian, alamat antar/jemput, lalu mencetak lembar untuk dibawa ke meja.

## 7. Peta halaman

**Sudah memakai shell (`layouts.admin`)** — 10 halaman:
`rental/admin`, `rental/admin-detail`, `rental/schedule`, `rental/cashbook`,
`rental/vouchers`, `rental/universities`, `rental/payment-settings`, `catalog/admin`
(plus partial `cashbook-fields`, `product-fields`, `admin-progress`,
`admin-cancellation-history`).

**Halaman mandiri yang kini ikut memuat gerak & navigasi penuh** — dasbor, profil,
ubah profil, notifikasi, audit, pesanan laundry, walk-in, keuangan, laporan, kategori,
voucher laundry, laporan/create. Kesebelas halaman ini memakai bottom-nav bersama
`_navbar_admin` yang sekarang menghitung halaman aktif dari rute (bukan parameter yang
bisa salah) dan memuat ikon dari `_icon`.

**Belum diseragamkan**: halaman laundry di atas tetap memakai tata letak lamanya
(desain penuh ditunda bersama rencana penghapusan modul). Sidebar menandainya sebagai
kelompok **"Modul laundry lama"** agar tidak ada halaman yatim.

## 8. Yang diperbaiki pada babak ini (detail kecil)

1. Sidebar lama hanya punya 3 tujuan; menu shell kini **17 tujuan berkelompok**.
   Diperiksa otomatis: 26 dari 27 halaman admin (GET) punya tautan masuk dari UI;
   satu-satunya yang tidak adalah `admin.dashboard`, yaitu URL kembar dari
   `dashboard.admin`. Sebelumnya modul laundry (pesanan, walk-in, keuangan, laporan,
   kategori, voucher) adalah kepulauan tanpa jalan masuk — kini ada di kelompok
   "Modul laundry lama", dan halaman profil admin mendapat tautan Notifikasi,
   Audit trail, serta **Ubah profil** (sebelumnya `profile.edit` hanya tertaut dari
   profil pelanggan).
2. `_topbar` memakai `@hasSection/@else/@endif` yang membuat pemindai blok ragu;
   diganti `@if (View::hasSection(...))` — semantik sama, statis bersih.
3. `object-fit:contain` pada bingkai foto katalog admin dipulihkan tanpa spasi
   (kontrak `RentalCatalogTest`).
4. Sidebar memakai `min-height: 100vh` + `100dvh` (cadangan lebih dulu) supaya tidak
   terpotong di WebView lama.
5. `rental/progress` & `rental/cancellation-history` yang dipakai **halaman pelanggan**
   dikembalikan ke versi asli; versi admin dipisah ke `admin-progress` dan
   `admin-cancellation-history` agar pelanggan tidak menerima kelas admin.
6. Formulir kategori katalog: tombol simpan nama & tombol aktif dipisah menjadi dua
   form (sebelumnya memakai `form="…"` yang berisiko salah target).
7. ID field kini unik per unit/produk (`cash-*-{{ id ?? 'new' }}`,
   `product-{{ id ?? 'new' }}`) sehingga label `<label for>` tidak menunjuk elemen lain.
8. Tautan `class="bk-back"` dipulihkan di `rental/schedule`, `rental/payment-settings`,
   `rental/universities`, `rental/admin-detail`, dan `rental/vouchers` (kontrak
   `UiKitTest::test_secondary_actions_use_the_design_system`), lengkap dengan gaya
   `.adm-app .bk-back` (berkah-admin.css bagian 20) karena `berkah-experience.css`
   tidak dimuat di panel admin.
9. Dasbor admin memuat **kedua** tautan aksi cepat: Voucher **dan** Panduan admin
   (`RentalAdminDashboardTest::test_admin_navigation_reaches_the_help_guide`).

## 9. Pemeriksaan statis yang dijalankan

| Pemeriksaan | Hasil |
|---|---|
| `blade-scan.py` (122 view) | 0 direktif menempel kata, 0 blok tidak seimbang |
| `tagscan.php` | `img_bad=0 a_bad=0 lang_bad=0 zoom=0 azka=0` |
| `form_scan.py` | 0 form POST tanpa `action`/`@csrf` |
| Nama rute di 16 berkas admin | 100% ada di `routes/web.php` (167 nama) |
| Nama field & atribut `action` baru vs `HEAD` | identik (0 field hilang/berlebih) |
| Variabel & metode model vs `HEAD` | tidak ada nama karangan (hanya `$unit->id`, `$month->copy()`, `->total()`, `->count()`, `->isEmpty()`) |
| Kelas `adm-*` dipakai vs didefinisikan | 0 hilang |
| `animation:` menunjuk `@keyframes` | semua terdefinisi |
| `node --check berkah-admin.js` | lolos |
| `dvh` tanpa cadangan `vh` | 0 |

## 10. Yang belum diuji (jujur)

1. **Render nyata** — sandbox tidak punya `vendor/`, jadi Blade belum pernah dieksekusi
   dan PHPUnit belum dijalankan di sini.
2. **Tampilan** — kontras, jarak, dan animasi hanya diperiksa sebagai kode, belum dilihat
   mata di peramban.
3. **Tes yang perlu dijalankan pemilik** di Windows (`D:\download\berkah-preview`):

```bash
php artisan optimize:clear
php artisan test --filter="RentalCatalogTest|UIkitTest|UiKitTest|UiDefaultsTest|ResponsiveLayoutTest|UiAccessibilityTest|RentalCashBookTest|RentalScheduleTest|RentalAdminSearchTest|RentalWaitingQueuesTest|RentalVoucherTest|RentalAdminDashboardTest"
```

4. **Sudah diverifikasi pemilik:** `php artisan test` pada commit `8ad9d38` = **346 lulus, 2.773 asersi**.
5. **Jika tampilan tidak berubah setelah `git pull`:** jalankan `php artisan view:clear` (atau `optimize:clear`). Blade hanya mengompilasi ulang bila mtime sumber lebih baru dari berkas di `storage/framework/views`; berkas hasil sinkronisasi bisa membawa mtime lama sehingga versi lama terus dipakai.
6. **Kemungkinan kegagalan yang paling perlu dicurigai**:
   `RentalCatalogTest` (frasa `object-fit:contain`, `Foto Papan Berfoto`, `Belum ada foto`),
   `RentalScheduleTest` (`substr_count('Mawar unik') === 1`), dan
   `RentalCashBookTest` (nominal `12.000/4.000/16.000`).
