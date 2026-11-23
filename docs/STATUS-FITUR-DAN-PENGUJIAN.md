# Status fitur dan pemeriksaan Berkah Kreatif

Snapshot kode setelah babak C alur pemesanan (ongkos dua arah, batal mandiri, pesan ulang, draf checkout, pengingat kembali). Hasil `php artisan test` terakhir yang dilaporkan pengguna adalah pada commit `943494e`: **306 lulus, 4 gagal**; kegagalannya diperbaiki di `01b6f4b` tetapi belum diuji ulang, dan perubahan babak C sesudahnya belum dijalankan di Windows. Ini adalah inventaris berdasarkan kode dan tes yang ada, bukan pernyataan bahwa seluruh kebutuhan sudah selesai, aman, atau siap produksi. Dokumen audit awal `AUDIT-BERKAH-KREATIF.md` adalah snapshot historis; status fitur di sana dapat sudah berubah.

## Inventaris alur sewa

| Area | Implementasi yang tersedia | Pemeriksaan yang masih diperlukan |
| --- | --- | --- |
| Publik dan akun | Katalog, kategori, foto, filter perkiraan stok berbasis tanggal, keranjang sesi dengan penambahan/pengurangan unit, pesan langsung, login/registrasi | Coba tampilan dan navigasi berbagai perangkat; sesi lintas browser/perangkat tidak disinkronkan; periksa alur Google secara nyata. |
| Katalog admin | Kelola produk, kategori, stok, paket durasi dan tarif universitas | Uji perubahan katalog bersamaan dengan transaksi; foto produk yang diganti dibersihkan bila tidak direferensikan produk lain, dan perubahan produk, kategori serta paket yang baru dilakukan dicatat di audit. |
| Checkout | Beberapa unit terpisah, tanggal acara sekaligus penerimaan per unit dan tanggal pengembalian, rincian desain/serah terima, harga dan tarif snapshot, persetujuan ketentuan; pesanan lama tetap mempertahankan tanggalnya | Uji banyak unit dan input batas di browser; tombol dinonaktifkan saat pengiriman jika JavaScript aktif; pengulangan untuk unit keranjang yang sama diperiksa di server, tetapi penguncian dan interleaving sesi tetap perlu uji proses bersamaan di MySQL; checkout memeriksa stok terkonfirmasi dan overlap antarunit, tetapi tidak mencadangkan stok; unit pending lain tidak dihitung sebagai reservasi dan perlu konfirmasi admin. |
| Reservasi | Tanggal ditahan sejak pesanan dikirim (pending), stok berdasarkan irisan tanggal, konfirmasi, pembatalan dan riwayat, pelanjutan, pembatalan otomatis setelah 24 jam, jendela pemesanan 366 hari | Uji transaksi bersamaan pada MySQL dan scheduler nyata. Periksa penanganan jadwal dekat dan semua interleaving pembatalan/pelanjutan. |
| Ongkos | Tarif universitas atau penawaran manual per unit; pelanggan menyetujui atau **menolak beralasan**, admin mengirim versi baru selama belum ada pembayaran, riwayat penawaran tersimpan | Uji negosiasi dua arah, penolakan setelah pembayaran masuk (harus ditolak), dan tampilan riwayat di dua peran. |
| Desain | Preview privat berversi, persetujuan/revisi, kontrol sebelum pengerjaan, tenggat respons (48 jam / H-3) dan keputusan admin "lanjutkan dengan catatan" beralasan yang tercatat di riwayat | Uji unggahan, akses silang, kegagalan storage dan penggunaan nyata pada perangkat. |
| Pembayaran | Bukti privat per unit, DP fleksibel, verifikasi, koreksi dan refund tercatat, toleransi bukti terlambat sampai H+1 pukul 12.00 WIB sebelum DP hangus | Uji batas H-1 WIB, bukti terlambat/ditolak, race saat unggah dan verifikasi serta konsistensi setelah kegagalan. Refund adalah pencatatan manual, bukan transfer otomatis. |
| Operasional | Progres per unit (serah terima memeriksa desain dan tagihan), jadwal bulanan dan filter kegiatan, ringkasan cetak, filter "toleransi" dan "lewat tanggal kembali", kartu dasbor "menunggu pelanggan" | Periksa kesesuaian alur bisnis di perangkat; jadwal bukan bukti serah terima. |
| Buku kas | Entri manual, perbandingan periode, ekspor CSV, pemasukan dari verifikasi dan pengeluaran dari refund | Audit konsistensi dan duplikasi secara menyeluruh, termasuk data finance lama yang ikut ditampilkan. |
| Notifikasi | Pemberitahuan rental dalam aplikasi untuk beberapa perubahan | Periksa seluruh transisi, penjadwal dan ketersediaan penerima; bukan jaminan email, WhatsApp, atau pemberitahuan push. |
| Pengingat | Perintah `rental:remind` dijadwalkan tiap lima menit: bukti menganggur lebih dari 6 jam (admin), pelunasan H-1 (pelanggan), toleransi H+1 (pelanggan dan admin), respons desain lebih dari 24 jam (pelanggan), eskalasi 48 jam atau H-3 (admin), unit belum ditandai kembali H+2 (admin); satu pesan per unit, jenis, penerima dan tanggal | Uji pada data nyata bahwa pesan tidak menumpuk, penerima nonaktif benar-benar dilewati, dan isi pesan sesuai tagihan; pengingat tidak mengubah status apa pun |

