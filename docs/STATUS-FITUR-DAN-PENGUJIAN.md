# Status fitur dan pemeriksaan Berkah Kreatif

Snapshot kode setelah babak C dan D alur pemesanan (ongkos dua arah, batal mandiri, pesan ulang, draf checkout, pengingat dua arah termasuk ongkos, penutupan otomatis unit menggantung, rating pesanan dengan masa tenggang). Diverifikasi pengguna di Windows pada commit `d13f6b7`: **326 tes lulus, 2.683 assertion** (13,93 detik). Perubahan sesudah itu — umur tunggu pada kartu dasbor "menunggu pelanggan" dan tautan WhatsApp siap kirim (`RentalWhatsAppLinkTest`) — belum dijalankan di Windows. **Verifikasi terbaru (22 November 2026, commit `1ea281d`)**: `php artisan test` di Windows — **469 lulus, 4.316 asersi, 16,84 detik, 0 gagal**. Rincian penyebab 28 kegagalan sebelumnya dan daftar periksa pra-produksi ada di `docs/AUDIT-RITME-VISUAL-DAN-GERAK.md` §9.130–§9.133. Ini adalah inventaris berdasarkan kode dan tes yang ada, bukan pernyataan bahwa seluruh kebutuhan sudah selesai, aman, atau siap produksi. Dokumen audit awal `AUDIT-BERKAH-KREATIF.md` adalah snapshot historis; status fitur di sana dapat sudah berubah.

## Inventaris alur sewa

| Area | Implementasi yang tersedia | Pemeriksaan yang masih diperlukan |
| --- | --- | --- |
| Publik dan akun | Katalog, kategori, foto, filter perkiraan stok berbasis tanggal, keranjang sesi yang dikelompokkan per papan + tanggal dengan stepper −/+ dan subtotal, kanal Instagram/TikTok/WhatsApp di footer & halaman bantuan, pesan langsung, login/registrasi | Coba tampilan dan navigasi berbagai perangkat; sesi lintas browser/perangkat tidak disinkronkan; periksa alur Google secara nyata. |
| Katalog admin | Kelola produk, kategori, stok, paket durasi dan tarif universitas | Uji perubahan katalog bersamaan dengan transaksi; foto produk yang diganti dibersihkan bila tidak direferensikan produk lain, dan perubahan produk, kategori serta paket yang baru dilakukan dicatat di audit. |
| Checkout | Beberapa unit terpisah, tanggal acara sekaligus penerimaan per unit dan tanggal pengembalian, rincian desain/serah terima, harga dan tarif snapshot, persetujuan ketentuan; pesanan lama tetap mempertahankan tanggalnya | Uji banyak unit dan input batas di browser; tombol dinonaktifkan saat pengiriman jika JavaScript aktif; pengulangan untuk unit keranjang yang sama diperiksa di server, tetapi penguncian dan interleaving sesi tetap perlu uji proses bersamaan di MySQL; checkout memeriksa stok terkonfirmasi dan overlap antarunit, tetapi tidak mencadangkan stok; unit pending lain tidak dihitung sebagai reservasi dan perlu konfirmasi admin. |
| Reservasi | Tanggal ditahan sejak pesanan dikirim (pending), stok berdasarkan irisan tanggal, konfirmasi, pembatalan dan riwayat, pelanjutan, pembatalan otomatis setelah 24 jam, jendela pemesanan 366 hari | Uji transaksi bersamaan pada MySQL dan scheduler nyata. Periksa penanganan jadwal dekat dan semua interleaving pembatalan/pelanjutan. |
| Ongkos | Tarif universitas atau penawaran manual per unit; persetujuan pelanggan **hanya** untuk penawaran manual di atas nol (tarif universitas yang sudah tampil saat checkout dan penawaran 0 langsung disetujui), pelanggan dapat **menolak beralasan**, admin mengirim versi baru selama belum ada pembayaran, riwayat penawaran tersimpan | Uji negosiasi dua arah, penolakan setelah pembayaran masuk (harus ditolak), dan tampilan riwayat di dua peran. |
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

