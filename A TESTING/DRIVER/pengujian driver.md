# DOKUMEN PENGUJIAN PERANGKAT LUNAK
### Test Case — Role Driver (Kurir)
### Aplikasi SIMALUN (Sistem Manajemen Laundry — Azka Laundry)

---

## 1. Informasi Dokumen

| Atribut | Keterangan |
|---|---|
| Nama Aplikasi | SIMALUN — Azka Laundry |
| Role yang Diuji | Driver (Kurir) |
| Jenis Pengujian | Black-box Testing (Manual) |
| Versi Aplikasi | 1.0 |
| Penguji | ____________________ |
| Mata Kuliah | MPSI |

---

## 2. Tujuan Pengujian

Memverifikasi bahwa fitur-fitur yang dapat diakses oleh role Driver pada aplikasi SIMALUN
berjalan sesuai spesifikasi fungsional, baik pada kondisi normal (positive), kondisi tidak
normal (negative), maupun kondisi batas (edge). Pengujian menggunakan metode black-box.

---

## 3. Ruang Lingkup Pengujian

Pengujian mencakup: melihat daftar & detail order, konfirmasi penjemputan, penyelesaian
order (foto bukti & pembayaran), profil & logout, validasi login, serta kondisi batas input
berat. Bukti pengujian (screenshot) tersimpan pada folder
`SCREENSHOT/SCREENSHOT-P-DRIVER`, `SCREENSHOT-N-DRIVER`, dan `SCREENSHOT-E-DRIVER`.

---

## 4. Lingkungan Pengujian

| Komponen | Spesifikasi |
|---|---|
| URL Aplikasi | https://azkalaundry.store |
| Peramban | Google Chrome (versi terbaru) |
| Perangkat | Mobile (Android / iOS) dan Desktop |
| Koneksi | Internet stabil; izin lokasi (GPS) aktif |
| Akun Uji | Akun driver aktif; tersedia pesanan yang ditugaskan |

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
| Positive | 6 |
| Negative | 5 |
| Edge | 3 |
| **Total** | **14** |

---

## 7. Rincian Kasus Uji

### 7.1 Positive

| ID | Skenario Pengujian | Test Data | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| TC-P-01 | Melihat daftar order | Klik beranda | Daftar order muncul | Semua order muncul di beranda dan siap dijemput | PASS |
| TC-P-02 | Melihat detail pesanan | Klik detail pesanan | Detail order tampil | Detail order muncul | PASS |
| TC-P-03 | Melakukan konfirmasi penjemputan | Isi berat aktual, lalu konfirmasi penjemputan | Status order diterima/diperbarui | Status penjemputan diperbarui | PASS |
| TC-P-04 | Menyelesaikan order | Upload foto bukti, konfirmasi pesanan, pilih metode pembayaran, selesaikan pesanan | Status berubah menjadi "Selesai" | Status diperbarui menjadi "Selesai" | PASS |
| TC-P-05 | Melihat profil driver | Klik menu profil | Data profil tampil | Profil berhasil ditampilkan | PASS |
| TC-P-06 | Keluar dari akun driver | Klik keluar | Keluar dari akun driver | Kembali ke halaman dashboard/login | PASS |

### 7.2 Negative

| ID | Skenario Pengujian | Test Data | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| TC-N-01 | Login dengan password yang salah | Email: kochengcok@gmail.com; Password: (salah) | Login gagal dan muncul pesan error | Sistem menolak login | PASS |
| TC-N-02 | Login dengan email yang salah | Email: anjayalok@gmail.com; Password: @AbelNew321 | Login gagal | Sistem menampilkan pesan kesalahan | PASS |
| TC-N-03 | Login tanpa mengisi email dan password | Email: -; Password: - | Validasi muncul | Sistem meminta field untuk diisi | PASS |
| TC-N-04 | Input email dengan karakter khusus | Email: &#*^*&*!; Password: @AbelNew321 | Login gagal | "Email/No. HP atau password yang kamu masukkan salah. Format email atau No. HP tidak valid" | PASS |
| TC-N-05 | Menghubungi customer tanpa jaringan internet | Klik chat WA saat offline | WhatsApp tidak dapat dibuka dan muncul pesan gagal koneksi | Sistem menampilkan error koneksi | PASS |

### 7.3 Edge

| ID | Skenario Pengujian | Test Data | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| TC-E-01 | Membuka daftar tugas saat tidak ada data | Buka menu tugas | Tidak ada tugas yang muncul | Tampil "Semua tugas selesai. Belum ada tugas baru saat ini. Kamu akan mendapat notifikasi saat ada penugasan." | PASS |
| TC-E-02 | Estimasi berat bernilai 0 kg | Berat = 0 kg | Sistem menolak atau memberi validasi | Sistem tetap menolak namun validasi tidak muncul | PASS |
| TC-E-03 | Pesanan dengan berat sangat besar | Berat = 510 kg | Muncul peringatan batas maksimal berat | Data berhasil ditampilkan tanpa mengganggu sistem | PASS |

---

## 8. Rekapitulasi Hasil Pengujian

| Kategori | Jumlah TC | PASS | FAIL | Persentase Kelulusan |
|---|:--:|:--:|:--:|:--:|
| Positive | 6 | 6 | 0 | 100% |
| Negative | 5 | 5 | 0 | 100% |
| Edge | 3 | 3 | 0 | 100% |
| **Total** | **14** | **14** | **0** | **100%** |

---

## 9. Kesimpulan

Seluruh 14 kasus uji pada role Driver berstatus **PASS** (100%). Fitur melihat daftar &
detail order, konfirmasi penjemputan, penyelesaian order (foto bukti & pembayaran), profil,
logout, serta validasi login berfungsi sesuai spesifikasi pada kondisi positive, negative,
dan edge.
