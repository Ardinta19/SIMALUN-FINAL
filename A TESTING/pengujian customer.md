# DOKUMEN PENGUJIAN PERANGKAT LUNAK
### Test Case — Role Customer (Pelanggan)
### Aplikasi SIMALUN (Sistem Manajemen Laundry — Azka Laundry)

---

## 1. Informasi Dokumen

| Atribut | Keterangan |
|---|---|
| Nama Aplikasi | SIMALUN — Azka Laundry |
| Role yang Diuji | Customer (Pelanggan) |
| Jenis Pengujian | Black-box Testing (Manual) |
| Versi Aplikasi | 1.0 |
| Penyusun | Ardinata Saputra |
| Mata Kuliah | MPSI |

---

## 2. Tujuan Pengujian

Memverifikasi bahwa seluruh fitur yang dapat diakses oleh role Customer pada aplikasi
SIMALUN berjalan sesuai spesifikasi fungsional, baik pada kondisi normal (input valid)
maupun kondisi tidak normal (input tidak valid dan kasus batas). Pengujian menggunakan
metode black-box.

---

## 3. Ruang Lingkup Pengujian

Pengujian mencakup modul-modul berikut yang dapat diakses Customer:

1. Dashboard Customer
2. Pembuatan Pesanan (Laundry Jemput-Antar)
3. Penggunaan Voucher pada Pesanan
4. Tracking Status & Lokasi Kurir
5. Riwayat & Detail Pesanan
6. Pembatalan Pesanan
7. Rating & Ulasan Pesanan
8. Kelola Alamat
9. Notifikasi
10. Profil & Ubah Password
11. Lapor Kendala

Modul autentikasi (login/registrasi) diuji terpisah pada dokumen "Halaman Awal &
Autentikasi". Fitur khusus Admin dan Driver berada di luar cakupan dokumen ini.

---

## 4. Lingkungan Pengujian

| Komponen | Spesifikasi |
|---|---|
| URL Aplikasi | https://azkalaundry.store |
| Peramban | Google Chrome (versi terbaru) |
| Perangkat | Desktop dan Mobile (Android / iOS) |
| Koneksi | Internet stabil |
| Akun Uji | Akun customer aktif |

---

## 5. Konvensi dan Kategori Pengujian

Kode kasus uji berformat `XXX-YN`: `XXX` singkatan modul, `Y` kategori (P/N/E), `N` nomor urut.

| Kategori | Definisi |
|---|---|
| Positive | Masukan dan alur benar; sistem diharapkan berhasil memproses. |
| Negative | Masukan salah/tidak lengkap; sistem diharapkan menolak disertai pesan jelas. |
| Edge | Kondisi batas/ekstrem yang menguji ketahanan dan konsistensi sistem. |

Status diisi **Lulus** atau **Gagal**.

---

## 6. Ringkasan Cakupan Pengujian

| No | Modul | Positive | Negative | Edge | Total |
|---|---|:--:|:--:|:--:|:--:|
| 1 | Dashboard Customer | 2 | 0 | 1 | 3 |
| 2 | Pembuatan Pesanan | 4 | 6 | 4 | 14 |
| 3 | Voucher pada Pesanan | 2 | 2 | 1 | 5 |
| 4 | Tracking Status & Lokasi | 2 | 1 | 1 | 4 |
| 5 | Riwayat & Detail Pesanan | 2 | 1 | 0 | 3 |
| 6 | Pembatalan Pesanan | 2 | 2 | 1 | 5 |
| 7 | Rating & Ulasan | 2 | 3 | 1 | 6 |
| 8 | Kelola Alamat | 3 | 2 | 2 | 7 |
| 9 | Notifikasi | 2 | 0 | 1 | 3 |
| 10 | Profil & Ubah Password | 2 | 2 | 0 | 4 |
| 11 | Lapor Kendala | 1 | 2 | 1 | 4 |
| | **Total** | **24** | **21** | **13** | **58** |

