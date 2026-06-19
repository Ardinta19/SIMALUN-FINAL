# DOKUMEN PENGUJIAN PERANGKAT LUNAK
### Test Case — Modul Halaman Awal & Autentikasi
### Aplikasi SIMALUN (Sistem Manajemen Laundry — Azka Laundry)

---

## 1. Informasi Dokumen

| Atribut | Keterangan |
|---|---|
| Nama Aplikasi | SIMALUN — Azka Laundry |
| Modul yang Diuji | Halaman Awal (Splash) & Autentikasi |
| Jenis Pengujian | Black-box Testing (Manual) |
| Versi Aplikasi | 1.0 |
| Penyusun | Ardinata Saputra |
| NIM | 701240133 |
| Mata Kuliah | MPSI |

---

## 2. Tujuan Pengujian

Dokumen ini disusun untuk memverifikasi bahwa fitur Halaman Awal dan seluruh alur
Autentikasi pada aplikasi SIMALUN berjalan sesuai spesifikasi fungsional, baik pada
kondisi normal (input valid) maupun kondisi tidak normal (input tidak valid dan kasus
batas). Pengujian dilakukan dengan metode black-box, yaitu menilai keluaran sistem
berdasarkan masukan tanpa melihat struktur kode internal.

---

## 3. Ruang Lingkup Pengujian

Pengujian mencakup enam modul berikut:

1. Halaman Awal (Splash / Onboarding)
2. Registrasi Akun
3. Login (Email atau Nomor HP)
4. Login dengan Google
5. Lupa Password
6. Reset Password

Pengujian fitur di luar enam modul tersebut (dashboard, pemesanan, pembayaran, dan
lainnya) berada di luar cakupan dokumen ini.

---

## 4. Lingkungan Pengujian

| Komponen | Spesifikasi |
|---|---|
| URL Aplikasi | https://azkalaundry.store |
| Peramban | Google Chrome (versi terbaru) |
| Perangkat | Desktop dan Mobile (Android / iOS) |
| Koneksi | Internet stabil |
| Akun Uji | Disiapkan terpisah untuk peran customer, driver, dan admin |

---

## 5. Konvensi dan Kategori Pengujian

Setiap kasus uji diberi kode identifikasi unik dengan format `XXX-YN`, dengan ketentuan:
- `XXX` = singkatan modul (mis. REG untuk Registrasi)
- `Y` = kategori (P = Positive, N = Negative, E = Edge)
- `N` = nomor urut

| Kategori | Definisi |
|---|---|
| Positive | Masukan dan alur benar; sistem diharapkan berhasil memproses. |
| Negative | Masukan salah atau tidak lengkap; sistem diharapkan menolak disertai pesan yang jelas. |
| Edge | Kondisi batas atau ekstrem yang menguji ketahanan dan konsistensi sistem. |

Kolom **Hasil Aktual** dan **Status** diisi oleh penguji pada saat eksekusi. Status diisi
dengan **Lulus** atau **Gagal**.

---

## 6. Ringkasan Cakupan Pengujian

| No | Modul | Positive | Negative | Edge | Total |
|---|---|:--:|:--:|:--:|:--:|
| 1 | Halaman Awal (Splash) | 4 | 1 | 3 | 8 |
| 2 | Registrasi | 4 | 11 | 7 | 22 |
| 3 | Login | 5 | 5 | 5 | 15 |
| 4 | Login dengan Google | 2 | 4 | 2 | 8 |
| 5 | Lupa Password | 1 | 2 | 4 | 7 |
| 6 | Reset Password | 1 | 4 | 4 | 9 |
| | **Total** | **17** | **27** | **25** | **69** |

---

## 7. Rincian Kasus Uji

### 7.1 Modul Halaman Awal (Splash / Onboarding)

