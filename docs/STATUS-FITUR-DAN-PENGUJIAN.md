# Status fitur dan pemeriksaan Berkah Kreatif

Snapshot kode setelah babak C dan D alur pemesanan (ongkos dua arah, batal mandiri, pesan ulang, draf checkout, pengingat dua arah termasuk ongkos, penutupan otomatis unit menggantung, rating pesanan dengan masa tenggang). Diverifikasi pengguna di Windows pada commit `d13f6b7`: **326 tes lulus, 2.683 assertion** (13,93 detik). Perubahan sesudah itu — umur tunggu pada kartu dasbor "menunggu pelanggan" dan tautan WhatsApp siap kirim (`RentalWhatsAppLinkTest`) — belum dijalankan di Windows. **Verifikasi terbaru (22 November 2026, commit `1ea281d`)**: `php artisan test` di Windows — **469 lulus, 4.316 asersi, 16,84 detik, 0 gagal**. Rincian penyebab 28 kegagalan sebelumnya dan daftar periksa pra-produksi ada di `docs/AUDIT-RITME-VISUAL-DAN-GERAK.md` §9.130–§9.133. Ini adalah inventaris berdasarkan kode dan tes yang ada, bukan pernyataan bahwa seluruh kebutuhan sudah selesai, aman, atau siap produksi. Dokumen audit awal `AUDIT-BERKAH-KREATIF.md` adalah snapshot historis; status fitur di sana dapat sudah berubah.

## Inventaris alur sewa

