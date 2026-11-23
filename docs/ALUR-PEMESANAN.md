# Alur pemesanan sewa: peta, titik bolak-balik, dan titik saling menunggu

Analisis pada commit `9075f4d`. Semua klaim di bawah menunjuk ke berkas dan baris
yang bisa diperiksa ulang; tidak ada yang disimpulkan dari nama fitur saja.

## 1. Peta status yang sebenarnya dipakai kode

Tiga lapis status hidup berdampingan dan masing-masing punya pemilik berbeda:

| Lapis | Nilai | Dipakai oleh |
| --- | --- | --- |
| `rental_orders.confirmation_deadline` | `+24 jam` saat checkout (`RentalOrderController.php:116`) | pembatalan otomatis unit `pending` |
| `rental_order_units.status` | `pending` → `confirmed` → `cancelled` | stok, akses halaman |
| `rental_order_units.progress` | `waiting` → `production` → `ready` → `handed_over` → `returned` | pengerjaan & serah terima |
| `rental_order_units.cancel_code` | `manual`, `payment_timeout` | jalur pelanjutan & DP hangus |
| `rental_payments.status` | `pending` → `verified` / `rejected` | buku kas & gerbang serah terima |
| `rental_design_previews.status` | `pending` → `approved` / `revision` / `superseded` | gerbang mulai pengerjaan |

`progress` hanya bisa maju satu langkah per aksi dan harus cocok dengan nilai
yang dilihat admin saat halaman dimuat (`RentalProgressController.php:15-21`).
Tidak ada lompatan, tidak ada mundur.

```
pelanggan                              admin
---------                              -----
pilih tanggal di katalog
tambah ke keranjang (boleh tanpa tanggal)
checkout ─────────────────────────────► pesanan pending + tenggat 24 jam
   ▲                                        │  lewat 24 jam → cancelled otomatis
   │                                        ▼
   │                                    konfirmasi (jadwal dekat: centang khusus)
   │                                        ▼
   │  (jika perlu ongkos) ◄───────────── penawaran ongkos ──► pelanggan setujui
   │                                        │                 (tidak ada opsi tolak)
   │  unggah preview desain ◄───────────────┘
   │        └─► pelanggan setujui / minta revisi   ← tidak ada tenggat
   │                                        │
   │  bayar (bukti) ───────────────────────► verifikasi admin ← tidak ada tenggat
   │                                        ▼
   │            progress: waiting → production → ready → handed_over → returned
   │            (production butuh preview approved; handed_over butuh lunas verified)
   ▼                                        ▼
beri rating produk & pesanan  ◄───── unit returned
```

## 2. Siapa menunggu siapa

| Titik tunggu | Yang menunggu | Yang harus bertindak | Tenggat otomatis | Kalau tidak bertindak |
| --- | --- | --- | --- | --- |
| Konfirmasi pesanan | pelanggan | admin | **ada**: 24 jam, `ExpireRentalConfirmations` | unit batal + tanggal dilepas (benar) |
| Penawaran ongkos | pelanggan | admin | tidak ada (pengingat: eskalasi desain belum mencakup ongkos) | `payableAmount()` = `null`, pembayaran ditolak, unit tidak bisa dibatalkan otomatis |
| Persetujuan ongkos | admin | pelanggan | tidak ada | sama seperti di atas |
| Preview desain | admin (tidak bisa mulai pengerjaan) | pelanggan | tidak ada, tetapi ada pengingat harian + eskalasi admin (babak A) | unit tetap `waiting` sampai acara, pembayaran penuh pun tidak menolong |
| Verifikasi bukti bayar | pelanggan (tidak bisa lunas) | admin | tidak ada, tetapi ada pengingat bila bukti menganggur > 6 jam (babak A) | serah terima tidak bisa ditandai |
| Tandai kembali | pelanggan (tidak bisa rating) | admin | tidak ada | unit menggantung `handed_over` selamanya |
| Rating | admin (indikator kualitas) | pelanggan | tidak ada | wajar; bukan penghambat |

Empat baris tanpa tenggat itulah inti "saling menunggu".

