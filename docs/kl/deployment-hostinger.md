# Deployment ke Hostinger — Azka Laundry (SIMALUN)

Panduan langkah-demi-langkah deploy aplikasi ini ke **Hostinger**.
Dokumen ini khusus Hostinger; untuk konsep umum / VPS lihat
[`deployment.md`](deployment.md).

Hostinger punya beberapa jenis layanan, dan caranya beda:

| Layanan | SSH | Composer | Cocok untuk app ini? | Catatan |
|---|---|---|---|---|
| Shared **Premium** | ❌ tidak ada | ❌ | Bisa, tapi repot | Tanpa SSH → harus upload `vendor/` manual |
| Shared **Business** | ✅ ada | ✅ | **Direkomendasikan** | SSH + Composer + Git tersedia |
| **Cloud / VPS** | ✅ root | ✅ | Paling fleksibel | Ikuti `deployment.md` (Nginx) |

> **Rekomendasi:** pakai paket **Business** ke atas supaya ada akses SSH.
> Tanpa SSH, deploy Laravel di shared hosting jauh lebih menyakitkan.

Persyaratan aplikasi: **PHP 8.4** (wajib — `openspout ^5.7` & `symfony/* v8`
di `composer.lock` butuh PHP >= 8.4), MySQL/MariaDB,
ekstensi `mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, bcmath, fileinfo, gd, curl, zip`.

---

## Bagian A — Shared Hosting Business (dengan SSH) — REKOMENDASI

### 1. Siapkan domain & PHP version

1. Login **hPanel** → **Websites** → pilih domain (atau tambah domain/subdomain,
   mis. `app.azkalaundry.com`).
2. hPanel → **Advanced → PHP Configuration** → set versi **PHP 8.4**.
   > **WAJIB 8.4.** Dependency `openspout ^5.7` dan `symfony/* v8`
   > (terkunci di `composer.lock`) mensyaratkan PHP >= 8.4. PHP 8.3 ke
   > bawah akan gagal di `composer install`. Pastikan juga PHP **CLI/SSH**
   > ikut 8.4 (reconnect SSH lalu `php -v`); kalau CLI masih 8.3, jalankan
   > Composer via binary eksplisit, mis. `php8.4 $(which composer) install ...`.
3. Di tab **PHP Extensions**, pastikan aktif: `mbstring`, `pdo_mysql`,
   `openssl`, `gd`, `curl`, `zip`, `fileinfo`, `bcmath`, `intl`.

### 2. Buat database MySQL

1. hPanel → **Databases → MySQL Databases**.
2. Buat database baru, mis. `uXXXXXX_azka`.
3. Buat user database + password kuat, lalu **assign** user ke database
   dengan semua privilege.
4. **Catat**: nama database, username, password, dan host (biasanya
   `localhost`). Dipakai di `.env` nanti.

### 3. Aktifkan SSH

1. hPanel → **Advanced → SSH Access** → aktifkan, catat **IP/host, port, username**.
2. Connect dari komputer kamu:
   ```bash
   ssh -p <port> u123456789@<server-ip>
   ```

### 4. Upload kode

Pilih salah satu:

**Opsi A — Git (paling bersih):** hPanel → **Advanced → Git** → "Create Repository",
masukkan URL repo dan branch `main`, deploy ke folder mis. `domains/azkalaundry.com/repo`.
Atau via SSH manual:
```bash
cd ~/domains/azkalaundry.store
git clone https://github.com/Ardinta19/SIMALUN-FINAL.git app
```

**Opsi B — Upload ZIP:** export project (tanpa `vendor/`, `node_modules/`,
`.env`) → hPanel **File Manager** → upload ke `~/domains/azkalaundry.com/app`
→ extract.

> **Struktur yang dituju:** taruh seluruh aplikasi di luar `public_html`,
> mis. `~/domains/azkalaundry.com/app`, lalu arahkan document root ke
> folder `public` aplikasi (langkah 7). Ini mencegah `.env`, `storage`,
> dan kode sumber terekspos publik.

### 5. Install dependency (via SSH)

Hostinger menyediakan Composer. Cek dulu:
```bash
cd ~/domains/azkalaundry.com/app
php -v          # pastikan 8.2/8.3
composer --version
```
Lalu:
```bash
composer install --no-dev --optimize-autoloader --prefer-dist
```
Kalau perintah `composer` tidak ada, pakai:
```bash
php ~/composer.phar install --no-dev --optimize-autoloader --prefer-dist
# atau unduh: curl -sS https://getcomposer.org/installer | php
```

### 6. Konfigurasi `.env`

