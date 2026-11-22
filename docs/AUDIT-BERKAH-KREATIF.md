# Audit dan daftar pemeriksaan Berkah Kreatif

> Snapshot audit awal: sejumlah status fitur di bawah telah berubah. Lihat `STATUS-FITUR-DAN-PENGUJIAN.md` untuk inventaris yang lebih baru; temuan keamanan historis tetap perlu diperiksa.

Audit ini membandingkan kode yang ada dengan kebutuhan penyewaan yang disepakati. Status adalah hasil pemeriksaan statis dan observasi preview pengguna, bukan sertifikasi keamanan atau kesiapan produksi. Tes yang dilaporkan pengguna pada Windows: 146 lulus, 428 assertion; ini bukan pengujian seluruh alur bisnis sewa atau konkurensi MySQL.

## Prioritas keamanan dan keandalan

- **Kritis — kredensial historis:** `.env.production.example` pada commit dasar mengandung nilai yang tampak seperti kredensial database dan SMTP. Versi terbaru sudah mengosongkan nilainya, namun riwayat Git tetap mengandung versi lama. Rotasi/revoke kredensial jika berlaku, periksa akses dan salinan; sanitasi riwayat memerlukan koordinasi pemilik repo. Jangan tampilkan nilainya di log atau dokumentasi.
- **Tinggi — batas akses:** `OrderController::show` memeriksa customer namun tidak membatasi driver untuk order awal (`menunggu`/`dijemput`). Aturan akses perlu policy terpusat, termasuk pesanan, item, desain, bukti, refund dan ekspor. Jangan menganggap kode order sulit ditebak sebagai otorisasi.
- **Tinggi — data privat:** bukti dan screenshot lama menggunakan disk `public`; untuk pembayaran, preview pribadi dan lampiran sewa gunakan disk privat dan route terotorisasi. Terapkan batas MIME/ukuran, nama acak, dan uji akses silang antarcustomer.
- **Tinggi — transaksi bersamaan:** guard status order lama memeriksa model sebelum transaksi tanpa reload dengan row lock. Reservasi dan transisi baru harus memeriksa ulang di dalam transaksi dan memakai penguncian yang sesuai database. Pengujian SQLite saja tidak membuktikan perilaku MySQL.
- **Tinggi — queue:** `OrderStatusUpdated` memakai queue `notifications`, sedangkan perintah worker dokumentasi default tidak menyebutnya. Pastikan worker memproses queue tersebut dan pantau failed jobs. `after_commit` queue database saat ini false; observer mengirim notifikasi pada event pembuatan dalam transaksi. Rancang pengiriman setelah commit agar worker tidak melihat data belum committed dan rollback tidak menghasilkan efek eksternal.
- **Tinggi — auth:** `GoogleAuthController` menggunakan `email_verified ?? true`; respons yang tidak memiliki klaim verifikasi perlu ditolak secara aman, lalu uji skenario penautan akun. `trustProxies('*')` perlu ditinjau sesuai topologi deployment karena header forward tidak boleh dipercaya dari klien langsung.
- **Sedang — reset password:** controller membuat token langsung alih-alih menggunakan alur broker `sendResetLink`, sehingga throttle broker perlu diuji dan ditegakkan selain throttle route.
- **Sedang — data pengguna:** validasi unik nomor pada pembaruan profil dilakukan sebelum normalisasi; validasi registrasi mengecualikan soft-deleted sementara unique index database lama bisa tetap mencakup akun terhapus. Selaraskan aturan aplikasi dan constraint melalui migrasi yang aman dan tes.
- **Sedang — eksternal dan UI:** login mengandalkan GSAP CDN sementara elemen dimulai dengan opacity 0; jika skrip tidak termuat, formulir dapat tetap tersembunyi. Pastikan fallback dan cek dependensi eksternal. `Permissions-Policy` menonaktifkan geolocation padahal form alamat lama menggunakannya; putuskan penggunaan lokasi dan konfigurasi dengan tepat.
- **Sedang — generator kode lama:** `Order::generateCode` mengurutkan string dan membaca tiga digit terakhir; batas empat digit dan order pertama bersamaan tidak aman untuk diasumsikan. Gunakan identitas pesanan baru yang unik dan uji konkurensi.
- **Operasional:** akun demo berpassword yang diketahui hanya untuk lokal, jangan jalankan `UserSeeder` pada produksi. `APP_DEBUG` harus mati, gunakan HTTPS dan backup teruji. Pastikan deployment memakai migrations, worker dan scheduler.

## Katalog dan keranjang