## 3. Temuan A — alur bolak-balik

**RT-1. Kembali dari detail papan membuang filter.**
`resources/views/catalog/show.blade.php:5` memakai `route('welcome')` tanpa query,
padahal kartu produk membawa `starts_on`/`ends_on` (`catalog/index.blade.php`).
Pelanggan yang menyaring "12 Des 2026 + kategori Wisuda + kata kunci" harus
memasang ulang filternya setelah melihat satu papan.
_Dampak_: sedang, terjadi setiap kali membandingkan papan.

**RT-2. Keranjang ⇄ checkout kehilangan isian.**
`resources/views/rental/checkout.blade.php:4` secara eksplisit memberi tahu bahwa
menambah/mengurangi unit harus lewat keranjang dan isian di checkout akan hilang.
Untuk pesanan 3 unit, mengubah jumlah unit berarti mengetik ulang penerima,
ucapan, dan alamat tiga kali.
_Dampak_: tinggi pada pesanan multiunit.

**RT-3. Revisi desain tanpa batas dan tanpa pengingat.**
Setiap revisi membuat preview baru berstatus `pending` dan men-supersede yang lama
(`RentalDesignController.php:28-30`). Tidak ada batas versi, tidak ada pengingat,
dan pelanggan harus membuka ulang halaman unit untuk melihat versi baru.
_Dampak_: sedang; menambah panjang siklus tanpa jaminan selesai.

**RT-4. Ongkos hanya bisa naik satu arah.**
Setelah pelanggan menyetujui, admin tidak bisa mengirim penawaran baru
(`RentalOrderController.php:283` menolak jika `transport_approved_at` sudah terisi),
dan pelanggan tidak punya tombol menolak — hanya menyetujui. Negosiasi harga
pasti keluar aplikasi (WhatsApp), lalu berakhir di pembatalan manual.
_Dampak_: tinggi ketika tarif salah, karena satu-satunya jalan adalah membatalkan unit.

**RT-5. Pembatalan dan perubahan data hanya lewat admin.**
Rute `rental.cancel` hanya ada di grup admin (`routes/web.php:189`); halaman unit
pelanggan hanya memuat 4 form: respons desain, bukti bayar, setujui ongkos, rating.
Salah ketik nama penerima atau ucapan tidak bisa diperbaiki sendiri, dan
membatalkan berarti menghubungi admin. Tidak ada tombol "pesan ulang" dari unit
yang dibatalkan, jadi pesanan baru dimulai dari nol.
_Dampak_: tinggi, karena ini jalur paling sering dipakai saat ada kesalahan.

**RT-6. Pelunasan terlambat bergantung pada verifikasi di hari yang sama.**
`eligibleForLatePayment` hanya berlaku tepat pada `starts_on`
(`RentalPaymentDeadline.php:24-28`), dan `resume` mensyaratkan pembayaran
**terverifikasi** pada hari itu juga (`RentalOrderController.php:321`).
Kiriman bukti pukul 23.50 dan verifikasi pukul 00.05 berarti uang masuk tetapi unit
tetap batal dan DP hangus — harus diselesaikan manual.
_Dampak_: tinggi (uang pelanggan menggantung).

**RT-7. Semua pemberitahuan hanya di dalam aplikasi.**
`RentalUpdate::via()` mengembalikan `['database']` (satu-satunya kanal). Tidak ada
pengingat H-1, tidak ada email/WhatsApp. Pelanggan harus membuka aplikasi untuk
tahu ada preview yang menunggu; admin harus membuka dasbor untuk tahu ada bukti
yang menunggu. Ini memperpanjang semua titik tunggu di atas.

_Babak A_: `rental:remind` membuat pemberitahuan menjadi proaktif (bukti menganggur,
pelunasan H-1, respons desain, eskalasi admin). Kanalnya tetap di dalam aplikasi;
email/WhatsApp masuk babak C.
_Dampak_: tinggi, karena menjadi pengali bagi temuan lain.

## 4. Temuan B — saling menunggu yang benar-benar mengunci

