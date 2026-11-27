# Analisis Alur Pengguna — Berkah Kreatif

Dokumen ini menjawab satu pertanyaan pemilik usaha: **apakah memesan lewat
situs ini lebih cepat daripada lewat chat WhatsApp?** Isinya hitungan ketukan
nyata untuk tiap peran, gesekan yang ditemukan saat menelusuri kode dan
tangkapan layar, apa yang sudah diperbaiki, dan apa yang masih tersisa.

Terakhir diperbarui: putaran 35 (22 November 2026).

## 1. Cara mengukur

Ukuran yang dipakai sengaja kasar supaya bisa diperiksa siapa pun tanpa alat:

- **ketukan** = satu klik/tap yang mengubah halaman, membuka panel, atau
  mengirim formulir. Mengetik dihitung terpisah karena panjangnya berbeda-beda
  (nama penerima vs daftar pesanan di WhatsApp).
- **halaman perantara** = layar yang harus dilewati tanpa ada keputusan baru di
  dalamnya. Targetnya nol: setiap layar harus punya satu tindakan yang jelas.
- **waktu menunggu balasan** = lama orang menunggu informasi yang bisa
  ditampilkan situs sendiri (stok, harga, status, ongkir).

Batas yang dipakai sebagai target: **tindakan utama tiap peran ≤ 2 ketukan dari
layar tempat orang itu biasanya berada**, dan **tidak ada halaman perantara**.

## 2. Ringkasan

| Peran | Tindakan utama | Titik awal yang wajar | Ketukan sekarang | Target | Status |
|---|---|---|---|---|---|
| Publik | Cek harga & ketersediaan satu papan | Beranda / Instagram | 2 (buka papan → periksa tanggal) | ≤ 2 | tercapai |
| Publik | Masukkan papan ke keranjang | Halaman papan | 2 (isi tanggal → tambah) | ≤ 2 | tercapai |
| Publik | Selesaikan pesanan | Keranjang | 2 (checkout → kirim) + isi formulir | ≤ 2 | tercapai |
| Publik | Tahu seberapa jauh pengisian checkout | Halaman checkout | 0 (bilah + sisa isian selalu tampil) | 0 | **baru** |
| Publik | Pindah antar bagian pengisian | Tab bagian per papan | 1 (tab atau Lanjut) | 1 | **baru** |
| Publik | Tanya stok tanpa keluar dari situs | Halaman papan | 1 (tombol WhatsApp terisi) | 1 | **baru** |
| Pelanggan | Tahu pesanannya sampai mana | Dasbor | 0 (stepper sudah tampil) | 0–1 | **baru** |
| Pelanggan | Tahu pesanannya sampai mana | Daftar pesanan | 0 (ringkasan progres di kartu) | 0–1 | **baru** |
| Pelanggan | Tahu apa yang menunggu dia | Dasbor | 0 (chip tindakan per unit) | 0–1 | **baru** |
| Pelanggan | Buka unit & kirim bukti bayar | Notifikasi | 2 (pesanan → unit) | 1 | sisa pekerjaan |
| Admin | Konfirmasi satu unit | Dasbor (chip antrean) | 3 (antrean → detail → tombol) | ≤ 2 | mendekati |
| Admin | Kirim penawaran ongkir | Detail pesanan | 1 (simpan penawaran) | 1 | tercapai |
| Admin | Bereskan antrean ongkir | Dasbor | 0 untuk tarif universitas (disetujui otomatis) | 0 | **baru** |
| Admin | Verifikasi bukti bayar | Dasbor (chip antrean) | 2 (antrean → tombol verifikasi) | ≤ 2 | tercapai |
| Admin | Naikkan progres + kabari pelanggan | Detail pesanan | 1 (tombol langkah berikutnya) | 1 | tercapai |

