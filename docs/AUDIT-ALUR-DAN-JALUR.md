# Audit Alur dan Jalur — Berkah Kreatif (Sewa Papan Bunga)

Tanggal audit: 22 November 2026 · Branch `arena/abd2ecb2-simalun-final` · Dasar commit `a3fe132`

Dokumen ini menjawab satu pertanyaan: **apakah setiap alur yang dipakai pengguna benar-benar
tersambung dari awal sampai akhir, dan apakah setiap jalur (rute) punya penjagaan yang tepat?**

## 1. Ringkasan

| Pemeriksaan | Hasil |
|---|---|
| Statement rute | 168 (167 bernama, 1 tanpa nama) |
| Nama rute ganda / URI ganda | 0 / 0 |
| Aksi controller & metode yang dirujuk rute | semua ada (0 hilang) |
| View yang dirujuk controller | semua ada (0 hilang), 116 berkas Blade |
| Formulir: atribut `action`, metode, `@csrf`, parameter rute | 100% sesuai (0 temuan) |
| Rute admin/driver yang lolos tanpa middleware peran | 0 |
| Halaman tersambung ke navigasi | semua halaman produk sewa; 1 halaman (Panduan admin) sebelumnya yatim — **sudah diperbaiki** |
| Alur bisnis sewa (12 alur) | 11 utuh, 1 utuh tetapi boros (analitik tak dirender) |
| Modul laundry lama | 1 kepulauan tertutup: tidak ada jalan masuk dari UI sewa (hanya lewat URL langsung) |

Kesimpulan singkat: **jalur transaksi sewa tidak punya titik putus.** Semua langkah dari
katalog sampai penutupan unit punya rute, form, penjagaan peran, dan notifikasi. Yang ditemukan
bukan alur yang buntu, melainkan sisa-sisa modul laundry (halaman & endpoint tanpa jalan masuk),
satu halaman bantuan yang tidak tertaut (kini tertaut), satu perhitungan dashboard yang
sia-sia, dan beberapa berkas view yatim.

## 2. Metode dan batas audit

Audit dilakukan **statis** di sandbox (tanpa runtime Laravel, tanpa database):

1. Mengurai `routes/web.php` menjadi tabel rute (verb, URI, aksi, nama, middleware) →
   `/tmp/routes.json`.
2. Memeriksa setiap aksi controller benar-benar ada (kelas + metode) dan setiap `view('...')`
   punya berkas Blade.
3. Menelusuri keterjangkauan: rute → view → `@extends`/`@include`/`<x-...>` → rute yang
   ditautkan, lalu membandingkan dengan daftar rute yang tidak pernah disebut di view mana pun.
4. Memindai **semua** `<form>`: ada `action`, metode cocok dengan verb rute, ada `@csrf`,
   jumlah/urutan parameter rute terpenuhi.
5. Memeriksa penjagaan peran per rute dan penjagaan kepemilikan data di controller
   (`abort_unless`).
6. Memetakan perintah terjadwal (`routes/console.php`) dan memastikan tiap perintah punya
   kelas layanan yang dipanggil.
7. Menyilangkan dengan perangkat tes yang ada (74 berkas, ±345 tes).

**Batas:** tidak ada eksekusi kode. Temuan “berfungsi” di sini berarti *tersambung secara
statis*, bukan hasil uji jalan. Daftar uji manual ada di bagian 7.

## 3. Peta jalur (rute)

| Blok | Baris `routes/web.php` | Middleware | Isi |
|---|---|---|---|
| Publik | 39–46 | – (throttle pada 3 rute) | katalog, ketersediaan, apresiasi publik, keranjang tamu |
| Tamu | 48–75 | `guest` | login, daftar, lupa sandi, reset sandi, Google |
| Bersama (login) | 77–121 | `auth` | dasbor, verifikasi email, konfirmasi sandi, profil, notifikasi, QRIS, ringkasan sewa, bukti, gambar desain |
| Pelanggan — alur sewa | 123–142 | `auth` + `role:customer` | checkout, voucher, pesanan, unit, bayar, ongkos, batal, ubah data, pesan ulang, desain, rating |
| Pelanggan — laundry lama | 144–178 | `auth` + `role:customer` | pesanan laundry, alamat, bantuan, laporan |
| Admin — sewa | 180–228 | `auth` + `role:admin` | verifikasi bayar, desain, jadwal, pesanan, perkembangan, ongkos, konfirmasi, voucher, katalog, universitas, pengaturan bayar, buku kas |
| Admin — laundry lama | 216–262 | `auth` + `role:admin` | pesanan/walk-in, keuangan, voucher laundry, kategori/layanan, laporan, audit, notifikasi, profil, bantuan |
| Kurir (laundry) | 266–291 | `auth` + `role:driver` | dasbor, tugas, lokasi, tracking, notifikasi, bantuan, laporan, profil |
| Dasbor alternatif | 289–291 | `role:*` | `/dashboard/{role}` (duplikat `/admin|driver|customer/dashboard`) |