### Babak J — validasi mikro, tombol kembali jujur, gulir sentuh halus (`statis, belum diuji di Windows`)

Putaran 31 memperbaiki empat keluhan langsung dari pemakaian:

1. **Balon "Bagian ini wajib diisi." hilang.** `berkah-controls.js` kini menyetel `form.noValidate`, memeriksa semua kolom ber-kendala (termasuk kolom yang sudah menjadi sheet lewat trigger-nya), lalu menampilkan satu kalimat kecil di bawah kolom — "Nama lengkap belum diisi." — dengan `role="alert"`, `aria-describedby`, dan fokus tanpa lompatan gulir. Detail: `docs/AUDIT-RITME-VISUAL-DAN-GERAK.md` §9.138.
2. **Tombol "Kembali ke keranjang" tidak lagi salah tujuan.** `berkah-smart-back.js` hanya memakai `history.back()` bila jalur halaman asal sama dengan jalur pada href tombol; bila berbeda, href yang diikuti. `/checkout` juga masuk daftar jalur yang tidak pernah dituju lewat riwayat. Detail: §9.139.
3. **Gulir ponsel dihaluskan.** `syncTouch: true` dengan `syncTouchLerp: 0.075`, ditambah opsi `prevent` yang mengembalikan sentuhan pada kolom isian, sheet, dialog, dialog tema, sidebar admin, isi drawer HP, dan `[data-lenis-prevent]`. Jaring pengaman `reviveIfStuck` dan `lagSmoothing` tetap berlaku. Detail: §9.140.
4. **Setiap halaman punya animasi masuk.** `initPageEnter()` menganimasikan maksimal empat anak langsung `<main>` (naik 12 px + memudar, stagger 60 ms) dengan `clearProps`, melewati elemen `fixed`/`sticky` dan halaman yang sudah memakai `[data-bk-reveal]`. Dua jalan keluar: `<html data-bk-no-page-enter>` dan `data-bk-no-enter`.

Tes penjaga baru: `tests/Feature/SmartBackTest.php` (3), `tests/Feature/FormCueTest.php` (7), dan satu tes tambahan di `tests/Feature/MotionAssetsTest.php`. `MotionAssetsTest::test_gulir_sentuh_tetap_milik_peramban` diganti nama dan isinya menjadi `test_gulir_sentuh_dihaluskan_dengan_pengaman` karena keputusan §9.55 dibalik oleh §9.140 — penjelasan perubahan ada di dokumen audit, bukan sekadar asersi yang dilonggarkan.

Perkiraan jumlah tes setelah putaran 30 dan 31: **±493 lulus** (469 pada `1ea281d` + 13 tes putaran 30 + 11 tes putaran 31). Belum dijalankan di Windows.

Pemeriksaan manual yang perlu dilakukan:

1. `php artisan optimize:clear` lalu `php artisan test`; catat jumlah lulus/gagal.
2. Checkout: kosongkan nama dan nomor, tekan Kirim — hanya baris kecil di bawah kolom, tanpa balon oranye; isi kolomnya, pesan langsung hilang.
3. Dari katalog, tekan "Pesan langsung" pada satu papan sampai checkout, lalu "Kembali ke keranjang" — harus terbuka halaman keranjang, bukan detail papan.
4. Di ponsel: gulir beranda dan katalog — harus meluncur halus; buka sheet filter lalu gulir isinya — isi sheet yang bergerak, bukan halaman di belakangnya.
5. Muat ulang halaman keranjang dan halaman bantuan — isi masuk dengan lembut, tidak berkedip.

### Babak K — suite hijau lagi, galat konsol admin, preload huruf (`statis, belum diuji di Windows`)

Putaran 32 menindaklanjuti laporan setelah `git pull` ke `df87abc`: **2 gagal, 493 lulus**. Kedua kegagalan bukan regresi putaran 31, melainkan tes yang tidak ikut diperbarui saat keranjang diubah menjadi berkelompok (§9.135).