Sebagai pembanding, "memesan lewat WhatsApp" bukan satu tindakan: buka WA (1),
cari chat (1–2), menulis papan + tanggal + ucapan + alamat (puluhan ketukan
papan ketik), kirim (1), menunggu balasan admin, lalu admin **menyalin ulang**
semuanya ke catatan. Situs ini menang bukan karena jumlah ketukannya lebih
sedikit, melainkan karena (a) tidak ada yang perlu diketik ulang, (b) tanggal
sudah ditahan saat pesanan dikirim, dan (c) status dan tagihan bisa dilihat
kapan saja tanpa menunggu balasan.

## 3. Alur publik (belum masuk)

Alur sekarang: beranda → kartu papan → isi tanggal → "Periksa tanggal" →
jumlah → "Pesan langsung" → (masuk/daftar bila perlu) → checkout satu halaman →
"Kirim pesanan".

Temuan dan tindakan:

1. **Kanal kontak hanya ada di footer sebagai baris tautan kecil** (butuh
   menggulir sampai bawah dan membidik tautan 12 px). Diperbaiki: footer
   sekarang memakai strip berjalan yang sama dengan beranda, jadi nomor
   WhatsApp terbaca sambil bergerak dan setinggi area sentuh yang layak.
2. **Pembeli yang tetap ingin bertanya harus mencari nomor dan mengetik ulang
   papan yang sedang dilihatnya.** Diperbaiki: tombol "Tanya papan ini lewat
   WhatsApp" di detail papan dan "Tanya keranjang ini lewat WhatsApp" di
   keranjang; pesannya sudah berisi nama papan, warna, harga, dan tanggal.
3. **Ketersediaan hanya perkiraan.** Tetap begitu secara desain: tanggal baru
   ditahan setelah pesanan dikirim. Pesan di layar sudah menyebut ini, jadi
   tidak ada janji palsu yang membuat orang menunggu.
4. **Formulir checkout punya enam bagian di satu halaman dan terasa penuh.**
   Diperbaiki pada putaran 36: satu papan = satu kartu dengan tiga tab
   (Jadwal → Ucapan & desain → Serah terima), bilah kemajuan "N dari M isian
   wajib", kolom kesiapan yang menempel, dan tombol kirim yang baru berdenyut
   setelah semuanya lengkap. Tetap satu halaman — tidak ada halaman perantara
   yang ditambahkan. Unit kedua dan seterusnya bisa mengisi "Salin detail dari
   unit sebelumnya".
5. **Keranjang tidak menahan stok.** Dijelaskan dalam satu kalimat pendek di
   keranjang dan checkout; ini pilihan sadar agar tidak ada stok "hantu" dari
   keranjang terbengkalai.

## 4. Alur pelanggan (sudah masuk)

Alur sekarang: dasbor → (progres unit yang berjalan) → pesanan → unit → bayar,
setujui desain, pantau, beri rating.

Temuan dan tindakan:

1. **Dasbor pelanggan tidak memuat satu pun informasi pesanan** — hanya
   pratinjau katalog. Akibatnya pertanyaan "papan saya sudah jadi belum?"
   berakhir di WhatsApp. Diperbaiki: bagian **"Progres pesananmu"** berisi
   maksimal empat unit aktif dengan stepper ringkas dan tautan "Buka unit".
2. **Daftar pesanan hanya menampilkan hitungan status** (menunggu/dikonfirmasi/
   dibatalkan) — tidak menjawab "sedang dikerjakan atau sudah siap". Diperbaiki:
   baris ringkasan progres pada kartu pesanan.