- Produk/kategori rental terpisah dari layanan laundry; saat ini hanya kategori tambah, produk tambah/ubah/toggle, harga per hari/per acara, satu foto dan stok dasar. Belum ada pengubahan/penonaktifan kategori, paket durasi, galeri, ketersediaan tanggal atau tarif lokasi.
- Pemilihan produk aktif dibatasi di server, tetapi jumlah yang ditambah ke keranjang bukan jaminan stok. Keranjang session menyimpan unit terpisah dan tidak melakukan reservasi; penggabungan lintas perangkat belum ada. Harga pada keranjang dibaca dari katalog saat ini, belum berupa penawaran checkout.
- Unggah katalog menyimpan di public disk; tangani kegagalan penulisan, penggantian/pembersihan gambar lama dan rollback agar tidak meninggalkan file yatim. Public hanya cocok untuk foto produk publik.
- Perlu audit akses, validasi, pagination, status kategori nonaktif, harga nol, upload dan lifecycle foto, duplikasi kategori, serta batas keranjang. Jangan tampilkan ketersediaan tanggal tanpa perhitungan aktual.

## Model sewa dan checkout

- Belum ada model item sewa per unit, jadwal masing-masing, lokasi, tarif pengantaran/pengembalian, harga snapshot, desain dan pembayaran. Skema laundry lama mengharuskan service/berat/jemput dan tidak boleh dipakai seolah-olah sudah mendukung penyewaan.
- Setiap unit pada satu checkout dapat berbeda desain, jadwal, durasi dan lokasi. Simpan snapshot harga dan syarat disetujui; hitung harga server-side. Paket per hari, per acara dan durasi harus ditetapkan oleh admin.
- Hitung overlap periode pada konfirmasi dalam transaksi; uji pesanan serentak, pembatalan dan pelanjutan. Stok baru dicadangkan saat admin konfirmasi tanpa mewajibkan DP; stok belum terikat saat checkout. Unit kembali tersedia sesuai periode dan kondisi pengembalian; tidak ada buffer tambahan.
- Universitas dan tarif tetap dikelola admin. Alamat lain menunggu penawaran dan persetujuan pelanggan; perubahan biaya tidak boleh diterapkan diam-diam. Pilihan penerimaan dan pengembalian dipisah.
- Pendaftaran saat ini selalu redirect ke dashboard, sedangkan login memakai intended URL. Alur checkout harus menyimpan tujuan dengan aman dan mempertahankan keranjang sesudah login, registrasi dan Google login; jangan biarkan pengguna admin memesan sebagai customer.

## Desain dan pembayaran

- Preview harus berjenis dan versi per unit, memiliki ownership, catatan revisi serta persetujuan eksplisit; pengerjaan hanya setelah disetujui. Belum ada implementasi.
- DP tanpa minimum; pembayaran bisa bertahap per unit. Bukti yang dikirim paling lambat H-1 pukul 23.59 WIB melindungi item dari pembatalan otomatis sambil menunggu verifikasi; pengiriman bukan bukti lunas sampai diverifikasi. Bukti ditolak/kurang dapat diperbaiki, aturan batas khusus belum ditetapkan.
- Jika tanpa bukti pelunasan tepat waktu, batalkan dan lepas stok; admin boleh melanjutkan pada hari serah-terima jika lunas diverifikasi dan stok tersedia, dengan risiko keterlambatan. Batas terakhir pukul 00.00 WIB hari berikutnya. DP tetap kredit bila dilanjutkan; jika tidak, hangus setelah kesempatan berakhir. Pembatalan karena kesalahan penjual memerlukan pengembalian. Aturan berlaku per unit.
- Test batas waktu menggunakan timestamps WIB dan instan persis sekitar tengah malam, transaksi serentak bukti/verifikasi/pembatalan, scheduler mati/retry, serta pending bukti yang ditinjau setelah hari acara. Kebijakan untuk bukti tidak valid setelah batas akhir dan pesanan mendadak harus ditangani manual dengan transparan, bukan diasumsikan otomatis.
- Scheduler belum diimplementasikan di `routes/console.php`; jadwalkan idempotent, durable, serta dapat pulih dari keterlambatan worker. Catat alasan, actor, waktu, notifikasi dan jejak refund.

## Keuangan

- `FinanceService` lama mencatat total order ketika selesai dan unique `(order_id, entry_type, source_type)` menghalangi beberapa pemasukan order dari sumber sama. Model baru harus mencatat setiap pembayaran diverifikasi sekali pada tanggal verifikasi, terikat ke item dan pembayaran, bukan mengulang total order saat selesai.
- Verifikasi, koreksi dan pencatatan kas harus atomik dan idempotent; refund terhubung ke pembayaran asal dan diverifikasi/catat sebagai pengeluaran. Hindari menelan semua SQLSTATE 23000 sebagai duplikasi: kelas tersebut juga mencakup jenis pelanggaran lain.
- Buku kas manual harus mendukung pemasukan/pengeluaran, tanggal, kategori yang dikelola, keterangan, bukti, koreksi dan audit. Ringkasan, periode dan ekspor harus memakai filter yang sama; bandingkan periode berjalan dengan periode setara dan sebut selisih kas, bukan laba bersih otomatis.

## Antarmuka dan aksesibilitas

