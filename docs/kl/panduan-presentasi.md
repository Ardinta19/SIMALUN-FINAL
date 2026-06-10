# Panduan Presentasi Project — Azka Laundry (SIMALUN)

Panduan ini untuk kamu yang **belum pernah presentasi project**. Isinya:
apa yang harus **ditampilkan di layar** dan **kalimat yang kamu ucapkan**
(hampir kata-per-kata). Latih 2–3 kali sebelum hari-H sampai lancar.

Format penanda:
- 🖥️ **TAMPILKAN** = yang muncul di layar / yang kamu klik
- 🗣️ **KATAKAN** = kalimat yang kamu ucapkan (boleh kamu ubah dengan gaya sendiri)
- 💡 **TIPS** = catatan penyaji

Total durasi target: **15–20 menit** + tanya jawab.

---

## BAGIAN 0 — Persiapan Sebelum Mulai (lakukan H-1 dan 30 menit sebelum)

### Siapkan data & aplikasi
Jalankan di terminal (folder project):
```bash
php artisan migrate:fresh --seed
php artisan serve
```
- `migrate:fresh --seed` mengisi database dengan data demo (akun + layanan).
- `php artisan serve` menjalankan aplikasi di `http://127.0.0.1:8000`.

> ⚠️ `migrate:fresh` MENGHAPUS semua data lama. Jalankan hanya untuk
> persiapan demo, bukan di data asli.

### Akun demo (semua password: `password123`)
| Peran | Email | No. HP |
|---|---|---|
| Customer | `customer@test.com` | `081234567890` |
| Admin | `admin@test.com` | `081234567891` |
| Driver | `driver@test.com` | `081234567892` |
| Driver 2 | `driver2@test.com` | `081234567893` |

> Login bisa pakai email **atau** nomor HP. Untuk demo, pakai email biar mudah diingat.

### Layanan yang sudah ada (untuk demo pesanan)
- Cuci Saja — Rp5.000/kg
- Cuci + Setrika — Rp7.000/kg
- Express 1 Hari — Rp10.000/kg
- Jas/Kemeja — Rp20.000/item, dan beberapa Bedcover

### Siapkan browser (PENTING biar transisi mulus)
Buka **3 jendela browser terpisah** (atau 1 normal + 2 mode Incognito/Private),
masing-masing login sebagai peran berbeda:
- Jendela 1 → login **Customer**
- Jendela 2 → login **Admin**
- Jendela 3 → login **Driver**

> 💡 Kenapa jendela terpisah? Karena satu browser hanya bisa login satu
> peran. Dengan 3 jendela, kamu tinggal pindah jendela saat demo alur
> antar-peran, tanpa logout-login berulang (yang makan waktu & bikin gugup).

### Checklist terakhir
- [ ] Laptop dicas / colok charger
- [ ] Matikan notifikasi (WhatsApp Desktop, email) biar tidak muncul saat share screen
- [ ] Zoom browser 100–110% biar tulisan kebaca penonton
- [ ] Tutup tab yang tidak perlu
- [ ] Siapkan 1 foto di laptop untuk demo "upload bukti" driver

---

## BAGIAN 1 — Pembuka (1–2 menit)

🖥️ **TAMPILKAN:** Slide judul / atau halaman welcome aplikasi.

🗣️ **KATAKAN:**
> "Selamat pagi/siang Bapak/Ibu penguji. Perkenalkan, saya [nama], dan
> hari ini saya akan mempresentasikan project saya: **SIMALUN — Sistem
> Informasi Manajemen Laundry**, sebuah aplikasi web untuk Azka Laundry.
>
> Saya akan mulai dari latar belakang masalahnya, lalu menunjukkan
> solusinya lewat demo langsung, kemudian membahas sisi teknis dan
> keamanannya, dan ditutup dengan rencana pengembangan ke depan."

💡 **TIPS:** Tarik napas, bicara pelan. Pembuka yang tenang menentukan
sisa presentasi.

---

## BAGIAN 2 — Latar Belakang Masalah (2 menit)

🖥️ **TAMPILKAN:** Slide berisi poin masalah (atau cukup bicara).