3. **Halaman unit hanya menyebut status dalam satu kalimat** dan riwayat
   tersembunyi di dalam panel. Diperbaiki: stepper lima langkah tepat di bawah
   judul unit, plus kalimat arahan di bawahnya (mis. "Papan sudah siap. Serah
   terima menunggu jadwal dan pembayaran yang lunas.").
4. **Notifikasi progres berbunyi "Perkembangan unit telah diperbarui"** — isi
   yang memaksa pelanggan membuka aplikasi untuk tahu apa yang berubah.
   Diperbaiki: judul memuat langkahnya (`Progres: Siap diserahkan`) dan pesan
   memuat arahan serta catatan admin bila ada.
5. **Belum ada tautan langsung dari notifikasi ke bagian yang perlu
   ditindak.** Notifikasi saat ini membuka halaman pesanan; pelanggan masih
   perlu memilih unit. Ini sisa pekerjaan P1 di §6.
6. **Ongkir bisa menahan pembayaran untuk angka yang tidak pernah berubah.**
   Diperbaiki: tarif universitas yang sudah tampil saat checkout langsung
   disetujui, dan penawaran 0 dari admin berarti gratis tanpa persetujuan.

## 5. Alur admin

Alur sekarang: dasbor (chip antrean, sudah terfilter) → daftar pesanan →
detail (akordeon per unit) → satu tombol per keputusan.

Temuan dan tindakan:

1. **Antrean "perlu penawaran ongkos" berisi unit yang tarifnya sudah pasti.**
   Admin dipaksa mengirim penawaran untuk angka yang sudah diketahui pelanggan,
   lalu menunggu persetujuan yang tidak perlu — dua antrean yang menganggur.
   Diperbaiki di sisi checkout; antrean tinggal berisi alamat bebas yang
   memang perlu ditawar.
2. **Semua tindakan sudah satu formulir satu tombol** (konfirmasi, tolak,
   penawaran ongkos, verifikasi bayar, progres). Tidak ada halaman perantara.
3. **Batas dua ketukan tercapai untuk verifikasi pembayaran** karena chip
   antrean di dasbor sudah membawa filter status ke daftar pesanan.
4. **Aksi berisiko memakai modal bersama** (`showConfirmModal`), bukan balon
   peramban, sehingga tidak ada konfirmasi yang terlewat di ponsel.
5. **Konfirmasi unit masih tiga ketukan** (dasbor → detail → tombol). Ini
   disengaja: admin perlu melihat tanggal, stok, dan catatan pelanggan sebelum
   menahan tanggal orang lain. Tidak dijadikan satu ketukan.

## 6. Sisa pekerjaan (diurutkan menurut dampak)

| Prioritas | Pekerjaan | Alasan |
|---|---|---|
| P1 | Tautan notifikasi langsung ke unit + jangkar yang relevan (bayar/desain/ongkir) | Menghapus satu ketukan pada alur yang paling sering dipakai pelanggan |
| P1 | Ringkasan "perlu tindakanmu" di dasbor pelanggan — **dikerjakan sebagian**: chip per unit (setujui ongkos, tinjau desain, bukti ditolak, belum lunas) sudah tampil di dasbor; yang belum: satu baris total di atas halaman | Pertanyaan yang paling sering masuk ke WhatsApp |
| P2 | Tombol "Bayar" langsung di kartu unit yang belum lunas | Jalur terpendek ke pemasukan |
| P2 | Admin: aksi massal "verifikasi beberapa bukti" setelah diperiksa satu per satu | Mengurangi pengulangan saat ramai |
| P3 | Simpan filter katalog di sesi (bukan hanya URL) agar "Kembali" selalu membawa filter yang sama | Keranjang dan detail papan sudah membawa `q`/`category`/tanggal |
| P3 | Ringkasan mingguan otomatis ke admin (jumlah unit, antrean terlama) | Menggantikan rekap manual |

## 7. Cara memeriksa dokumen ini tetap benar

1. Beranda → buka satu papan: tombol "Tanya papan ini lewat WhatsApp" ada di
   bawah tombol keranjang, dan pesannya sudah menyebut nama papan.
2. Keranjang berisi dua papan → tombol "Tanya keranjang ini lewat WhatsApp"
   membuka chat dengan dua baris rincian.
3. Masuk sebagai pelanggan yang punya unit aktif: dasbor menampilkan "Progres
   pesananmu"; kartu di "Pesanan saya" menampilkan "… dikerjakan / siap
   diserahkan / selesai".
4. Sebagai admin: kirim penawaran ongkos 0 → pelanggan menerima "Ongkos gratis"
   dan tombol bayar langsung tersedia.
5. Sebagai admin: naikkan progres → notifikasi pelanggan berjudul langkah baru,
   bukan "Perkembangan unit".