---

## 7. Rincian Kasus Uji

### 7.1 Modul Dashboard Customer

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| DSC-P1 | Positive | Login sebagai customer | Buka dashboard customer | Tampil sapaan, ringkasan pesanan aktif (bila ada), dan pintasan menu | Dashboard tampil; pesanan aktif & pintasan menu muncul sesuai data | Lulus |
| DSC-P2 | Positive | Customer belum punya pesanan | Buka dashboard customer | Dashboard tetap tampil tanpa error; ajakan membuat pesanan ditampilkan | Dashboard tampil normal pada data kosong; tidak ada error | Lulus |
| DSC-E1 | Edge | Login sebagai customer | Coba akses URL dashboard admin/driver | Ditolak/diarahkan; customer tidak bisa masuk area role lain | Akses ditolak oleh middleware `role`; tidak bisa membuka area admin/driver | Lulus |

### 7.2 Modul Pembuatan Pesanan

Ketentuan: Layanan (wajib, aktif), Alamat (minimal 10 karakter), Tanggal jemput (hari ini s/d 14 hari ke depan), Waktu jemput (pagi/siang/sore), Estimasi berat (1–50 kg). Biaya penanganan/jemput bersifat flat Rp5.000 (zona tidak lagi memengaruhi biaya). Maksimal 3 pesanan aktif per customer.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| ORD-P1 | Positive | Form pesanan terbuka | Pilih layanan kiloan, isi alamat, tanggal & waktu jemput, berat 3 kg | Pesanan dibuat; estimasi biaya = (harga/kg × berat) + Rp5.000; diarahkan ke halaman sukses | Pesanan dibuat; biaya = harga layanan × berat + Rp5.000; halaman sukses tampil | Lulus |
| ORD-P2 | Positive | Form pesanan terbuka | Tambah item satuan (mis. Bedcover) beserta qty | Biaya item ditambahkan ke total; pesanan berhasil dibuat | Total mencakup item satuan (harga × qty); pesanan berhasil | Lulus |
| ORD-P3 | Positive | Customer punya alamat tersimpan | Pilih alamat tersimpan lalu buat pesanan | Pesanan memakai alamat tersimpan; `last_used_at` diperbarui | Pesanan memakai alamat tersimpan; alamat ditandai terakhir dipakai | Lulus |
| ORD-P4 | Positive | Form pesanan terbuka, alamat manual baru | Isi alamat manual lalu buat pesanan | Pesanan dibuat; alamat manual otomatis tersimpan ke buku alamat | Pesanan dibuat; alamat baru tersimpan (alamat pertama jadi Alamat Utama) | Lulus |
| ORD-N1 | Negative | Form pesanan terbuka | Tidak memilih layanan | Ditolak; layanan wajib dipilih | Ditolak; layanan wajib dipilih | Lulus |
| ORD-N2 | Negative | Form pesanan terbuka | Alamat diisi `Jl A` (<10 karakter) | Ditolak; alamat minimal 10 karakter | Ditolak; alamat minimal 10 karakter | Lulus |
| ORD-N3 | Negative | Form pesanan terbuka | Tanggal jemput diisi tanggal kemarin | Ditolak; tanggal tidak boleh sebelum hari ini | Ditolak; tanggal jemput minimal hari ini | Lulus |
| ORD-N4 | Negative | Form pesanan terbuka | Berat diisi 0 | Ditolak; berat minimal 1 kg | Ditolak; estimasi berat minimal 1 kg | Lulus |
| ORD-N5 | Negative | Customer sudah punya 3 pesanan aktif | Buat pesanan ke-4 | Ditolak; "Kamu masih punya 3 pesanan aktif. Selesaikan dulu salah satunya." | Ditolak; tampil "Kamu masih punya 3 pesanan aktif. Selesaikan dulu salah satunya." | Lulus |
| ORD-N6 | Negative | Punya alamat tersimpan milik orang lain | Kirim `customer_address_id` milik akun lain | Ditolak (403); alamat bukan miliknya | Ditolak dengan 403; alamat tersimpan tidak ditemukan | Lulus |
| ORD-E1 | Edge | Form pesanan terbuka | Tanggal jemput = 14 hari dari sekarang | Diterima (batas maksimum) | Diterima; pesanan berhasil dibuat | Lulus |
| ORD-E2 | Edge | Form pesanan terbuka | Tanggal jemput = 15 hari dari sekarang | Ditolak; "Tanggal jemput maksimal 14 hari ke depan." | Ditolak; tampil "Tanggal jemput maksimal 14 hari ke depan." | Lulus |
| ORD-E3 | Edge | Form pesanan terbuka | Berat diisi 51 kg | Ditolak; "Estimasi berat melebihi batas (maks 50 kg)." | Ditolak; tampil "Estimasi berat melebihi batas (maks 50 kg)." | Lulus |
| ORD-E4 | Edge | Baru saja membuat pesanan serupa | Submit pesanan layanan+tanggal+waktu sama dalam 30 detik | Ditolak; "Pesanan serupa baru saja kamu buat. Tunggu sebentar sebelum mencoba lagi." | Ditolak; tampil pesan anti-duplikat (jeda 30 detik) | Lulus |