| Area | Implementasi yang tersedia | Pemeriksaan yang masih diperlukan |
| --- | --- | --- |
| Publik dan akun | Katalog, kategori, foto, filter perkiraan stok berbasis tanggal, keranjang sesi yang dikelompokkan per papan + tanggal dengan stepper −/+ dan subtotal, kanal Instagram/TikTok/WhatsApp di footer & halaman bantuan, pesan langsung, login/registrasi | Coba tampilan dan navigasi berbagai perangkat; sesi lintas browser/perangkat tidak disinkronkan; periksa alur Google secara nyata. |
| Katalog admin | Kelola produk, kategori, stok, paket durasi dan tarif universitas | Uji perubahan katalog bersamaan dengan transaksi; foto produk yang diganti dibersihkan bila tidak direferensikan produk lain, dan perubahan produk, kategori serta paket yang baru dilakukan dicatat di audit. |
| Checkout | Beberapa unit terpisah, tanggal acara sekaligus penerimaan per unit dan tanggal pengembalian, rincian desain/serah terima, harga dan tarif snapshot, persetujuan ketentuan; pesanan lama tetap mempertahankan tanggalnya | Uji banyak unit dan input batas di browser; tombol dinonaktifkan saat pengiriman jika JavaScript aktif; pengulangan untuk unit keranjang yang sama diperiksa di server, tetapi penguncian dan interleaving sesi tetap perlu uji proses bersamaan di MySQL; checkout memeriksa stok terkonfirmasi dan overlap antarunit, tetapi tidak mencadangkan stok; unit pending lain tidak dihitung sebagai reservasi dan perlu konfirmasi admin. |
| Reservasi | Tanggal ditahan sejak pesanan dikirim (pending), stok berdasarkan irisan tanggal, konfirmasi, pembatalan dan riwayat, pelanjutan, pembatalan otomatis setelah 24 jam, jendela pemesanan 366 hari | Uji transaksi bersamaan pada MySQL dan scheduler nyata. Periksa penanganan jadwal dekat dan semua interleaving pembatalan/pelanjutan. |
| Ongkos | Tarif universitas atau penawaran manual per unit; pelanggan menyetujui atau **menolak beralasan**, admin mengirim versi baru selama belum ada pembayaran, riwayat penawaran tersimpan | Uji negosiasi dua arah, penolakan setelah pembayaran masuk (harus ditolak), dan tampilan riwayat di dua peran. |
| Desain | Preview privat berversi, persetujuan/revisi, kontrol sebelum pengerjaan, tenggat respons (48 jam / H-3) dan keputusan admin "lanjutkan dengan catatan" beralasan yang tercatat di riwayat | Uji unggahan, akses silang, kegagalan storage dan penggunaan nyata pada perangkat. |
| Pembayaran | Bukti privat per unit, DP fleksibel, verifikasi, koreksi dan refund tercatat, toleransi bukti terlambat sampai H+1 pukul 12.00 WIB sebelum DP hangus | Uji batas H-1 WIB, bukti terlambat/ditolak, race saat unggah dan verifikasi serta konsistensi setelah kegagalan. Refund adalah pencatatan manual, bukan transfer otomatis. |
| Operasional | Progres per unit (serah terima memeriksa desain dan tagihan), jadwal bulanan dan filter kegiatan, ringkasan cetak, filter "toleransi" dan "lewat tanggal kembali", kartu dasbor "menunggu pelanggan" | Periksa kesesuaian alur bisnis di perangkat; jadwal bukan bukti serah terima. |
| Buku kas | Entri manual, perbandingan periode, ekspor CSV, pemasukan dari verifikasi dan pengeluaran dari refund | Audit konsistensi dan duplikasi secara menyeluruh, termasuk data finance lama yang ikut ditampilkan. |
| Notifikasi | Pemberitahuan rental dalam aplikasi untuk beberapa perubahan, ditambah **tautan WhatsApp siap kirim** dua arah (pelanggan↔admin) dengan pesan berisi referensi pesanan dan status unit | Periksa seluruh transisi, penjadwal dan ketersediaan penerima; tautan hanya membuka chat (tidak mengirim otomatis), dan pengiriman email/push tetap belum ada. |
| Pengingat | Perintah `rental:remind` dijadwalkan tiap lima menit: bukti menganggur lebih dari 6 jam (admin), pelunasan H-1 (pelanggan), toleransi H+1 (pelanggan dan admin), respons desain lebih dari 24 jam (pelanggan), eskalasi 48 jam atau H-3 (admin), unit belum ditandai kembali H+2 (admin), ongkos belum ditawarkan 24 jam (admin), penawaran ongkos belum dijawab 24 jam (pelanggan), acara lewat tetapi progres belum selesai (admin); satu pesan per unit, jenis, penerima dan tanggal | Uji pada data nyata bahwa pesan tidak menumpuk, penerima nonaktif benar-benar dilewati, dan isi pesan sesuai tagihan; pengingat tidak mengubah status apa pun |
| Penutupan otomatis | `rental:close-events` harian 02.00 WIB menutup unit `handed_over` yang 7 hari lewat tanggal kembali, mencatat riwayat "Ditutup otomatis oleh sistem", dan memberi tahu pelanggan + admin | Uji di data nyata bahwa unit dalam tenggang, `ready`, atau batal tidak tersentuh; pastikan admin menerima notifikasi dan rating pesanan terbuka setelahnya |

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

### Uji manual babak D (belum dijalankan)