## Pemeriksaan sebelum penggunaan nyata

1. Lengkapi dan sepakati detail operasional yang masih manual, termasuk perbaikan bukti pembayaran dan penyelesaian ongkos untuk jadwal dekat. Jangan mengasumsikan aturan yang belum disepakati.
2. Uji seluruh perjalanan pelanggan dan admin secara manual, termasuk setiap unit dalam satu pesanan; uji di layar kecil, tablet, desktop, navigasi keyboard dan tanpa JavaScript.
3. Uji race MySQL dengan proses terpisah untuk reservasi stok, pembayaran, pembatalan, pelanjutan dan scheduler; tes SQLite tidak membuktikan keamanan konkurensi MySQL.
4. Audit otorisasi, penyimpanan berkas privat, validasi, notifikasi, ketepatan zona waktu dan retry/idempotensi semua transisi. Periksa juga route laundry lama yang masih dapat diakses walau tidak menjadi alur sewa.
5. Tinjau advisory Composer dan npm, perbarui dependensi secara terukur dan jalankan ulang tes serta build. Lihat audit historis untuk risiko yang telah dicatat; jangan menganggap advisory yang lama sebagai inventaris terkini.
6. Pastikan scheduler dan worker berjalan serta terpantau, pencadangan dan pemulihan diuji, konfigurasi produksi diamankan, akun demo tidak digunakan dan kredensial historis yang masih berlaku dirotasi.

Jangan jalankan transaksi atau pembayaran nyata sebelum pemeriksaan dan keputusan tersebut selesai. Jangan menjalankan seeder atau migrasi destruktif terhadap database yang berisi data penting.

### Uji manual babak C (belum dijalankan)

1. **Ongkos dua arah.** Buat unit dengan metode antar; admin kirim penawaran, pelanggan tekan "Tolak" tanpa alasan (harus ditolak) lalu dengan alasan. Pastikan tagihan kembali "belum pasti" dan riwayat menampilkan penolakan. Admin kirim versi 2, pelanggan setujui, lalu coba setujui versi 1 lewat alat pengembang (harus ditolak). Setelah pelanggan mengunggah bukti, admin tidak boleh lagi mengubah ongkos.
2. **Batal mandiri.** Pada unit `pending`/`confirmed` yang belum dikerjakan, tekan "Batalkan unit ini" tanpa centang (harus ditolak), lalu dengan alasan + centang. Unit harus `cancelled` dengan kode `customer_request`, tanggal kembali tersedia di katalog, dan admin masih bisa "Lanjutkan unit" pada hari acara. Unit yang sudah "Dikerjakan" tidak boleh dibatalkan sendiri.
3. **Perbaiki data.** Ubah nama/ucapan/alamat sebelum bayar: harus tersimpan dan muncul di riwayat progres. Setelah desain disetujui, ubah nama (harus ditolak) dan ubah alamat (harus diterima). Setelah pembayaran terverifikasi, semua perubahan ditolak.
4. **Pesan ulang.** Dari unit batal, tekan "Pesan ulang": keranjang berisi unit dengan isian lama tanpa tanggal, checkout menampilkan isian itu, dan tanggal baru tetap diperiksa ketersediaannya.
5. **Filter katalog.** Saring katalog (kata kunci + kategori + tanggal), buka satu papan, tekan "← Kembali ke katalog": filter harus sama. Halaman lain di detail juga harus kembali ke katalog berfilter.
6. **Draf checkout.** Isi checkout sebagian, kirim (gagal), buka keranjang, lalu kembali ke checkout: isian tadi harus muncul lagi. Setelah pesanan berhasil, keranjang dan draf harus kosong.
7. **Pengingat kembali.** Tandai unit serah terima, atur `ends_on` dua hari ke belakang, jalankan `php artisan rental:remind` dua kali: hanya satu notifikasi `return_pending`, dan dasbor menampilkan kartu "sudah lewat tanggal kembali". Filter `return_overdue` dan `grace` di daftar pesanan admin harus menyaring dengan benar.
8. **Gerbang serah terima.** Coba majukan unit `ready → handed_over` tanpa desain disetujui (ubah data lewat database): harus ditolak dengan pesan desain.

## Lampiran laporan kendala

