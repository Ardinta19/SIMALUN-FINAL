# Audit UI/UX panel admin — putaran 2

Tanggal: 22 November 2026
Cakupan: seluruh halaman role admin (`/admin/**`), dua tema, tiga lebar layar.
Bukti: tangkapan layar pengguna, berkas `resources/views/**`, `public/css/berkah-admin.css`,
`public/css/berkah-theme.css`.

---

## 1. Ringkasan: kenapa masih terasa belum beres

Bukan karena satu halaman kurang rapi, tapi karena panel admin punya **tujuh sumber
ketidakkonsistenan yang berjalan bersamaan**. Selama akar-akarnya ada, setiap perbaikan
kecil hanya memindahkan rasa "kurang" ke halaman lain.

| # | Akar masalah | Akibat yang terlihat |
|---|---|---|
| A | **Dua bahasa desain**: 10 halaman memakai shell admin (`layouts.admin`), 7 halaman masih dokumen sendiri (dasbor, panduan, pesanan laundry, kasir walk-in, keuangan, laporan, kategori, voucher) | kepala halaman, tombol, dan jarak berbeda antar halaman; pindah menu terasa pindah aplikasi |
| B | **Tema gelap tidak pernah dirancang**, hanya dipetakan ulang per nama variabel | kartu menyatu dengan latar, tombol utama pucat, catatan berwarna zaitun, teks kecil redup |
| C | **Navigasi diulang empat kali** (sidebar/laci, bilah atas, bilah bawah, "Aksi Cepat") dengan isi yang sama | layar terasa penuh tapi tidak informatif; tidak ada satu pun area yang "ini yang penting" |
| D | **Tipografi mikro**: `.55–.68rem`, huruf kapital semua, `letter-spacing` lebar, dipakai untuk label penting | mata lelah; label terbaca sebagai hiasan, bukan informasi |
| E | **Dua keluarga ikon**: emoji berwarna di dasbor, Lucide garis di tempat lain | kesan "belum selesai"/kurang profesional |
| F | **Cacat isi**: nomor daftar ganda di Panduan, aksi kembar di build lama, sisa elemen bocor | teks tampak berantakan meski tata letaknya benar |
| G | **Kepala halaman bermacam-macam**: pita maroon bergelombang, pita datar, dan topbar tipis | tidak ada pola yang bisa dikenali pengguna saat berpindah halaman |

Kesimpulan: perbaikan berikutnya bukan "menambah hiasan", tapi **mengurangi jumlah pola**
dan **menyelesaikan tema gelap sebagai palet**, bukan sebagai pemetaan nama variabel.

---

## 2. Temuan teknis (dengan bukti)

### B. Tema gelap — akar kesulitan membaca

1. **Satu token dipakai untuk dua peran.** `--adm-wine` dipakai sebagai *warna teks*
   (16 tempat) sekaligus *latar tombol* (3 tempat). Di tema gelap nilainya menjadi
   `#e9a7b8` agar teks terbaca; akibatnya tombol utama menjadi merah muda pucat dengan
   **teks putih** → kontras 1,9:1.
2. **Token "deep" menjadi hitam di tema gelap.** `--adm-wine-deep: #1b1014` dipakai
   sebagai warna teks saat hover (`.adm-btn--ghost:hover`, `.adm-page-note summary:hover`)
   → teks hilang di atas latar gelap.
3. **Permukaan dan latar terlalu berdekatan.** `--adm-cream #141013` vs
   `--adm-surface #1d171b` (1,15:1) dan garis `#3a2c32` (1,4:1). Bayangan tidak menolong
   karena hitam di atas hitam.
4. **`--surface` dan `--card` dipetakan ke nilai yang sama** (`--theme-surface`) di
   `berkah-theme.css`. Di tema terang keduanya berbeda (`#faf4f3` vs `#ffffff`), di tema
   gelap keduanya `#201820` → kartu tidak punya bidang.
5. **Catatan memakai latar status pekat** (`--warning-soft #3a2f1a`, zaitun) sehingga
   terbaca sebagai blok kusam, bukan informasi.
6. **Pita merek tetap pekat** di tema gelap (`#8c2440` pada ujung gradien) sehingga mata
   beradaptasi ke bidang terang dan bagian halaman lain tampak lebih gelap dari aslinya.

### C. Navigasi berulang