1. **Pengingat ongkos (dua arah).** Unit antar/jemput tanpa penawaran lebih dari 24 jam (atur `created_at` unit ke belakang): jalankan `php artisan rental:remind` dua kali — admin menerima satu notifikasi "Ongkos belum ditawarkan". Kirim penawaran, atur `created_at` event penawaran ke 25 jam lalu, jalankan lagi — pelanggan menerima "Ongkos menunggu persetujuan". Setelah pelanggan menyetujui, pengingat itu tidak muncul lagi.
2. **Acara lewat.** Unit `confirmed` dengan `progress = waiting` dan `event_on` kemarin: jalankan `rental:remind` — admin menerima "Tanggal acara sudah lewat" sekali per hari.
3. **Penutupan otomatis.** Unit `handed_over` dengan `ends_on` 8 hari lalu: `php artisan rental:close-events` harus mengubahnya menjadi `returned`, menambah riwayat "Ditutup otomatis oleh sistem ...", dan mengirim notifikasi ke pelanggan serta admin. Unit dengan `ends_on` 6 hari lalu atau `progress = ready` tidak boleh berubah. Jalankan ulang: tidak ada perubahan ganda. Setelah itu rating pesanan pelanggan harus bisa disimpan.
4. **Rating dengan masa tenggang.** Pesanan dengan satu unit `returned` dan satu unit `handed_over` yang tanggal acaranya 10 hari lalu: form rating muncul di halaman pesanan dan server menerimanya. Pesanan yang seluruh unitnya batal tetap ditolak.
5. **Tautan WhatsApp.** Di halaman unit pelanggan, tombol "Tanya admin via WhatsApp" harus membuka chat ke nomor usaha (`LAUNDRY_WA`) dengan pesan berisi referensi pesanan dan status unit. Di detail pesanan admin, tombol "Chat pelanggan via WhatsApp" harus membuka chat ke nomor HP pelanggan. Kosongkan nomor (atau isi nomor tidak valid) di database: tombol harus hilang, bukan menghasilkan tautan rusak.

## Lampiran laporan kendala

Lampiran laporan baru disimpan pada disk lokal privat dan ditampilkan melalui rute admin terautentikasi. Lampiran historis pada `storage/app/public/reports` tetap berada di lokasi publik lama agar tidak rusak; tautan langsung lama masih dapat diakses jika `public/storage` tersedia. Untuk melindungi lampiran lama, cadangkan dan rencanakan migrasi berkas serta pembaruan jalurnya secara teruji; perubahan ini tidak memindahkan atau menghapus data lama. Bukti laundry lama juga perlu audit terpisah.

## Hasil tes pengguna terbaru

Pada commit `d13f6b7`, pengguna menjalankan `php artisan test` di Windows: **326 lulus, 2.683 assertion**, 13,93 detik — seluruh tes hijau, termasuk `RentalCustomerControlsTest`, `RentalWaitingQueuesTest`, `RentalCheckoutDraftTest`, `RentalTransportWaitTest`, dan `RentalAutoCloseTest`. `php artisan list rental` juga memastikan empat perintah tersedia: `rental:expire-confirmations`, `rental:expire-payments`, `rental:remind`, `rental:close-events`.

Riwayat: pada commit `943494e` pernah tercatat 306 lulus / 4 gagal; penyebab sebenarnya adalah `$request` yang tidak di-`use` di closure `approveTransport` (rollback transaksi tanpa pesan galat) dan dua tes yang memanggil rute admin sebagai pelanggan, diperbaiki di `4435220`. Tes otomatis ini tetap bukan pengganti uji browser, uji konkurensi MySQL, atau pemeriksaan kesiapan produksi. Hasil ini mencakup tes fitur rating dan suka serta kebijakan referrer tautan publik. Perubahan setelah commit tersebut belum termasuk hasil ini. Tes otomatis tersebut bukan pengganti uji visual browser, uji konkurensi MySQL, atau pemeriksaan kesiapan produksi.

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

Email transaksi (termasuk reset kata sandi) mengambil nama usaha dari `Laundry::name()` yang membaca `BUSINESS_NAME` di `.env` (kunci `LAUNDRY_*` lama sudah tidak dibaca — lihat §9.130). Nilai bawaan kode, `config/laundry.php`, dan `.env.example` adalah "Berkah Kreatif". Ubah nilai berikut pada `.env` produksi sebelum dipakai pelanggan: `APP_NAME`, `BUSINESS_NAME="Berkah Kreatif"`, `BUSINESS_TAGLINE`, `BUSINESS_ADDRESS`, `BUSINESS_GMAPS`, `BUSINESS_PHONE_DISPLAY`, `BUSINESS_WA`, serta `MAIL_FROM_ADDRESS`. Ini perubahan konfigurasi, bukan kode; nilai alamat dan nomor harus berasal dari pemilik usaha, jangan dikarang.

