# Menghapus data: pesanan uji vs data katalog

Dua hal berbeda, dan mencampurnya berbahaya:

| Kebutuhan | Alat | Akibat |
| --- | --- | --- |
| Mengosongkan **riwayat pesanan** untuk UAT dari nol | `php artisan rental:purge-orders` | pesanan, unit, pembayaran, desain, riwayat hilang; **katalog, voucher, universitas, akun tetap** |
| Menghapus **satu** papan/kategori/voucher/paket yang salah input | tombol Hapus di panel | hanya baris itu; ditolak bila sudah dipakai pesanan |
| Mengulang **seluruh** basis data dari nol | `php artisan migrate:fresh --seed` | **semua** hilang, termasuk katalog dan akun — jangan pada basis data berisi data |

## 1. `rental:purge-orders`

```bash
# 1. Lihat dampaknya lebih dulu (tidak menghapus apa pun)
php artisan rental:purge-orders --dry-run

# 2. Cadangkan basis data (contoh SQLite)
cp database/database.sqlite database/database-cadangan-$(date +%F).sqlite

# 3. Hapus
php artisan rental:purge-orders
```

Pilihan lain:

| Pilihan | Arti |
| --- | --- |
| `--force` | tanpa pertanyaan konfirmasi; **wajib** bila `APP_ENV=production` |
| `--keep-finance` | catatan buku kas yang lahir dari pembayaran sewa dipertahankan |
| `--with-notifications` | notifikasi yang menunjuk ke pesanan sewa ikut dihapus |

Yang **dihapus** (urutan mengikuti foreign key, anak dulu):

```
rental_payment_corrections → rental_payment_refunds → rental_payments
→ rental_design_previews → rental_unit_progress_events
→ rental_unit_cancellation_events → rental_product_reviews → rental_order_reviews
→ rental_order_unit_addons → rental_order_references → rental_order_units
→ rental_voucher_usages → rental_orders
```

Ikut terhapus otomatis (cascade): pengingat unit, peristiwa penawaran ongkos, suka/bagikan pesanan, foto referensi. Bila `--keep-finance` tidak dipakai, `finance_entries` dengan `source_type` `rental_payment`/`rental_refund` juga dihapus.

Yang **tidak disentuh**: `rental_categories`, `rental_products`, `rental_product_variants`, `rental_duration_packages`, `rental_addons`, `vouchers`, `rental_universities`, `rental_payment_settings`, `users`, dan seluruh tabel modul lain.

> Catatan: nomor otomatis (`AUTO_INCREMENT`) tidak direset. Itu sengaja — nomor urut yang melompat tidak merusak apa pun, sedangkan meresetnya membutuhkan `TRUNCATE` yang justru menghapus lebih banyak daripada yang diminta.

## 2. Tombol Hapus di panel

Semua hapus memakai satu pola: **dua langkah**.

```
[ Hapus papan ▾ ]  →  "Papan Mawar beserta 2 warna dan 1 paket durasinya
                        akan dihapus permanen, termasuk fotonya."
                        [ Ya, hapus papan ]
```

Tidak ada `confirm()` bawaan peramban: teksnya tidak bisa menjelaskan akibat, tidak bisa digayakan, dan diblokir sebagian peramban. Bila sesuatu tidak boleh dihapus, tombolnya **tidak dirender** dan alasannya yang tampil.

### Matriks hapus

| Data | Boleh dihapus bila | Bila tidak | Alasan teknis |
| --- | --- | --- | --- |
| Kategori | belum berisi papan | nonaktifkan | `rental_products.rental_category_id` = `restrictOnDelete` |
| Papan | belum pernah dipesan | nonaktifkan | `rental_order_units.rental_product_id` = `restrictOnDelete`; riwayat menunjuk ke sini |
| Warna papan | tidak menahan tanggal | nonaktifkan | stok per warna harus bisa ditelusuri |
| Paket durasi | belum dipakai unit | nonaktifkan | `rental_order_units.rental_duration_package_id` = `restrictOnDelete` |
| Item tambahan | belum dipakai unit | nonaktifkan | `rental_order_unit_addons.rental_addon_id` |
| Voucher | belum pernah dipakai | nonaktifkan | `rental_voucher_usages` = `cascadeOnDelete`; catatan pemakaian tidak boleh hilang diam-diam |
| Universitas & tarif | selalu boleh | — | pesanan menyimpan salinan nama + tarifnya sendiri |
| Catatan kas manual | `source_type = manual` | — | catatan dari pembayaran sewa bersumber dari pesanan (403) |
| QRIS / instruksi bayar | ada tombol hapus QRIS | — | baris pengaturan hanya satu |

Prinsipnya: **data yang sudah menyentuh uang atau riwayat tidak dihapus; ia dinonaktifkan.** Menonaktifkan sudah cukup untuk menyembunyikannya dari katalog dan checkout.

## 3. Yang sengaja **tidak** punya tombol hapus

| Data | Alasan | Jalan keluarnya |
| --- | --- | --- |
| Pesanan & unit | riwayat uang dan serah terima; menghapusnya membuat buku kas dan rekap tidak bisa dipertanggungjawabkan | batalkan unit (tetap tercatat) atau `rental:purge-orders` bila memang seluruh riwayat uji |
| Pembayaran, koreksi, pengembalian dana | bukti transaksi | koreksi/kembalikan dana lewat panel, jangan hapus |
| Catatan kas dari pembayaran sewa | lahir dari pesanan, bukan dari buku kas (403 bila dipaksa) | perbaiki lewat koreksi pembayaran |
| Akun pengguna | jejak audit "siapa mengubah apa" menunjuk ke akun | `php artisan user:edit` (nonaktifkan), `php artisan user:restore` |
| Notifikasi | riwayat pemberitahuan | `php artisan rental:prune-notifications` |

## 4. Setelah purge: pastikan aplikasi siap dipakai lagi

1. `/admin/sewa` → daftar kosong, antrean berangka 0.
2. `/katalog` → papan, warna, paket, item tambahan masih ada.
3. `/admin/voucher-sewa` → voucher ada; "dipakai pada N pesanan" kembali 0.
4. `/admin/buku-kas` → kosong bila catatan sewa ikut dihapus; saldo awal tidak terpengaruh.
5. Buat satu pesanan uji dari katalog sampai selesai.
