# Item tambahan & foto referensi desain

Dua pelengkap alur checkout, ditambahkan atas permintaan pemilik:

1. **Foto referensi desain** — pelanggan mengunggah contoh desain saat checkout,
   karena menjelaskan selera lewat teks sering tidak cukup.
2. **Item tambahan** — admin menyiapkan katalog pelengkap (lilin, kartu ucapan,
   bunga ekstra); pelanggan memilih jumlahnya per unit dan harganya masuk tagihan.

## Keputusan yang dipakai

| Pertanyaan | Keputusan |
|---|---|
| Berapa foto referensi? | Beberapa foto (maksimal 5) untuk **satu pesanan**, bukan per unit. |
| Daftar item tambahan berlaku untuk apa? | **Semua produk** — satu katalog global, bukan per kategori/produk. |
| Harga item masuk ke mana? | **Total unit** (`addon_amount`), sehingga ikut DP, pelunasan, sisa tagihan, pengingat, dan buku kas. |
| Boleh diubah setelah pesanan dikirim? | **Tidak** — pilihan terkunci saat checkout; perubahan lewat admin. |

## Tabel baru

| Tabel | Isi |
|---|---|
| `rental_addons` | Katalog admin: `name`, `description`, `price`, `image_path`, `is_active`, `sort_order`. |
| `rental_order_unit_addons` | Salinan pilihan per unit: `name`, `price`, `quantity`, `total`, `rental_addon_id` (nullable). |
| `rental_order_references` | Foto contoh per pesanan: `image_path`, `uploaded_by`. |
| `rental_order_units.addon_amount` | Total item tambahan unit, dijumlahkan di `payableAmount()`. |

Migrasi: `database/migrations/2026_11_22_000023_create_rental_addons_and_references.php`.

**Nama dan harga disalin** ke `rental_order_unit_addons` saat checkout. Admin boleh
mengubah harga atau menghapus item kapan saja tanpa menulis ulang tagihan pesanan
lama. Item yang sudah dipakai pesanan tidak bisa dihapus — panel menyarankan
"nonaktifkan" agar hilang dari checkout berikutnya tetapi riwayat tetap utuh.

## Alur

1. Admin membuka **Katalog & penawaran → Item tambahan**, mengisi nama, harga,
   keterangan, foto (opsional), dan urutan tampil.
2. Pelanggan membuka checkout: setiap unit punya bagian **Item tambahan** berisi
   kotak jumlah 0–5 per item; bagian **Foto referensi desain** (satu untuk seluruh
   pesanan) menerima sampai 5 berkas JPG/PNG/WebP maksimal **25 MB per berkas**
   dan **60 MB untuk seluruhnya**.
3. Saat dikirim, server memeriksa ulang: item harus masih aktif, jumlah 0–5,
   harga dihitung ulang dari katalog yang dikunci (`lockForUpdate`), lalu
   `addon_amount` dan baris salinan dibuat dalam transaksi yang sama dengan unit.
4. Foto disimpan di **disk privat** (`storage/app/rental-references`) sebelum
   transaksi; bila transaksi gagal atau pesanan ternyata sudah pernah dikirim,
   berkas yang baru diunggah dihapus lagi.
5. Foto bisa dibuka pelanggan pemilik pesanan dan admin lewat rute
   `rental.reference.image` (header `Cache-Control: private, no-store`).

## Tampilan

| Halaman | Yang terlihat |
|---|---|
| Checkout | Bagian item tambahan per unit, bagian foto referensi, `enctype="multipart/form-data"`. |
| Detail pesanan pelanggan | Daftar "Item tambahan: 2× Lilin · Rp 24.000" per unit + galeri foto referensi. |
| Detail unit | Baris item tambahan sebelum rincian voucher. |
| Ringkasan cetak | Baris "Item tambahan" beserta nama, jumlah, dan total. |
| Detail pesanan admin | Galeri foto referensi di kartu pelanggan + rincian item per unit. |

Gaya baru: `.bk-addon-list`, `.bk-addon`, `.bk-addon__img`, `.bk-addon__text`,
`.bk-addon__qty` (di `public/css/berkah-experience.css`) dan `.bk-reference-list`
(di `public/css/berkah-ui-kit.css`). Semua memakai token tema dengan cadangan
hangat, jadi tema gelap tidak perlu tambalan tambahan, dan sasaran ketuk tetap
44 px.

## Batas & perilaku tepi

- **Tanpa JavaScript tetap jalan**: kotak jumlah berupa `input[type=number]`
  biasa, unggahan memakai `<input type="file" multiple>`.
