# DOKUMEN PENGUJIAN PERANGKAT LUNAK
### Test Case — Role Admin (Pengelola)
### Aplikasi SIMALUN (Sistem Manajemen Laundry — Azka Laundry)

---

## 1. Informasi Dokumen

| Atribut | Keterangan |
|---|---|
| Nama Aplikasi | SIMALUN — Azka Laundry |
| Role yang Diuji | Admin (Pengelola) |
| Jenis Pengujian | Black-box Testing (Manual) |
| Versi Aplikasi | 1.0 |
| Penguji | ____________________ |
| Mata Kuliah | MPSI |

---

## 2. Tujuan Pengujian

Memverifikasi bahwa fitur-fitur yang dapat diakses oleh role Admin pada aplikasi SIMALUN
berjalan sesuai spesifikasi fungsional, baik pada kondisi normal (positive), kondisi tidak
normal (negative), maupun kondisi batas (edge). Pengujian menggunakan metode black-box.

---

## 3. Ruang Lingkup Pengujian

Pengujian mencakup: pesanan walk-in, pembaruan status cucian, penugasan driver, rekap
pendapatan harian, notifikasi email otomatis, kelola layanan & voucher, serta validasi
input dan kondisi batas. Bukti pengujian (screenshot) tersimpan pada folder
`TestPositive-Admin`, `TestNegative-Admin`, dan `TestEdge-Admin`.

---

## 4. Lingkungan Pengujian

| Komponen | Spesifikasi |
|---|---|
| URL Aplikasi | https://azkalaundry.store |
| Peramban | Google Chrome (versi terbaru) |
| Perangkat | Desktop dan Mobile |
| Koneksi | Internet stabil |
| Akun Uji | Akun admin; tersedia akun driver aktif & beberapa pesanan |

---

## 5. Konvensi dan Kategori Pengujian

Kode kasus uji: `TC-P-xx` (Positive), `TC-N-xx` (Negative), `TC-E-xx` (Edge).

| Kategori | Definisi |
|---|---|
| Positive | Masukan dan alur benar; sistem diharapkan berhasil memproses. |
| Negative | Masukan salah/tidak lengkap; sistem diharapkan menolak disertai pesan jelas. |
| Edge | Kondisi batas/ekstrem yang menguji ketahanan dan konsistensi sistem. |

Status diisi **PASS** atau **FAIL**.

---

## 6. Ringkasan Cakupan Pengujian

| Kategori | Jumlah Kasus Uji |
|---|:--:|
| Positive | 5 |
| Negative | 5 |
| Edge | 5 |
| **Total** | **15** |

---

## 7. Rincian Kasus Uji

### 7.1 Positive

| ID | Skenario Pengujian | Test Data | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| TC-P-01 | Menambahkan data pelanggan order walk-in | Nama: boy; No HP: 08123456789; Layanan: Cuci saja; Berat: 3 kg; Slot: Siang | Data pelanggan berhasil ditambahkan | Data tampil di menu detail struk | PASS |
| TC-P-02 | Mengubah status cucian pelanggan | No. Resi: AL-20260611-002; Status: Sedang dicuci | Status cucian berhasil diperbarui menjadi "Sedang disetrika" | Status cucian berhasil diperbarui | PASS |
| TC-P-03 | Menugaskan kurir/driver untuk jemput lokasi | Pesanan: AL-20260611-002 | Tugas penjemputan berhasil didelegasikan ke akun kurir yang dipilih | Tugas penjemputan berhasil ditugaskan ke kurir | PASS |
| TC-P-04 | Memantau rekap total pendapatan harian | Filter: Transaksi Hari Ini | Dashboard menampilkan grafik dan total nominal uang masuk hari ini secara akurat | Nominal pendapatan harian muncul sesuai total transaksi | PASS |
| TC-P-05 | Memicu notifikasi email otomatis lewat perubahan status | Konfirmasi selesai | Status berubah ke "Selesai" dan otomatis mengirim email ke pelanggan | Status berubah dan email terkirim ke pelanggan | PASS |