Distribusi peran: **73 rute admin, 45 pelanggan, 13 kurir, 19 hanya-login, 10 tamu, 8 publik**.

## 4. Alur bisnis dan statusnya

### A. Tamu: katalog → keranjang → masuk → checkout ✅
Tamu bisa menambah/menggandakan/menghapus isi keranjang tanpa akun (`cart.*`, sesi). Keranjang
bertahan setelah masuk karena masih sesi yang sama; `LoginController::redirectByRole()` memakai
`redirect()->intended(...)` sehingga tamu yang menekan *checkout* kembali ke checkout setelah
login. Tidak ada data pengguna yang tersentuh pada rute publik ber-parameter.

### B. Checkout → pesanan ✅
`RentalOrderController@create` memuat ulang katalog dari sesi, menolak produk nonaktif/stok 0,
memuat alamat tersimpan, tanggal penuh, dan memulihkan draf isian. `store()` mengunci stok,
memvalidasi `sha256` keranjang, menerapkan voucher, dan menulis order + unit dalam satu
transaksi. Endpoint cek voucher: `POST /voucher-sewa/cek` (`{code, base?}`).

### C. Konfirmasi admin ✅
Unit `pending` menahan stok sejak dikirim sampai `admin.rental.confirm`. Bila tanggal
penerimaan jatuh pada/di depan batas konfirmasi, form mewajibkan centang
`special_confirmation` (`rental/admin-detail.blade.php:21`). Tanpa tindakan, unit dibatalkan
otomatis oleh `rental:expire-confirmations` (jadwal tiap menit).

### D. Ongkos antar/jemput ✅
Alur: admin kirim penawaran (`rental.transport.quote`) → pelanggan setuju/tolak
(`approveTransport`/`rejectTransport`, cek kepemilikan di baris 336 & 357) → penawaran baru
bila ditolak. Ongkos boleh direvisi selama belum ada pembayaran. Antrean admin tersedia di
filter `transport_quote`/`transport_approval`.

### E. Desain ✅
Admin unggah preview (`rental.design.store`) → pelanggan setuju/revisi (`respond`, cek
kepemilikan baris 54) → tenggat `min(unggah + 48 jam, starts_on − 3 hari)` → bila lewat,
admin boleh melanjutkan dengan catatan ≥10 karakter + konfirmasi (`override`).
Pengerjaan (`waiting → production`) ditahan sampai desain disetujui atau diputuskan admin.

### F. Pembayaran ✅
Pelanggan unggah bukti per unit (`rental.payment.store`, cek kepemilikan baris 20; ditolak bila
tenggat lewat dan tidak dalam masa peninjauan). Admin verifikasi/tolak/koreksi/kembalikan dana;
semua menulis entri buku kas secara idempoten (`FinanceIdempotencyTest`). Bukti hanya bisa
dibuka pemilik atau admin (baris 54). Tenggat H+1 12.00 WIB lewat
`RentalPaymentDeadline::graceDeadline()`, lalu `rental:expire-payments` menandai
`deposit_forfeited_at`; admin bisa melanjutkan (`rental.resume`) sebelum itu.

### G. Perkembangan → serah terima ✅
`RentalProgressController@advance` hanya menerima langkah satu per satu dengan
`expected_progress` (anti klik ganda). `handed_over` mensyaratkan desain disetujui **dan**
pembayaran terverifikasi ≥ tagihan (baris 24–29). Setiap langkah menulis riwayat progres dan
memberi tahu pelanggan.

### H. Pengembalian & penutupan ✅
Admin menandai `returned`; bila terlewat, `rental:close-events` (harian 02.00 WIB) menutup unit
yang `ends_on ≤ hari ini − 7` dan memberi tahu pelanggan + admin.

### I. Rating, suka, bagikan ✅
Rating unit & pesanan hanya setelah unit kembali dan lunas; tautan apresiasi publik memakai
token 64 karakter + throttle; suka katalog hanya untuk pelanggan.

### J. Voucher sewa ✅
Admin membuat voucher dengan cakupan (`rental` / `rental_transport`), nilai, masa berlaku, dan
batas pakai; pelanggan memasukkan kode di checkout (endpoint cek + potongan dihitung ulang di
`store`). Halaman admin tertaut dari *Aksi Cepat* dasbor.

### K. Notifikasi & pengingat ✅
Daftar + tandai semua dibaca + buka (diarahkan sesuai peran dan data notifikasi).
`rental:remind` (tiap 5 menit) mengirim 10 jenis pengingat dengan dedupe per unit+jenis+
penerima+tanggal. Tiga perintah terjadwal terdaftar di `routes/console.php`.

