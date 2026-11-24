@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Panduan admin')
@section('crumbs')<strong>Panduan admin</strong>@endsection
@section('content')
{{-- Halaman ini dulu memakai tata letak bantuan pelanggan: kepala halaman
     berbeda, nomor daftar bertumpuk, dan tema gelap tidak ikut. Sekarang ikut
     shell admin yang sama dengan halaman lain. --}}

<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Panduan</span>
        <h1>Panduan admin</h1>
        <p class="adm-page-sub">Urutan pekerjaan harian penyewaan papan bunga, dari katalog sampai buku kas.</p>
    </div>
    <div class="adm-actions">
        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.orders') }}">
            @include('layouts.component._icon', ['name' => 'orders', 'size' => 16])
            Buka pesanan
        </a>
    </div>
</div>

<div class="adm-note adm-note--warn" data-adm-reveal="note">
    <span class="adm-note__title">Mode pengujian</span>
    Masih dalam pengujian. Jangan gunakan sistem untuk transaksi nyata.
</div>

<section class="adm-card" data-adm-reveal="card" aria-labelledby="panduan-urutan">
    <div class="adm-card__head">
        <h2 id="panduan-urutan">Urutan pekerjaan</h2>
        <span class="adm-chip">5 langkah</span>
    </div>
    <ol class="adm-steps">
        <li>
            <span class="adm-steps__num" aria-hidden="true">1</span>
            <div class="adm-steps__body">
                <h3>Rapikan katalog</h3>
                <p>Kelola papan, kategori, stok dan paket di
                    <a href="{{ route('admin.catalog.index') }}">kelola katalog</a>. Atur tarif universitas jika tersedia.</p>
            </div>
        </li>
        <li>
            <span class="adm-steps__num" aria-hidden="true">2</span>
            <div class="adm-steps__body">
                <h3>Konfirmasi unit</h3>
                <p>Periksa <a href="{{ route('admin.rental.orders', ['status' => 'pending']) }}">unit menunggu konfirmasi</a>. Tanggal sudah ditahan sejak pesanan dikirim, jadi konfirmasi hanya mengunci unit untuk dikerjakan. Pastikan kesiapan sebelum menyetujui jadwal dekat.</p>
            </div>
        </li>
        <li>
            <span class="adm-steps__num" aria-hidden="true">3</span>
            <div class="adm-steps__body">
                <h3>Ongkos dan desain</h3>
                <p>Jika ada pengantaran atau penjemputan, periksa ongkos dan tunggu persetujuan pelanggan. Unggah pratinjau untuk setiap unit dan tunggu persetujuan sebelum memulai pengerjaan.</p>
            </div>
        </li>
        <li>
            <span class="adm-steps__num" aria-hidden="true">4</span>
            <div class="adm-steps__body">
                <h3>Verifikasi pembayaran</h3>
                <p>Periksa bukti pembayaran per unit. Verifikasi hanya setelah memastikan dana diterima; pelunasan terverifikasi diperlukan sebelum serah terima.</p>
            </div>
        </li>
        <li>
            <span class="adm-steps__num" aria-hidden="true">5</span>
            <div class="adm-steps__body">
                <h3>Catat serah terima</h3>
                <p>Perbarui perkembangan tiap unit dan gunakan <a href="{{ route('admin.rental.schedule') }}">jadwal</a> sebagai rencana serah terima, bukan bukti bahwa serah terima sudah terjadi.</p>
            </div>
        </li>
    </ol>
</section>

<section class="adm-card" data-adm-reveal="card" aria-labelledby="panduan-keuangan">
    <div class="adm-card__head">
        <h2 id="panduan-keuangan">Pembayaran dan buku kas</h2>
    </div>

    <details class="adm-faq">
        <summary>Bagaimana mengatur QRIS dan WhatsApp?</summary>
        <p>Gunakan <a href="{{ route('admin.rental.payment-settings.edit') }}">pengaturan pembayaran</a>. Pastikan QRIS dan nomor tujuan benar. Sistem tidak memeriksa transfer secara otomatis; jangan masukkan PIN atau kata sandi.</p>
    </details>

    <details class="adm-faq">
        <summary>Kapan pemasukan dicatat?</summary>
        <p>Pembayaran sewa masuk ke <a href="{{ route('admin.rental.cashbook.index') }}">buku kas</a> saat admin memverifikasi bukti. Jangan mencatat pembayaran yang sama lagi sebagai pemasukan manual. Koreksi nominal dilakukan melalui pembayaran terkait.</p>
    </details>

    <details class="adm-faq">
        <summary>Bagaimana mencatat pengembalian?</summary>
        <p>Kembalikan dana terlebih dahulu, lalu catat pengembalian pada pembayaran terverifikasi yang terkait. Form pengembalian hanya mencatat; sistem tidak mengirim dana.</p>
    </details>

    <details class="adm-faq">
        <summary>Apa yang dilakukan jika ada peninjauan manual?</summary>
        <p>Periksa ongkos, jadwal dan bukti. Jika ada bukti yang perlu diperbaiki, berikan alasan. Lepaskan peninjauan setelah penanganan selesai dengan memahami bahwa penjadwal akan memeriksa kembali tenggat.</p>
    </details>
</section>

<section class="adm-card" data-adm-reveal="card" aria-labelledby="panduan-pengingat">
    <div class="adm-card__head">
        <h2 id="panduan-pengingat">Pengingat operasional</h2>
    </div>
    <p class="adm-copy">Penjadwal harus berjalan untuk pembatalan otomatis, pengingat (<code>rental:remind</code>), dan tindak lanjut tenggat. Bukti yang masuk setelah tenggat pelunasan masih dapat diverifikasi sampai H+1 pukul 12.00 WIB; setelah itu DP hangus. Bila pelanggan tidak merespons desain sampai batasnya, gunakan "Lanjutkan dengan catatan" pada bagian pratinjau. Periksa pembatalan akibat kesalahan penjual dan pengembalian dana secara manual. Hasil cetak dan jadwal hanya mencerminkan keadaan saat dibuka.</p>
</section>
@endsection