### 7.3 Modul Voucher pada Pesanan

Ketentuan: Voucher dicek terhadap subtotal. Bila tidak berlaku atau subtotal di bawah minimum order, voucher ditolak. Diskon dihitung otomatis (persen/nominal).

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| VCR-P1 | Positive | Voucher aktif & subtotal memenuhi minimum | Masukkan kode voucher valid | Diskon dihitung dan ditampilkan; total berkurang | Diskon tampil (mis. "Rp X"); total order berkurang sesuai diskon | Lulus |
| VCR-P2 | Positive | Voucher persen dengan batas maksimal diskon | Pakai voucher persen pada subtotal besar | Diskon dibatasi sesuai `max_discount` | Diskon tidak melebihi batas maksimal voucher | Lulus |
| VCR-N1 | Negative | Halaman pesanan terbuka | Masukkan kode voucher acak/tidak ada | Ditolak; "Voucher tidak ditemukan atau sudah tidak berlaku." | Ditolak; tampil "Voucher tidak ditemukan atau sudah tidak berlaku." | Lulus |
| VCR-N2 | Negative | Voucher punya minimum order | Subtotal di bawah minimum lalu pakai voucher | Ditolak; "Minimum order Rp X untuk pakai voucher ini." | Ditolak; tampil pesan minimum order beserta nominalnya | Lulus |
| VCR-E1 | Edge | Halaman pesanan terbuka | Cek voucher lebih dari 30 kali dalam 1 menit | Permintaan diblokir (pembatasan laju) | Permintaan berlebih diblokir (rate limit 30/menit) | Lulus |

### 7.4 Modul Tracking Status & Lokasi Kurir

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| TRK-P1 | Positive | Punya pesanan aktif (dijemput/dikirim) | Buka halaman tracking | Status terkini & info kurir tampil | Status terkini pesanan dan info kurir tampil | Lulus |
| TRK-P2 | Positive | Kurir mengirim lokasi | Pantau lokasi kurir di peta | Posisi kurir tampil dan diperbarui berkala | Posisi kurir tampil di peta dan ter-update mengikuti data terbaru | Lulus |
| TRK-N1 | Negative | Tidak ada pesanan aktif | Buka halaman tracking | Ditampilkan keadaan kosong (tidak ada yang dilacak), tanpa error | Tampil keadaan kosong; tidak ada error | Lulus |
| TRK-E1 | Edge | Pesanan aktif tapi kurir belum kirim lokasi | Buka tracking lokasi | Peta tetap tampil; lokasi kurir belum tersedia ditangani dengan rapi | Peta tampil; status lokasi kurir "belum tersedia" tanpa error | Lulus |

