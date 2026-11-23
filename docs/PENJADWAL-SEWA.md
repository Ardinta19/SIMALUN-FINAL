# Penjadwal konfirmasi sewa (pengujian)

Perintah `php artisan rental:expire-confirmations` membatalkan unit yang masih menunggu konfirmasi saat tenggat 24 jam tercapai, dan pembatalan itu melepas kembali tanggal yang ditahannya. Unit yang sudah dikonfirmasi tidak dibatalkan oleh perintah ini. Perintah aman dijalankan berulang; konfirmasi dan pembatalan otomatis mengunci baris unit agar tidak saling menimpa. Untuk kasus jadwal dekat, tanggal penerimaan pada atau sebelum tanggal tenggat konfirmasi memerlukan centang persetujuan khusus admin sebelum tenggat (belum ada pilihan jam serah-terima, sehingga satu hari penuh dianggap dekat).

Laravel menjadwalkan perintah ini setiap menit, tetapi **scheduler harus benar-benar dijalankan** oleh lingkungan server. Bila scheduler berhenti, pembatalan tertunda sampai scheduler kembali berjalan; konfirmasi setelah tenggat tetap ditolak. Perintah `rental:expire-payments` juga dijadwalkan setiap menit untuk memproses tenggat pelunasan, pembatalan unit dan akhir masa kelanjutan.

**Toleransi pembayaran terlambat.** Pelunasan jatuh tempo pukul 23.59 WIB pada H-1, dan unit yang belum tertutup bukti tepat waktu dibatalkan pada pukul 00.00 WIB di hari penerimaan. Sejak pembatalan itu sampai **H+1 pukul 12.00 WIB** pelanggan masih boleh mengirim bukti (`RentalPaymentDeadline::graceDeadline()`), dan admin masih boleh memverifikasi lalu melanjutkan unit. Sesudah pukul 12.00 WIB, penjadwal menandai `deposit_forfeited_at`: DP hangus, bukti baru ditolak, dan kelanjutan ditolak. Pembatalan manual (bukan karena tenggat) tetap hanya dapat dilanjutkan sampai hari penerimaan berakhir. Bukti tepat waktu yang jumlahnya menutup tagihan menahan pembatalan meskipun belum diverifikasi; penolakan setelah tenggat ditahan untuk peninjauan manual. Ongkos belum disepakati dan konfirmasi khusus setelah tenggat juga ditahan untuk ditinjau. Pengembalian dana tetap dilakukan dan dicatat manual. Perilaku bersamaan pada MySQL, khususnya unggahan yang melintasi tenggat, belum diuji dan perlu diaudit sebelum produksi. Jangan gunakan sistem untuk transaksi nyata.

Untuk mencoba sekali pada database preview: `php artisan rental:expire-confirmations`. Untuk menjalankan scheduler secara interaktif pada komputer pengujian: `php artisan schedule:work` (biarkan terminal terbuka; hentikan dengan Ctrl+C). Di server, konfigurasi cron atau layanan scheduler sesuai dokumentasi Laravel dan pantau kegagalannya; jangan bergantung pada terminal interaktif. Pastikan zona waktu aplikasi dan jam server benar. Jangan gunakan `migrate:fresh` atau seeder pada database yang berisi data.

## Pengingat dua arah (`rental:remind`)

Perintah `php artisan rental:remind` mengirim notifikasi tanpa mengubah status apa pun. Tujuannya memutus dua pihak yang saling menunggu: pelanggan menunggu admin, admin menunggu pelanggan.

| Jenis | Penerima | Syarat | Pesan |
| --- | --- | --- | --- |
| `payment_proof_pending` | admin | bukti berstatus `pending` sudah dikirim lebih dari **6 jam** | nominal dan unit yang menunggu verifikasi |
| `payment_due` | pelanggan | unit `confirmed`, `starts_on` **besok**, tagihan sudah pasti, dan bukti tepat waktu belum menutup tagihan | sisa tagihan harus dibayar sebelum 23.59 WIB |
| `payment_grace` | pelanggan | unit batal karena tenggat, belum hangus, masih dalam toleransi H+1 pukul 12.00, dan tagihan belum tertutup | batas mengirim bukti dan melanjutkan unit |
| `payment_grace_admin` | admin | sama seperti di atas dan ada bukti `pending` yang belum diverifikasi | bukti harus diverifikasi sebelum batas toleransi |
| `design_response` | pelanggan | preview terbaru `pending` lebih dari **24 jam**, unit masih `waiting`, acara belum lewat | preview menunggu persetujuan |
| `design_escalation` | admin | tenggat respons desain tercapai: **48 jam** sejak preview diunggah atau **3 hari** sebelum acara | pelanggan belum merespons desain |
| `return_pending` | admin | unit `confirmed`, `progress = handed_over`, dan `ends_on` sudah lewat **2 hari** | unit belum ditandai kembali, rating pelanggan tertahan |

Eskalasi desain berhenti setelah admin memakai "lanjutkan dengan catatan", karena keputusan sudah diambil. Pengingat `return_pending` berhenti sendiri begitu unit ditandai kembali atau dibatalkan; penandaan tetap manual karena admin perlu memastikan papan benar-benar sudah diterima. Setiap pengingat hanya dikirim sekali per unit, jenis, penerima, dan tanggal. Kuncinya tabel `rental_unit_reminders` dengan indeks unik, sehingga aman dijalankan berulang (dijadwalkan tiap lima menit) dan dua proses penjadwal tidak menggandakan pesan. Unit yang pelanggannya nonaktif atau tidak punya admin aktif dilewati tanpa mencatat baris, jadi pengingatnya masih bisa terkirim setelah akun diaktifkan lagi.

Untuk menjalankan pemeriksaan pembayaran secara manual pada database preview: `php artisan rental:expire-payments`. Perintah tersebut mengubah status data yang memenuhi kriteria; jalankan hanya jika memahami dampaknya. `php artisan rental:remind` hanya menulis notifikasi dan baris pengingat, tidak mengubah status unit atau pembayaran.
