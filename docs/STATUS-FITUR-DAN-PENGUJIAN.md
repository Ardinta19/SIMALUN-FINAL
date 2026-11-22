# Status fitur dan pemeriksaan Berkah Kreatif

Snapshot setelah pengujian Windows pada commit `bdb1101`: **215 tes lulus, 1.225 assertion**. Perubahan setelah commit tersebut belum termasuk hasil itu. Ini adalah inventaris berdasarkan kode dan tes yang ada, bukan pernyataan bahwa seluruh kebutuhan sudah selesai, aman, atau siap produksi. Dokumen audit awal `AUDIT-BERKAH-KREATIF.md` adalah snapshot historis; status fitur di sana dapat sudah berubah.

## Inventaris alur sewa

| Area | Implementasi yang tersedia | Pemeriksaan yang masih diperlukan |
| --- | --- | --- |
| Publik dan akun | Katalog, kategori, foto, filter perkiraan stok berbasis tanggal, keranjang sesi dengan penambahan/pengurangan unit, pesan langsung, login/registrasi | Coba tampilan dan navigasi berbagai perangkat; sesi lintas browser/perangkat tidak disinkronkan; periksa alur Google secara nyata. |
| Katalog admin | Kelola produk, kategori, stok, paket durasi dan tarif universitas | Uji perubahan katalog bersamaan dengan transaksi; foto produk yang diganti dibersihkan bila tidak direferensikan produk lain, dan perubahan produk, kategori serta paket yang baru dilakukan dicatat di audit. |
| Checkout | Beberapa unit terpisah, tanggal acara sekaligus penerimaan per unit dan tanggal pengembalian, rincian desain/serah terima, harga dan tarif snapshot, persetujuan ketentuan; pesanan lama tetap mempertahankan tanggalnya | Uji banyak unit dan input batas di browser; tombol dinonaktifkan saat pengiriman jika JavaScript aktif; pengulangan untuk unit keranjang yang sama diperiksa di server, tetapi penguncian dan interleaving sesi tetap perlu uji proses bersamaan di MySQL; checkout memeriksa stok terkonfirmasi dan overlap antarunit, tetapi tidak mencadangkan stok; unit pending lain tidak dihitung sebagai reservasi dan perlu konfirmasi admin. |
| Reservasi | Konfirmasi, stok berdasarkan irisan tanggal, pembatalan dan riwayat, pelanjutan, pembatalan otomatis setelah 24 jam | Uji transaksi bersamaan pada MySQL dan scheduler nyata. Periksa penanganan jadwal dekat dan semua interleaving pembatalan/pelanjutan. |
| Ongkos | Tarif universitas atau penawaran manual per unit dan persetujuan pelanggan | Uji perubahan penawaran, metode, batas nominal dan peninjauan manual dalam alur lengkap. |
| Desain | Preview privat berversi, persetujuan/revisi, kontrol sebelum pengerjaan | Uji unggahan, akses silang, kegagalan storage dan penggunaan nyata pada perangkat. |
| Pembayaran | Bukti privat per unit, DP fleksibel, verifikasi, koreksi dan refund tercatat | Uji batas H-1 WIB, bukti terlambat/ditolak, race saat unggah dan verifikasi serta konsistensi setelah kegagalan. Refund adalah pencatatan manual, bukan transfer otomatis. |
| Operasional | Progres per unit, jadwal bulanan dan filter kegiatan, ringkasan cetak | Periksa kesesuaian alur bisnis di perangkat; jadwal bukan bukti serah terima. |
| Buku kas | Entri manual, perbandingan periode, ekspor CSV, pemasukan dari verifikasi dan pengeluaran dari refund | Audit konsistensi dan duplikasi secara menyeluruh, termasuk data finance lama yang ikut ditampilkan. |
| Notifikasi | Pemberitahuan rental dalam aplikasi untuk beberapa perubahan | Periksa seluruh transisi, penjadwal dan ketersediaan penerima; bukan jaminan email, WhatsApp, atau pemberitahuan push. |

## Pemeriksaan sebelum penggunaan nyata

