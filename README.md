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
- **Frontend** — Blade, TailwindCSS 3, AlpineJS, GSAP (transisi halaman & micro-interaction)
- **Build** — Vite 7
- **Tooling** — Laravel Pint, PHPUnit 11, Laravel Pail

---

## Setup Pengembangan

### Prasyarat

- PHP 8.2 atau lebih baru beserta ekstensi standar (`mbstring`, `xml`,
  `pdo_mysql` / `pdo_sqlite`, `gd` untuk avatar)
- Composer 2.x, Node.js 20.x + npm
- MySQL 8 atau SQLite 3.40+

### Langkah Singkat

```bash
git clone https://github.com/Ardinta19/SIMALUN-FINAL.git
cd SIMALUN-FINAL

# install composer + copy .env + key:generate + migrate + npm install + build
composer setup

# Data awal: akun demo customer & admin
php artisan db:seed
```

Untuk dev sehari-hari pakai shortcut yang sudah disediakan — menjalankan
`php artisan serve`, queue listener, log viewer, dan Vite secara paralel:

```bash
composer dev
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

> Ganti password demo sebelum deploy. Akun admin tambahan dibuat lewat
> `php artisan user:create` (tidak ada pendaftaran admin publik).

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
