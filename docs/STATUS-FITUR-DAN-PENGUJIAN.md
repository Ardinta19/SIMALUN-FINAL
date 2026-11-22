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