Lampiran laporan baru disimpan pada disk lokal privat dan ditampilkan melalui rute admin terautentikasi. Lampiran historis pada `storage/app/public/reports` tetap berada di lokasi publik lama agar tidak rusak; tautan langsung lama masih dapat diakses jika `public/storage` tersedia. Untuk melindungi lampiran lama, cadangkan dan rencanakan migrasi berkas serta pembaruan jalurnya secara teruji; perubahan ini tidak memindahkan atau menghapus data lama. Bukti laundry lama juga perlu audit terpisah.

## Hasil tes pengguna terbaru

Pada commit `943494e`, pengguna menjalankan `php artisan test` di Windows: **306 lulus, 4 gagal (2.539 assertion)**; empat kegagalan itu diperbaiki di `01b6f4b` dengan `RentalCategory::firstOrCreate` dan `actingAs($admin)` pada tes tenggat desain, tetapi hasil ulangnya belum dilaporkan. Hasil stabil terakhir sebelum rangkaian perbaikan alur adalah **230 passed, 1.469 assertions** pada commit `4870668`. Hasil ini mencakup tes fitur rating dan suka serta kebijakan referrer tautan publik. Perubahan setelah commit tersebut belum termasuk hasil ini. Tes otomatis tersebut bukan pengganti uji visual browser, uji konkurensi MySQL, atau pemeriksaan kesiapan produksi.

### Perubahan setelah `4870668` yang belum diuji di Windows

Commit `7a2c10e` (registrasi progresif), `5f5ff85` (login aksesibel), `3eb81e1` (galat checkout per unit), `58ebb76` (detail unit), `74a25aa` (filter tanggal gagal), `9a8e90d` (tanggal lama tidak dipakai), `0a6b07b` (filter pesanan), `af3e863` (tanggal keranjang lewat), `a57d07d` (checkout dengan isian tak wajar), `9e9cc96` (profil dan alamat), `cbad96c` (masuk, daftar, lupa sandi, edit alamat), dan `e56018c` (katalog admin, pengaturan pembayaran, kartu ulasan) belum dijalankan dengan `php artisan test` maupun browser nyata.

Langkah pemeriksaan yang disarankan:

1. `php artisan test` lalu catat jumlah lulus/gagal.
2. Buka keranjang berisi unit bertanggal kemarin: pastikan muncul peringatan, tautan detail tanpa tanggal, dan tombol tambah unit hilang; ubah tanggal di checkout.
3. Kirim form dengan nilai berbentuk larik (alat pengembang peramban) pada checkout, profil, alamat, masuk, daftar, lupa sandi, dan katalog admin; halaman harus kembali dengan pesan galat, bukan halaman error 500.
4. Periksa pesan galat jumlah di detail papan tampil tepat di bawah pemilih jumlah.
5. Jalankan `php artisan rental:remind` dua kali berturut-turut pada database uji: jalankan kedua tidak boleh menambah notifikasi (dedupe `rental_unit_reminders`), dan tidak boleh ada status unit yang berubah.
6. Uji toleransi pembayaran: biarkan satu unit lewat tenggat sampai batal, kirim bukti pada H+1 pagi, verifikasi, lalu tekan "Lanjutkan unit" dengan alasan. Ulangi dengan jam perangkat disetel H+1 pukul 12.01: unggah bukti dan kelanjutan harus ditolak, dan setelah penjadwal berjalan DP harus bertanda hangus.
7. Uji desain: unggah preview, majukan waktu (atau tunggu) sampai tenggat, lalu pakai "Lanjutkan dengan catatan" tanpa alasan (harus ditolak) dan dengan alasan (harus diterima). Pastikan tombol "Mulai pengerjaan" berhasil setelahnya, dan keputusan itu tampil di riwayat unit pelanggan serta admin.

### Merek pada email dan konfigurasi

Email transaksi (termasuk reset kata sandi) masih mengambil nama usaha dari `Laundry::name()` yang membaca `LAUNDRY_NAME` di `.env`, dengan nilai bawaan "Azka Laundry". Ubah nilai berikut pada `.env` produksi sebelum dipakai pelanggan: `APP_NAME`, `LAUNDRY_NAME="Berkah Kreatif"`, `LAUNDRY_TAGLINE`, `LAUNDRY_ADDRESS`, `LAUNDRY_PHONE`, `LAUNDRY_PHONE_DISPLAY`, `LAUNDRY_WA`, serta `MAIL_FROM_ADDRESS`. Ini perubahan konfigurasi, bukan kode; nilai alamat dan nomor harus berasal dari pemilik usaha, jangan dikarang.

### Pemeriksaan form pemesanan (belum dijalankan)

Selain `php artisan test`, periksa di browser: pintasan tanggal satu hari, batas minimal tanggal pengembalian tanpa JavaScript, fokus otomatis ke kolom galat pertama setelah kirim gagal, tombol "Isi sisa tagihan" pada unit dengan sisa pembayaran, serta kewajiban catatan saat memilih "Minta revisi". Uji juga pada layar kecil karena tombol pintasan menjadi selebar layar di bawah 420 px.