- Referensi: login glass card, header gradasi, kartu membulat, navigasi bawah, tombol utama, form bertahap dan bottom sheet. Jangan membuang ciri utama tanpa kebutuhan.
- Banyak halaman lama max-width sekitar 520px dan menggunakan `user-scalable=no`; perbaiki zoom dan breakpoint mobile kecil/besar, tablet, desktop. Periksa navigasi, input, tabel, modal, keyboard, fokus, overflow dan teks panjang.
- Screenshot katalog mobile menunjukkan header cukup padat. Katalog yang baru masih membutuhkan penyesuaian floral/motion dan keselarasan dengan komponen lama; dashboard/login masih identitas laundry. Jangan menggunakan dekorasi gelembung untuk branding baru.
- Pastikan animasi tidak menyembunyikan konten tanpa JavaScript, menghormati reduced motion, dan tidak menghalangi submit.

## Pengujian dan rilis

- Tambah tes hak akses silang dan upload privat; test per item/cart/checkout, harga snapshot, stok dan overlap, dua permintaan MySQL paralel, semua batas tanggal, scheduler ulang, desain, bukti ditolak/kurang, koreksi dan refund, serta agregasi dan ekspor.
- Uji browser mobile/tablet/desktop termasuk keyboard, zoom, foto, navigasi kembali setelah login/registrasi, dan layar dengan konten panjang.
- Tes lokal 146 lulus memverifikasi suite yang ada, bukan fitur penyewaan yang belum dibangun atau keamanan produksi. Audit npm melaporkan 13 vulnerability pada instalasi pengguna; periksa `npm audit` dan jalur dependensi sebelum memutuskan upgrade, tanpa otomatis menjalankan perubahan breaking.
- Jangan menghapus tabel atau data SIMALUN tanpa keputusan pemilik dan backup; buat perubahan skema bertahap. Push hanya branch sesi; jangan merge ke main.

## Audit dependency npm (22 November 2026)

- `npm audit` terhadap lockfile melaporkan 13 paket terdampak: 1 low, 2 moderate, 8 high, 2 critical. Nama langsung yang dilaporkan termasuk axios, concurrently, postcss dan vite; severity adalah klasifikasi advisory, bukan bukti eksploitasi aplikasi. Detail dan relevansinya perlu dikaji menurut jalur pemakaian masing-masing.
- `npm audit --omit=dev` melaporkan 0 paket terdampak menurut klasifikasi dependency npm. Ini **tidak** membuktikan aset produksi aman: axios tercantum sebagai devDependency tetapi diimpor `resources/js/bootstrap.js` dan mungkin ikut dibundel untuk browser. Analisis bundle dan advisory tetap dibutuhkan.
- Jangan jalankan `npm audit fix --force` tanpa memeriksa perubahan lockfile, kompatibilitas PHP/Node, build dan tes. Jangan mengekspos Vite dev server ke jaringan publik; ada advisory terkait tooling Windows. `composer audit` belum dapat dijalankan di lingkungan audit karena Composer/PHP tidak terpasang.

## Pemeriksaan build frontend

- `npm ci --no-audit --no-fund` dan `npm run build` berhasil di lingkungan audit dengan Node 22; output memperingatkan data Browserslist berusia sekitar enam bulan. Ini bukan pengujian browser, responsivitas, atau pengujian PHP.
- Meskipun `/public/build` tercantum di `.gitignore`, sebagian output build sudah telanjur terlacak. Build menghasilkan manifest/CSS berbeda dari versi terlacak; deployment perlu strategi yang konsisten (build di deployment atau sengaja mengelola artefak), agar manifest tidak menunjuk file yang tidak diunggah. Jangan commit artefak build secara tidak sengaja.
- Lockfile mencantumkan Vite 7.3.3, axios 1.16.0, dan concurrently 9.2.1; ketiganya termasuk paket yang dilaporkan pada audit npm saat pemeriksaan. Tidak otomatis berarti seluruh advisory dapat dieksploitasi pada aplikasi ini.

## Audit Composer dari preview pengguna (22 November 2026)

`composer audit` pada salinan preview pengguna melaporkan 47 advisory pada 12 paket, termasuk advisory critical untuk `laravel/framework` dan high untuk Guzzle, CommonMark, Symfony HTTP Foundation dan YAML. Paket lain yang dilaporkan mencakup Dompdf, guzzlehttp/psr7, Flysystem, phpseclib, Symfony HTTP Kernel, MIME dan Routing. Severity advisory bukan bukti bahwa jalur yang terdampak dapat dieksploitasi pada aplikasi ini; periksa konfigurasi, penggunaan dan advisories satu per satu.

Tindakan: jangan deploy atau mempublikasikan preview; rencanakan pembaruan versi yang diperbaiki sesuai constraint Composer dan versi PHP, tinjau perubahan lockfile, jalankan `composer audit` ulang, suite pengujian, build dan pengujian MySQL/browser. Periksa `trustProxies('*')`, konfigurasi trusted hosts dan paparan fitur PDF secara khusus. Jangan menjalankan `composer update` secara membabi buta atau menganggap tes lama sebagai bukti semua advisory tidak relevan.
