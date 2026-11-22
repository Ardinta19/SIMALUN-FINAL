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
- **Command palette desktop:** pintasan opsional untuk navigasi dan pencarian yang memang tersedia. Sediakan tombol terlihat, petunjuk shortcut, dukungan keyboard, fokus yang dikembalikan, dan Escape untuk menutup. Tidak menggantikan navigasi utama.
- **Radial sidebar mobile:** prioritaskan Keranjang, Pesanan, Notifikasi, dan Bantuan/FAQ di dalam menu; tab utama pelanggan memuat Beranda, Katalog, Menu, dan Profil. Pertahankan akses langsung di desktop melalui navigasi atau command palette. Gunakan pemicu yang mudah dijangkau. Jaga urutan baca logis, ukuran target sentuh, keterangan teks dan tata letak yang tidak terpotong; bila ruang sempit gunakan susunan alternatif yang tetap mudah digunakan.
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