Halaman pembuka berisi tiga slide pengenalan dengan navigasi geser (swipe) dan tombol
menuju halaman login.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| SPL-P1 | Positive | Aplikasi dibuka pertama kali | Akses halaman utama (`/`) | Tiga slide pengenalan tampil, slide pertama aktif | Tiga slide pengenalan tampil; slide pertama aktif dan indikator titik berada di posisi pertama | Lulus |
| SPL-P2 | Positive | Berada di halaman splash | Geser layar ke kiri/kanan | Slide berpindah dan indikator titik ikut menyesuaikan | Slide berpindah mengikuti geseran; indikator titik menyesuaikan slide aktif | Lulus |
| SPL-P3 | Positive | Berada di slide terakhir | Tekan tombol lanjut hingga akhir | Pengguna diarahkan ke halaman login | Setelah slide terakhir, tombol lanjut mengarahkan ke halaman login (`/login`) | Lulus |
| SPL-P4 | Positive | Berada di halaman splash | Tekan tombol "Lewati" | Pengguna langsung diarahkan ke halaman login | Tombol "Lewati" langsung membuka halaman login | Lulus |
| SPL-N1 | Negative | Pengguna sudah login | Akses kembali halaman `/` | Perilaku sistem tercatat dan konsisten | Pengguna yang sudah login diarahkan otomatis ke dashboard sesuai peran (middleware `guest`); splash tidak tampil lagi | Lulus |
| SPL-E1 | Edge | Perangkat layar pendek (<600px) | Buka splash, gulir ke bawah | Konten dapat digulir, tombol tidak tertutup elemen lain | Konten dapat digulir; tombol tetap terlihat dan tidak tertutup elemen lain | Lulus |
| SPL-E2 | Edge | Berada di halaman splash | Geser cepat berulang-ulang | Tidak terjadi gangguan; slide berhenti pada posisi valid | Geser cepat berulang tidak menimbulkan gangguan; slide berhenti pada posisi valid | Lulus |
| SPL-E3 | Edge | Berada di halaman splash | Putar orientasi layar | Tata letak tetap rapi dan tidak terpotong | Saat orientasi diputar, tata letak tetap rapi dan tidak terpotong | Lulus |

### 7.2 Modul Registrasi

