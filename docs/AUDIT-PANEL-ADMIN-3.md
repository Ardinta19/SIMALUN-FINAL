# Audit panel admin — putaran 43

Dokumen ini menilai **alur kerja admin** (bukan hanya rupa halaman): apakah admin
tahu apa yang harus dikerjakan, di mana tombolnya, dan berapa banyak yang harus
dibaca sebelum menekan tombol itu. Penilaian memakai angka yang diukur dari
berkas view, bukan kesan.

## Cara mengukur

```bash
python3 - <<'PY'
import re
for f in ["rental/admin","rental/admin-detail","rental/schedule","rental/cashbook",
          "rental/universities","rental/addons","rental/vouchers","rental/payment-settings",
          "roles/admin/dashboard","roles/admin/audit/index","roles/admin/help","catalog/admin"]:
    s = open(f"resources/views/{f}.blade.php").read()
    print(f, s.count("\n")+1, len(re.findall(r"<button", s)),
          len(re.findall(r"<(input|select|textarea)", s)),
          len(re.findall(r"<form", s)), len(re.findall(r"<details", s)))
PY
```

Hasil sebelum perbaikan putaran ini:

| Halaman | baris | tombol | isian | formulir | lipatan |
|---|---|---|---|---|---|
| `rental/admin-detail` | 467 | **12** | **20** | **12** | 7 |
| `catalog/admin` | 291 | **13** | 18 | **13** | 1 |
| `rental/vouchers` | 188 | 2 | 12 | 2 | 1 |
| `rental/cashbook` | 189 | 3 | 2 | 3 | 2 |
| `roles/admin/dashboard` | 217 | 0 | 0 | 0 | 0 |
| `rental/addons` | 162 | 4 | 10 | 4 | 2 |
| `rental/schedule` | 173 | 1 | 3 | 1 | 2 |
| `rental/admin` | 135 | 1 | 4 | 1 | 0 |
| `rental/universities` | 102 | 3 | 6 | 3 | 2 |
| `rental/payment-settings` | 86 | 1 | 4 | 1 | 1 |
| `roles/admin/audit/index` | 183 | 1 | 4 | 1 | 0 |
| `roles/admin/help` | 113 | 0 | 0 | 0 | 4 |

Angka mentah saja tidak cukup, tetapi dua baris teratas sudah menunjukkan
masalahnya: **halaman operasional harian adalah halaman terpadat**, dan justru
di situ admin menghabiskan waktunya.

## Temuan, diurutkan menurut kerugian

### A1. CSS panel admin tidak memuat komponen yang dipakainya (diperbaiki)

Panel admin hanya memuat `berkah-admin.css` dan `page-transitions.css`. Namun
partial bersama memakai kelas dari `berkah-ui-kit.css` / `berkah-experience.css`
yang **tidak pernah dimuat** di admin:

| Kelas | Dipakai di | Akibat sebelum diperbaiki |
|---|---|---|
| `bk-sr-only` | caption tabel buku kas | teks "Rincian kas: tanggal, jenis, kategori…" **terlihat** di atas tabel |
| `bk-swatch` | katalog admin, isian pesanan | kotak warna varian tidak muncul, hanya nama warna |
| `bk-disclosure` | detail pesanan, katalog | pembuka lipatan tampil seperti teks biasa tanpa penanda |
| `bk-items__*` | `rental/_ordered_items` di detail pesanan | daftar isian kehilangan kolom harga dan nomor urut |
| `bk-reference-list` | foto referensi desain | foto 96 px berjajar tanpa bingkai |

Ini sumber utama kesan "penuh dan tidak rapi" yang sulit ditunjuk penyebabnya.

**Perbaikan:** aturan mandiri untuk sembilan kelas itu ditambahkan ke
`berkah-admin.css`, dan `AdminFlowTest` (baru) sekarang menolak kelas admin
yang tidak punya aturan gaya. `AdminSurfaceTest` yang sudah ada tetap menjaga
larangan `style` sebaris dan daftar `.adm-*` yang wajib tersedia.

### A2. Tindakan utama terkubur di dasar kartu (diperbaiki)