**DL-1. Persetujuan desain tanpa tenggat mengunci seluruh unit (berat).**
`production` hanya boleh dimulai jika preview terbaru `approved`
(`RentalProgressController.php:22`), sedangkan `production` adalah syarat untuk
`ready`, `handed_over`, dan `returned`. Jadi pelanggan yang sudah **lunas** tetapi
tidak pernah merespons preview membuat unit mustahil diserahkan, mustahil
dikembalikan, dan mustahil dinilai. Satu-satunya jalan keluar adalah membatalkan
unit (refund) — padahal acaranya mungkin besok. Pembatalan otomatis juga tidak
akan menolong karena `timelyCoverage` sudah menutup tagihan
(`ExpireRentalPayments.php:36`).

_Babak A_: pelanggan diingatkan setiap hari setelah preview menganggur 24 jam, dan
admin dieskalasi setelah 48 jam atau bila acara tinggal 3 hari. Status tetap tidak
berubah — keputusan "lanjut dengan catatan" menunggu babak B.

**DL-2. Verifikasi bukti bayar tanpa tenggat menahan serah terima (sedang-berat).**
`handed_over` mensyaratkan `verifiedNet() >= payableAmount()`
(`RentalProgressController.php:25-28`). Bukti yang hanya `pending` tidak menolong,
meski pengiriman tepat waktu mencegah pembatalan. Bila admin tidak memverifikasi,
papan tidak bisa diserahkan walau pelanggan sudah membayar. Tidak ada pengingat
otomatis untuk bukti yang menunggu lebih dari X jam; hanya satu notifikasi saat bukti
dikirim (`RentalPaymentController.php:37`).

_Babak A_: `rental:remind` mengingatkan admin sekali sehari untuk setiap bukti yang
menganggur lebih dari 6 jam, sehingga antrean tidak lagi hanya bergantung pada
kartu dasbor.

**DL-3. Penawaran ongkos yang tidak pernah dikirim membuat unit tak bisa dibatalkan
otomatis (sedang-berat).**
Selama `payableAmount()` = `null` (ongkos belum ada atau belum disetujui),
pembayaran ditolak (`RentalPaymentController.php:33`), dan penjadwal hanya memasang
`payment_review_hold` tanpa membatalkan (`ExpireRentalPayments.php:29-34`). Unit
tetap `confirmed` dan menahan tanggal tanpa ada tenggat. Admin melihat antreannya di
dasbor (`rentalQuoteNeeded`), jadi tidak sepenuhnya gelap, tetapi tidak ada
eskalasi bila antrean bertambah.

**DL-4. Pelanggan tidak punya jalan keluar selain WhatsApp (sedang).**
Tidak ada rute batal untuk pelanggan. Bila admin tidak merespons, unit menahan
tanggal sampai acara dan pelanggan tidak punya cara mundur yang tercatat.

**DL-5. Unit yang lupa ditandai kembali tidak pernah selesai (ringan-sedang).**
`handed_over → returned` murni manual (`RentalProgressController.php:21`). Tanpa
tanda itu, rating produk dan rating pesanan terkunci
(`RentalFeedbackController.php:57`), dan unit tetap dihitung "aktif" di dasbor
selamanya.

**DL-6. `payment_review_hold` lengket (sudah diperbaiki di babak A).**
Hold dipasang saat konfirmasi jadwal dekat, saat penjadwal menandai ongkos manual,
dan saat bukti ditolak (`RentalPaymentController.php:146`). Dulu hanya dilepas oleh
`resolveReview`, sehingga kartu "perlu peninjauan" menghitung unit yang sebenarnya
beres — termasuk unit yang sudah `returned`.

Sekarang `verify()` melepas hold otomatis begitu `verifiedNet() >= payableAmount()`
(`RentalPaymentController.php:69-70`; pembayaran sebagian tidak melepasnya), dan
antrean memakai scope bersama
`RentalOrderUnit::needsPaymentReview()` yang mengabaikan unit `returned`. Scope yang
sama dipakai kartu dasbor dan filter "review" daftar pesanan admin, jadi angkanya
konsisten.