- Jumlah 0 berarti tidak dipilih (bukan error).
- Item yang berubah menjadi nonaktif di antara halaman checkout dan pengiriman
  ditolak dengan pesan "ada item tambahan yang sudah tidak tersedia".
- Foto tidak ikut dihitung dalam hash idempotensi pesanan: mengirim ulang
  pesanan yang sama tidak menggandakan pesanan maupun fotonya.
- Voucher tetap memotong **harga sewa** saja, tidak memotong item tambahan.

## Pengujian

`tests/Feature/RentalAddonsTest.php` (8 tes): pembuatan item oleh admin + jejak
audit, harga masuk tagihan & tersalin, penolakan item nonaktif dan jumlah >5,
larangan hapus item terpakai, penyimpanan foto (batas 5), hak akses foto, serta
tampilan di halaman pelanggan dan admin — termasuk render halaman panel admin
(kosong, terisi, dan chip "Dipakai pesanan" yang menyembunyikan tombol Hapus).

`UiAccessibilityTest::test_form_checkout_siap_mengunggah_foto_referensi` menjaga
`enctype`, atribut `multiple`, dan tinggi sasaran ketuk kontrol jumlah.

### Batas dan pesan galat

| Batas | Ditegakkan oleh | Pesan |
|---|---|---|
| Maksimal 5 **jenis** item per unit | `RentalOrderController::store` (cek manual per unit) | `Unit 1: pilih maksimal 5 jenis item tambahan per unit.` |
| Jumlah tiap item 0–5 | aturan `integer\|min:0\|max:5` | `Jumlah tiap item tambahan maksimal 5.` |
| Maksimal 5 foto per pesanan | aturan `array\|max:5` | `Maksimal 5 foto referensi.` |
| Format foto JPG/PNG/WebP | `image` + `mimes` | `Berkas harus berupa gambar.` / `Format foto harus JPG, PNG, atau WebP.` |
| Ukuran tiap foto ≤25 MB | `max:25600` | `Ukuran tiap foto maksimal 25 MB.` |
| Total foto ≤60 MB | `RentalOrderController::store` (jumlah ukuran berkas) | `Total ukuran foto referensi maksimal 60 MB. Perkecil foto atau unggah lebih sedikit.` |

Batas jenis diperiksa secara manual (bukan `max` pada array) supaya pesannya
menyebut unit yang salah, bukan nama field teknis `units.1.addons`. Semua pesan
berada di `lang/id/validation.php` (`custom` + `attributes`), sehingga tidak ada
lagi teks bawaan berbahasa Inggris atau nama kunci mentah di layar pelanggan.
Petunjuk batas juga ditulis di halaman checkout.

**Batas PHP.** Laravel hanya bisa menilai berkas yang berhasil masuk; bila
`upload_max_filesize`/`post_max_size` di `php.ini` lebih kecil, PHP membuang
berkasnya lebih dulu. Karena itu `App\Support\UploadLimit` membaca kedua nilai
itu (termasuk singkatan `2M`/`512K`/`1G`), dan checkout menampilkan peringatan
bila batas server masih di bawah 25 MB. `public/.user.ini` menyetel 30 MB per
berkas dan 80 MB per permintaan; pada Apache `mod_php` berkas itu diabaikan,
jadi nilai yang sama perlu diatur di `php.ini` (lihat `docs/kl/deployment.md`).

### Tulisan baku item tambahan

Semua halaman memakai satu bentuk: **nama**, lalu ` × jumlah` hanya bila jumlah
lebih dari satu — `Lilin`, `Lilin × 2`. Sumber tunggalnya
`RentalOrderUnitAddon::label()`; halaman unit, ringkasan cetak, detail pesanan
pelanggan, dan detail pesanan admin memanggil fungsi itu, sehingga tidak ada lagi
tiga variasi (`2× Lilin`, `Lilin × 2`, `Lilin × 1`). Baris item di halaman unit
sengaja berada **di luar** blok potongan voucher: harga item tetap terlihat
walau pesanan tanpa voucher.

`UiAccessibilityTest::test_semua_ekspresi_blade_bisa_diurai` menyapu seluruh
`resources/views`: setiap `{{ }}`, `{!! !!}`, dan blok `@php` diumpankan ke
`token_get_all(..., TOKEN_PARSE)`. Ini menutup celah yang tidak terlihat oleh
`php -l` — template hanya dikompilasi saat halaman diminta, sehingga kurung yang
kurang baru muncul sebagai galat 500 di tes halaman. Pesannya menyebut nama
berkas view, contoh: `resources/views/rental/unit.blade.php: syntax error,
unexpected token ";"`.