1. Lengkapi dan sepakati detail operasional yang masih manual, termasuk perbaikan bukti pembayaran dan penyelesaian ongkos untuk jadwal dekat. Jangan mengasumsikan aturan yang belum disepakati.
2. Uji seluruh perjalanan pelanggan dan admin secara manual, termasuk setiap unit dalam satu pesanan; uji di layar kecil, tablet, desktop, navigasi keyboard dan tanpa JavaScript.
3. Uji race MySQL dengan proses terpisah untuk reservasi stok, pembayaran, pembatalan, pelanjutan dan scheduler; tes SQLite tidak membuktikan keamanan konkurensi MySQL.
4. Audit otorisasi, penyimpanan berkas privat, validasi, notifikasi, ketepatan zona waktu dan retry/idempotensi semua transisi. Periksa juga route laundry lama yang masih dapat diakses walau tidak menjadi alur sewa.
5. Tinjau advisory Composer dan npm, perbarui dependensi secara terukur dan jalankan ulang tes serta build. Lihat audit historis untuk risiko yang telah dicatat; jangan menganggap advisory yang lama sebagai inventaris terkini.
6. Pastikan scheduler dan worker berjalan serta terpantau, pencadangan dan pemulihan diuji, konfigurasi produksi diamankan, akun demo tidak digunakan dan kredensial historis yang masih berlaku dirotasi.

Jangan jalankan transaksi atau pembayaran nyata sebelum pemeriksaan dan keputusan tersebut selesai. Jangan menjalankan seeder atau migrasi destruktif terhadap database yang berisi data penting.

## Lampiran laporan kendala

Lampiran laporan baru disimpan pada disk lokal privat dan ditampilkan melalui rute admin terautentikasi. Lampiran historis pada `storage/app/public/reports` tetap berada di lokasi publik lama agar tidak rusak; tautan langsung lama masih dapat diakses jika `public/storage` tersedia. Untuk melindungi lampiran lama, cadangkan dan rencanakan migrasi berkas serta pembaruan jalurnya secara teruji; perubahan ini tidak memindahkan atau menghapus data lama. Bukti laundry lama juga perlu audit terpisah.

## Hasil tes pengguna terbaru

Pada commit `4870668`, pengguna menjalankan `php artisan test` di Windows: **230 passed, 1469 assertions**, 8,01 detik. Hasil ini mencakup tes fitur rating dan suka serta kebijakan referrer tautan publik. Perubahan setelah commit tersebut belum termasuk hasil ini. Tes otomatis tersebut bukan pengganti uji visual browser, uji konkurensi MySQL, atau pemeriksaan kesiapan produksi.

### Perubahan setelah `4870668` yang belum diuji di Windows

Commit `7a2c10e` (registrasi progresif), `5f5ff85` (login aksesibel), `3eb81e1` (galat checkout per unit), `58ebb76` (detail unit), `74a25aa` (filter tanggal gagal), `9a8e90d` (tanggal lama tidak dipakai), `0a6b07b` (filter pesanan), `af3e863` (tanggal keranjang lewat), `a57d07d` (checkout dengan isian tak wajar), `9e9cc96` (profil dan alamat), `cbad96c` (masuk, daftar, lupa sandi, edit alamat), dan `e56018c` (katalog admin, pengaturan pembayaran, kartu ulasan) belum dijalankan dengan `php artisan test` maupun browser nyata.

Langkah pemeriksaan yang disarankan:

1. `php artisan test` lalu catat jumlah lulus/gagal.
2. Buka keranjang berisi unit bertanggal kemarin: pastikan muncul peringatan, tautan detail tanpa tanggal, dan tombol tambah unit hilang; ubah tanggal di checkout.
3. Kirim form dengan nilai berbentuk larik (alat pengembang peramban) pada checkout, profil, alamat, masuk, daftar, lupa sandi, dan katalog admin; halaman harus kembali dengan pesan galat, bukan halaman error 500.
4. Periksa pesan galat jumlah di detail papan tampil tepat di bawah pemilih jumlah.

### Merek pada email dan konfigurasi

Email transaksi (termasuk reset kata sandi) masih mengambil nama usaha dari `Laundry::name()` yang membaca `LAUNDRY_NAME` di `.env`, dengan nilai bawaan "Azka Laundry". Ubah nilai berikut pada `.env` produksi sebelum dipakai pelanggan: `APP_NAME`, `LAUNDRY_NAME="Berkah Kreatif"`, `LAUNDRY_TAGLINE`, `LAUNDRY_ADDRESS`, `LAUNDRY_PHONE`, `LAUNDRY_PHONE_DISPLAY`, `LAUNDRY_WA`, serta `MAIL_FROM_ADDRESS`. Ini perubahan konfigurasi, bukan kode; nilai alamat dan nomor harus berasal dari pemilik usaha, jangan dikarang.
