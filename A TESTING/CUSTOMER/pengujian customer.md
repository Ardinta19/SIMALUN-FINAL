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
| Penguji | ____________________ |
| Mata Kuliah | MPSI |

---

## 2. Tujuan Pengujian

Memverifikasi bahwa fitur-fitur yang dapat diakses oleh role Customer pada aplikasi SIMALUN
berjalan sesuai spesifikasi fungsional, baik pada kondisi normal (positive), kondisi tidak
normal (negative), maupun kondisi batas (edge). Pengujian menggunakan metode black-box.

---

## 3. Ruang Lingkup Pengujian

Pengujian mencakup: pembuatan pesanan, melihat detail/riwayat pesanan, mengubah profil,
validasi alamat & nomor HP, serta validasi registrasi. Bukti pengujian (screenshot)
tersimpan pada folder `TestPositive-Customer`, `TestNegative-Customer`, dan
`TestEdge-Customer`.

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
| Positive | 3 |
| Negative | 3 |
| Edge | 3 |
| **Total** | **9** |

---

## 7. Rincian Kasus Uji

### 7.1 Positive

| ID | Skenario Pengujian | Test Data | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| TC-P-01 | Membuat pesanan laundry baru | Pilih layanan cuci kiloan, berat 5 kg | Pesanan berhasil dibuat | Pesanan berhasil tersimpan | PASS |
| TC-P-02 | Melihat detail pesanan | Membuka menu riwayat pesanan | Detail pesanan tampil lengkap | Detail pesanan muncul | PASS |
| TC-P-03 | Mengubah profil customer | Mengganti nama atau nomor telepon | Data profil berhasil diperbarui | Profil berhasil diperbarui | PASS |

### 7.2 Negative

| ID | Skenario Pengujian | Test Data | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| TC-N-01 | Membuat pesanan tanpa alamat | Alamat kosong | Sistem menolak | Muncul pesan "Silakan isi kolom ini" | PASS |
| TC-N-02 | Registrasi tanpa mengisi nama | Nama kosong | Sistem menolak registrasi | Muncul pesan "Silakan isi kolom ini" | PASS |
| TC-N-03 | Mengubah profil dengan nomor HP kosong | Nomor HP kosong | Update gagal | Muncul pesan "Silakan isi kolom ini" | PASS |

### 7.3 Edge

| ID | Skenario Pengujian | Test Data | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| TC-E-01 | Nomor HP tepat jumlah digit minimum | 10 digit | Sistem menerima | Data tersimpan | PASS |
| TC-E-02 | Nomor HP melebihi batas digit | 20 digit | Sistem menolak | Pesan validasi muncul | PASS |
| TC-E-03 | Alamat menggunakan karakter khusus | Lrg.Sidodadi | Sistem menerima | Alamat tersimpan | PASS |

---

## 8. Rekapitulasi Hasil Pengujian

| Kategori | Jumlah TC | PASS | FAIL | Persentase Kelulusan |
|---|:--:|:--:|:--:|:--:|
| Positive | 3 | 3 | 0 | 100% |
| Negative | 3 | 3 | 0 | 100% |
| Edge | 3 | 3 | 0 | 100% |
| **Total** | **9** | **9** | **0** | **100%** |

---

## 9. Kesimpulan

Seluruh 9 kasus uji pada role Customer berstatus **PASS** (100%). Fitur pembuatan pesanan,
melihat detail/riwayat, ubah profil, serta validasi input (alamat, nama, nomor HP)
berfungsi sesuai spesifikasi pada kondisi positive, negative, dan edge.