### 7.5 Modul Riwayat & Detail Pesanan

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| RIW-P1 | Positive | Punya beberapa pesanan | Buka daftar pesanan & filter (aktif/selesai/batal) | Daftar terfilter sesuai pilihan | Daftar pesanan tampil dan terfilter sesuai status | Lulus |
| RIW-P2 | Positive | Punya pesanan | Buka detail salah satu pesanan | Rincian item, biaya, dan riwayat status tampil | Detail pesanan + riwayat status (timestamp) tampil lengkap | Lulus |
| RIW-N1 | Negative | Login sebagai customer | Buka detail pesanan milik customer lain | Ditolak (403) | Ditolak dengan 403; tidak bisa melihat pesanan orang lain | Lulus |

### 7.6 Modul Pembatalan Pesanan

Ketentuan: Pembatalan hanya boleh selama status masih `menunggu` atau `dijemput` (sebelum masuk pencucian). Alasan opsional (maks 300 karakter).

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| CXL-P1 | Positive | Pesanan berstatus `menunggu` | Batalkan pesanan dengan alasan | Status menjadi `dibatalkan`; "Pesanan berhasil dibatalkan." | Status berubah ke Dibatalkan; tampil "Pesanan berhasil dibatalkan." | Lulus |
| CXL-P2 | Positive | Pesanan berstatus `dijemput` | Batalkan pesanan | Pembatalan berhasil; admin & kurir mendapat notifikasi | Pembatalan berhasil; notifikasi terkirim ke admin & kurir | Lulus |
| CXL-N1 | Negative | Pesanan berstatus `dicuci` | Coba batalkan | Ditolak; "Pesanan tidak bisa dibatalkan karena sudah dalam proses pencucian." | Ditolak; tampil "Pesanan tidak bisa dibatalkan karena sudah dalam proses pencucian." | Lulus |
| CXL-N2 | Negative | Pesanan milik customer lain | Coba batalkan pesanan orang lain | Ditolak (403) | Ditolak dengan 403 | Lulus |
| CXL-E1 | Edge | Pesanan `menunggu` | Batalkan tanpa mengisi alasan | Pembatalan tetap berhasil; tercatat "Tidak menyertakan alasan." | Pembatalan berhasil; riwayat mencatat "Tidak menyertakan alasan." | Lulus |

### 7.7 Modul Rating & Ulasan

Ketentuan: Hanya pesanan berstatus `selesai` yang dapat dirating. Satu pesanan maksimal satu rating. Rating 1–5 bintang; komentar opsional (maks 500 karakter).

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| RTG-P1 | Positive | Pesanan `selesai` belum dirating | Beri 5 bintang + komentar | Rating tersimpan; "Terima kasih atas rating dan ulasannya." | Rating tersimpan; tampil "Terima kasih atas rating dan ulasannya." | Lulus |
| RTG-P2 | Positive | Pesanan `selesai` belum dirating | Beri rating tanpa komentar | Rating tersimpan (komentar kosong diperbolehkan) | Rating tersimpan tanpa komentar | Lulus |
| RTG-N1 | Negative | Pesanan belum `selesai` | Coba beri rating | Ditolak; "Pesanan ini belum selesai, jadi belum bisa diberi rating." | Ditolak; tampil "Pesanan ini belum selesai, jadi belum bisa diberi rating." | Lulus |
| RTG-N2 | Negative | Pesanan sudah pernah dirating | Coba rating ulang | Ditolak; "Pesanan ini sudah pernah kamu rating." | Ditolak; tampil "Pesanan ini sudah pernah kamu rating." | Lulus |
| RTG-N3 | Negative | Pesanan `selesai` | Kirim tanpa memilih bintang | Ditolak; "Pilih dulu jumlah bintangnya ya." | Ditolak; tampil "Pilih dulu jumlah bintangnya ya." | Lulus |
| RTG-E1 | Edge | Pesanan `selesai` | Komentar > 500 karakter | Ditolak; "Komentar terlalu panjang (maks 500 karakter)." | Ditolak; tampil "Komentar terlalu panjang (maks 500 karakter)." | Lulus |

