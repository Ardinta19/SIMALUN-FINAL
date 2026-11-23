# Tinjauan UI/UX per peran (Berkah Kreatif)

Dokumen ini menjawab pertanyaan "UI/UX semua role bagaimana?" dengan menilai
setiap permukaan antarmuka yang bisa dibuka pengguna, bukan hanya halaman
utama. Ditulis setelah menelusuri seluruh 115 berkas Blade, CSS tema, dan
navigasi per peran.

Bagian 1 memetakan halaman mana yang benar-benar dipakai alur sewa, bagian 2
menilai per peran, bagian 3 mencatat perbaikan yang sudah dikerjakan, dan
bagian 4 berisi sisa temuan beserta prioritasnya.

---

## 1. Peta permukaan UI

Ada dua generasi halaman di dalam aplikasi ini: alur **sewa papan bunga**
(produk saat ini) dan sisa alur **laundry** (produk lama). Keduanya masih
bisa dibuka lewat URL, tetapi hanya yang pertama yang tertaut dari navigasi.

| Peran | Halaman yang tertaut dari navigasi | Halaman sisa yang masih bisa dibuka lewat URL |
| --- | --- | --- |
| Tamu | Katalog, detail papan, keranjang, masuk, daftar | — |
| Pelanggan | Beranda, Katalog, Pesanan sewa, Keranjang, Notifikasi, Profil, Alamat | Daftar pesanan laundry, detail pesanan laundry, lacak kurir, bantuan laundry |
| Admin | Beranda admin, Pesanan sewa, Profil admin, Aksi cepat (jadwal, katalog, tarif universitas, buku kas, petunjuk pembayaran, notifikasi, audit) | Manajemen pesanan laundry, order walk-in, laporan keuangan, voucher, kategori layanan, laporan, bantuan |
| Kurir | Beranda, Daftar tugas, Lacak tugas, Notifikasi, Profil, Bantuan | — (kurir hanya dipakai alur laundry) |

Kesimpulan peta: **tamu dan pelanggan serta admin sewa memakai satu keluarga
layout yang sama** (`resources/views/catalog/layout.blade.php` dengan
`css/berkah-experience.css`), sedangkan **halaman sisa laundry** (admin lama,
kurir, pesanan laundry pelanggan) memakai layout mandiri per halaman dengan
`<style>` sendiri, token sendiri, dan merek lama.

---

## 2. Penilaian per peran

### Tamu (belum masuk)

Yang sudah baik: satu layout konsisten untuk katalog, detail papan, keranjang,
dan halaman masuk/daftar; token warna tunggal (`#85092d`), judul halaman
memakai nama usaha, tombol WhatsApp hanya muncul bila nomor admin valid,
keranjang kosong punya keadaan kosong yang jelas, dan tautan "Langsung ke isi"
serta `<main id="main-content">` sudah ada.

Yang diperbaiki putaran ini: zoom halaman tidak lagi dikunci di semua halaman
tamu, dan cincin fokus keyboard kini tampil pada kontrol yang sebelumnya hanya
mengandalkan perubahan warna.

Sisa kekurangan: katalog belum punya sorotan "paling sering disewa" dan tidak
ada penanda jumlah hasil saat filter aktif. Keduanya kosmetik, bukan penghalang.

### Pelanggan

Yang sudah baik: navigasi atas memuat Beranda, Katalog, Pesanan, Keranjang,
Notifikasi, dan Profil dengan `aria-current` pada halaman aktif; pesan sukses
dan galat sudah memakai `role="status"` / `role="alert"`; keadaan kosong ada
pada notifikasi, daftar alamat, dan daftar pesanan; status unit punya label
Indonesia yang konsisten.

Yang diperbaiki putaran ini: tiga halaman pesanan lama (daftar, detail, lacak)
yang tidak memakai layout induk kini punya landmark `main`, dan tautan
"Buka di tab baru" pada halaman lacak memakai `rel="noopener"`.

Sisa kekurangan: halaman lacak kurir membuka WhatsApp dengan pesan tetap
("Halo Kurir, pesanan #..."), bukan kalimat yang bisa disunting pelanggan;
sebaiknya disamakan dengan tautan siap kirim seperti pada babak E.

### Admin (alur sewa — yang dipakai sehari-hari)

Yang sudah baik: beranda admin berbentuk daftar tugas, bukan grafik: "Perlu
perhatian", "Menunggu pelanggan", "Pesanan sewa terbaru", lalu "Aksi cepat".
Setiap kartu tugas menuju daftar pesanan dengan filter status yang tepat,
termasuk `grace` dan `return_overdue`. Ada keadaan kosong pada pesanan terbaru,
badge jumlah menunggu pada navigasi bawah, dan peringatan "sistem masih dalam
pengujian" di kaki halaman.

Yang diperbaiki putaran ini: cincin fokus keyboard untuk seluruh form admin
yang sebelumnya mematikan `outline`, landmark `main` pada halaman profil,
edit profil, dan kategori yang belum punya, serta versi merek pada halaman
kurir dan judul halaman.

Sisa kekurangan (perlu keputusan pemilik): halaman sisa laundry
(`/admin/orders`, `/admin/walkin`, `/admin/finance`, `/admin/vouchers`,
`/admin/categories`, `/admin/reports`) tidak tertaut dari mana pun, tetapi
masih bisa dibuka dan tampil dengan merek lama serta istilah laundry (kg,
cucian, kurir). Ini berisiko membingungkan admin baru. Pilihannya: hapus,
alihkan (redirect) ke halaman sewa, atau sembunyikan di balik izin khusus.