Ketentuan: Nama (minimal 3 karakter), Jenis Kelamin (wajib), Nomor HP (wajib, format
`8xxxxxxxx` sepanjang 9–13 digit, unik, dinormalisasi otomatis dari awalan `+62`/`62`/`0`),
Email (opsional namun harus valid dan unik bila diisi), Password (minimal 8 karakter dan
wajib konfirmasi), Alamat (minimal 10 karakter), serta persetujuan Syarat & Ketentuan.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| REG-P1 | Positive | Halaman registrasi terbuka | Isi seluruh data dengan benar termasuk email | Akun dibuat, pengguna otomatis masuk ke dashboard, alamat utama tersimpan | Akun dibuat; pengguna langsung login ke dashboard customer; alamat utama tersimpan; pesan "Akun berhasil dibuat. Selamat datang di Azka Laundry!" tampil | Lulus |
| REG-P2 | Positive | Halaman registrasi terbuka | Isi data lengkap, kosongkan email | Registrasi berhasil; kolom email kosong | Registrasi berhasil; kolom email tersimpan kosong (null) | Lulus |
| REG-P3 | Positive | Halaman registrasi terbuka | Nomor HP diisi `081234567890` | Nomor dinormalisasi menjadi `81234567890`; berhasil | Nomor dinormalisasi menjadi `81234567890`; registrasi berhasil | Lulus |
| REG-P4 | Positive | Halaman registrasi terbuka | Nomor HP diisi `+6281234567890` | Nomor dinormalisasi menjadi `81234567890`; berhasil | Awalan `+62` dibuang; nomor menjadi `81234567890`; registrasi berhasil | Lulus |
| REG-N1 | Negative | Halaman registrasi terbuka | Nama diisi `Ab` | Ditolak; pesan nama minimal 3 karakter | Ditolak; tampil pesan nama minimal 3 karakter; data tidak tersimpan | Lulus |
| REG-N2 | Negative | Halaman registrasi terbuka | Jenis kelamin tidak dipilih | Ditolak; jenis kelamin wajib dipilih | Ditolak; jenis kelamin wajib dipilih (hanya menerima L/P) | Lulus |
| REG-N3 | Negative | Halaman registrasi terbuka | Nomor HP dikosongkan | Ditolak; "Nomor HP wajib diisi." | Ditolak; tampil "Nomor HP wajib diisi." | Lulus |
| REG-N4 | Negative | Halaman registrasi terbuka | Nomor HP diisi `12345` | Ditolak; "Format nomor HP tidak valid." | Ditolak; tampil "Format nomor HP tidak valid (contoh: 81234567890)." | Lulus |
| REG-N5 | Negative | Terdapat akun dengan HP sama | Daftar dengan nomor HP yang sudah ada | Ditolak; "Nomor HP sudah terdaftar. Silakan login." | Ditolak; tampil "Nomor HP sudah terdaftar. Silakan login." | Lulus |
| REG-N6 | Negative | Terdapat akun dengan email sama | Daftar dengan email yang sudah ada | Ditolak; "Email sudah terdaftar." | Ditolak; tampil "Email sudah terdaftar. Silakan pakai email lain atau kosongkan." | Lulus |
| REG-N7 | Negative | Halaman registrasi terbuka | Email diisi `budi@@mail` | Ditolak; format email tidak valid | Ditolak; format email tidak valid; data tidak tersimpan | Lulus |
| REG-N8 | Negative | Halaman registrasi terbuka | Password diisi `1234` | Ditolak; password minimal 8 karakter | Ditolak; password minimal 8 karakter | Lulus |
| REG-N9 | Negative | Halaman registrasi terbuka | Password dan konfirmasi berbeda | Ditolak; konfirmasi password tidak cocok | Ditolak; konfirmasi password tidak cocok | Lulus |
| REG-N10 | Negative | Halaman registrasi terbuka | Alamat diisi `Jl A` | Ditolak; alamat minimal 10 karakter | Ditolak; alamat minimal 10 karakter | Lulus |
| REG-N11 | Negative | Halaman registrasi terbuka | Syarat & Ketentuan tidak dicentang | Ditolak; "Kamu harus menyetujui syarat & ketentuan." | Ditolak; tampil "Kamu harus menyetujui syarat & ketentuan." | Lulus |
| REG-E1 | Edge | Halaman registrasi terbuka | Nomor HP 9 digit `812345678` | Diterima (memenuhi batas minimum) | Diterima; registrasi berhasil (memenuhi batas minimum 9 digit) | Lulus |
| REG-E2 | Edge | Halaman registrasi terbuka | Nomor HP 13 digit `8123456789012` | Diterima (memenuhi batas maksimum) | Diterima; registrasi berhasil (memenuhi batas maksimum 13 digit) | Lulus |
| REG-E3 | Edge | Halaman registrasi terbuka | Nomor HP 14 digit `81234567890123` | Ditolak (melebihi batas) | Ditolak; nomor melebihi batas 13 digit | Lulus |
| REG-E4 | Edge | Halaman registrasi terbuka | Nama berisi karakter spesial/non-latin | Tersimpan apa adanya; tampilan tetap rapi | Nama tersimpan apa adanya; tampilan tetap rapi tanpa merusak tata letak | Lulus |
| REG-E5 | Edge | Halaman registrasi terbuka | Kirim formulir lebih dari 3 kali dalam 1 menit | Permintaan keempat diblokir (pembatasan laju) | Permintaan ke-4 dalam 1 menit diblokir (rate limit 3/menit) | Lulus |
| REG-E6 | Edge | Halaman registrasi terbuka | Email diisi `BUDI@Mail.com` | Disimpan dalam huruf kecil `budi@mail.com` | Email disimpan sebagai `budi@mail.com` (huruf kecil) | Lulus |
| REG-E7 | Edge | Halaman registrasi terbuka | Nomor HP `0812-3456-7890` | Tanda pemisah dibersihkan menjadi `81234567890`; berhasil | Tanda strip dibersihkan; nomor menjadi `81234567890`; registrasi berhasil | Lulus |

### 7.3 Modul Login