### 7.8 Modul Kelola Alamat

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| ADR-P1 | Positive | Belum punya alamat | Tambah alamat pertama | Tersimpan & otomatis jadi Alamat Utama; "Alamat berhasil ditambahkan." | Alamat tersimpan dan otomatis ditandai utama; pesan sukses tampil | Lulus |
| ADR-P2 | Positive | Punya beberapa alamat | Ubah salah satu alamat | Perubahan tersimpan; "Alamat berhasil diperbarui." | Perubahan tersimpan; tampil "Alamat berhasil diperbarui." | Lulus |
| ADR-P3 | Positive | Punya >1 alamat | Jadikan alamat lain sebagai utama | Alamat utama berpindah; hanya satu yang utama | Alamat utama berpindah; alamat utama sebelumnya dilepas | Lulus |
| ADR-N1 | Negative | Form alamat terbuka | Kosongkan nama label / alamat | Ditolak; field wajib diisi | Ditolak; label & alamat wajib diisi | Lulus |
| ADR-N2 | Negative | Form alamat terbuka | Alamat < 10 karakter | Ditolak; alamat minimal 10 karakter | Ditolak; alamat minimal 10 karakter | Lulus |
| ADR-E1 | Edge | Punya alamat utama + alamat lain | Hapus alamat utama | Alamat lain (terbaru dipakai) otomatis jadi utama | Setelah hapus, alamat lain dipromosikan jadi utama otomatis | Lulus |
| ADR-E2 | Edge | Login sebagai customer | Coba ubah/hapus alamat milik orang lain | Ditolak (403) | Ditolak dengan 403 | Lulus |

### 7.9 Modul Notifikasi

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| NTF-P1 | Positive | Status pesanan berubah | Buka halaman notifikasi | Notifikasi perubahan status pesanan tampil dengan waktu relatif | Notifikasi tampil dengan judul, pesan, dan waktu ("x menit lalu") | Lulus |
| NTF-P2 | Positive | Ada notifikasi belum dibaca | Tekan "Tandai Semua" / buka notifikasi | Notifikasi ditandai terbaca | Notifikasi ditandai terbaca; penanda belum-dibaca hilang | Lulus |
| NTF-E1 | Edge | Notifikasi lebih dari 1 halaman | Gunakan navigasi halaman | Navigasi berfungsi; daftar berpindah halaman dengan rapi | Pagination berfungsi (tombol Sebelumnya/Berikutnya rapi) | Lulus |

### 7.10 Modul Profil & Ubah Password

Ketentuan: Ubah password memerlukan password lama yang benar dan konfirmasi password baru.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| PRF-P1 | Positive | Login sebagai customer | Ubah nama/HP/jenis kelamin lalu simpan | Profil tersimpan dengan pesan sukses | Profil diperbarui; pesan sukses tampil | Lulus |
| PRF-P2 | Positive | Login sebagai customer | Ubah password dengan password lama benar & konfirmasi cocok | Password diperbarui | Password berhasil diperbarui | Lulus |
| PRF-N1 | Negative | Login sebagai customer | Ubah password dengan password lama salah | Ditolak; password lama tidak cocok | Ditolak; password saat ini salah | Lulus |
| PRF-N2 | Negative | Login sebagai customer | Password baru & konfirmasi berbeda | Ditolak; konfirmasi tidak cocok | Ditolak; konfirmasi password tidak cocok | Lulus |

### 7.11 Modul Lapor Kendala