```bash
cp .env.production.example .env
nano .env
```
Isi minimal:
```env
APP_NAME="Azka Laundry SIMALUN"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://azkalaundry.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXXXX_azka
DB_USERNAME=uXXXXXX_azka
DB_PASSWORD=__PASSWORD_DB__

# Shared hosting: hindari supervisor. Pakai sync atau cron-based queue.
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# SMTP Hostinger (Email Hostinger) — atau provider lain
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_SCHEME=tls
MAIL_USERNAME=no-reply@azkalaundry.com
MAIL_PASSWORD=__PASSWORD_EMAIL__
MAIL_FROM_ADDRESS="no-reply@azkalaundry.com"
MAIL_FROM_NAME="${APP_NAME}"
```
Generate key + migrate + seed:
```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force --class=UserSeeder
php artisan db:seed --force --class=ServiceCategorySeeder
php artisan storage:link
```

> **Kalau `storage:link` gagal** (symlink dilarang di sebagian shared
> hosting), lihat [Lampiran: storage tanpa symlink](#lampiran-storage-tanpa-symlink).

### 7. Arahkan document root ke folder `public`

Tujuannya: domain melayani dari `app/public`, bukan `public_html`.

**Cara 1 — ubah root domain (kalau hPanel mengizinkan):**
hPanel → **Websites → [domain] → Dashboard → Advanced → "Change Website Root Directory"**
→ set ke `domains/azkalaundry.com/app/public`.

**Cara 2 — symlink `public_html` (umum di Hostinger):**
```bash
cd ~/domains/azkalaundry.com
rm -rf public_html
ln -s app/public public_html
```

**Cara 3 — kalau tidak bisa ubah root & tidak bisa symlink folder:**
salin isi `app/public/*` ke `public_html/`, lalu edit `public_html/index.php`
agar menunjuk ke aplikasi:
```php
require __DIR__.'/../app/vendor/autoload.php';
$app = require_once __DIR__.'/../app/bootstrap/app.php';
```
(Sesuaikan path relatif ke lokasi folder `app` kamu.)

### 8. Permission

```bash
cd ~/domains/azkalaundry.com/app
chmod -R 775 storage bootstrap/cache
```

### 9. Cache produksi

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 10. SSL (HTTPS)

hPanel → **Security → SSL** → pasang **Let's Encrypt gratis** untuk domain.
Setelah aktif, pastikan `APP_URL` pakai `https://` dan
`SESSION_SECURE_COOKIE=true`. Hostinger biasanya auto-redirect HTTP→HTTPS;
kalau tidak, aktifkan "Force HTTPS" di hPanel.

### 11. Queue worker via Cron (pengganti Supervisor)

Notifikasi customer (`OrderStatusUpdated`) di-queue ke driver `database`.
Di shared hosting tidak ada Supervisor, jadi pakai cron tiap menit:

hPanel → **Advanced → Cron Jobs** → tambah:
```
* * * * * cd ~/domains/azkalaundry.com/app && php artisan queue:work --queue=notifications,default --stop-when-empty --max-time=55 >> storage/logs/queue.log 2>&1
```
`--stop-when-empty` membuat worker berhenti setelah antrian habis;
`--max-time=55` memastikan ia tidak menumpuk antar-menit. Latensi
notifikasi jadi maksimal ~1 menit (wajar untuk skala Azka).

> **Alternatif paling sederhana:** kalau tidak mau pusing queue, set
> `QUEUE_CONNECTION=sync` di `.env`. Notifikasi dikirim langsung saat
> request (tanpa cron), tapi update status order jadi sedikit lebih
> lambat karena menunggu kirim email. Untuk volume kecil ini OK.

### 12. Scheduler via Cron

hPanel → **Cron Jobs** → tambah (sekali, tiap menit):
```
* * * * * cd ~/domains/azkalaundry.com/app && php artisan schedule:run >> /dev/null 2>&1
```

### 13. Smoke test

- [ ] Buka `https://azkalaundry.com` → landing render, tidak ada error 500.
- [ ] HTTP auto-redirect ke HTTPS.
- [ ] Login akun admin hasil seeder → masuk dashboard admin.
- [ ] Register customer → auto-login → dashboard customer.
- [ ] Buat order test → sampai halaman sukses.
- [ ] Reset password → email masuk (cek juga folder spam).
- [ ] Upload avatar/bukti foto → file tampil via URL `/storage/...`.
- [ ] `curl -I https://azkalaundry.com` → ada `X-Frame-Options: DENY`,
      `X-Content-Type-Options: nosniff`, dan untuk halaman login user
      (terautentikasi) ada `Cache-Control: no-store` (anti back-button).

---

## Bagian B — Shared Premium (tanpa SSH)

Tanpa SSH, Composer tidak bisa jalan di server. Workflow-nya:

1. **Di komputer lokal**, jalankan:
   ```bash
   composer install --no-dev --optimize-autoloader --prefer-dist
   ```
   sehingga folder `vendor/` terisi lengkap.
2. **Zip seluruh project** termasuk `vendor/` (kecualikan `.git`,
   `node_modules`, `.env`, `tests`).
3. hPanel **File Manager** → upload ke `~/domains/azkalaundry.com/app` → extract.
4. Buat database (Bagian A langkah 2).
5. Buat `.env` lewat **File Manager** (copy dari `.env.production.example`,
   isi nilai). Untuk `APP_KEY`: generate di lokal dengan
   `php artisan key:generate --show` lalu tempel hasilnya ke `.env`.
6. **Migrasi DB tanpa SSH:** opsi termudah —
   - Jalankan `php artisan migrate` di lokal yang terhubung ke DB Hostinger
     (aktifkan **Remote MySQL** di hPanel + whitelist IP kamu), **atau**
   - Export skema dari lokal (`mysqldump`) dan import via **phpMyAdmin**
     di hPanel.
7. **Document root**: pakai File Manager untuk menyalin isi `app/public`
   ke `public_html` (Cara 3 di Bagian A langkah 7) dan sesuaikan
   `index.php`.
8. **Cache**: tanpa SSH, hapus saja `config:cache` (biarkan tidak di-cache),
   atau generate file cache di lokal lalu upload folder `bootstrap/cache`.
9. **Queue**: set `QUEUE_CONNECTION=sync` (tidak butuh cron worker).
10. **Cron scheduler**: tetap bisa lewat hPanel Cron Jobs (langkah 12).

> Premium bisa, tapi setiap update kode = ulang upload `vendor/`. Sangat
> dianjurkan upgrade ke Business untuk SSH + Git.

---

## Deploy update (sudah pernah deploy, Business + SSH)

```bash
cd ~/domains/azkalaundry.com/app
git pull origin main
composer install --no-dev --optimize-autoloader --prefer-dist
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```
Tidak perlu restart apa pun karena queue jalan via cron `--stop-when-empty`
(otomatis pakai kode terbaru di menit berikutnya).

---

## Troubleshooting khusus Hostinger

**500 Internal Server Error setelah deploy**
- Cek `storage/logs/laravel-*.log`.
- Pastikan `APP_KEY` sudah terisi (`php artisan key:generate --force`).
- Pastikan permission `storage` & `bootstrap/cache` = 775.
- Pastikan PHP version di hPanel 8.2/8.3 (bukan 7.x).

**Halaman tampil source code / "index of" / blank**
- Document root belum mengarah ke folder `public` (lihat langkah 7).

**`The stream or file ".../laravel.log" could not be opened`**
- `chmod -R 775 storage` dan pastikan owner benar.

**CSS/JS tidak ter-load**
- Jalankan `npm run build` di lokal, lalu upload folder `public/build`.
  (Aset Vite di-compile saat build, bukan di server.)

**Email reset password masuk spam / tidak terkirim**
- Pakai email domain Hostinger (`no-reply@domainmu`) sebagai `MAIL_FROM_ADDRESS`.
- Set DNS **SPF**, **DKIM**, **DMARC** di hPanel → **Emails → DNS records**.
- Tes: `php artisan tinker` →
  `Mail::raw('tes', fn($m) => $m->to('kamu@gmail.com')->subject('tes'));`

**`php artisan migrate` minta konfirmasi / error koneksi**
- Cek kredensial DB di `.env`. Di Hostinger `DB_HOST` umumnya `localhost`.
- Untuk migrate dari lokal ke DB Hostinger, aktifkan **Remote MySQL**.

**Perubahan kode tidak kebaca**
- `php artisan optimize:clear` lalu re-cache. Config/route/view di-cache
  saat produksi; lupa clear = perubahan tidak muncul.

---

## Lampiran: storage tanpa symlink

Sebagian shared hosting melarang `ln -s`. Kalau `php artisan storage:link`
gagal:

1. Buat folder `public/storage` manual.
2. Pastikan upload disimpan dan diakses lewat path yang konsisten.
3. Cara paling andal di shared hosting: salin/junction. Jalankan via SSH:
   ```bash
   cd ~/domains/azkalaundry.com/app
   ln -s ../storage/app/public public/storage   # coba dulu
   ```
   Kalau tetap gagal, salin berkala isi `storage/app/public` ke
   `public/storage` (kurang ideal), atau pertimbangkan disk S3
   (`FILESYSTEM_DISK=s3`) supaya file tidak bergantung pada symlink.

---

## Ringkasan keputusan cepat

- **Punya paket Business+** → Bagian A. Pakai SSH + Git, queue via cron
  `--stop-when-empty`. Ini jalur paling lancar.
- **Punya paket Premium** → Bagian B. Upload `vendor/`, set
  `QUEUE_CONNECTION=sync`, migrasi via phpMyAdmin/Remote MySQL.
- **Punya VPS/Cloud Hostinger** → abaikan dokumen ini, ikuti
  [`deployment.md`](deployment.md) (Nginx + Supervisor + OPcache).