### Pemeriksaan form pemesanan (belum dijalankan)

Selain `php artisan test`, periksa di browser: pintasan tanggal satu hari, batas minimal tanggal pengembalian tanpa JavaScript, fokus otomatis ke kolom galat pertama setelah kirim gagal, tombol "Isi sisa tagihan" pada unit dengan sisa pembayaran, serta kewajiban catatan saat memilih "Minta revisi". Uji juga pada layar kecil karena tombol pintasan menjadi selebar layar di bawah 420 px.

### Babak F — UI/UX lintas peran (`belum diuji di Windows`)

Rincian penilaian per peran ada di `docs/UIUX-PER-ROLE.md`. Yang berubah pada kode:

1. Zoom tidak lagi dikunci: 21 halaman yang memakai `maximum-scale=1.0, user-scalable=no` kini hanya `width=device-width, initial-scale=1.0, viewport-fit=cover`.
2. `public/css/berkah-ui-kit.css` mendapat cincin fokus keyboard global (`:focus-visible`, termasuk varian tema gelap) yang mengalahkan `outline: none` milik halaman.
3. Merek tunggal: 17 judul halaman, halaman sambutan, dan teks bantuan kurir memakai `Laundry::name()`; nilai bawaan `config/laundry.php`, `app/Support/Laundry.php`, `.env.example` menjadi "Berkah Kreatif".
4. Nomor WhatsApp bantuan kurir memakai `Laundry::waLink()` (bukan angka contoh `6281234567890`).
5. Landmark `main` ditambahkan pada 9 halaman peran yang belum punya; `rel="noopener"` ditambahkan pada seluruh tautan `target="_blank"` yang belum memilikinya.
6. Tes baru `tests/Feature/UiAccessibilityTest.php` (7 tes, tanpa database) menjaga keenam hal di atas pada berkas view. Perkiraan total `php artisan test`: **±336 tes**.

Pemeriksaan di peramban yang masih perlu dilakukan: buka halaman walk-in dan edit profil admin dengan tombol Tab, pastikan cincin fokus tampil; cubit layar pada ponsel di katalog dan halaman admin; periksa tema gelap masih terbaca (garis fokus `#ffb0c4`).

### Babak G — voucher sewa (`belum diuji di Windows`)

Rincian aturan ada di `docs/VOUCHER-SEWA.md`. Ringkasnya:

1. Admin membuat voucher di **Beranda admin → Voucher** (kode, jenis potongan persen/nominal, cakupan "hanya harga sewa" atau "harga sewa + ongkos", minimum belanja, batas potongan, kuota total, batas per pelanggan, masa berlaku, aktif/nonaktif).
2. Pelanggan memasukkan kode di halaman checkout dan dapat menekan **Cek kode** untuk melihat perkiraan potongan sebelum mengirim (perkiraan memakai tanggal dan paket yang sedang diisi; bila belum lengkap dipakai harga dasar keranjang).
3. Potongan dihitung saat pesanan dikirim, disimpan per unit (`discount_amount`, `transport_discount_amount`) dan per pesanan (`discount_amount`), lalu dipakai otomatis oleh tagihan, pembayaran, tenggat, pengingat, dan struk karena semuanya memakai `payableAmount()`.
4. Voucher ber-cakupan ongkos dihitung ulang setelah pelanggan menyetujui ongkos, dengan batas agar tagihan tidak turun di bawah pembayaran terverifikasi.
5. Migrasi baru: `2026_11_22_000022_add_rental_voucher_discount.php` (kolom `vouchers.scope`, `vouchers.per_customer_limit`, kolom voucher pada `rental_orders` dan `rental_order_units`, tabel `rental_voucher_usages`).
6. Tes baru `tests/Feature/RentalVoucherTest.php` (9 tes, `RefreshDatabase`). Perkiraan total `php artisan test`: **±345 tes**. Hasil uji Windows pertama: **344 lulus, 1 gagal** — kegagalannya karena tes mengharapkan perkiraan dua hari padahal keranjang uji belum punya tanggal; tes diperbaiki dan endpoint kini menerima perkiraan harga dari formulir.

