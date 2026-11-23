# Voucher sewa: aturan dan cara pakai

Fitur voucher untuk alur **sewa papan bunga**. Kode dibuat admin, dimasukkan
pelanggan di halaman checkout, lalu potongannya dibagi ke setiap unit pesanan.

## 1. Yang diatur admin saat membuat voucher

Halaman: **Beranda admin → Aksi cepat → Voucher** (`/admin/sewa/voucher`).

| Pengaturan | Arti |
| --- | --- |
| Kode | Huruf/angka/`-`/`_`, disimpan UPPERCASE. Satu kode hanya bisa ada sekali. |
| Jenis potongan | **Persen** (1–100, boleh diberi batas rupiah) atau **nominal tetap**. |
| Nilai potongan | Angka persen atau rupiah sesuai jenis. |
| Potongan berlaku untuk | **Hanya harga sewa** atau **harga sewa + ongkos antar/jemput**. |
| Minimum belanja | Total harga sewa minimal agar kode bisa dipakai (0 = tanpa minimum). |
| Batas potongan persen | Untuk jenis persen: potongan tidak melebihi angka ini. Kosong = tanpa batas. |
| Kuota total | Berapa kali kode boleh dipakai semua pelanggan. Kosong = tanpa batas. |
| Batas per pelanggan | Sekali (bawaan), 3, 5, 10, atau tanpa batas per pelanggan. |
| Masa berlaku | Tanggal mulai dan/atau tanggal berakhir. Kosong = berlaku sekarang / tanpa batas. |
| Aktif | Voucher nonaktif tidak bisa dipakai, tetapi pesanan lama tidak berubah. |

## 2. Yang dialami pelanggan

1. Di halaman **checkout**, ada bagian "Kode voucher". Pelanggan mengetik kode
   lalu menekan **Cek kode**.
2. Tombol itu memanggil `POST /voucher-sewa/cek` dan menampilkan perkiraan
   potongan dari harga dasar keranjang. Pilihan paket durasi baru diketahui saat
   pesanan dikirim, jadi angkanya bisa berbeda sedikit dari potongan final.
3. Saat pesanan dikirim, server memeriksa ulang kode (aktif, masa berlaku,
   kuota, batas per pelanggan, minimum belanja) lalu menghitung potongan final.
   Kode yang tidak layak membuat pesanan ditolak dan keranjang tetap utuh.
4. Potongan tampil di halaman detail pesanan, detail unit, struk cetak, dan
   detail pesanan admin.

## 3. Cara potongan dihitung

- **Basis potongan** = jumlah harga sewa semua unit pada pesanan.
- Bila cakupan voucher **harga sewa + ongkos**, potongan dihitung ulang setelah
  pelanggan menyetujui ongkos: basisnya menjadi harga sewa + ongkos yang
  disetujui. Potongan tidak pernah melebihi tagihan dan tidak pernah membuat
  tagihan lebih kecil dari pembayaran yang sudah terverifikasi.
- Potongan **dibagi ke setiap unit** secara proporsional terhadap harga sewa
  unit. Sisa pembulatan diberikan ke unit dengan harga terbesar, sehingga
  jumlah potongan per unit selalu sama dengan total potongan pesanan.
- Tagihan unit menjadi: **harga sewa − potongan sewa + (ongkos − potongan
  ongkos)**. Semua perhitungan pembayaran, tenggat, pengingat, dan
  pengembalian memakai angka yang sama (`payableAmount()`), jadi tidak ada
  rumus kembar yang bisa berbeda.

## 4. Yang disimpan

| Tempat | Isi |
| --- | --- |
| `rental_orders` | `voucher_id`, `voucher_code`, `discount_amount` (total potongan pesanan). |
| `rental_order_units` | `discount_amount` (potongan sewa per unit), `transport_discount_amount` (potongan ongkos per unit). |
| `rental_voucher_usages` | Satu baris per pesanan: voucher, pesanan, pelanggan, potongan. Unik per (voucher, pesanan). |
| `vouchers` | `scope`, `per_customer_limit`, `used_count`. |

`vouchers` dan `voucher_usages` adalah tabel lama modul laundry. Voucher sewa
memakai `vouchers` (bentuknya sudah cocok) dan mencatat pemakaiannya di tabel
baru `rental_voucher_usages` karena `voucher_usages.order_id` menunjuk tabel
pesanan laundry.

## 5. Perilaku pada kasus pinggir

- **Pesanan diulang (idempotensi):** kode voucher ikut menjadi bagian isi
  pesanan. Pengiriman ulang dengan kode berbeda dianggap pesanan berbeda,
  bukan duplikat.
- **Kuota bersamaan:** baris voucher dikunci (`lockForUpdate`) di dalam
  transaksi checkout sebelum `used_count` ditambah.
- **Unit dibatalkan setelah voucher dipakai:** potongan unit itu ikut hilang
  bersama tagihannya; pesanan lain tidak terpengaruh. Kuota tidak dikembalikan
  otomatis — bila pemilik ingin mengembalikan kuota, lakukan manual.
- **Voucher dinonaktifkan:** hanya mencegah pemakaian baru.
- **Ongkos dikirim ulang setelah disetujui:** ongkos terkunci bila sudah ada
  pembayaran, sehingga potongan ongkos juga tidak berubah tanpa sengaja.

## 6. Pemeriksaan yang sudah ada

`tests/Feature/RentalVoucherTest.php` menguji: potongan persen dan pencatatan
pemakaian, checkout tanpa voucher, minimum belanja, batas sekali per pelanggan,
kuota habis, voucher nonaktif/kedaluwarsa, pemeriksaan kode di checkout, dan
potongan ulang setelah ongkos disetujui, serta pembuatan/penonaktifan voucher
oleh admin.

## 7. Yang belum dikerjakan

- Voucher tidak bisa digabung (satu pesanan satu kode).
- Tidak ada laporan pemakaian voucher per periode di buku kas.
- Penghapusan modul laundry lama ditunda sesuai keputusan pemilik.