## 5. Temuan C — celah konsistensi yang memperparah

**G-1. Gerbang serah terima tidak memeriksa desain, hanya pembayaran.**
`handed_over` hanya memeriksa tagihan. Konsistensinya masih terjaga karena
`production` wajib lewat `approved`, tetapi bila kelak ada tombol "lewati", gerbang
desain hilang tanpa peringatan.

**G-2. Tidak ada penyelesaian otomatis setelah acara.**
Tidak ada perintah yang menandai `returned` atau menutup unit `confirmed` yang
sudah lewat tanggal. Antrean "aktif" hanya bertambah.

**G-3. Rating pesanan menuntut semua unit selesai atau batal.**
Satu unit yang tersangkut `handed_over` memblokir rating seluruh pesanan
(`RentalFeedbackController.php:57`).

## 6. Rekomendasi berprioritas

Rekomendasi disusun agar perubahan sekecil mungkin dan tetap memakai pola yang
sudah ada (transaksi, notifikasi dalam transaksi, perintah penjadwal idempoten).

### P0 — mencegah uang atau jadwal menggantung

1. **Tenggat respons desain + eskalasi.**
   Tambah `design_deadline` per unit (mis. H-3 atau 48 jam setelah preview) dan aksi
   admin **"lanjutkan dengan catatan"** yang tetap butuh konfirmasi ulang tertulis
   (audit di `progress_events`) supaya pelanggan yang tidak responsif tidak mengunci
   operasional.
   _Menyelesaikan_: DL-1, RT-3.
   _Sudah dikerjakan sebagian (babak A)_: pelanggan diingatkan tiap hari dan admin
   dieskalasi pada ambang 48 jam / H-3; tenggat dan aksi admin belum ada.
2. **Auto-bersihkan hold saat tagihan tertutup.**
   _Selesai (babak A)_: `verify()` melepas hold ketika `verifiedNet() >=
   payableAmount()`, dan antrean memakai `needsPaymentReview()` yang mengecualikan
   unit `returned`. _Menyelesaikan_: DL-6.
3. **Pengingat bukti pembayaran yang menunggu.**
   _Selesai (babak A)_: perintah `rental:remind` mengirim pengingat admin untuk bukti
   `pending` lebih dari 6 jam dan pengingat pelanggan H-1 bila tagihan belum tertutup.
   _Menyelesaikan_: DL-2, RT-7.
4. **Jendela pembayaran terlambat yang aman.**
   Entah larang bukti baru setelah pukul 21.00 di hari acara, atau beri tenggang
   verifikasi sampai H+1 pukul 12.00 sebelum DP hangus. Pilih satu; sekarang
   pelanggan bisa membayar pada detik terakhir yang mustahil diverifikasi.
   _Menyelesaikan_: RT-6.

### P1 — menghapus bolak-balik yang paling mahal

5. **Ongkos dua arah**: tombol "Ajukan keberatan/tolak" untuk pelanggan, dan admin
   boleh mengirim versi baru selama belum ada pembayaran (reset
   `transport_approved_at`, naikkan `transport_quote_version`) — pelanggan wajib
   menyetujui versi terbaru, sudah didukung pengecekan versi.
   _Menyelesaikan_: RT-4, DL-3.
6. **Pelanggan dapat membatalkan sendiri** dengan konsekuensi DP yang tertulis dan
   konfirmasi ganda; memakai alur `cancel` yang sudah ada (catat
   `cancel_responsibility = customer`).
   _Menyelesaikan_: DL-4, RT-5.
7. **Perbaiki data unit sebelum diproses** (nama penerima, ucapan, alamat) selama
   `progress = waiting` dan belum ada pembayaran terverifikasi; setiap perubahan
   dicatat sebagai event agar teraudit.
   _Menyelesaikan_: RT-5.
8. **"Pesan ulang" dari unit batal**: isi otomatis keranjang/checkout dengan data
   unit lama plus tanggal baru divalidasi ketersediaan.
   _Menyelesaikan_: RT-5.