1. **`RentalCartTest` mencari label `UNIT 1`/`UNIT 2`.** Label itu sudah tidak ada karena keranjang merender satu baris per papan + tanggal. Tes diperbarui ke bentuk baru: penghitung stepper `2`, nama papan sebagai `<h2>`, dan tombol "Hapus semua unit …" yang berlaku untuk seluruh kelompok — bukan asersi yang dilonggarkan, melainkan ekspektasi yang menyesuaikan desain yang sudah disetujui. Detail: §9.143.
2. **`RentalDateFilteringTest` menuntut `cart.duplicate` tidak ada pada jadwal kedaluwarsa.** View memang masih merendernya (tombol `+` ber-`disabled` di dalam `<form>`). Sekarang cabang kedaluwarsa merender tombol mati **tanpa formulir**, jadi aksinya benar-benar tidak dikirim ke peramban dan tes tetap ketat. Detail: §9.143.
3. **`Uncaught ReferenceError: sheet is not defined` (2×) di `berkah-admin.js`.** `layers()` masih memeriksa panel bawah yang sudah dicabut, sehingga resize, Escape, dan Tab di dalam drawer melempar galat dan kurungan fokus mati. Ketiga pemeriksaan dihapus; diuji ulang di jsdom tanpa galat. Detail: §9.141.
4. **Peringatan preload huruf.** Preload memakai URL ber-`?v=`, sedangkan `@font-face` meminta URL tanpa query — peramban mengunduh huruf dua kali dan menganggap preload tidak terpakai. Keduanya kini memakai alamat yang sama. Detail: §9.142.
5. **Chip kartu dasbor berdesakan di ponsel.** Di bawah 560 px chip turun ke barisnya sendiri, judul boleh terlipat. Detail: §9.144.

Tes penjaga baru: `UiDefaultsTest::test_pengendali_lapisan_admin_hanya_memakai_lapisan_yang_ada` dan `UiAccessibilityTest::test_preload_huruf_memakai_alamat_yang_sama_dengan_font_face` (2 tes). Perkiraan jumlah tes: **±495 lulus**.

Pemeriksaan manual:

1. `git pull; php artisan optimize:clear; php artisan test` → harap **495 lulus, 0 gagal**.
2. Buka dasbor admin, kecilkan/lebarkan jendela beberapa kali, buka menu di ponsel lalu tekan Escape dan Tab — konsol harus bersih, drawer tertutup, fokus tetap di dalam drawer.
3. Konsol tidak lagi memuat "was preloaded using link preload but not used".
4. Keranjang berisi papan bertanggal kemarin: tombol `+` tampak mati, dan tidak ada formulir "tambah" di HTML-nya.

### Babak L — strip berjalan: alur pemesanan + kontak (`statis, belum diuji di Windows`)

Permintaan pemilik usaha: kontak (Instagram, TikTok, WhatsApp + nomor) dimasukkan ke strip langkah di beranda katalog, dengan lambang merek dan nama yang bisa diklik, baris berjalan ke kanan, berhenti saat disentuh/diklik, lalu lanjut dua detik kemudian. Sekaligus langkah pemesanan dibuat sesuai alur nyata.

1. **Lima langkah yang benar.** "Pilih papan → Atur tanggal & ucapan → Admin konfirmasi → Bayar & setujui desain → Pantau sampai terpasang". Langkah lama ("Pantau pesanan / Tunggu konfirmasi admin") tidak menggambarkan alur: konfirmasi admin terjadi sebelum pembayaran, bukan sesudahnya. Detail: `docs/AUDIT-RITME-VISUAL-DAN-GERAK.md` §9.145.
2. **Tiga pil kontak** dengan lambang SVG sebaris dan keterangan `@berkahkreatif_` / `0895-1405-7305`; kanal yang belum diatur di `config/laundry.php` hilang sendiri.
3. **Gerak & jeda.** `public/js/berkah-ribbon.js`: isi bergeser ke kanan 38 px/detik, berhenti saat kursor masuk, disentuh, diklik, atau difokuskan, lalu lanjut 2 detik setelah interaksi terakhir. Salinan isi `aria-hidden` + `tabindex="-1"`.
4. **Pengaman.** `prefers-reduced-motion` dan `?gerak=mati` membuat strip diam namun tetap bisa digulir; tanpa JavaScript pun tetap terbaca.

