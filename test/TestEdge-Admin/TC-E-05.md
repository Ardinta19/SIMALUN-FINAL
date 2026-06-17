Skenario Pengujian:
Menginput Berat Cucian Tepat di Batas Minimum Valid (0,5 kg)

Test Data:
Tambah Pesanan Walk-in
Layanan: Cuci Setrika
Berat: 0,5

Expected Result:
Sistem menerima input tersebut karena angka 0,5 adalah batas bawah yang diizinkan. Form berhasil diproses tanpa memunculkan error validasi.

Actual Result:
Sistem menerima angka 0,5 dan pesanan berhasil disimpan ke database

Status:
PASS