🗣️ **KATAKAN:**
> "Azka Laundry selama ini mengelola pesanan secara manual — dicatat di
> buku atau pesan WhatsApp. Ini menimbulkan beberapa masalah:
> pertama, **pesanan mudah tercecer** dan sulit dilacak statusnya.
> Kedua, **pelanggan tidak tahu progres** cuciannya sudah sampai mana.
> Ketiga, **pencatatan keuangan tercampur** dan rawan salah hitung.
> Keempat, **tidak ada data** untuk tahu layanan apa yang paling laku.
>
> Dari hasil wawancara dengan pemilik, saya menyimpulkan mereka butuh
> satu sistem terpusat yang merapikan alur pesanan dari penjemputan
> sampai pengantaran, sekaligus mencatat keuangan otomatis."

💡 **TIPS:** Kalau ada dokumen wawancara, sebutkan: *"berdasarkan
wawancara dengan pemilik"* — ini menunjukkan riset, bukan asumsi.

---

## BAGIAN 3 — Gambaran Solusi (2 menit)

🖥️ **TAMPILKAN:** Halaman welcome / dashboard salah satu peran.

🗣️ **KATAKAN:**
> "Solusi saya adalah aplikasi **web yang mobile-friendly** — jadi
> diakses lewat browser HP seperti aplikasi, tapi tanpa perlu install
> dari Play Store. Ini dipilih supaya ringan, mudah diakses siapa saja,
> dan murah dirawat.
>
> Aplikasi ini punya **tiga peran pengguna**:
> 1. **Pelanggan** — membuat pesanan, memilih lokasi jemput di peta, dan
>    melacak status cucian secara real-time.
> 2. **Admin** — memantau semua pesanan, mencatat keuangan, membuat
>    voucher, melihat laporan analitik, dan menugaskan driver secara manual
>    bila diperlukan (cadangan).
> 3. **Driver** — menerima tugas jemput/antar dan memperbarui status di
>    lapangan.
>
> Sekarang saya tunjukkan cara kerjanya lewat demo langsung."

---

## BAGIAN 4 — DEMO LANGSUNG (7–9 menit) ⭐ INTI PRESENTASI

> 💡 Ini bagian terpenting. Ikuti alur **end-to-end** seperti cerita:
> satu pesanan dari dibuat sampai selesai. Jangan klik acak.

### 4.1 — Sisi Pelanggan: Membuat Pesanan (pakai Jendela 1 - Customer)

🖥️ **TAMPILKAN:** Dashboard customer.

🗣️ **KATAKAN:**
> "Saya mulai dari sisi pelanggan. Ini dashboard pelanggan setelah login.
> Di bawah ada navigasi seperti aplikasi mobile. Saya akan membuat
> pesanan baru dengan menekan tombol **Pesan** di tengah."

🖥️ **TAMPILKAN:** Klik tombol **Pesan** (FAB oranye) → halaman buat pesanan.

🗣️ **KATAKAN:**
> "Di sini pelanggan memilih layanan. Misalnya saya pilih **Cuci +
> Setrika** seharga tujuh ribu per kilo. Lalu saya tentukan lokasi
> penjemputan langsung di peta ini."

🖥️ **TAMPILKAN:** Klik salah satu layanan, lalu geser/klik **peta** untuk menaruh titik lokasi.

🗣️ **KATAKAN:**
> "Petanya menggunakan **OpenStreetMap**, yang gratis dan tidak butuh
> biaya API seperti Google Maps. Pelanggan cukup menggeser pin ke lokasi
> rumahnya. Lalu saya isi perkiraan berat, jadwal jemput, dan kalau punya
> kode voucher bisa dimasukkan di sini."

🖥️ **TAMPILKAN:** Isi estimasi berat, pilih tanggal & waktu jemput → klik **Buat Pesanan** → halaman sukses.

🗣️ **KATAKAN:**
> "Pesanan berhasil dibuat dan mendapat **kode pesanan unik**. Yang menarik
> di sini: begitu pesanan dibuat, sistem **otomatis menugaskan driver yang
> tersedia** tanpa perlu admin turun tangan. Statusnya langsung menjadi
> 'Dijemput', dan driver-nya langsung dapat notifikasi. Jadi alurnya cepat
> dan efisien."

💡 **TIPS:** Karena akun driver di data demo aktif, pesanan ini akan
langsung ter-assign otomatis. Tunjukkan catatan "Kurir [nama] otomatis
ditugaskan" kalau muncul.

### 4.2 — Sisi Admin: Memantau & Mengelola (pindah ke Jendela 2 - Admin)

🖥️ **TAMPILKAN:** Pindah ke jendela Admin → buka menu **Pesanan**.