Berkas: `resources/views/catalog/_ribbon.blade.php` (baru), `public/js/berkah-ribbon.js` (baru), `public/css/berkah-experience.css` + `berkah-theme.css` (tambahan), `catalog/index.blade.php` + `catalog/layout.blade.php`.

Tes penjaga baru: `tests/Feature/ContactRibbonTest.php` (9). Perkiraan jumlah tes: **±504 lulus**.

Pemeriksaan manual di beranda katalog:

1. Baris langkah + kontak bergerak ke kanan dengan mulus, tanpa lompatan saat animasi mengulang.
2. Kursor di atas strip → berhenti; pindahkan → 2 detik kemudian lanjut.
3. Tab sampai salah satu pil kontak → berhenti; Tab keluar → lanjut.
4. Buka dengan `?gerak=mati` → strip diam dan bisa digeser; isi tetap terbaca.

### Babak M — varian warna & harga yang berubah langsung (`statis, belum diuji di Windows`)

Permintaan pemilik usaha: harga ikut berubah saat jumlah unit naik/turun (katalog dan keranjang), item punya varian/warna, dan klik warna langsung mengganti preview.

1. **Skema varian.** `rental_product_variants` (nama, warna heksa, stok, foto, urutan, aktif) dan tiga kolom salinan di `rental_order_units` (`rental_product_variant_id`, `variant_name`, `variant_color`). Papan tanpa varian berperilaku persis seperti sebelumnya.
2. **Stok per warna.** Ketersediaan tanggal, konfirmasi admin, kalender, dan keranjang semuanya memeriksa warna yang dipilih; tanggal baru "penuh" bila semua warna habis.
3. **Harga tidak berubah antarvarians** (keputusan pemilik). Yang berubah: cincin warna, nama warna, foto pratinjau, batas jumlah unit, dan total baris.
4. **Harga bergerak sendiri.** Kartu katalog & halaman detail menampilkan total `harga satuan × jumlah` yang diperbarui setiap kali jumlah berubah — dihitung dari harga satuan yang ditulis server.
5. **Keranjang tanpa muat ulang.** Tombol −/+/Hapus mengirim formulir lewat fetch; server menjawab JSON berisi jumlah, subtotal, dan total yang dihitung ulang dari sesi. Gagal → halaman dimuat ulang agar pesan server tampil. Tanpa JavaScript tetap bekerja.
6. **Id baris keranjang diperbaiki.** Dulu `cart-unit-<produk>` bisa ganda untuk papan yang sama dengan warna/tanggal berbeda; sekarang `cart-group-<hash>` dari kunci kelompok.
7. **Pesanan & admin.** Warna tampil di checkout, detail pesanan, kuitansi, halaman unit, dan detail admin. Admin mengelola warna (nama, pemilih warna, stok, urutan, foto, aktif/nonaktif, hapus dengan modal) di halaman katalog.

Berkas utama: `database/migrations/2026_11_22_000026_*` & `000027_*`, `app/Models/RentalProductVariant.php`, `app/Models/RentalProduct.php`, `app/Services/RentalAvailability.php`, `app/Http/Controllers/{RentalCartController,RentalCatalogController,RentalOrderController}.php`, `resources/views/catalog/{_variant_picker,index,show,cart,admin}.blade.php`, `public/js/{berkah-variants,berkah-cart-live}.js`, `public/css/{berkah-experience,berkah-theme}.css`, `lang/id/validation.php`.

Tes penjaga baru: `tests/Feature/ProductVariantTest.php` (10). Perkiraan jumlah tes: **±514 lulus**.

Pemeriksaan manual:

1. `php artisan migrate` (dua migrasi baru), lalu `php artisan optimize:clear`.
2. Admin → Katalog → buka satu papan → tambah warna (mis. "Marun", "Putih") dengan stok 2 dan 1.
3. Katalog: klik warna → foto/label berganti; jumlah unit dibatasi stok warna; total di bawah tombol berubah saat jumlah diubah.
4. Keranjang: tekan − dan + beberapa kali tanpa halaman dimuat ulang — angka unit, subtotal baris, dan total keranjang berubah; baris dengan warna berbeda tetap terpisah.
5. Tambah warna melebihi stok → pesan server muncul setelah halaman dimuat ulang (jalur cadangan).
6. Checkout & detail pesanan menampilkan "warna Marun"; admin melihat warna yang sama di detail pesanan.

