Skenario Pengujian:
Menginput Berat Cucian Ekstrem (Batas Atas Wajar)

Test Data:
Tambah Pesanan Walk-in
Berat: 999 Kg

Expected Result:
Sistem mendeteksi angka melebihi batas maksimal yang diizinkan (50 kg), mencegah form untuk diproses, dan menampilkan pesan peringatan.

Actual Result:
Muncul pop-up validasi browser: "Value must be less than or equal to 50." Form tidak bisa disubmit.

Status:
PASS