### 7.2 Negative

| ID | Skenario Pengujian | Test Data | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| TC-N-01 | Mengosongkan form harga saat tambah layanan | Nama: Cuci Helm; Harga: 0 | Sistem menolak penyimpanan dan menampilkan validasi "Kolom harga wajib diisi" | Data gagal disimpan dan muncul teks validasi merah | PASS |
| TC-N-02 | Menginput format harga non-angka (huruf/simbol) | Nama: Express Kilat; Harga: ketik huruf | Sistem mencegah karakter non-angka diketik (terblokir otomatis) | Karakter huruf/simbol tidak bisa diketik dan tidak tampil di form harga | PASS |
| TC-N-03 | Menginput nilai diskon negatif (minus) | Nama: Voucher Ramadhan; Diskon: -10 | Sistem menolak diskon di bawah nol dan menampilkan validation error | Data gagal disimpan dan muncul peringatan diskon tidak valid | PASS |
| TC-N-04 | Mengubah status pesanan yang sudah dibatalkan | Pesanan status "DIBATALKAN" (No. Resi: #AL-20260611-009) | Sistem mengunci pesanan dan menghilangkan tombol aksi perubahan status | Tombol ubah status tidak tersedia pada pesanan yang sudah dibatalkan | PASS |
| TC-N-05 | Menyimpan pesanan walk-in tanpa nama pelanggan | Nama: (dikosongkan); Layanan: Cuci Setrika; Berat: 3 kg | Sistem menolak pembuatan pesanan dan menampilkan validasi error | Pesanan gagal diproses dan muncul peringatan merah pada kolom Nama | PASS |

### 7.3 Edge

| ID | Skenario Pengujian | Test Data | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| TC-E-01 | Memberikan diskon maksimal (batas atas 100%) | Diskon: 100% | Sistem menerima (100% valid sebagai promo gratis); total tagihan menjadi Rp 0 | Total tagihan menjadi Rp 0 dan berhasil disimpan | PASS |
| TC-E-02 | Menginput berat cucian di bawah batas minimum (<0,5 kg) | Walk-in; Berat: 0,1 | Sistem memblokir submit dan menampilkan peringatan | Muncul pop-up validasi: "Value must be greater than or equal to 0,5."; form tidak bisa dilanjutkan | PASS |
| TC-E-03 | Menginput berat cucian ekstrem (batas atas) | Walk-in; Berat: 999 kg | Sistem mendeteksi melebihi 50 kg dan mencegah proses | Muncul pop-up validasi: "Value must be less than or equal to 50."; form tidak bisa disubmit | PASS |
| TC-E-04 | Memaksimalkan batas karakter kolom catatan | Catatan: tepat 255 karakter | Sistem menampung teks penuh tanpa terpotong | Teks tersimpan utuh dan tampil rapi pada detail pesanan | PASS |
| TC-E-05 | Menginput berat tepat di batas minimum valid (0,5 kg) | Walk-in; Layanan: Cuci Setrika; Berat: 0,5 | Sistem menerima (0,5 adalah batas bawah); form berhasil tanpa error | Sistem menerima 0,5 dan pesanan tersimpan ke database | PASS |

---

## 8. Rekapitulasi Hasil Pengujian

| Kategori | Jumlah TC | PASS | FAIL | Persentase Kelulusan |
|---|:--:|:--:|:--:|:--:|
| Positive | 5 | 5 | 0 | 100% |
| Negative | 5 | 5 | 0 | 100% |
| Edge | 5 | 5 | 0 | 100% |
| **Total** | **15** | **15** | **0** | **100%** |

---

## 9. Kesimpulan

Seluruh 15 kasus uji pada role Admin berstatus **PASS** (100%). Fitur pesanan walk-in,
pembaruan status, penugasan driver, rekap pendapatan, notifikasi email otomatis, serta
validasi input (harga, diskon, berat, nama) dan kondisi batas berfungsi sesuai spesifikasi.