### Babak N — kontak berjalan di footer, tagline satu sumber, ongkos opsional, progres terlihat (`statis, belum diuji di Windows`)

Permintaan pemilik usaha: strip berjalan juga dipakai di halaman yang memuat link WhatsApp/media sosial; tagline dari deskripsi Instagram diseragamkan; persetujuan ongkir hanya untuk pesanan yang memang punya ongkir; progres yang terlihat bagi pelanggan beserta notifikasinya; dan seluruh alur per peran dibuat lebih cepat daripada WhatsApp.

1. **Strip kontak di footer.** `catalog/_ribbon.blade.php` menerima `withSteps`/`withContacts`; footer layout memanggilnya tanpa langkah untuk semua halaman selain beranda (beranda sudah punya strip di atas). Ubin "Tanya stok & jadwal" menambah isi, kelas `bk-ribbon--contact` merapatkan garis dan lebar.
2. **Tagline satu sumber.** Lima tempat kini memanggil `Laundry::tagline()` (`BUSINESS_TAGLINE`): header katalog (dua cabang), wordmark navigasi pelanggan, splash login, lupa sandi, halaman suka publik. Tidak ada lagi "Papan bunga untuk momen istimewa" atau "Rangkaian bunga untuk momen yang kamu tunggu" yang ditulis harfiah.
3. **Ongkos tidak lagi menahan pembayaran.** Tarif universitas tetap yang sudah tampil saat checkout langsung disetujui (tercatat sebagai peristiwa `approve` di riwayat ongkos). Penawaran manual bernilai 0 berarti gratis dan langsung disetujui admin; voucher ongkos dihitung ulang dan pelanggan menerima kabar "Ongkos gratis". Penawaran manual > 0 tetap perlu persetujuan pelanggan dan tetap terkunci setelah ada pembayaran.
4. **Progres terlihat.** `RentalLabels` menambah `PROGRESS_STEPS`/`PROGRESS_HINT`/`progressHint()`/`progressStep()`; partial `rental/_progress_steps.blade.php` dipasang di halaman unit, kartu unit pada detail pesanan, dan beranda pelanggan (bagian "Progres pesananmu"). Kartu daftar pesanan menampilkan "… dikerjakan / siap diserahkan / selesai" dari `withCount`.
5. **Notifikasi progres konkret.** Judul `Progres: <langkah>` + arahan dari `PROGRESS_HINT` + catatan admin. Tidak ada lagi "Perkembangan unit telah diperbarui".
6. **Jalur chat tetap cepat.** Tombol "Tanya papan ini lewat WhatsApp" (detail papan) dan "Tanya keranjang ini lewat WhatsApp" (keranjang) membawa pesan terisi nama papan, warna, harga, dan tanggal.
7. **Analisis UX per peran.** `docs/ANALISIS-ALUR-PENGGUNA.md` memuat hitungan ketukan, temuan, dan sisa pekerjaan berprioritas.

Berkas utama: `resources/views/catalog/{_ribbon,layout,index,show,cart,customer-navigation}.blade.php`, `resources/views/auth/{login,forgot-password}.blade.php`, `resources/views/rental/{_progress_steps,unit,show,index,admin-detail,public-like}.blade.php`, `resources/views/roles/customer/dashboard.blade.php`, `app/Http/Controllers/{RentalOrderController,RentalProgressController,DashboardController}.php`, `app/Support/{RentalLabels,Laundry}.php`, `public/css/berkah-experience.css`, `docs/ANALISIS-ALUR-PENGGUNA.md`.

Tes penjaga baru: `tests/Feature/ProgressJourneyTest.php` (3), `tests/Feature/TaglineSourceTest.php` (2), satu tes footer strip di `ContactRibbonTest`, dan satu tes penawaran nol di `RentalTransportQuoteTest`. `RentalUniversityTest` disesuaikan ke kontrak baru (tarif universitas disetujui saat checkout). Perkiraan jumlah tes: **±520 lulus**.

