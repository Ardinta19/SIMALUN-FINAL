# Panduan UI/UX Berkah Kreatif

Dokumen ini adalah pegangan desain untuk pengembangan tampilan berikutnya. Arah visual dikembangkan secara orisinal dari identitas Berkah Kreatif dan kebutuhan pengguna, tanpa bergantung pada folder referensi. Penerapan harus sesuai dengan fitur nyata proyek, bukan menjanjikan interaksi atau informasi yang tidak tersedia.

## Tujuan dan batasan

- Prioritaskan guest dan customer, kemudian admin dan role lain yang masih relevan. Pertahankan alur penyewaan, hak akses, validasi, data, dan perilaku form yang ada.
- Jangan mengubah backend tanpa persetujuan pengguna. Jangan menjanjikan fungsi yang belum tersedia.
- Identitas Berkah Kreatif: marun, putih/krem, floral, hangat, estetik dan sedikit playful. Hasil harus terasa unik tanpa mengorbankan kejelasan dan ketenangan.
- Bangun tampilan untuk ponsel berbagai ukuran, tablet, dan desktop; pastikan zoom, orientasi layar, safe area, teks panjang, serta keyboard perangkat tidak menutupi kontrol.
- Desain berangkat dari konten dan kebutuhan pengguna; efek visual mendukung tugas, tidak mengalihkan perhatian atau memperlambat transaksi.

## Pola interaksi default

Pola di bawah adalah pilihan default yang diterapkan jika sesuai konteks, bukan kewajiban memasang semuanya di setiap halaman. Harus ada jalur navigasi yang terlihat, dapat dimengerti, dan tetap berfungsi tanpa animasi atau JavaScript bila memungkinkan.

- **Floating pill:** navigasi atas melayang dan responsif. Saat scroll, dapat mengecil menjadi logo; logo harus tetap menjadi tombol yang jelas untuk membuka navigasi, bukan menyembunyikan satu-satunya cara mengakses halaman. Pertimbangkan ruang pada layar kecil, posisi konten dan keadaan saat fokus keyboard.
- **Bottom tab bar:** untuk tujuan utama yang sering digunakan di mobile, dengan label dan status aktif jelas, memperhatikan safe area. Jangan menutup aksi, input, pesan, atau konten. Pilihan untuk guest dan customer mengikuti akses masing-masing.
- **Navigasi desktop:** tombol dan command palette “Navigasi” dihapus sesuai permintaan; pertahankan tautan utama dan pintasan akun yang mudah dijangkau.
- **Radial sidebar mobile:** prioritaskan Keranjang, Pesanan, Notifikasi, dan Bantuan/FAQ di dalam menu; tab utama pelanggan memuat Beranda, Katalog, Keranjang, dan Profil. Pertahankan akses langsung di desktop melalui navigasi. Gunakan pemicu yang mudah dijangkau. Jaga urutan baca logis, ukuran target sentuh, keterangan teks dan tata letak yang tidak terpotong; bila ruang sempit gunakan susunan alternatif yang tetap mudah digunakan.
- **Jump page:** gunakan pada halaman panjang atau yang memiliki banyak bagian, berdasarkan bagian yang benar-benar ada. Beri label jelas dan perhitungkan header tetap agar judul tidak tertutup.
- **Bottom sheet / upper sheet:** untuk pencarian atau filter yang relevan. Pilih posisi menurut konteks dan keyboard; sediakan judul, tombol tutup, fokus terkelola, Escape bila tersedia, scroll internal, dan alternatif yang dapat diakses.
- **Anchored popover:** gunakan untuk bantuan atau tindakan kontekstual singkat; posisikan agar tidak terpotong dan dapat ditutup dengan keyboard maupun tombol.
- **Liquid glass:** lapisan transparan pada tombol atau permukaan yang cocok, dengan fallback warna solid, kontras cukup dan performa wajar. Jangan mengorbankan keterbacaan di atas gambar atau latar yang ramai.

