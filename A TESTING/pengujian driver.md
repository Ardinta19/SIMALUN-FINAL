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
| Penyusun | Ardinata Saputra |
| Mata Kuliah | MPSI |

---

## 2. Tujuan Pengujian

Memverifikasi bahwa seluruh fitur yang dapat diakses oleh role Driver pada aplikasi SIMALUN
berjalan sesuai spesifikasi fungsional, baik pada kondisi normal maupun tidak normal dan
kasus batas. Pengujian menggunakan metode black-box.

---

## 3. Ruang Lingkup Pengujian

Pengujian mencakup modul-modul berikut yang dapat diakses Driver:

1. Dashboard & Daftar Tugas
2. Detail Tugas
3. Aksi Penjemputan (jemput → dicuci, input berat aktual)
4. Aksi Pengantaran (kirim → selesai, foto bukti)
5. Konfirmasi Pembayaran COD
6. Update Lokasi (GPS)
7. Tracking Tugas
8. Notifikasi
9. Lapor Kendala

Modul autentikasi diuji terpisah. Fitur khusus Customer dan Admin berada di luar cakupan.

---

## 4. Lingkungan Pengujian

| Komponen | Spesifikasi |
|---|---|
| URL Aplikasi | https://azkalaundry.store |
| Peramban | Google Chrome (versi terbaru) |
| Perangkat | Mobile (Android / iOS) dan Desktop |
| Koneksi | Internet stabil; izin lokasi (GPS) aktif |
| Akun Uji | Akun driver aktif; tersedia pesanan yang ditugaskan kepadanya |

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
| 1 | Dashboard & Daftar Tugas | 2 | 0 | 1 | 3 |
| 2 | Detail Tugas | 1 | 1 | 0 | 2 |
| 3 | Aksi Penjemputan | 2 | 2 | 1 | 5 |
| 4 | Aksi Pengantaran | 2 | 1 | 1 | 4 |
| 5 | Konfirmasi Pembayaran COD | 2 | 1 | 1 | 4 |
| 6 | Update Lokasi (GPS) | 2 | 1 | 1 | 4 |
| 7 | Tracking Tugas | 1 | 1 | 0 | 2 |
| 8 | Notifikasi | 1 | 0 | 1 | 2 |
| 9 | Lapor Kendala | 1 | 1 | 0 | 2 |
| | **Total** | **14** | **8** | **6** | **28** |

---

## 7. Rincian Kasus Uji

### 7.1 Modul Dashboard & Daftar Tugas

Ketentuan: Driver hanya melihat pesanan yang ditugaskan kepadanya dengan status `dijemput`, `dikirim`, atau `siap`.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| DTU-P1 | Positive | Driver punya tugas aktif | Buka dashboard/daftar tugas | Hanya tugas milik driver yang tampil (dijemput/dikirim/siap) | Daftar tugas tampil; hanya pesanan milik driver yang muncul | Lulus |
| DTU-P2 | Positive | Driver belum punya tugas | Buka daftar tugas | Daftar kosong tampil rapi tanpa error | Tampil keadaan kosong; tidak ada error | Lulus |
| DTU-E1 | Edge | Login sebagai driver | Coba akses URL area admin/customer | Ditolak; driver tidak bisa masuk area role lain | Akses ditolak middleware `role` | Lulus |

### 7.2 Modul Detail Tugas

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| DDT-P1 | Positive | Punya tugas | Buka detail tugas | Alamat, item, biaya, dan riwayat status tampil | Detail tugas + riwayat status tampil lengkap | Lulus |
| DDT-N1 | Negative | Login sebagai driver | Buka detail pesanan yang bukan tugasnya | Ditolak (403) | Ditolak dengan 403 | Lulus |

### 7.3 Modul Aksi Penjemputan (Jemput → Dicuci)