Pemeriksaan manual:

1. `php artisan optimize:clear`, lalu buka Keranjang dan detail papan: di footer ada strip kontak berjalan (tanpa langkah). Beranda hanya punya satu strip.
2. Ganti `BUSINESS_TAGLINE` di `.env` → header, splash login, dan wordmark pelanggan ikut berubah.
3. Pesan papan dengan pengantaran ke universitas bertarif: halaman unit langsung "Disetujui"/"Gratis" dan tombol bayar tersedia; tidak ada lagi layar "Menunggu persetujuan ongkos".
4. Pesan papan dengan "Diambil sendiri": bagian ongkos tidak muncul dan tidak ada penundaan pembayaran.
5. Admin kirim penawaran 0 → pelanggan menerima "Ongkos gratis" dan bisa membayar.
6. Admin naikkan progres → notifikasi `Progres: …` berisi arahan; dasbor pelanggan menampilkan "Progres pesananmu"; kartu pesanan menampilkan ringkasan progres.

### Babak O — checkout bertahap & pembersihan teks (`statis, belum diuji di Windows`)

Permintaan pemilik usaha: halaman checkout terasa penuh dan membingungkan; minta versi yang jelas, tidak bertele-tele, dan memuaskan; tambahkan progres pengisian; teks katalog terlalu panjang dan tanpa hook; kolom total harga yang beranimasi tidak diperlukan; keseluruhan terasa lebih "branded".

1. **Checkout tiga lapis.** Kepala bidang anggur dengan satu janji + tiga bukti (tanggal ditahan 24 jam, admin mengonfirmasi, bukti bayar privat); bilah kemajuan berisi "N dari M isian wajib", persentase, dan tiga tahap; kolom kesiapan menempel berisi daftar per papan, hitungan sisa isian, persetujuan, dan tombol kirim.
2. **Tiga bagian per papan.** Tab Jadwal → Ucapan & desain → Serah terima, tombol Lanjut memeriksa bagian yang terbuka, Kembali muncul mulai bagian kedua, tab lengkap bertanda centang, kartu menampilkan "N isian lagi"/"Lengkap".
3. **Validasi diambil alih skrip** (`novalidate` hanya saat JS jalan) supaya isian kosong di bagian tertutup tidak membuat formulir diam; pesan Indonesia UI kit tetap dipakai lewat `reportValidity()`.
4. **Tanpa JavaScript tetap bekerja**: semua bagian terbuka di server; CSS menyembunyikan bagian tidak aktif hanya setelah `data-co-ready`.
5. **Teks dipendekkan**: kartu katalog memakai hook "Dirangkai ulang untukmu."; detail papan memakai pita jaminan dua baris; keranjang dua kalimat; lima poin ketentuan dilipat (teks tetap ada).
6. **Kolom total beranimasi dicabut** dari kartu & detail katalog beserta penghitungnya di `berkah-variants.js`; harga satuan tetap dari server.
7. **Foto referensi, voucher, dan salin unit** pindah ke kartu lipat; id/nama field tidak berubah sehingga draf checkout di sesi tetap hidup. Salin unit memakai modal bersama dan melaporkan jumlah isian yang disalin.
8. **Tema gelap** untuk seluruh permukaan baru.
9. **Judul "Detail penyewaan" dikembalikan** (dipakai `RentalCartTest`) dan **balon validasi tidak lagi melayang di sudut halaman**: lapisan yang menutupi isian dibuka lebih dulu, balon diminta setelah satu bingkai dan hanya untuk elemen yang tampil.
10. **Foto referensi desain tampil terbuka** (bukan lagi di dalam `<details>`) di bawah kartu papan, dengan tautan pintas dari tab "Ucapan & desain". Batas tetap 5 foto × 25 MB, total 60 MB. `CheckoutFlowTest` juga menjaga setiap `for="…"` punya kendali dengan id yang sama — galat nyata di mana tag `<input` tertelan saat memangkas teks label.