## Visual, gerak, dan aksesibilitas

- Terapkan sistem konsisten untuk tipografi, jarak, warna, status, ikon, tombol, form dan kartu. Status harus menggunakan teks, bukan warna saja; form memiliki label, bantuan dan kesalahan yang jelas.
- Gunakan foto produk nyata bila tersedia; hindari klaim dan data contoh yang dapat disalahartikan sebagai informasi usaha.
- Animasi bersifat fungsional: umpan balik, transisi dan orientasi. Hindari gerak otomatis berlebihan; hormati `prefers-reduced-motion` dan berikan fallback jika skrip gagal.
- GSAP digunakan bila benar-benar membantu. Pilih hanya plugin yang dibutuhkan, pastikan kompatibilitas dan lisensi, dan bersihkan listener/animasi. `@gsap/react` tidak diperlukan untuk Blade tanpa React.
- Pastikan akses keyboard, urutan fokus yang logis, indikator fokus terlihat, target sentuh memadai, kontras, nama aksesibel, pesan status, dan dukungan pembaca layar. Dialog harus mengelola fokus dengan benar.

## Pemeriksaan sebelum dianggap selesai

1. Audit seluruh halaman dan variasi guest/customer, termasuk kosong, loading, validasi, kesalahan, data panjang, dan banyak item.
2. Uji alur katalog → keranjang → login → checkout → detail unit → ongkos, desain, pembayaran dan progres, tanpa melakukan transaksi nyata.
3. Uji desktop, tablet dan beberapa ukuran mobile, keyboard, zoom, safe area, reduced motion, jaringan lambat, dan fallback tanpa fitur visual tertentu.
4. Minta screenshot dan umpan balik pengguna; perbaiki berdasarkan hasil pengujian sebelum melanjutkan role berikutnya.
5. Jalankan build dan tes terkait; pisahkan hasil pemeriksaan otomatis dari pengujian browser nyata. Jangan menyatakan sempurna atau siap produksi sebelum terbukti.

### Beranda pelanggan dan navigasi mobile
- Hindari dua tombol yang membuka menu yang sama: menu radial berada di header; tab bawah membuka keranjang secara langsung. Navigasi desktop tetap tersedia.
- Beranda menampilkan pratinjau hingga empat papan dari katalog publik, bukan daftar aktivitas dan statistik pesanan. Pesanan tetap dapat diakses dari menu pelanggan.
- Pratinjau memakai katalog yang sudah ada sebagai progressive enhancement; bila JavaScript atau pemuatan gagal, tautan katalog tetap tersedia. Detail papan memiliki tautan kembali ke katalog; tombol kembali browser dari pratinjau diarahkan ke katalog juga. Uji alur ini di browser nyata, termasuk back/forward cache.
- Di katalog publik, gambar dan nama kartu membuka detail papan; tombol menambahkan satu unit ke keranjang melalui formulir biasa. Pertahankan tanggal filter saat menambahkan, serta status stok dan pesan kesalahan server.
- Cari dan filter katalog berada di header pada halaman katalog, dengan formulir inline sebagai fallback tanpa JavaScript. Header ringkas tetap menyediakan tombol pencarian.
- Tautan kembali utama ditampilkan seperti tombol berukuran sentuh, tetap menggunakan tautan tujuan yang jelas (bukan history.back). Tombol ikon saja harus memiliki label aksesibel yang menyebut tujuannya.
- Perpindahan halaman menggunakan View Transitions lintas dokumen secara progresif dengan slide push/pull ringan; arah riwayat balik dipakai bila API browser menyediakan indeksnya. Jangan mencegat navigasi atau formulir; browser yang belum mendukung tetap berpindah normal, dan reduced motion mematikan animasi. Periksa di browser nyata, termasuk kembali/maju dan halaman cache.