Ketentuan: Identitas masuk dapat berupa Email atau Nomor HP. Email diproses dalam huruf
kecil; Nomor HP dinormalisasi ke format `8xxxxxxxx`. Akun yang dinonaktifkan tidak dapat
masuk meskipun kata sandi benar. Pengguna diarahkan sesuai perannya.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| LOG-P1 | Positive | Akun customer aktif tersedia | Masuk dengan email dan password benar | Berhasil masuk; diarahkan sesuai peran | Berhasil masuk; diarahkan ke dashboard sesuai peran | Lulus |
| LOG-P2 | Positive | Akun aktif tersedia | Masuk dengan nomor HP `081234567890` | Berhasil masuk (nomor dinormalisasi) | Berhasil masuk; nomor dinormalisasi ke `81234567890` | Lulus |
| LOG-P3 | Positive | Akun aktif tersedia | Centang "Ingat saya" lalu masuk | Sesi tetap bertahan setelah peramban ditutup | Cookie "remember" terbentuk; sesi tetap bertahan setelah peramban ditutup | Lulus |
| LOG-P4 | Positive | Akun admin tersedia | Masuk sebagai admin | Diarahkan ke dashboard admin | Diarahkan ke dashboard admin | Lulus |
| LOG-P5 | Positive | Akun driver tersedia | Masuk sebagai driver | Diarahkan ke dashboard driver | Diarahkan ke dashboard driver | Lulus |
| LOG-N1 | Negative | Akun tersedia | Email benar, password salah | Ditolak; "Email/No. HP atau password salah." | Ditolak; tampil "Email/No. HP atau password yang kamu masukkan salah." | Lulus |
| LOG-N2 | Negative | Halaman login terbuka | Kolom identitas dikosongkan | Ditolak; "Email atau No. HP wajib diisi." | Ditolak; tampil "Email atau No. HP wajib diisi" | Lulus |
| LOG-N3 | Negative | Halaman login terbuka | Kolom password dikosongkan | Ditolak; "Password wajib diisi." | Ditolak; tampil "Password wajib diisi" | Lulus |
| LOG-N4 | Negative | Halaman login terbuka | Masuk dengan email tidak terdaftar | Ditolak dengan pesan umum (tidak membocorkan keberadaan akun) | Ditolak dengan pesan umum yang sama seperti password salah; keberadaan akun tidak terbongkar | Lulus |
| LOG-N5 | Negative | Akun dinonaktifkan admin | Masuk dengan kredensial benar | Ditolak; "Akun kamu sedang dinonaktifkan." dan sesi tidak terbentuk | Ditolak; tampil "Akun kamu sedang dinonaktifkan. Silakan hubungi admin."; sesi langsung di-logout (tidak terbentuk) | Lulus |
| LOG-E1 | Edge | Akun tersedia | Masukkan password salah 5 kali berturut | Percobaan keenam diblokir sementara dengan pesan tunggu | Percobaan ke-6 diblokir sementara (rate limit 5/menit) | Lulus |
| LOG-E2 | Edge | Akun tersedia | Email diketik `BUDI@Mail.com` | Tetap dikenali (diproses huruf kecil) | Tetap dikenali; identitas diproses huruf kecil | Lulus |
| LOG-E3 | Edge | Akun tersedia | Masuk dengan format `+62`, `62`, dan `0` | Ketiganya merujuk ke akun yang sama | Ketiga format dinormalisasi ke `8xxxxxxxx` dan merujuk ke akun yang sama | Lulus |
| LOG-E4 | Edge | Pengguna sudah login | Akses kembali halaman login | Perilaku tercatat dan konsisten | Pengguna yang sudah login diarahkan ke dashboard saat membuka `/login` (middleware `guest`) | Lulus |
| LOG-E5 | Edge | Belum login | Akses halaman terproteksi lalu login | Setelah login diarahkan ke halaman tujuan semula | Setelah login diarahkan kembali ke halaman tujuan semula (`redirect()->intended`) | Lulus |

### 7.4 Modul Login dengan Google

