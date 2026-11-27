# SIMALUN — Sistem Manajemen Sewa Papan Bunga Berkah Kreatif

[![CI](https://github.com/Ardinta19/simalun/actions/workflows/ci.yml/badge.svg)](https://github.com/Ardinta19/simalun/actions/workflows/ci.yml)

> Aplikasi web Laravel untuk mengelola penyewaan papan bunga: katalog dan
> ketersediaan per tanggal, pengajuan desain, pembayaran transfer/QRIS,
> ongkos antar–jemput, hingga buku kas dan audit trail admin.

Versi aktif: **v3.0.0**

> **Catatan rilis v3.0.0** — modul laundry (pesanan kain, kasir walk-in,
> keuangan laundry, laporan, kategori/layanan, voucher laundry, dan peran
> kurir) sudah **dicabut total**. Sisa aplikasi hanya modul sewa papan bunga
> dengan dua peran: `customer` dan `admin`. Jika database Anda masih menyimpan
> tabel lama, migrasi `drop_laundry_module_tables` akan membersihkannya.

---

## Tentang Proyek

SIMALUN dibangun oleh tim MPSI Kelompok 2 sebagai studi kasus penerapan SDLC
pada usaha persewaan papan bunga. Sebelumnya pesanan, ongkos antar/jemput,
dan rekap keuangan ditulis manual di buku — sering bentrok ketika dua pesanan
jatuh pada tanggal yang sama, bukti transfer tercecer, dan rekap akhir bulan
menghabiskan waktu.

Aplikasi ini menjawab masalah itu dengan dua peran yang punya alur dan
dasbor sendiri:

- **Customer** — lihat katalog, atur alamat, pesan unit, unggah bukti bayar,
  setujui ongkos antar/jemput, tinjau desain, ajukan perubahan, beri rating.
- **Admin** — konfirmasi pesanan, kirim penawaran ongkos, verifikasi
  pembayaran, unggah preview desain, tandai progres serah terima, catat buku
  kas, pantau audit trail.

Alur satu unit sewa: `pending → confirmed → diproses desain → diserahkan →
dikembalikan`, dengan cabang pembatalan dan tenggat pembayaran yang dijaga
sistem (termasuk masa tenggang H+1 pukul 12.00 WIB).

---

## Fitur Utama

| Area | Cakupan |
|---|---|
| Auth | Login email, registrasi, verifikasi email, reset password, Google login, rate limit, soft delete user |
| Katalog | Produk papan bunga, kategori, paket durasi, stok per tanggal, tarif antar/jemput per universitas |
| Keranjang & checkout | Keranjang sesi, checkout multi-unit dengan tanggal berbeda, idempotency anti-dobel pesan |
| Pesanan sewa | Konfirmasi admin, pembatalan dua arah, resumption, ubah data unit, pesan ulang, ringkasan/struk |
| Pembayaran | Transfer manual + QRIS, verifikasi/tolak admin, koreksi, refund, tenggat bayar & masa tenggang |
| Ongkos | Penawaran antar/jemput oleh admin, persetujuan/penolakan customer, riwayat penawaran |
| Desain | Upload preview per unit, revisi, persetujuan atau penolakan customer |
| Voucher sewa | Voucher persen/nominal, batas pemakaian per customer, cakupan ongkos |
| Keuangan | Buku kas manual + otomatis dari pembayaran sewa, ekspor CSV |
| Umpan balik | Rating produk & pesanan, suka produk, bagikan suka pesanan |
| Operasional | Jadwal sewa harian, buku kas, audit trail admin, notifikasi database |
| Keamanan | `SanitizeInput`, `RoleMiddleware`, throttling, `lockForUpdate` pada transaksi kritis, audit trail perubahan data |

---

## Tech Stack

- **Backend** — PHP 8.2+, Laravel 12, MySQL 8 (atau SQLite untuk pengembangan cepat)
- **Frontend** — Blade + CSS/JS tulis tangan di `public/css` dan `public/js`
  (tanpa langkah build). Rangka Vite 7 + Tailwind 3 + Alpine tersedia di
  `resources/` untuk migrasi bertahap, tetapi belum dipakai satu view pun —
  lihat catatan di bagian *Setup* di bawah.
- **Tooling** — Laravel Pint, PHPUnit 11, Laravel Pail

---

## Setup Pengembangan

### Prasyarat

- PHP 8.2 atau lebih baru beserta ekstensi standar (`mbstring`, `xml`,
  `pdo_mysql` / `pdo_sqlite`, `gd` untuk avatar)
- Composer 2.x (Node.js 20.x + npm hanya bila mau memakai rangka Vite di `resources/`)
- MySQL 8 atau SQLite 3.40+

### Langkah Singkat

```bash
git clone https://github.com/Ardinta19/SIMALUN-FINAL.git
cd SIMALUN-FINAL

# install composer + copy .env + key:generate + migrate + storage:link
composer setup

# Data awal: akun demo customer & admin
php artisan db:seed
```

Untuk dev sehari-hari pakai shortcut yang sudah disediakan — menjalankan
`php artisan serve`, queue listener, dan log viewer (Pail) secara paralel:

```bash
composer dev
```

Tidak ada langkah `npm install`/`npm run build` yang wajib: seluruh halaman
memuat berkas CSS dan JS dari `public/` (`asset('css/…')`, `asset('js/…')`),
bukan dari `public/build`. `composer setup` karena itu berhenti di
`storage:link`. Kalau nanti ada halaman yang dipindah ke Vite (`@vite`),
jalankan `npm install && npm run build` dan kembalikan langkah itu ke
`composer setup`.

`composer setup` sudah menjalankan `php artisan storage:link`, jadi gambar
produk, item tambahan, dan avatar yang diunggah lewat panel admin langsung
tampil. Kalau folder `public/storage` hilang (misalnya setelah zip ulang atau
deploy baru), jalankan sekali:

```bash
php artisan storage:link
```

Buka `http://localhost:8000`.

### Pakai SQLite (tanpa MySQL)

```env
DB_CONNECTION=sqlite
# kosongkan/komentari DB_HOST, DB_DATABASE, dst.
```

```bash
touch database/database.sqlite
php artisan migrate --seed
```

### Akun Demo

Setelah `db:seed`:

| Role | Email | Password |
|---|---|---|
| Customer | customer@test.com | password123 |
| Admin | admin@test.com | password123 |

> Akun demo di atas **hanya dibuat di lingkungan `local` dan `testing`**.
> Di server produksi `db:seed` melewati `UserSeeder` (menulis peringatan di
> terminal), karena sandi `password123` tertulis di repositori dan tidak
> layak dipakai di dunia nyata. Akun sungguhan dibuat lewat
> `php artisan user:create` (tidak ada pendaftaran admin publik); untuk
> pelanggan, pendaftaran biasa lewat `/register`.
>
> Pelanggan yang akunnya pernah dihapus tidak dapat mendaftar ulang dengan
> nomor HP/email yang sama (penghapusan menyimpan riwayat pesanan). Pulihkan
> akunnya dengan `php artisan user:restore`, lalu masukkan nomor HP atau email.

---

## Struktur Folder Penting

```
app/
├── Http/Controllers/         # Rental*Controller (katalog, pesanan, pembayaran, desain, buku kas, ...)
├── Http/Middleware/          # RoleMiddleware, SanitizeInput
├── Models/                   # RentalOrder, RentalOrderUnit, RentalProduct, RentalPayment, FinanceEntry, Voucher, ...
├── Notifications/            # RentalUpdate (+ Auth/ResetPasswordNotification)
├── Services/                 # Aturan bisnis sewa (RentalVouchers, RentalPayments, RentalStock, ...)
├── Support/                  # Laundry (branding usaha), AdminNav, Audit, WhatsApp
└── View/Components/          # AppLayout, GuestLayout, BackButton

config/laundry.php            # Single source of truth info usaha (nama, alamat, WA, jam)
database/migrations/          # Skema users, alamat, voucher, buku kas, rental_*, audit
database/seeders/             # UserSeeder

resources/views/
├── auth/                     # login, register, verify-email, reset-password
├── rental/                   # halaman sewa (katalog, checkout, unit, jadwal, buku kas, ...)
├── roles/                    # admin/, customer/ — view per peran
└── layouts/                  # app, guest, admin shell

routes/web.php                # Rute dikelompokkan per middleware role
tests/Feature/                # Alur utama sewa: checkout, bayar, ongkos, desain, voucher, UI
docs/                         # Dokumen analisis dan audit
```

---

## Menyiapkan Server Produksi

`.env.example` disetel untuk pengembangan lokal, jadi ada beberapa nilai
yang **wajib** diganti sebelum dipasang:

| Kunci | Nilai produksi | Kenapa |
| --- | --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | `true` menampilkan jejak kode dan isi `.env` di halaman galat. |
| `APP_URL` | `https://domain-anda` | Dipakai menyusun tautan di surel dan `sitemap.xml`. |
| `SESSION_SECURE_COOKIE` | `true` | Cookie sesi tidak dikirim lewat koneksi non-HTTPS. |
| `MAIL_MAILER` | `smtp` (+ `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`) | Bawaan `log` hanya menulis surel ke `storage/logs`, sehingga tautan "Lupa sandi" tidak pernah sampai. |
| `MAIL_FROM_ADDRESS` | alamat milik domain sendiri | Alamat contoh akan masuk spam atau ditolak penerima. |
| `QUEUE_CONNECTION` | `sync` (boleh tetap `database`) | Belum ada pekerjaan yang diantrekan: notifikasi tersimpan di tabel `notifications` dan surel reset sandi dikirim langsung. `php artisan queue:work` baru diperlukan kalau nanti ada job ber-`ShouldQueue`. |
| `TRUSTED_PROXIES` | IP proxy hosting (bukan `*`) | `*` membuat pembatasan percobaan masuk mudah dilewati dengan memalsukan header IP. |

Setelah `.env` final:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan storage:link
php artisan user:create      # akun admin pertama
```

### Penjadwal WAJIB berjalan

Empat perintah terjadwal (`rental:expire-confirmations`, `rental:expire-payments`,
`rental:remind`, `rental:close-events`) membatalkan unit yang lewat batas
konfirmasi, menghanguskan DP yang telat dibayar, mengirim pengingat, dan
menutup unit yang tidak ditandai kembali. Tanpa penjadwal, aplikasi tetap
tampak normal tetapi tenggat tidak pernah ditegakkan.

Linux/cPanel (crontab):

```cron
* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1
```

Windows (Task Scheduler, setiap 1 menit):

```
php C:\path\ke\proyek\artisan schedule:run
```

Saat pengembangan lokal cukup `php artisan schedule:work`. Setiap tugas
memakai `withoutOverlapping(5)` (batas menit sesuai frekuensi tugas) dan `onOneServer()`, yang bergantung
pada cache store bersama (`CACHE_STORE=database`, `file`, atau `array` —
semuanya mendukung lock; driver `null` tidak).

---

## Pengujian

```bash
php artisan test
```

Cakupan saat ini: autentikasi, katalog & ketersediaan, checkout, pembayaran
dan tenggat, ongkos antar/jemput, desain, voucher, buku kas, notifikasi,
serta kontrak UI (kontras, landmark, navigasi per peran). Test memakai
`RefreshDatabase` sehingga aman dijalankan berulang.

Untuk cek code style sebelum commit:

```bash
./vendor/bin/pint --test
```

---

## Lisensi

Proyek akademik MPSI Kelompok 2 — Universitas Jambi.