Ketentuan: Driver hanya dapat beraksi pada pesanan berstatus `dijemput`/`dikirim`. Saat menjemput, driver dapat mengisi berat aktual yang otomatis menghitung ulang biaya.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| PCK-P1 | Positive | Tugas berstatus `dijemput` | Konfirmasi penjemputan + isi berat aktual | Status → `dicuci`; biaya dihitung ulang dari berat aktual | Status berubah ke Dicuci; service_cost & total diperbarui sesuai berat aktual | Lulus |
| PCK-P2 | Positive | Tugas berstatus `dijemput` | Konfirmasi penjemputan tanpa ubah berat | Status → `dicuci`; biaya tetap dari estimasi | Status berubah ke Dicuci; biaya tetap | Lulus |
| PCK-N1 | Negative | Tugas berstatus `siap` (domain workshop) | Coba lakukan aksi penjemputan | Ditolak; "Status pesanan saat ini di luar kendali driver." | Ditolak; tampil pesan di luar kendali driver | Lulus |
| PCK-N2 | Negative | Tugas `dijemput` | Isi berat aktual 0 / negatif | Ditolak; berat aktual harus ≥ 0,1 | Ditolak; berat aktual minimal 0,1 | Lulus |
| PCK-E1 | Edge | Pesanan sudah `selesai` | Coba ubah status | Ditolak; "Pesanan sudah final, tidak bisa diubah lagi." | Ditolak; tampil "Pesanan sudah final, tidak bisa diubah lagi." | Lulus |

### 7.4 Modul Aksi Pengantaran (Kirim → Selesai)

Ketentuan: Pengantaran dimulai dari status `dikirim`. Driver dapat mengunggah foto bukti penyerahan saat menyelesaikan.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| DLV-P1 | Positive | Tugas berstatus `dikirim` | Selesaikan pengantaran + unggah foto bukti | Status → `selesai`; foto bukti tersimpan | Status Selesai; foto bukti tersimpan | Lulus |
| DLV-P2 | Positive | Tugas berstatus `dikirim` | Selesaikan tanpa foto | Status → `selesai` (foto opsional) | Status Selesai; tanpa foto bukti | Lulus |
| DLV-N1 | Negative | Tugas `dikirim` | Unggah berkas non-gambar sebagai bukti | Ditolak; bukti harus berupa gambar (jpeg/png/webp, maks 5 MB) | Ditolak; berkas non-gambar tidak diterima | Lulus |
| DLV-E1 | Edge | Tugas `dijemput` | Coba loncat langsung ke `selesai` | Ditolak; transisi tidak sah (harus lewat alur) | Ditolak; transisi status tidak diizinkan | Lulus |

### 7.5 Modul Konfirmasi Pembayaran COD

Ketentuan: Saat menyelesaikan pesanan, pesanan ditandai lunas (COD). Channel pembayaran dapat berupa cash/transfer/qris. Pemasukan tercatat otomatis di laporan keuangan.

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| COD-P1 | Positive | Tugas `dikirim` | Konfirmasi selesai + terima pembayaran tunai | Pesanan `selesai` & lunas; pemasukan tercatat di keuangan | Status Selesai & lunas (is_paid, paid_at); pemasukan tercatat sekali | Lulus |
| COD-P2 | Positive | Tugas `dikirim` | Pilih channel transfer/qris saat menyelesaikan | Channel pembayaran tercatat sesuai pilihan | Channel pembayaran (cash/transfer/qris) tersimpan | Lulus |
| COD-N1 | Negative | Pesanan `selesai`/lunas | Coba konfirmasi pembayaran lagi | Ditolak; pesanan sudah final | Ditolak; pesanan final tidak bisa diproses ulang | Lulus |
| COD-E1 | Edge | Tugas `dikirim` selesai | Pemasukan tercatat | Tidak ada entri pemasukan ganda walau aksi terulang | Pemasukan tunggal; idempotent (tidak ganda) | Lulus |

### 7.6 Modul Update Lokasi (GPS)

Ketentuan: Driver mengirim koordinat lokasi yang ditampilkan ke customer pada tracking. Pengiriman lokasi dibatasi lajunya (maks 60 kali/menit).

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| GPS-P1 | Positive | Punya tugas aktif, izin lokasi aktif | Kirim/update lokasi | Koordinat tersimpan; waktu update tercatat | Lokasi (lat/lng) tersimpan; `driver_location_updated_at` diperbarui | Lulus |
| GPS-P2 | Positive | Lokasi terkirim | Customer pantau tracking | Posisi kurir tampil di peta customer | Posisi kurir tampil di peta customer | Lulus |
| GPS-N1 | Negative | Login sebagai driver | Kirim lokasi untuk pesanan yang bukan tugasnya | Ditolak; bukan tugasnya | Ditolak; tidak bisa update lokasi pesanan orang lain | Lulus |
| GPS-E1 | Edge | Punya tugas aktif | Kirim lokasi sangat sering (>60×/menit) | Permintaan berlebih diblokir (rate limit) | Permintaan berlebih diblokir (rate limit 60/menit) | Lulus |