Fitur ini bersifat sign-in only: sistem tidak membuat akun baru melalui Google. Akun harus
sudah terdaftar sebelumnya. Pencocokan dilakukan melalui Google ID, lalu email Google yang
telah terverifikasi. Peran pengguna selalu diambil dari basis data.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| GGL-P1 | Positive | Email Google sudah terdaftar di sistem | Masuk melalui Google | Berhasil masuk; Google ID tertaut; diarahkan sesuai peran | Berhasil masuk; `google_id` tertaut ke akun; diarahkan sesuai peran dari DB | Lulus |
| GGL-P2 | Positive | Akun sudah pernah tertaut Google | Masuk melalui Google kembali | Dikenali melalui Google ID; langsung masuk | Dikenali via `google_id`; langsung masuk tanpa diminta daftar | Lulus |
| GGL-N1 | Negative | Email Google belum terdaftar | Masuk melalui Google | Ditolak; "Akun belum terdaftar. Silakan daftar terlebih dahulu." | Ditolak; tampil "Akun dengan email ini belum terdaftar. Silakan daftar terlebih dahulu."; tidak ada akun baru dibuat | Lulus |
| GGL-N2 | Negative | Akun terdaftar namun nonaktif | Masuk melalui Google | Ditolak; "Akun kamu sedang dinonaktifkan." | Ditolak; tampil "Akun kamu sedang dinonaktifkan. Silakan hubungi admin." | Lulus |
| GGL-N3 | Negative | Email Google belum terverifikasi | Masuk melalui Google | Ditolak; "Email Google kamu belum terverifikasi." | Ditolak; tampil "Email Google kamu belum terverifikasi." | Lulus |
| GGL-N4 | Negative | Halaman pemilihan akun Google | Batalkan proses di halaman Google | Kembali ke login; "Gagal masuk dengan Google." | Kembali ke halaman login; tampil "Gagal masuk dengan Google. Silakan coba lagi." | Lulus |
| GGL-E1 | Edge | Konfigurasi OAuth belum sesuai | Masuk melalui Google | Muncul galat dari Google (catatan: konfigurasi, bukan kesalahan aplikasi) | Galat berasal dari halaman Google (masalah konfigurasi OAuth), aplikasi tidak crash | Lulus |
| GGL-E2 | Edge | Email Google sama dengan akun manual | Masuk melalui Google | Tertaut ke akun yang sama; tidak terbentuk akun ganda | `google_id` ditautkan ke akun manual yang ada; tidak terbentuk akun ganda | Lulus |

### 7.5 Modul Lupa Password

Ketentuan: Email wajib diisi dengan format yang valid. Demi keamanan, sistem menampilkan
pesan keberhasilan yang sama meskipun email tidak terdaftar (mencegah penelusuran akun).

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| FGT-P1 | Positive | Email terdaftar dan memiliki email | Kirim permintaan dengan email valid | Tampil pesan tautan reset telah dikirim; email diterima | Tampil pesan tautan reset telah dikirim; email reset diterima | Lulus |
| FGT-N1 | Negative | Halaman lupa password terbuka | Kolom email dikosongkan | Ditolak; email wajib diisi | Ditolak; email wajib diisi | Lulus |
| FGT-N2 | Negative | Halaman lupa password terbuka | Email diisi `budi@@` | Ditolak; format email tidak valid | Ditolak; format email tidak valid | Lulus |
| FGT-E1 | Edge | Email tidak terdaftar | Kirim permintaan dengan email asing | Pesan keberhasilan tetap tampil; tidak ada email terkirim | Pesan keberhasilan tetap tampil; tidak ada email terkirim (anti user-enumeration) | Lulus |
| FGT-E2 | Edge | Halaman lupa password terbuka | Kirim permintaan lebih dari 3 kali dalam 1 menit | Permintaan diblokir (pembatasan laju) | Permintaan ke-4 dalam 1 menit diblokir (rate limit 3/menit) | Lulus |
| FGT-E3 | Edge | Akun terdaftar tanpa email | Kirim permintaan untuk akun tersebut | Tidak ada email terkirim; pesan keberhasilan tetap tampil | Tidak ada email terkirim; pesan keberhasilan tetap tampil | Lulus |
| FGT-E4 | Edge | Email terkirim | Periksa kotak masuk dan folder spam | Idealnya masuk kotak masuk; bila spam, catat sebagai temuan | Email reset diterima di kotak masuk (Gmail SMTP); perlu pemantauan berkala karena bergantung reputasi pengirim | Lulus |

### 7.6 Modul Reset Password