Di detail pesanan, untuk memajukan pengerjaan (mulai → siap → diserahkan →
dikembalikan) admin harus: menggulir kartu unit, membuka lipatan **ketiga**
("Riwayat dan perkembangan"), lalu menemukan tombolnya. Tiga lipatan lain
(Ongkos, Pembayaran, Desain) tampil lebih dulu.

**Perbaikan:** panel **"Langkah berikutnya"** di puncak kartu unit berisi satu
kalimat keadaan, satu tombol utama, dan catatan opsional untuk pelanggan.
Riwayat tetap ada di lipatan, tanpa tombol duplikat (`actions => false`).

### A3. Satu pekerjaan, tiga tempat (diperbaiki)

Formulir "melanjutkan unit" muncul **tiga kali** pada satu halaman (blok
payment_timeout, blok dibatalkan, blok batal terakhir), masing-masing dengan
syarat berbeda yang tidak dijelaskan di dekat tombol. Konfirmasi unit berada
jauh di bawah dua grid informasi.

**Perbaikan:** ketiganya disatukan ke panel, dengan syarat yang dihitung dari
aturan yang sama dengan `RentalProgressController`. Bila syarat belum terpenuhi,
tombol dinonaktifkan dan panel menyebut alasannya persis:

- "Pratinjau desain belum disetujui pelanggan." → tautan **Buka pratinjau desain**
- "Tagihan belum final karena ongkos belum disetujui." → **Buka ongkos dan tagihan**
- "Pembayaran belum lunas: kurang Rp …" → **Buka pembayaran**

Admin tidak lagi menekan tombol lalu ditolak.

### A4. Katalog: setiap baris membuka formulir penuh (diperbaiki)

Satu papan dengan lima warna menghasilkan lima formulir sunting lima isian,
semuanya terbuka. Halaman 291 baris berisi 13 formulir dan 13 tombol.

**Perbaikan:** sunting varian, sunting paket, tambah varian, tambah paket, dan
ubah kategori kini berada di balik `<details>` berlabel jelas ("Ubah warna ini",
"Tambah paket baru"). Baris ringkas (nama, stok, status) tetap terlihat.

### A5. Daftar pesanan tidak mengatakan "apa yang harus dilakukan" (diperbaiki)

Kartu di `rental/admin` hanya menampilkan jumlah unit dan badge status samar
"perlu tindakan", lalu tombol "Lihat detail pesanan". Admin harus membuka tiap
pesanan untuk tahu mana yang mendesak.

**Perbaikan:** setiap kartu kini memuat hingga tiga **tugas konkret** dengan
tautan langsung ke bagian terkait:

- "2 bukti bayar diperiksa" → `#pembayaran-<unit>`
- "Konfirmasi Papan Mawar" → `#unit-<unit>`
- "Pratinjau awal …" → `#desain-<unit>`
- "Ongkos … belum ditawar" → `#ongkos-<unit>`
- "Tandai kembali …" (unit lewat tanggal kembali) → `#riwayat-<unit>`
- "Pelanggan meninjau desain" (menunggu pelanggan, bukan admin)

Sisanya diringkas "+N tugas lain". Bila tidak ada, kartu berkata terus terang
"Tidak ada yang menunggu admin di pesanan ini." Badge "perlu tindakan" dihapus
karena tidak memberi tahu apa pun. Datanya dimuat lewat `units.payments` dan
`units.previews` sekaligus, jadi tidak ada kueri per unit.

**Diperbaiki lanjutan (putaran 44):** urutan kartu kini bisa dipilih —
**Bawaan**, **Tenggat konfirmasi terdekat**, atau **Tanggal acara terdekat**.
Bawaan tidak berubah (antrean konfirmasi memakai tenggat, selebihnya terbaru),
dan pilihan aktif muncul sebagai chip yang bisa dilepas seperti filter lain.

### A6. Pil tahap belum ada di daftar (sebagian diperbaiki)

Header unit di detail sekarang menyebut `Tahap 2/5 · Dalam pengerjaan`, tetapi
daftar pesanan belum. **Usulan P2:** bawa pil yang sama ke daftar (satu per
unit, ringkas) supaya admin bisa memilah pekerjaan tanpa membuka pesanan.

### A6b. Paginasi admin tampil tanpa tata letak (diperbaiki)