### 7.7 Modul Tracking Tugas

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| DTR-P1 | Positive | Punya tugas aktif | Buka halaman tracking driver | Tugas aktif & tujuan tampil | Tugas aktif & info tujuan tampil | Lulus |
| DTR-N1 | Negative | Tidak ada tugas aktif | Buka tracking | Keadaan kosong tampil tanpa error | Tampil keadaan kosong; tidak ada error | Lulus |

### 7.8 Modul Notifikasi (Driver)

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| NTD-P1 | Positive | Driver ditugaskan/ada pembatalan | Buka notifikasi driver | Notifikasi "Tugas Baru"/pembatalan tampil dengan waktu relatif | Notifikasi tugas tampil dengan judul, pesan, dan waktu | Lulus |
| NTD-E1 | Edge | Notifikasi lebih dari 1 halaman | Gunakan navigasi halaman | Pagination berfungsi rapi | Pagination berfungsi; tombol rapi | Lulus |

### 7.9 Modul Lapor Kendala (Driver)

| ID | Kategori | Prakondisi | Langkah & Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| LPD-P1 | Positive | Form lapor kendala terbuka | Pilih kategori, isi deskripsi cukup, kirim | Laporan terkirim; "Laporan berhasil dikirim. Terima kasih atas masukannya!" | Laporan terkirim; pesan terima kasih tampil | Lulus |
| LPD-N1 | Negative | Form lapor kendala terbuka | Deskripsi < 10 karakter | Ditolak; deskripsi minimal 10 karakter | Ditolak; deskripsi minimal 10 karakter | Lulus |

---

## 8. Rekapitulasi Hasil Pengujian

| No | Modul | Jumlah TC | Lulus | Gagal | Persentase Kelulusan |
|---|---|:--:|:--:|:--:|:--:|
| 1 | Dashboard & Daftar Tugas | 3 | 3 | 0 | 100% |
| 2 | Detail Tugas | 2 | 2 | 0 | 100% |
| 3 | Aksi Penjemputan | 5 | 5 | 0 | 100% |
| 4 | Aksi Pengantaran | 4 | 4 | 0 | 100% |
| 5 | Konfirmasi Pembayaran COD | 4 | 4 | 0 | 100% |
| 6 | Update Lokasi (GPS) | 4 | 4 | 0 | 100% |
| 7 | Tracking Tugas | 2 | 2 | 0 | 100% |
| 8 | Notifikasi | 2 | 2 | 0 | 100% |
| 9 | Lapor Kendala | 2 | 2 | 0 | 100% |
| | **Total** | **28** | **28** | **0** | **100%** |

---

## 9. Kesimpulan dan Rekomendasi

Kesimpulan:

Seluruh 28 kasus uji pada role Driver berstatus **Lulus** (100%). Alur kerja kurir —
melihat daftar tugas miliknya, menjemput (dengan penimbangan berat aktual & perhitungan
ulang biaya), mengantar (dengan foto bukti), konfirmasi pembayaran COD, dan pembaruan
lokasi GPS — berfungsi sesuai spesifikasi. Proteksi kepemilikan tugas (403), guard alur
status, pembatasan laju update lokasi, dan idempotensi pencatatan pemasukan berjalan
dengan baik.

Rekomendasi:

1. Lengkapi bukti screenshot untuk tiap kasus uji role Driver sebagai lampiran visual.
2. Lakukan pengujian lapangan GPS pada kondisi jaringan seluler nyata (sinyal lemah,
   berpindah sel) untuk memastikan akurasi & kestabilan pembaruan lokasi.

---

## Lampiran — Catatan Perilaku yang Disengaja (Bukan Cacat)

1. **Batas kendali driver.** Driver hanya boleh beraksi pada pesanan berstatus
   `dijemput`/`dikirim` (yang ada di tangannya). Status `dicuci`/`disetrika`/`siap` adalah
   domain workshop/admin — penolakan adalah perilaku yang diharapkan.
2. **Foto bukti opsional.** Penyelesaian pengantaran tetap dapat dilakukan tanpa foto,
   namun bila diunggah harus berupa gambar (jpeg/png/webp) maks 5 MB.
3. **Pembatasan laju lokasi.** Pengiriman koordinat dibatasi 60×/menit untuk mencegah beban
   berlebih; pemblokiran sementara adalah perilaku yang diharapkan.