### L. Buku kas & laporan ✅
Entri manual + verifikasi pembayaran otomatis masuk buku kas; ekspor CSV memakai BOM UTF-8 dan
menetralkan formula (`=`, `+`, `-`, `@`); ringkasan sewa dapat dicetak; PDF laundry lama tetap
berfungsi (dompdf terpasang).

### M. Profil & akun ✅
`/profile/edit` memilih view per peran (`ProfileController:26–38`), semua form profil
(multi-part avatar, Ganti kata sandi, Hapus akun, alamat utama) punya `action`, metode, dan
`@csrf` yang benar. Penghapusan akun mempertahankan riwayat transaksi (soft delete).

### N. Autentikasi ⚠️ (satu alur dorman)
Login, daftar, lupa sandi, dan reset sandi utuh (halaman reset memakai
`auth/forgot-password.blade.php` yang berganti mode saat ada `$token`). **Verifikasi email
dorman:** rute & view ada, tetapi `User` tidak `implements MustVerifyEmail`, tidak ada listener
pengiriman email verifikasi, dan tidak ada rute yang memakai middleware `verified` — jadi email
verifikasi tidak pernah terkirim dan tidak pernah diminta. Konfirmasi kata sandi
(`password.confirm`) juga tidak dipakai middleware apa pun.

### O. Pendukung admin ✅
Katalog (produk, kategori, paket), universitas + tarif, jadwal + lembar harian, pengaturan
pembayaran, audit trail, bantuan (kini tertaut dari dasbor dan menu profil).

## 5. Temuan

### Sudah diperbaiki pada commit ini

| # | Temuan | Bukti | Perbaikan |
|---|---|---|---|
| 1 | Halaman **Panduan admin** (`admin.help`) berisi panduan sewa yang lengkap tetapi tidak punya jalan masuk sama sekali | tidak ada `route('admin.help')` di view mana pun | ditautkan dari *Aksi Cepat* dasbor admin dan menu *Pengaturan Operasional* di profil admin; dikunci tes baru `test_admin_navigation_reaches_the_help_guide` |

### Perlu keputusan / dikerjakan berikutnya

| # | Prioritas | Temuan | Bukti | Usulan |
|---|---|---|---|---|
| 2 | Sedang | Dasbor admin menghitung **7 agregat ber-cache** (layanan teratas, pelanggan teratas, rating, ulasan, voucher aktif, dsb.) yang tidak pernah dirender karena partial `_analytics_section` tidak di-`@include` | `DashboardController:87–147`; `roles/admin/dashboard.blade.php:15` hanya menyebut di komentar | Pilih: tampilkan partial-nya, atau hapus perhitungan + sesuaikan `AdminDashboardCacheTest` (saat ini tes mengunci perilaku boros ini) |
| 3 | Sedang | Profil **kurir** memakai tampilan profil pelanggan (judul “AKUN PELANGGAN”, isian alamat) karena view `roles.driver.profile-edit` tidak ada dan controller jatuh ke default | `ProfileController:26–38` | Buat view profil kurir ringkas, atau hentikan tautan edit profil untuk kurir sampai modul kurir dibongkar |
| 4 | Sedang | Dua URL untuk satu halaman dasbor admin: `/admin/dashboard` (`admin.dashboard`) dan `/dashboard/admin` (`dashboard.admin`) | `routes/web.php:214` dan `:289` | Pertahankan satu, arahkan sisanya dengan redirect permanen |
| 5 | Sedang | Endpoint tanpa UI (permintaan langsung tetap mengubah data): `admin.categories.update`, `admin.services.store`, `admin.services.update` | tidak dirujuk form mana pun | Hapus bersama modul laundry |
| 6 | Sedang | Modul laundry = **kepulauan tertutup**: `admin.orders`, `admin.walkin.*`, `admin.vouchers.*`, `admin.categories.*`, `admin.services.*`, `admin.reports*`, `admin.finance*`, `customer.orders/detail/tracking`, `customer.report*`, `order.*`, `driver.tracking`, `driver.report*` saling menautkan tetapi tidak punya jalan masuk dari UI sewa | pemindaian tautan; `admin.vouchers.index`, `admin.categories.index`, `customer.report`, `driver.tracking` nol rujukan | Hapus pada babak “pembongkaran laundry” (ditunda atas permintaan) |
| 7 | Rendah | `customer.notifications.read` (tandai satu dibaca) tidak dipakai; UI memakai tandai-semua + buka | tidak ada rujukan | Hapus rute+metode, atau pakai di daftar notifikasi |
| 8 | Rendah | `GET /confirm-password` tidak dilindungi middleware `password.confirm` di mana pun; `POST /confirm-password` tidak bernama sehingga form memakai nama rute GET | `routes/web.php:91–92`; `auth/confirm-password.blade.php:6` | Beri nama `password.confirm.store` (dan pakai `action` yang benar), atau hapus alur ini |
| 9 | Rendah | Alur verifikasi email dorman (lihat 4-N) | `app/Models/User.php` tanpa `MustVerifyEmail`; `routes/web.php:82–89` | Nyalakan (interface + listener + middleware `verified` pada rute pemesanan) atau hapus rute & view-nya |
| 10 | Rendah | View yatim: `auth/reset-password.blade.php` (malah berisi `route('token')` yang tidak ada), `profile/edit.blade.php` + `profile/partials/*` (3 berkas), `roles/admin/_analytics_section.blade.php`, `welcome.blade.php` | tidak dirujuk rute/controller mana pun | Hapus (aman, tidak dipakai) |
| 11 | Rendah | Halaman tambah/edit alamat masih bertema laundry (`<!DOCTYPE html>` sendiri) sementara daftar alamat sudah bertema katalog | `roles/customer/addresses/create|edit.blade.php` vs `index.blade.php` | Samakan tema saat modul laundry dibongkar |
| 12 | Rendah | `roles/driver/help.blade.php` masih berjudul “Bantuan Kurir – AZKA LAUNDRY” | berkas tersebut | Ikut terhapus bersama modul kurir |

