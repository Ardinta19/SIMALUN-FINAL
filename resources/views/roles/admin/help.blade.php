@extends('rental.help-layout')
@section('title', 'Panduan admin')
@section('back', route('dashboard.admin'))
@section('heading', 'Panduan admin')
@section('subtitle', 'Panduan operasional penyewaan papan bunga.')
@section('content')
<div class="help-card help-notice"><strong>Masih dalam pengujian.</strong> Jangan gunakan sistem untuk transaksi nyata.</div>
<section class="help-card"><h2>Urutan pekerjaan</h2><ol>
<li>Kelola papan, kategori, stok dan paket di <a href="{{ route('admin.catalog.index') }}">kelola katalog</a>. Atur tarif universitas jika tersedia.</li>
<li>Periksa <a href="{{ route('admin.rental.orders', ['status' => 'pending']) }}">unit menunggu konfirmasi</a>. Tanggal sudah ditahan sejak pesanan dikirim, jadi konfirmasi hanya mengunci unit untuk dikerjakan. Pastikan kesiapan sebelum menyetujui jadwal dekat.</li>
<li>Jika ada pengantaran atau penjemputan, periksa ongkos dan tunggu persetujuan pelanggan. Upload preview untuk setiap unit dan tunggu persetujuan sebelum memulai pengerjaan.</li>
<li>Periksa bukti pembayaran per unit. Verifikasi hanya setelah memastikan dana diterima; pelunasan terverifikasi diperlukan sebelum serah terima.</li>
<li>Perbarui perkembangan tiap unit dan gunakan <a href="{{ route('admin.rental.schedule') }}">jadwal</a> sebagai rencana serah terima, bukan bukti bahwa serah terima sudah terjadi.</li>
</ol></section>
<section class="help-card"><h2>Pembayaran dan buku kas</h2>
<details><summary>Bagaimana mengatur QRIS dan WhatsApp?</summary><p>Gunakan <a href="{{ route('admin.rental.payment-settings.edit') }}">pengaturan pembayaran</a>. Pastikan QRIS dan nomor tujuan benar. Sistem tidak memeriksa transfer secara otomatis; jangan masukkan PIN atau kata sandi.</p></details>
<details><summary>Kapan pemasukan dicatat?</summary><p>Pembayaran sewa masuk ke <a href="{{ route('admin.rental.cashbook.index') }}">buku kas</a> saat admin memverifikasi bukti. Jangan mencatat pembayaran yang sama lagi sebagai pemasukan manual. Koreksi nominal dilakukan melalui pembayaran terkait.</p></details>
<details><summary>Bagaimana mencatat pengembalian?</summary><p>Kembalikan dana terlebih dahulu, lalu catat pengembalian pada pembayaran terverifikasi yang terkait. Form pengembalian hanya mencatat; sistem tidak mengirim dana.</p></details>
<details><summary>Apa yang dilakukan jika ada peninjauan manual?</summary><p>Periksa ongkos, jadwal dan bukti. Jika ada bukti yang perlu diperbaiki, berikan alasan. Lepaskan peninjauan setelah penanganan selesai dengan memahami bahwa penjadwal akan memeriksa kembali tenggat.</p></details>
</section>
<section class="help-card"><h2>Pengingat operasional</h2><p>Penjadwal harus berjalan untuk pembatalan otomatis dan tindak lanjut tenggat. Periksa pembatalan akibat kesalahan penjual dan pengembalian dana secara manual. Hasil cetak dan jadwal hanya mencerminkan keadaan saat dibuka.</p></section>
@endsection
@section('navigation')@include('layouts.component.admin._navbar_admin', ['active' => ''])@endsection