### Kurir (hanya dipakai alur laundry)

Yang sudah baik: beranda kurir menampilkan tugas aktif, KPI, dan tombol aksi
besar yang ramah layar kecil; navigasi bawah memakai pola yang sama dengan
pelanggan.

Yang diperbaiki putaran ini: nomor WhatsApp bantuan yang sebelumnya
ditanam mati (`6281234567890`) sekarang memakai nomor resmi dari konfigurasi;
landmark `main` ditambahkan pada beranda, detail tugas, dan profil; merek pada
versi halaman tidak lagi "AZKA LAUNDRY".

Sisa kekurangan: karena kurir tidak dipakai alur sewa, seluruh modul ini tidak
punya tujuan produk saat ini. Bila pemilik tidak memakai penjemputan kurir,
peran ini sebaiknya dinonaktifkan agar tidak perlu dirawat.

### Halaman masuk, daftar, dan galat

Yang sudah baik: satu layout auth bersama, pesan galat per kolom, tautan lupa
sandi, dan halaman galat yang menyebut nama usaha serta tombol "Kembali ke
Beranda" (sudah diuji lewat `ErrorPageBrandingTest`).

Sisa kekurangan: tidak ada penanda kekuatan kata sandi dan tidak ada hitung
mundur pengiriman ulang surel verifikasi. Bukan penghalang, tetapi menambah
kenyamanan.

---

## 3. Perbaikan yang dikerjakan pada putaran ini

1. **Zoom tidak lagi dikunci.** 21 halaman memakai
   `maximum-scale=1.0, user-scalable=no`; semuanya kini hanya
   `width=device-width, initial-scale=1.0, viewport-fit=cover`. Pengguna
   low vision bisa mencubit untuk memperbesar.
2. **Cincin fokus keyboard global.** `public/css/berkah-ui-kit.css` mendapat
   aturan `html body :is(a, button, summary, input, select, textarea, [tabindex]):focus-visible`
   (plus varian tema gelap). Spesifisitasnya mengalahkan
   `input:focus { outline: none }` milik halaman tanpa `!important`, sehingga
   form walk-in, edit profil, dan halaman sisa lainnya kembali punya indikator
   fokus.
3. **Merek tunggal.** 17 judul halaman, judul dan tanda air halaman sambutan,
   serta teks bantuan kurir memakai `\App\Support\Laundry::name()`; nilai
   bawaan `config/laundry.php`, `app/Support/Laundry.php`, dan `.env.example`
   diubah dari "Azka Laundry" menjadi "Berkah Kreatif". Tidak ada lagi
   "Azka Laundry" di berkas view.
4. **Nomor WhatsApp kurir** memakai `Laundry::waLink()` sehingga mengikuti
   `LAUNDRY_WA`, bukan angka contoh.
5. **Landmark `main`** ditambahkan pada 9 halaman peran yang belum punya,
   sehingga pembaca layar bisa melompat ke konten.
6. **`rel="noopener"`** ditambahkan pada seluruh tautan `target="_blank"` yang
   belum memilikinya (dua templat surel, tanda terima, halaman lacak, dan
   tautan pratinjau di checkout).
7. **Tes penjaga** `tests/Feature/UiAccessibilityTest.php` (7 tes) memeriksa
   ketujuh hal di atas langsung pada berkas view, sehingga halaman baru tidak
   bisa mengembalikan masalah lama.

Catatan jujur: seluruh pemeriksaan dijalankan di sandbox (lint PHP, pemindaian
Blade, dan pemindaian tag). Tampilan belum dilihat di peramban sungguhan dan
`php artisan test` belum dijalankan untuk commit ini.

---

## 4. Sisa temuan dan prioritas

| Prioritas | Temuan | Usul |
| --- | --- | --- |
| Tinggi | Halaman sisa laundry masih bisa dibuka dengan istilah dan merek lama | Hapus/alihkan rute, atau sembunyikan di balik izin |
| Tinggi | Dua keluarga layout (layout katalog vs layout mandiri per halaman) membuat perbaikan harus digandakan | Satukan halaman peran ke satu layout induk bertahap, mulai dari halaman yang paling sering dibuka |
| Sedang | Zoom, fokus, dan landmark selesai, tetapi ukuran target sentuh tombol kecil di tabel admin belum diaudit | Ukur dengan layar 360 px, jadikan minimal 44×44 px |
| Sedang | Pesan WhatsApp pada halaman lacak kurir masih kalimat tetap | Pakai pola tautan siap kirim seperti babak E |
| Sedang | Tidak ada penanda hasil filter pada katalog ("12 papan") | Tambahkan baris jumlah di atas daftar |
| Rendah | Belum ada penanda kekuatan kata sandi dan hitung mundur kirim ulang verifikasi | Tambahkan di layout auth |
| Rendah | Tema gelap hanya tersedia di halaman peran mandiri, tidak di sisi pelanggan/tamu | Samakan bila tema gelap ingin dipertahankan |

Keputusan yang dibutuhkan dari pemilik sebelum lanjut: (a) nasib halaman sisa
laundry, (b) apakah tema gelap dipertahankan untuk sisi pelanggan, dan
(c) apakah modul kurir masih dipakai.