Pemeriksaan yang perlu dilakukan di Windows setelah `php artisan migrate`:

1. `php artisan test` lalu catat jumlah lulus/gagal.
2. Buat voucher `UJI10` (persen 10, cakupan harga sewa, kuota 5, sekali per pelanggan), lalu checkout satu papan dua hari; tagihan harus turun 10% dan potongan tampil di detail unit serta struk.
3. Kirim pesanan yang sama dua kali dengan kode berbeda; pesanan kedua harus dianggap baru, bukan duplikat.
4. Buat voucher cakupan "harga sewa + ongkos", pesan dengan pengantaran, setujui ongkos, lalu pastikan total potongan bertambah dan tagihan unit berkurang sesuai.
5. Coba kode yang kuotanya sudah habis dan kode nonaktif; keranjang harus tetap utuh.

Penghapusan modul laundry lama (pesanan, walk-in, keuangan, voucher laundry, kategori, laporan, kurir) **ditunda** sesuai keputusan pemilik. Rute dan halamannya masih ada tetapi tidak tertaut dari navigasi; rencananya dikerjakan sebagai babak tersendiri setelah voucher stabil.

### Babak H — audit alur dan jalur (`statis, belum diuji di Windows`)

Laporan lengkap: `docs/AUDIT-ALUR-DAN-JALUR.md`. Ringkasnya:

1. Seluruh **168 statement rute** diperiksa: 167 bernama, 0 nama/URI ganda, semua aksi controller dan semua `view()` ada, 0 rute admin/kurir tanpa middleware peran, dan **semua formulir** punya `action`, metode yang cocok dengan verb rute, serta `@csrf`.
2. Dua belas alur sewa ditelusuri ujung ke ujung (katalog → keranjang → checkout → konfirmasi → ongkos → desain → pembayaran → progres → serah terima → pengembalian → rating, ditambah voucher, notifikasi/pengingat, buku kas, profil, dan autentikasi). Tidak ada langkah yang buntu.
3. Temuan yang diperbaiki: halaman **Panduan admin** (`admin.help`) berisi panduan sewa lengkap tetapi tidak tertaut sama sekali. Sekarang tertaut dari *Aksi Cepat* dasbor admin dan menu *Pengaturan Operasional* di profil admin, dikunci tes baru `test_admin_navigation_reaches_the_help_guide` (total menjadi **±346 tes**).
4. Temuan yang menunggu keputusan: 7 agregat dasbor dihitung tetapi tidak dirender (partial `_analytics_section` tidak di-`@include`), profil kurir memakai tampilan profil pelanggan, dua URL dasbor admin, alur verifikasi email dorman, konfirmasi kata sandi tidak dipakai, serta endpoint dan view sisa modul laundry yang kini menjadi kepulauan tertutup (tidak punya jalan masuk dari UI sewa).
5. Tidak ada perubahan pada aturan bisnis, skema database, atau rute. Perubahan kode pada babak ini hanya dua tautan dan satu tes.

### Babak I — desain ulang panel admin + seluruh animasi (`statis, belum diuji di Windows`)

Laporan lengkap: `docs/DESAIN-PANEL-ADMIN.md`.