🗣️ **KATAKAN:**
> "Sekarang saya pindah ke sisi admin. Pesanan yang tadi dibuat sudah
> muncul di sini, dan perhatikan — statusnya **sudah 'Dijemput'** karena
> driver-nya tadi ditugaskan otomatis oleh sistem. Admin tidak perlu
> menugaskan manual di kondisi normal.
>
> Sistem penugasan otomatis ini membagi tugas secara adil ke semua driver
> aktif. Admin baru perlu menugaskan **manual** hanya pada kondisi khusus —
> misalnya saat semua driver sedang nonaktif atau libur, pesanan akan
> menunggu dan admin bisa menugaskannya. Jadi admin berperan sebagai
> **pengawas dan cadangan**, bukan penghambat alur."

💡 **TIPS:** Kalau mau menunjukkan fitur assign manual, kamu bisa
nonaktifkan semua driver dulu lewat data, lalu buat pesanan — pesanan akan
'menunggu' dan tombol assign muncul. Ini opsional; untuk demo utama cukup
tunjukkan bahwa order sudah otomatis ter-assign.

### 4.3 — Sisi Driver: Update Status di Lapangan (pindah ke Jendela 3 - Driver)

🖥️ **TAMPILKAN:** Pindah ke jendela Driver → dashboard driver → buka tugas.

🗣️ **KATAKAN:**
> "Di sisi driver, tugas tadi sudah muncul. Driver bisa melihat detail
> alamat di peta, lalu setelah menjemput, dia memperbarui status, misalnya
> jadi 'Dijemput' lalu 'Dicuci'."

🖥️ **TAMPILKAN:** Klik tombol update status / action. Saat tahap antar, tunjukkan **upload foto bukti**.

🗣️ **KATAKAN:**
> "Saat mengantar pesanan yang sudah selesai, driver wajib mengunggah
> **foto bukti pengiriman** dan mencatat pembayaran. Ini untuk
> akuntabilitas — ada bukti bahwa cucian benar-benar sampai ke pelanggan."

💡 **TIPS:** Di sinilah kamu pakai foto yang sudah disiapkan di laptop.

### 4.4 — Status Mengalir & Notifikasi (kembali ke Jendela 1 - Customer)

🖥️ **TAMPILKAN:** Pindah ke jendela Customer → buka **Lacak Pesanan** / Tracking, lalu **Notifikasi**.

🗣️ **KATAKAN:**
> "Dari sisi pelanggan, setiap perubahan status tadi otomatis terlihat di
> halaman pelacakan, lengkap dengan peta posisi. Pelanggan juga menerima
> **notifikasi** — baik di dalam aplikasi maupun email. Jadi pelanggan
> tidak perlu lagi bertanya 'cucian saya sudah sampai mana?' lewat
> WhatsApp."

🖥️ **TAMPILKAN:** Kalau pesanan sudah 'Selesai', tunjukkan fitur **beri rating**.

🗣️ **KATAKAN:**
> "Setelah pesanan selesai, pelanggan bisa memberi **rating dan ulasan**.
> Ini jadi masukan kualitas layanan buat pemilik."

### 4.5 — Admin: Keuangan, Voucher, & Analitik (pindah ke Jendela 2 - Admin)

🖥️ **TAMPILKAN:** Jendela Admin → buka **Keuangan**, lalu **Dashboard** (analitik).

🗣️ **KATAKAN:**
> "Begitu pesanan selesai, pemasukannya **otomatis tercatat** di modul
> keuangan — admin tidak perlu input manual, dan sistem mencegah pencatatan
> ganda. Admin bisa melihat pemasukan harian dan bulanan, bahkan
> mengekspor ke Excel atau PDF.
>
> Di dashboard ini ada **analitik 30 hari**: layanan terlaris, pelanggan
> paling aktif, jam penjemputan tersibuk, dan rata-rata rating. Data ini
> membantu pemilik mengambil keputusan, misalnya menambah driver di jam
> sibuk."

🖥️ **TAMPILKAN (opsional, kalau waktu cukup):** Menu **Voucher**, **Walk-in order**, **Laporan kendala**, **Audit log**.

🗣️ **KATAKAN (opsional):**
> "Admin juga bisa membuat **voucher diskon**, mencatat **pesanan
> langsung di tempat** (walk-in) untuk pelanggan yang datang ke toko, dan
> ada **log audit** yang merekam setiap tindakan penting admin untuk
> keamanan."

💡 **TIPS:** Kalau waktu menipis, lewati 4.5 bagian opsional. Yang wajib:
4.1 → 4.2 → 4.3 → 4.4.