Berkas utama: `resources/views/rental/checkout.blade.php` (ditulis ulang), `public/js/berkah-checkout.js` (baru), `public/css/berkah-experience.css`, `public/css/berkah-theme.css`, `resources/views/catalog/{index,show,cart}.blade.php`, `public/js/berkah-variants.js`.

Tes penjaga baru: `tests/Feature/CheckoutFlowTest.php` (7) dan `tests/Feature/TestHelperNameTest.php` (1). Perilaku skrip diuji di jsdom (`/tmp/wasm-php/checkout-steps-jsdom.cjs`, 27/27). `ProductVariantTest` disesuaikan: kolom total baris tidak lagi ada di katalog. Perkiraan jumlah tes: **±526 lulus**.

Catatan galat nyata: helper privat bernama `view()` pada kelas tes membuat PHPUnit berhenti sebelum suite berjalan ("Access level to ...::view() must be protected"). PHPUnit memuat semua berkas tes lebih dulu, jadi galat ini tidak tertangkap `php -l` maupun tes lain. Nama helper diganti `checkoutView()`/`checkoutScript()`, dan `TestHelperNameTest` memeriksa daftar nama metode bawaan `TestCase` (view, get, post, call, json, artisan, seed, actingAs, …) agar tidak terulang.

Pemeriksaan manual:

1. `php artisan optimize:clear`, buka keranjang → checkout.
2. Ikuti langkah 1–8 pada bagian "Cara memeriksa sendiri" di `docs/AUDIT-RITME-VISUAL-DAN-GERAK.md` §9.148.
3. Khusus ponsel: tab bagian bisa digeser mendatar, kolom kesiapan pindah ke bawah kartu, dan tombol kirim tetap terlihat setelah semua bagian lengkap.

### Babak P — notifikasi ke unit + ringkasan tindakan di dasbor (`statis, belum diuji di Windows`)

P1 dari `docs/ANALISIS-ALUR-PENGGUNA.md`:

1. **Notifikasi membuka papan yang berubah.** `RentalUpdate` menyimpan `rental_order_unit_id` (hanya bila ada), `RentalNotifications` mendapat `customerForUnit()`/`adminsForUnit()` (35 pemanggilan per unit dipindahkan), dan `markAndRedirect()` mengarah ke `rental.orders.unit` (pelanggan) atau `admin.rental.orders.show#unit-<id>` (admin).
2. **"Perlu tindakanmu" di atas dasbor admin**: hanya baris berangka > 0, urut tenggat, dua baris teratas membawa catatan waktu (batas konfirmasi terdekat, bukti tertua), dan keadaan kosong berupa satu kalimat.

Berkas: `app/Notifications/RentalUpdate.php`, `app/Services/RentalNotifications.php`, `app/Http/Controllers/NotificationController.php`, `app/Http/Controllers/DashboardController.php`, `resources/views/roles/admin/dashboard.blade.php`, `public/css/berkah-admin.css`.

Tes: `tests/Feature/NotificationPayloadTest.php` (3, satu di antaranya pemeriksaan berkas yang berjalan di sandbox), `RentalNotificationTest` diperbarui (notifikasi unit kini membuka halaman unit; tes baru untuk jangkar admin), `RentalAdminSearchTest` memakai judul "Perlu tindakanmu".

Pemeriksaan manual ada di `docs/AUDIT-RITME-VISUAL-DAN-GERAK.md` §9.151.

### Babak Q — konsistensi visual keranjang, pesanan, unit (`statis, belum diuji di Windows`)

1. **Keranjang**: tiga paragraf → satu; `notice` duplikat dihapus dan `id="cart-next"` dipindah ke kartu total; subtotal memakai teks redup untuk bagian "belum termasuk durasi dan ongkos".
2. **Halaman pesanan pelanggan**: kepala bidang anggur (kicker, judul, satu kalimat, pil referensi/tenggat/voucher), `notice` panjang dihapus, judul bagian "Papan dalam pesanan ini", status papan menjadi label kecil berwarna.
3. **Halaman unit**: kepala sama; catatan teknis "Penjadwal harus berjalan…" diganti peringatan pengujian singkat di dalam kepala.