Ketentuan: Token, email, dan password baru (beserta konfirmasi) wajib diisi. Password baru
mengikuti aturan keamanan minimum (sekurang-kurangnya 8 karakter).

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| RST-P1 | Positive | Memiliki tautan reset yang valid | Isi password baru dan konfirmasi yang cocok | Password diperbarui; diarahkan ke login dengan notifikasi berhasil | Password diperbarui; diarahkan ke halaman login dengan notifikasi berhasil | Lulus |
| RST-N1 | Negative | Tautan reset tidak valid | Akses dengan token keliru | Ditolak; token tidak valid | Ditolak; token tidak valid; password tidak berubah | Lulus |
| RST-N2 | Negative | Memiliki tautan reset valid | Password dan konfirmasi berbeda | Ditolak; konfirmasi tidak cocok | Ditolak; konfirmasi password tidak cocok | Lulus |
| RST-N3 | Negative | Memiliki tautan reset valid | Password baru diisi `123` | Ditolak; tidak memenuhi aturan minimum | Ditolak; password tidak memenuhi aturan minimum (8 karakter) | Lulus |
| RST-N4 | Negative | Memiliki tautan reset valid | Email berbeda dari pemilik token | Ditolak | Ditolak; token tidak cocok dengan email | Lulus |
| RST-E1 | Edge | Token sudah digunakan sekali | Gunakan token yang sama kembali | Ditolak; token tidak dapat dipakai ulang | Ditolak; token sudah hangus dan tidak dapat dipakai ulang | Lulus |
| RST-E2 | Edge | Password berhasil direset | Coba login dengan password lama | Gagal; password lama tidak berlaku | Login dengan password lama gagal; hanya password baru yang berlaku | Lulus |
| RST-E3 | Edge | Memiliki tautan reset valid | Kirim lebih dari 5 kali dalam 1 menit | Permintaan diblokir (pembatasan laju) | Permintaan ke-6 dalam 1 menit diblokir (rate limit 5/menit) | Lulus |
| RST-E4 | Edge | Password berhasil direset | Login dengan password baru | Berhasil masuk | Login dengan password baru berhasil | Lulus |

---

## 8. Rekapitulasi Hasil Pengujian

| No | Modul | Jumlah TC | Lulus | Gagal | Persentase Kelulusan |
|---|---|:--:|:--:|:--:|:--:|
| 1 | Halaman Awal (Splash) | 8 | 8 | 0 | 100% |
| 2 | Registrasi | 22 | 22 | 0 | 100% |
| 3 | Login | 15 | 15 | 0 | 100% |
| 4 | Login dengan Google | 8 | 8 | 0 | 100% |
| 5 | Lupa Password | 7 | 7 | 0 | 100% |
| 6 | Reset Password | 9 | 9 | 0 | 100% |
| | **Total** | **69** | **69** | **0** | **100%** |

---

## 9. Kesimpulan dan Rekomendasi

Kesimpulan:

Seluruh 69 kasus uji pada modul Halaman Awal dan Autentikasi berstatus **Lulus** (100%),
mencakup kondisi positive, negative, dan edge. Sistem terbukti memproses masukan valid
dengan benar, menolak masukan tidak valid disertai pesan yang jelas, serta konsisten pada
kondisi batas (panjang nomor HP, normalisasi format, dan pembatasan laju). Mekanisme
keamanan seperti anti user-enumeration pada Lupa Password, gating akun nonaktif, dan
sign-in only pada Login Google berfungsi sesuai rancangan. Dengan demikian modul ini
dinyatakan layak rilis.

Rekomendasi:

1. Pantau pengiriman email reset (FGT-E4) secara berkala; karena memakai SMTP Gmail,
   keterkiriman ke kotak masuk dapat dipengaruhi reputasi pengirim. Pertimbangkan
   konfigurasi SPF/DKIM bila volume bertambah.
2. Lanjutkan penyusunan dokumen test case untuk modul lain di luar cakupan ini
   (pemesanan, penjadwalan driver, tracking status, pembayaran COD, dan laporan keuangan).
3. Pastikan akun uji nonaktif (LOG-N5/GGL-N2) tersedia di lingkungan produksi agar skenario
   gating dapat diverifikasi ulang kapan pun.

---

## Lampiran — Catatan Perilaku yang Disengaja (Bukan Cacat)

1. **Pencegahan penelusuran akun (anti user-enumeration).** Pada fitur Lupa Password,
   sistem sengaja menampilkan pesan keberhasilan yang sama walau email tidak terdaftar,
   sehingga pihak luar tidak dapat menebak email mana yang terdaftar.
2. **Pembatasan laju permintaan (rate limiting).** Endpoint autentikasi membatasi jumlah
   percobaan: Login 5 kali/menit, Registrasi dan Lupa Password 3 kali/menit, serta Reset
   Password 5 kali/menit. Pemblokiran sementara merupakan perilaku yang diharapkan.