---

## BAGIAN 5 — Penjelasan Teknis (2–3 menit)

🖥️ **TAMPILKAN:** Slide arsitektur / atau buka kode singkat (mis. `Order.php` bagian status).

🗣️ **KATAKAN:**
> "Dari sisi teknis, aplikasi ini dibangun dengan **framework Laravel
> versi 12** dan bahasa **PHP**, dengan database **MySQL**. Tampilannya
> dirender di server menggunakan Blade, jadi cepat dan ringan di HP.
>
> Beberapa keputusan desain yang saya soroti:
> - **Alur status pesanan** saya buat sebagai 'state machine' — artinya
>   status hanya bisa berpindah ke tahap yang valid, misalnya tidak
>   mungkin loncat dari 'Menunggu' langsung ke 'Selesai'. Ini mencegah
>   data yang tidak masuk akal.
> - **Notifikasi** diproses di latar belakang (antrian) supaya pelanggan
>   tidak menunggu lama saat ada perubahan status.
> - **Peta** memakai OpenStreetMap yang gratis, jadi tidak ada biaya
>   tersembunyi."

💡 **TIPS:** Jangan baca kode baris per baris. Cukup tunjukkan satu
contoh dan jelaskan konsepnya dengan bahasa sederhana.

---

## BAGIAN 6 — Keamanan & Kualitas (2 menit)

🗣️ **KATAKAN:**
> "Soal keamanan, saya menerapkan beberapa lapis perlindungan:
> - **Pembatasan akses per peran** — pelanggan tidak bisa membuka halaman
>   admin walaupun tahu URL-nya. Sistem selalu memeriksa peran di setiap
>   permintaan.
> - **Akun yang dinonaktifkan tidak bisa login**, walau password-nya benar.
> - Setelah **logout, tombol 'kembali' di browser tidak bisa lagi
>   menampilkan halaman sensitif** — saya pasang anti-cache khusus.
> - **Semua input divalidasi**, dan upload file dibatasi hanya gambar
>   dengan ukuran maksimal tertentu.
> - Ada **log audit** untuk tindakan penting admin.
>
> Untuk kualitas, aplikasi ini punya **131 pengujian otomatis yang
> semuanya lulus**, termasuk skenario rumit seperti pencegahan voucher
> dipakai melebihi batas saat banyak orang mengakses bersamaan."

💡 **TIPS:** Angka **131 test lulus** itu kuat. Sebutkan dengan percaya diri.

---

## BAGIAN 7 — Deployment & Biaya (1 menit)

🗣️ **KATAKAN:**
> "Aplikasi ini sudah saya siapkan untuk diluncurkan ke internet. Saya
> sudah membuat **panduan deployment lengkap** menggunakan layanan
> Hostinger.
>
> Soal biaya, saya merancang agar **hampir semuanya gratis** — sertifikat
> keamanan HTTPS, database, peta, email, dan pemantauan semuanya pakai
> layanan gratis. Praktis satu-satunya biaya adalah paket hosting-nya
> sendiri."

---

## BAGIAN 8 — Penutup & Rencana Pengembangan (1–2 menit)

🗣️ **KATAKAN:**
> "Sebagai penutup, fungsi inti aplikasi sudah lengkap dan teruji. Untuk
> pengembangan ke depan, ada beberapa rencana:
> - Mengaktifkan **pelacakan real-time penuh** (saat ini pembaruan setiap
>   beberapa detik).
> - Menjadikannya **PWA** supaya bisa 'di-install' di layar HP seperti
>   aplikasi.
> - Menambah **pengujian unit** dan **pemantauan error** saat sudah online.
>
> Demikian presentasi saya. Terima kasih, dan saya siap menerima
> pertanyaan."

💡 **TIPS:** Bingkai sebagai 'rencana', bukan 'kekurangan'. Ini menunjukkan
kamu paham arah pengembangan.

---

## BAGIAN 9 — Antisipasi Pertanyaan Penguji (siapkan jawaban!)

**T: "Kenapa pakai web, bukan aplikasi Android (APK)?"**
> "Karena web mobile-friendly lebih ringan, bisa diakses dari HP apa pun
> tanpa install, dan jauh lebih murah dirawat. Untuk skala usaha laundry,
> ini lebih praktis. Ke depan bisa dijadikan PWA agar terasa seperti aplikasi."