### Yang sudah benar dan sebaiknya tidak diubah

- Penjagaan peran konsisten; tidak ada rute admin/kurir yang bisa dibuka peran lain
  (middleware `role` mengalihkan, bukan 500).
- Kepemilikan data diperiksa di controller untuk semua rute pelanggan ber-parameter
  (`RentalOrderController:206,216,336,357,374,393,430`; `RentalPaymentController:20,54`;
  `RentalDesignController:47,54`; `RentalFeedbackController:19,40,54,76,91`).
- Semua form punya `action`, metode sesuai verb rute, dan `@csrf`; tidak ada form bawaan
  Breeze yang salah sasaran.
- Semua panggilan `route('...')` memenuhi parameter URI (tidak ada `UrlGenerationException`
  statis).
- Idempotensi buku kas, gerbang progres (anti klik ganda), kunci draf checkout, dan
  pengamanan formula CSV sudah ada dan teruji.

## 6. Cakupan tes yang ada

74 berkas tes (±345 tes) mencakup: checkout, draf, stok, ongkos, desain + tenggat, pembayaran +
tenggat + toleransi, koreksi & pengembalian dana, progres, penutupan otomatis, pengingat,
voucher (9 tes), buku kas, jadwal, universitas, katalog, notifikasi, profil, dsb.

Rute tanpa rujukan tes yang layak ditambah nanti: halaman **lembar harian jadwal**
(`admin.rental.schedule.daily-sheet`), ekspor buku kas (sudah ada di `RentalCashBookTest`),
halaman bantuan admin (baru dikunci lewat tes dasbor), dan alur verifikasi email (bila
diaktifkan).

## 7. Uji manual yang disarankan (dijalankan di Windows)

Karena audit ini statis, langkah berikut menutup celah “belum dijalankan”:

1. Tamu: tambah 2 unit → ubah jumlah → hapus → checkout → diminta masuk → keranjang utuh.
2. Checkout: isi 2 unit dengan tanggal berbeda → kode voucher salah (ditolak) → kode benar
   (potongan tampil dan sama dengan nilai akhir setelah simpan).
3. Admin: konfirmasi unit, kirim ongkos, unggah preview; pelanggan setuju ongkos & desain.
4. Pelanggan unggah bukti; admin verifikasi → cek buku kas bertambah sekali saja (klik dua kali).
5. Admin majukan progres sampai `handed_over` (harus ditolak bila belum lunas) → tandai
   kembali → pelanggan memberi rating.
6. Lewatkan tenggat bayar → pastikan unit batal, lalu admin “lanjutkan” sebelum H+1 12.00.
7. Jalankan `php artisan rental:remind` dan `php artisan rental:close-events` → notifikasi
   masuk tanpa duplikat.
8. Admin: buka *Aksi Cepat → Panduan admin* dari dasbor **dan** dari menu profil.
9. Pelanggan: ubah profil, ganti kata sandi, ganti avatar, hapus avatar, set alamat utama.
10. Kurir: buka profil (perhatikan temuan #3).

## 8. Usulan urutan kerja

1. (Selesai) Tautan Panduan admin.
2. Putuskan #2: tampilkan analitik atau hentikan perhitungannya.
3. Rapikan #4 dan #8 (rute ganda, nama rute konfirmasi sandi).
4. Babak pembongkaran modul laundry (#5, #6, #10, #11, #12) — hapus rute, controller, model,
   view, migrasi terkait, beserta tesnya.
5. Putuskan #9 (verifikasi email) dan #3 (profil kurir) sebelum modul kurir dibongkar.