Berkas: `resources/views/catalog/cart.blade.php`, `resources/views/rental/show.blade.php`, `resources/views/rental/unit.blade.php`, `public/css/berkah-experience.css`, `public/css/berkah-theme.css`.

### Babak R — angka uang keranjang tenang (`statis, belum diuji di Windows`)

Animasi "pop" pada total dan jumlah unit keranjang dicabut (JS + CSS), diganti `aria-live="polite"` + `aria-atomic="true"` pada kedua angka. `ProductVariantTest` disesuaikan (dulu justru mewajibkan `@keyframes bk-total-pop`, kini melarangnya), dan `CartQuietNumbersTest` (3 tes) menjadi penjaganya.

Pemeriksaan manual: `docs/AUDIT-RITME-VISUAL-DAN-GERAK.md` §9.152 dan §9.153.

### Babak S — "Item yang dipesan" di empat tempat (`statis, belum diuji di Windows`)

1. **Satu sumber data**: `App\Support\OrderedItems` (`fromUnits()`, `fromCart()`) + partial `rental._ordered_items`.
2. **Tampil di**: checkout (di atas kolom kesiapan), halaman pesanan pelanggan, detail pesanan admin, dan kartu daftar "Pesanan saya" (nama papan, maks. tiga + "dan N papan lain", dimuat satu `with()`).
3. **Isi baris**: nomor, nama (bisa diketuk), warna + contoh swatch, label status, tanggal acara/kembali, item tambahan + harga, harga satuan.
4. **Gaya di `berkah-ui-kit.css`** karena layout admin tidak memuat `berkah-experience.css`.
5. **Kolom kanan checkout** kini grid dengan batas tinggi + gulir sendiri agar tombol kirim tetap terlihat; labelnya menjadi "Ringkasan pesanan dan kesiapan pengisian".

Tes: `tests/Feature/OrderedItemsTest.php` (6; dua di antaranya butuh basis data dan dilewati di sandbox).

Pemeriksaan manual: `docs/AUDIT-RITME-VISUAL-DAN-GERAK.md` §9.154.

### Babak T — panel admin tanpa gaya sebaris (`statis, belum diuji di Windows`)

Jawaban atas pertanyaan pemilik usaha *"apakah kita perlu meng unslop halaman
admin secara keseluruhan?"*: ya.

1. **202 atribut `style` di 15 view admin** dihapus; gayanya pindah ke 57
   kelas utilitas baru di akhir `public/css/berkah-admin.css` (jarak, susunan
   baris, teks, garis, gambar).
2. **Blok `<style>` di `catalog/admin.blade.php` dihapus**; `.admin-photo` kini di
   berkas CSS admin.
3. **Nama bertabrakan**: foto profil bulat memakai `.adm-avatar-img` supaya tidak
   menimpa inisial `.adm-avatar` 36 px di sidebar/topbar.
4. **Kelas hantu** `adm-narrow` didefinisikan; tiga paragraf panjang dipecah jadi
   butir daftar.
5. **Penjaga**: `tests/Feature/AdminSurfaceTest.php` (6 tes) — larangan `style`
   statis, larangan `<style>` di view, judul multi-baris, kelas `adm-*` harus ada,
   kelas kunci tidak boleh hilang, dan batas teks 12 px.
6. **Bug yang ikut ketahuan** (laporan uji Windows): dasbor admin 500 karena
   `RentalOrderUnit::min('confirmation_deadline')` membaca kolom milik
   `rental_orders`. SQLite mengembalikan nama kolom sebagai teks, lalu
   `Carbon::parse()` gagal. Kueri kini lewat relasi `RentalOrder` dan parsing
   dipindah ke controller. Dijaga `tests/Feature/QueryColumnTest.php` (2 tes) dan
   `RentalAdminDashboardTest` (menuntut teks tenggat, menolak nama kolom).

Berkas: 15 view `layouts.admin`, `public/css/berkah-admin.css`,
`resources/views/catalog/admin.blade.php`, `tests/Feature/AdminSurfaceTest.php`.

Pemeriksaan manual: `docs/AUDIT-RITME-VISUAL-DAN-GERAK.md` §9.155.