**T: "Kenapa pesanan tidak langsung ke driver? Apa admin harus menugaskan dulu?"**
> "Justru pesanan online **langsung ditugaskan otomatis** ke driver yang
> tersedia begitu dibuat — admin tidak perlu turun tangan. Sistem membagi
> tugas secara adil antar-driver. Admin hanya menugaskan manual sebagai
> cadangan, misalnya saat tidak ada driver aktif. Ini dirancang supaya
> alurnya efisien dan cepat sampai ke driver."

**T: "Kalau driver-nya cuma satu, untuk apa ada sistem pembagian tugas?"**
> "Sistemnya dibuat fleksibel: dengan satu driver, tugas otomatis selalu ke
> dia. Tapi kalau usaha berkembang dan driver bertambah, sistem yang sama
> langsung membagi tugas merata tanpa perlu ubah kode. Jadi ini investasi
> untuk skalabilitas."

**T: "Bagaimana kalau dua orang pesan/pakai voucher di saat bersamaan?"**
> "Sudah saya tangani. Misalnya voucher dengan batas pemakaian — sistem
> mengunci data saat diproses, jadi tidak bisa dipakai melebihi batas
> walau diakses bersamaan. Ini bahkan saya buatkan pengujiannya."

**T: "Apa yang membuat data aman?"**
> "Ada pembatasan akses per peran, validasi semua input, perlindungan dari
> manipulasi data, dan log audit. Password juga dienkripsi, bukan disimpan
> apa adanya."

**T: "Kenapa peta tidak pakai Google Maps?"**
> "Google Maps butuh kartu kredit dan bisa berbiaya. OpenStreetMap gratis
> dan sudah cukup untuk kebutuhan memilih lokasi dan melacak. Keputusan ini
> juga menekan biaya operasional."

**T: "Apakah sudah online/bisa diakses publik?"**
> "Belum, saat ini di tahap siap-deploy. Saya sudah menyiapkan panduan
> dan konfigurasi lengkap untuk meluncurkannya ke Hostinger."

**T: "Bagaimana notifikasi ke pelanggan bekerja?"**
> "Saat status pesanan berubah, sistem mengirim notifikasi di dalam
> aplikasi dan email secara otomatis. Prosesnya berjalan di latar belakang
> agar tidak memperlambat aplikasi."

**T: "Berapa lama mengerjakan ini / pakai apa saja?"**
> Jawab jujur sesuai pengalamanmu. Sebut: Laravel, PHP, MySQL, Blade,
> Leaflet/OpenStreetMap.

💡 **Kalau tidak tahu jawaban:** jangan mengarang. Katakan dengan jujur:
> "Pertanyaan bagus. Untuk bagian itu saya belum mendalami, tapi akan saya
> pelajari lebih lanjut." — ini jauh lebih baik daripada menebak dan salah.

---

## BAGIAN 10 — Tips Penyampaian untuk Pemula

1. **Latihan minimal 2–3 kali** sambil ngomong keras, pegang stopwatch.
2. **Pelan dan jelas** lebih baik daripada cepat. Gugup bikin orang
   ngomong cepat — sadari ini dan rem.
3. **Jangan baca layar membelakangi penonton.** Sesekali lihat penguji.
4. **Kalau salah klik / error**, tetap tenang: "Sebentar ya, saya ulangi."
   Penguji paham demo langsung kadang ada kendala.
5. **Kuasai alur 4.1–4.4** sampai hafal. Itu inti yang wajib mulus.
6. **Siapkan rencana cadangan:** screenshot/rekaman layar tiap langkah demo,
   untuk jaga-jaga kalau aplikasi error saat hari-H.
7. **Buka panduan ini di HP/tablet** sebagai contekan saat presentasi.

---

## Ringkasan Urutan (Contekan 1 Halaman)

1. Pembuka & perkenalan
2. Masalah Azka Laundry (manual, tercecer, tak terlacak)
3. Solusi: web mobile-friendly, 3 peran
4. **DEMO:** Customer buat pesanan (driver otomatis ditugaskan) → Admin
   memantau (order sudah 'dijemput') → Driver update + upload bukti →
   Customer lacak + notifikasi + rating → Admin keuangan + analitik
5. Teknis: Laravel, MySQL, state machine, antrian, peta gratis
6. Keamanan: akses per peran, validasi, 131 test lulus
7. Deployment & biaya: siap deploy Hostinger, nyaris gratis
8. Penutup: rencana pengembangan + terima kasih
9. Tanya jawab