### Rating dan suka penyewaan
- Suka produk dan rating bintang produk ditampilkan di katalog/detail; pelanggan yang masuk dapat menyukai atau membatalkan. Rating diberikan satu kali per unit setelah dikembalikan, tanpa komentar atau identitas publik.
- Rating pesanan diberikan pemilik satu kali setelah seluruh unit selesai/dibatalkan dan minimal satu telah dikembalikan. Rating pesanan bersifat privat, terpisah dari rating produk.
- Suka publik pesanan memakai tautan acak yang dibuat secara sukarela oleh pemilik; halaman publik hanya berisi jumlah suka, bukan rincian pesanan atau identitas. Pelanggan yang masuk dapat menyukai. Pemilik dapat menonaktifkan tautan; suka tetap tersimpan dan tautan lama tidak dapat digunakan. Perlakukan tautan sebagai rahasia; jangan klaim konten anonim sepenuhnya karena pengguna harus masuk untuk menyukai.
- Navbar desktop memprioritaskan Beranda, Katalog, Pesanan, dan Bantuan; keranjang, notifikasi, serta profil menggunakan pintasan berlabel aksesibel. Tombol “Cari & filter” digunakan untuk produk; tidak ada tombol atau shortcut command palette. Pada tablet yang lebih sempit, tautan utama tetap tersedia; header boleh bertambah tinggi agar tidak saling bertumpuk.
- Tautan suka dapat disalin dengan tombol saat clipboard tersedia dalam konteks aman; input tetap dapat disalin manual tanpa JavaScript. Halaman tautan harus mengirim kebijakan referrer `no-referrer` untuk mengurangi kebocoran token; pemilik tetap bertanggung jawab membagikannya hanya kepada orang yang dipilih.
- Registrasi tetap dapat dikirim tanpa JavaScript atau saat skrip gagal: tampilkan seluruh langkah secara default dan aktifkan tampilan bertahap hanya setelah skrip siap. Pastikan pilihan radio dapat difokuskan dengan keyboard, pesan kesalahan terhubung ke input, tombol kata sandi memiliki label/status, dan aturan validasi klien tidak lebih ketat daripada aturan server.
- Login tidak menolak format identitas yang masih diterima server; validasi format dan otentikasi tetap diputuskan server. Pertahankan keyboard yang mendukung email maupun nomor HP, tampilkan kesalahan terhubung ke input, dan sembunyikan toggle kata sandi yang tidak berfungsi saat JavaScript tidak tersedia.
- Checkout multiunit: tandai isian unit yang ditolak server dengan ringkasan dan tautan ke kolomnya; jangan sembunyikan kolom yang masih memiliki kesalahan. Salin unit harus mengumumkan hasil dan tidak mengubah paket. Persetujuan checkout harus menyebut ketentuan penyewaan dan status uji coba secara jelas.
- Detail unit menempatkan langkah berikutnya sebelum rating; rating tetap dapat dijangkau lewat tautan bagian dan baru ditawarkan setelah unit dikembalikan. Saat pengiriman bukti atau respons desain gagal, pertahankan nominal dan catatan yang aman, tampilkan kesalahan terkait, serta jelaskan bahwa berkas harus dipilih ulang oleh pengguna.
- Filter katalog dan pemeriksaan tanggal detail harus mempertahankan isian yang ditolak dan menautkan kesalahan ke kolomnya. Bila dialog didukung, buka filter bermasalah dan fokuskan kolom pertama yang salah; tanpa JavaScript formulir tetap tersedia inline. Bedakan hasil ketersediaan sebelumnya dari tanggal yang belum berhasil diperiksa.
- Jika pemeriksaan tanggal gagal setelah pengguna sebelumnya memilih periode lain, jelaskan bahwa hasil lama belum diperbarui dan jangan izinkan penambahan papan dengan tanggal lama secara tidak sengaja; pengguna harus memperbaiki dan memeriksa ulang atau menghapus filter.