Dasbor memuat 10 tujuan yang sama di "Aksi Cepat", sementara sidebar memuat 18 tujuan dan
bilah bawah 5. Tidak ada perbedaan peran antar permukaan itu; semuanya menu.

### D. Tipografi

Label `BUKUKAS`, `NOTIFIKASI`, `ANTREAN CEPAT` memakai kapital 8,8–9,9 px dengan
`letter-spacing`; pada 390 px ini memaksa ukuran turun lagi (9 px) dan menipiskan kontras.

### F. Cacat isi yang ditemukan

- `roles/admin/help.blade.php` memakai **layout bantuan pelanggan**, menghasilkan nomor
  daftar bertumpuk ("1.1", "2.2") dan kepala halaman yang tidak sama dengan admin.
- Build lama menampilkan **"Voucher" dua kali** di Aksi Cepat (di sumber terbaru sudah
  satu; kemungkinan cache view).
- Ada satu karakter `2` bocor di tepi kiri dasbor desktop — **belum teridentifikasi**,
  perlu tangkapan layar penuh untuk memastikan elemen sumbernya.

---

## 3. Yang diperbaiki pada putaran ini (commit `menyusul`)

| Perbaikan | Berkas | Dampak |
|---|---|---|
| Token isian baru `--adm-solid`, `--adm-danger-solid`; tombol/lencana/paginasi memakainya | `berkah-admin.css` | teks putih di tombol tetap 7,4:1 di kedua tema |
| `--adm-wine-deep` di tema gelap menjadi `#ffd3de` | `berkah-admin.css` | teks hover tidak lagi hilang |
| Palet gelap: latar `#0f0b0e`, permukaan `#1e181c`, garis `#4c3943`/`#614a55` | `berkah-admin.css` | kartu dan garis akhirnya terpisah dari latar |
| Tepi atas kartu diberi garis cahaya 5% di tema gelap | `berkah-admin.css` | isyarat elevasi tanpa bergantung pada bayangan |
| Catatan menjadi permukaan netral + garis aksen kiri 3 px | `berkah-admin.css` | tidak ada lagi blok zaitun |
| `--theme-bg #0c090b`, `--theme-line #533d47`, `--theme-ink-lt #b5a0a7` (7,05:1), hero gelap dilembutkan | `berkah-theme.css` | teks sekunder dan garis terbaca; pita merek tidak menyilaukan |
| **Panduan admin dipindah ke shell admin** | `roles/admin/help.blade.php` | kepala halaman, tema gelap, tombol kembali, dan navigasi sama dengan halaman lain; nomor ganda hilang |
| Emoji Aksi Cepat diganti ikon Lucide | `roles/admin/dashboard.blade.php` | satu keluarga ikon di seluruh panel |

---

## 4. Rencana prioritas berikutnya

**P0 — menyelesaikan satu bahasa desain**
1. Pindahkan 6 halaman laundry lama (`orders`, `walkin`, `finance`, `reports`,
   `categories`, `vouchers`) ke `layouts.admin`. Ini sekaligus menghapus bilah bawah
   ganda, lajur 520 px di desktop, dan chip status terpotong.
2. Samakan kepala halaman: satu pola `adm-page-head` (kicker, judul, subjudul, aksi
   kanan). Hapus pita bergelombang di dasbor bila tidak dipakai halaman lain.

**P1 — tema gelap sebagai palet**
3. Ganti pemetaan `--surface`/`--card` agar benar-benar berbeda di tema gelap.
4. Audit kontras otomatis untuk pasangan (latar, permukaan, garis, teks) di semua
   halaman, bukan hanya token.

**P2 — informasi, bukan hiasan**
5. Kurangi navigasi berulang: dasbor hanya menampilkan **antrean kerja** (yang harus
   dikerjakan hari ini), bukan menu yang sama dengan sidebar.
6. Naikkan ukuran label minimum ke 11 px dan hentikan kapital untuk kata biasa.
7. Satukan ukuran ikon (20/18 px) dan hapus emoji yang tersisa di halaman lama.

---

## 5. Cara memeriksa

```powershell
git pull
php artisan view:clear
php artisan test
```

Periksa berturut-turut: dasbor gelap (390/1440), Panduan admin gelap, buku kas terang,
dan satu halaman laundry lama. Yang dicari: kartu punya bidang jelas, tidak ada teks
hilang saat hover, dan kepala halaman berbentuk sama kecuali halaman laundry lama.
