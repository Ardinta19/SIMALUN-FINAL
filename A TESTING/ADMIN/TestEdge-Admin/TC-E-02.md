Skenario Pengujian:
Menginput Berat Cucian di Bawah Batas Minimum (< 0,5 kg)

Test Data:
Tambah Pesanan Walk-in
Berat: 0,1

Expected Result:
Sistem mendeteksi bahwa angka berada di bawah batas minimal (0,5 kg), memblokir form agar tidak bisa disubmit, dan menampilkan pesan peringatan.

Actual Result:
Muncul pop-up validasi browser: "Value must be greater than or equal to 0,5." Form tidak bisa dilanjutkan.

Status:
PASS