9. **Pertahankan filter katalog** saat kembali dari detail (kirim query asal), dan
   **simpan draf checkout** di sesi sehingga bolak-balik ke keranjang tidak
   menghapus isian.
   _Menyelesaikan_: RT-1, RT-2.

### P2 — penyelesaian dan pemantauan

10. **Pengingat & tutup otomatis setelah acara**: tandai `returned` bila H+2 belum
    ditandai (dengan catatan sistem) atau setidaknya munculkan peringatan di dasbor.
    _Menyelesaikan_: DL-5, G-2.
11. **Kartu dasbor "menunggu pelanggan"** (preview pending, ongkos menunggu
    persetujuan, bukti pending) beserta umur tunggu, agar admin melihat dua arah
    antrean, bukan hanya yang menunggu admin.
12. **Kanal pemberitahuan kedua** (email atau tautan WhatsApp siap kirim) karena
    semua titik tunggu bergantung pada pelanggan membuka aplikasi.
13. **Gerbang serah terima** ikut memeriksa `latestPreview->status === 'approved'`
    agar konsisten bila alur diubah kelak.

## 7. Status pelaksanaan

**Babak A — selesai.**

- `payment_review_hold` dilepas otomatis saat tagihan tertutup; antrean memakai
  `needsPaymentReview()` (dasbor + filter admin).
- Perintah `rental:remind` (dijadwalkan tiap lima menit) mengirim empat jenis
  pengingat dengan dedupe `rental_unit_reminders` (unit + jenis + penerima + tanggal).
- Tanggal pengingat, ambang, dan pesannya dijelaskan di `docs/PENJADWAL-SEWA.md`.
- Tes: `tests/Feature/RentalReminderTest.php`.

**Babak B — menunggu keputusan.** Tenggat respons desain + aksi admin "lanjutkan
dengan catatan" (DL-1), dan aturan pembayaran terlambat (RT-6): larang bukti setelah
pukul 21.00 di hari acara, atau beri toleransi verifikasi sampai H+1 pukul 12.00.

**Babak C — belum dikerjakan.** Ongkos dua arah (RT-4/DL-3), pembatalan mandiri
pelanggan (DL-4/RT-5), perbaikan data unit sebelum diproses, pesan ulang, filter
katalog dan draf checkout yang bertahan (RT-1/RT-2), pengingat penandaan kembali
(DL-5), kartu dasbor "menunggu pelanggan" (P2).

## 8. Yang sudah benar dan sebaiknya tidak diubah

- Tenggat konfirmasi 24 jam + pembatalan otomatis + pelepasan tanggal, idempoten
  (`ExpireRentalConfirmations`, dijalankan tiap menit).
- `timelyCoverage` mencegah pembatalan unit yang buktinya masuk tepat waktu meski
  belum diverifikasi — ini yang membuat DL-2 tidak berubah menjadi kerugian uang.
- Kunci baris produk/unit dan idempotensi checkout (`checkout_key`, hash payload).
- Koreksi nominal dan refund tercatat di buku kas dengan kunci permintaan.
- Pelanjutan hanya diizinkan bila stok dan pembayaran aman.
- Reservasi tanggal sejak `pending` (babak 10) membuat antrean konfirmasi tidak
  bisa diisi ganda.

## 9. Cara membuktikan perbaikan nanti

| Temuan | Bukti yang harus ada |
| --- | --- |
| DL-1 | tes: pelanggan tidak merespons → perintah pengingat jalan, admin bisa lanjut dengan catatan, event tercatat |
| DL-2/DL-6 | tes: verifikasi penuh membersihkan hold; kartu dasbor tidak menghitung unit `returned` |
| DL-3/RT-4 | tes: tolak ongkos → admin kirim versi baru → pelanggan setujui versi terbaru; versi lama ditolak |
| RT-5 | tes: pelanggan batal sendiri; perubahan data unit tercatat; pesan ulang mengisi keranjang |
| RT-6 | tes: bukti pukul 23.50 + verifikasi H+1 sesuai kebijakan yang dipilih |
| RT-1/RT-2 | tes: tautan kembali membawa query; draf checkout bertahan setelah kembali ke keranjang |