1. **Shell admin baru** (`layouts/admin` + `_sidebar`/`_topbar`/`_nav`): sidebar tetap di layar lebar, drawer berfokus-terkurung di HP, topbar dengan remah roti, pintasan Panduan/Notifikasi/Katalog publik/Profil, tombol tema dari `_ui_kit`, dan kolom identitas admin dengan tombol keluar.
2. **Satu design system** (`public/css/berkah-admin.css`, 19 bagian): token `--adm-*` terang & gelap, komponen kartu/statistik/badge/tabel/formulir/timeline/drawer/toast, pengaturan responsif, kontras tinggi, dan gaya cetak. Semua selektor di-scope ke `.adm-app`/`.adm-*`/`html`/`:root` sehingga memuat berkas ini di halaman lama tidak mengubah tampilannya.
3. **Animasi menyeluruh** (`public/js/berkah-admin.js`, 7 perilaku): reveal bertahap via IntersectionObserver, hitung angka naik (GSAP lokal bila ada), bar kemajuan, drawer, toast dengan bar waktu, topbar saat digulir, dan keadaan memuat tombol kirim. Ditambah transisi antarhalaman (View Transition API) yang kini juga memuat `page-transitions.*` di panel admin, sidebar & topbar dibekukan agar tidak ikut bergeser (`view-transition-name`), serta animasi masuk halaman galat. Semua dimatikan rapi saat `prefers-reduced-motion: reduce` dan konten tetap terlihat bila JavaScript gagal (`html:not(.adm-js)`).
4. **Sepuluh halaman transaksi dimigrasikan** ke shell tanpa mengubah satu pun nama field, atribut `action`, rute, atau teks yang diuji: `rental/admin`, `rental/admin-detail`, `rental/schedule`, `rental/cashbook`, `rental/vouchers`, `rental/universities`, `rental/payment-settings`, `catalog/admin`, plus partial `cashbook-fields` dan `product-fields`. Detail pesanan mendapat timeline perkembangan, riwayat pembatalan, tautan WhatsApp pelanggan (`RentalWhatsApp`), dan kartu ringkasan tagihan.
5. **Navigasi lengkap**: 17 tujuan berkelompok. Modul laundry lama (pesanan, walk-in, keuangan, laporan, kategori, voucher) yang sebelumnya tidak punya jalan masuk sekarang tertaut; halaman profil admin mendapat Notifikasi, Audit trail, dan Ubah profil. Sepuluh halaman admin mandiri juga memuat berkas gerak yang sama sehingga perpindahan halaman terasa seragam.
6. **Perbaikan detail**: `object-fit:contain` dipulihkan (kontrak `RentalCatalogTest`), `@hasSection/@else` diganti `@if (View::hasSection(...))` pada topbar, `min-height: 100vh` + `100dvh`, ID field unik per baris (`cash-*`, `product-*`), formulir kategori dipisah dua, dan partial pelanggan (`progress`, `cancellation-history`) dikembalikan ke versi asli sementara versi admin dipisah ke `admin-progress`/`admin-cancellation-history`.
7. **Pemeriksaan statis**: 122 view tanpa direktif menempel/blok tak seimbang; 0 form POST tanpa `action`/`@csrf`; seluruh nama rute ada; nama field & `action` identik dengan `HEAD`; kelas `adm-*` yang dipakai semuanya terdefinisi; semua `animation:` menunjuk `@keyframes` yang ada; `node --check` lolos; 0 `dvh` tanpa cadangan `vh`.
8. **Belum diuji**: render Blade, kontras visual, dan animasi di peramban belum pernah dijalankan (sandbox tanpa `vendor/`). Perintah tes untuk Windows ada di bagian 10 `docs/DESAIN-PANEL-ADMIN.md`; yang paling perlu dicurigai: `RentalCatalogTest`, `RentalScheduleTest`, `RentalCashBookTest`.

**Hasil verifikasi Windows (23 November 2026, commit `8ad9d38`)** — `php artisan test`: **346 lulus, 2.773 asersi, 12,35 detik**. Dua kegagalan yang muncul pada percobaan pertama sudah diperbaiki: `class="bk-back"` yang hilang dari lima view rental, dan tautan Panduan admin di dasbor. Pelajaran operasional: setelah menarik berkas baru, **selalu** jalankan `php artisan view:clear` (atau `optimize:clear`) sebelum menguji — berkas hasil sinkronisasi dapat membawa mtime lama sehingga Blade terus memakai view terkompilasi versi lama, dan gejalanya menyesatkan (kode di disk benar, hasil render lama).