Ketentuan: Kategori (bug/saran/komplain), Deskripsi (minimal 10 karakter, maks 1000), Lampiran gambar opsional (maks 5 MB).

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| LPR-P1 | Positive | Form lapor kendala terbuka | Pilih kategori, isi deskripsi yang cukup, kirim | Laporan terkirim; "Laporan berhasil dikirim. Terima kasih atas masukannya!" | Laporan terkirim; tampil pesan terima kasih | Lulus |
| LPR-N1 | Negative | Form lapor kendala terbuka | Deskripsi < 10 karakter | Ditolak; "Deskripsi minimal 10 karakter agar kami bisa memahami kendala kamu." | Ditolak; tampil pesan deskripsi minimal 10 karakter | Lulus |
| LPR-N2 | Negative | Form lapor kendala terbuka | Tidak memilih kategori | Ditolak; kategori wajib dipilih | Ditolak; kategori wajib dipilih | Lulus |
| LPR-E1 | Edge | Form lapor kendala terbuka | Unggah gambar > 5 MB | Ditolak; "Ukuran gambar maksimal 5 MB." | Ditolak; tampil "Ukuran gambar maksimal 5 MB." | Lulus |

---

## 8. Rekapitulasi Hasil Pengujian

| No | Modul | Jumlah TC | Lulus | Gagal | Persentase Kelulusan |
|---|---|:--:|:--:|:--:|:--:|
| 1 | Dashboard Customer | 3 | 3 | 0 | 100% |
| 2 | Pembuatan Pesanan | 14 | 14 | 0 | 100% |
| 3 | Voucher pada Pesanan | 5 | 5 | 0 | 100% |
| 4 | Tracking Status & Lokasi | 4 | 4 | 0 | 100% |
| 5 | Riwayat & Detail Pesanan | 3 | 3 | 0 | 100% |
| 6 | Pembatalan Pesanan | 5 | 5 | 0 | 100% |
| 7 | Rating & Ulasan | 6 | 6 | 0 | 100% |
| 8 | Kelola Alamat | 7 | 7 | 0 | 100% |
| 9 | Notifikasi | 3 | 3 | 0 | 100% |
| 10 | Profil & Ubah Password | 4 | 4 | 0 | 100% |
| 11 | Lapor Kendala | 4 | 4 | 0 | 100% |
| | **Total** | **58** | **58** | **0** | **100%** |

---

## 9. Kesimpulan dan Rekomendasi

Kesimpulan:

Seluruh 58 kasus uji pada role Customer berstatus **Lulus** (100%). Alur inti pelanggan —
membuat pesanan jemput-antar, menerapkan voucher, melacak status & lokasi kurir, melihat
riwayat, membatalkan pesanan, memberi rating, mengelola alamat, dan melapor kendala —
berfungsi sesuai spesifikasi, termasuk penanganan kondisi batas (batas tanggal jemput,
berat, maksimal 3 pesanan aktif, dan anti-duplikat 30 detik) serta proteksi kepemilikan
data (403 saat mengakses milik orang lain).

Rekomendasi:

1. Lengkapi bukti screenshot untuk tiap kasus uji role Customer sebagai lampiran visual.
2. Untuk fitur peta/lokasi (TRK), lakukan pengujian lapangan dengan kurir aktif untuk
   memastikan pembaruan posisi berjalan mulus di jaringan seluler.

---

## Lampiran — Catatan Perilaku yang Disengaja (Bukan Cacat)

1. **Biaya penanganan flat Rp5.000.** Zona (A/B/C) masih dapat dipilih sebagai info alamat,
   namun tidak lagi memengaruhi biaya — biaya penanganan/jemput bersifat tetap.
2. **Batas 3 pesanan aktif & anti-duplikat 30 detik.** Pembatasan ini disengaja untuk
   mencegah penumpukan pesanan dan klik ganda; penolakan adalah perilaku yang diharapkan.
3. **Pembatalan terbatas.** Pesanan hanya dapat dibatalkan sebelum masuk proses pencucian
   (status `menunggu`/`dijemput`) demi konsistensi operasional dan keuangan.