Paginasi memakai `vendor.pagination.token-simple` (lewat
`Paginator::defaultView`). Butirnya sudah diberi warna lewat
`.adm-app .bk-page-nav__link`, tetapi **wadah `.bk-page-nav`** hanya
didefinisikan di `berkah-ui-kit.css` yang tidak dimuat panel admin. Akibatnya
tautan halaman tampil sebagai tautan blok yang menumpuk ke bawah, bukan sederet
tombol 44 px.

**Perbaikan:** aturan wadah (`display:flex`, `flex-wrap`, `gap`, `justify-content`)
dan ukuran butir 44 px ditambahkan ke `berkah-admin.css`, diuji oleh
`AdminFlowTest::test_paginasi_admin_punya_tata_letak_bukan_hanya_warna`, dan
berkas paginasi kini ikut disapu oleh penjaga kelas.

### A6c. Buku kas: angka tanpa pembanding (diperbaiki)

Kartu "Periode dipilih" dan "Periode sebelumnya" berisi enam angka; admin tetap
harus menghitung sendiri naik atau turun. Sekarang satu kalimat di bawah
statistik meringkasnya: "Dibanding 01/10–31/10: pemasukan naik 20%, pengeluaran
turun 5%." Pembagi nol dicegat (`sebelum === 0` → "baru ada" atau disembunyikan),
jadi tidak ada pembagian nol ketika periode sebelumnya kosong.

### A7. Dua belas kata untuk satu keadaan (terbuka)

`UNIT_STATUS` dan `PROGRESS` punya 12 nilai berbeda; di layar semuanya muncul
sebagai badge abu-abu serupa. **Usulan P3:** petakan ke empat warna makna
(menunggu tindakan admin / menunggu pelanggan / dikerjakan / selesai) dan
tampilkan kata yang sama di daftar, detail, dan filter.

## Rencana bertahap

| Fase | Isi | Status |
|---|---|---|
| **P1** | CSS pinjaman, panel langkah berikutnya, satu formulir melanjutkan, katalog berlipat | **selesai, putaran 43** |
| **P2** | Baris tugas, pil tahap, **urutan tenggat/acara**, paginasi admin, perbandingan buku kas | **selesai, putaran 44** |
| **P3** | Penyatuan bahasa status (4 warna makna), audit teks jargon ("peninjauan manual", "toleransi verifikasi"), pintasan papan tunjuk di dasbor | setelah P2 |

## Soal "React, GSAP, Lenis"

Permintaan itu masuk akal untuk situs pemasaran, tetapi berbahaya di sini:

1. **Panel admin adalah aplikasi server-rendered.** 15 halaman Blade, 550+ tes
   yang membaca HTML hasil render. React berarti menulis ulang seluruh panel
   sekaligus memindahkan tes ke level komponen — risiko regresi jauh lebih besar
   daripada manfaatnya untuk formulir CRUD.
2. **Tidak ada jalur build yang dipakai.** Aset disajikan langsung dari `public/`
   dengan `?v=<mtime>`. Menambah bundler berarti mengubah cara deploy pemilik di
   Windows.
3. **Animasi sudah ada fondasinya.** `berkah-admin.js` menandai
   `[data-adm-reveal]` dengan IntersectionObserver, menghormati
   `prefers-reduced-motion`, dan punya jaring aman 1,6 detik bila skrip gagal.
   GSAP hanya akan menambah ±70 KB demi efek yang bisa dicapai dengan CSS
   transition. Lenis (smooth scroll) bertabrakan dengan perilaku gulir bawaan
   peramban dan tidak ramah pembaca layar.

Yang **dilakukan** untuk rasa "naik kelas":

- panel langkah berikutnya memakai satu aksen warna wine di tepi kiri sebagai
  penanda tindakan utama;
- pil tahap `tabular-nums` supaya angka tidak melompat saat berubah;
- `:target` menandai bagian yang dituju setelah lompatan jangkar;
- pembuka lipatan mendapat penanda `▸` yang berputar 90° (dimatikan saat
  `prefers-reduced-motion`).

## Berkas yang berubah di putaran 43

- `resources/views/rental/admin-next-step.blade.php` (baru)
- `resources/views/rental/admin-detail.blade.php`
- `resources/views/rental/admin-progress.blade.php`
- `resources/views/catalog/admin.blade.php`
- `public/css/berkah-admin.css`
- `tests/Feature/AdminFlowTest.php` (baru)
