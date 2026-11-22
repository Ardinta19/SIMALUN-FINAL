@extends('catalog.layout')
@section('title', 'Pengaturan pembayaran')
@section('content')
<p><a href="{{ route('admin.rental.orders') }}">← Pesanan sewa</a></p>
<h1>Pengaturan pembayaran</h1>
<p class="muted">QRIS adalah pilihan utama. Siapkan QRIS usaha yang benar dan nomor WhatsApp untuk metode lain. Pelanggan tetap mengirim bukti dan admin memverifikasinya secara manual; sistem tidak memeriksa transfer secara otomatis. Jangan masukkan PIN, kata sandi, atau kode rahasia.</p>
<form class="stack card body" method="post" action="{{ route('admin.rental.payment-settings.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
@if($settings?->qris_path)<p>QRIS saat ini:</p><img src="{{ route('rental.payment.qris') }}" alt="QRIS yang saat ini digunakan" style="max-width:260px;width:100%;height:auto"><label><input type="checkbox" name="remove_qris" value="1" style="width:auto"> Hapus QRIS saat ini</label>@else<p class="notice">QRIS belum diatur.</p>@endif
<label>{{ $settings?->qris_path ? 'Ganti gambar QRIS' : 'Unggah gambar QRIS' }} (JPG, PNG, WebP; maksimal 5 MB)<input type="file" name="qris" accept="image/jpeg,image/png,image/webp"></label><p class="muted">Unggah gambar yang terbaca dan pastikan nama penerima sesuai usaha. Jangan sekaligus memilih hapus dan ganti.</p>
<label>Nomor WhatsApp untuk metode lain<input type="tel" name="whatsapp_number" inputmode="numeric" maxlength="15" placeholder="6281234567890" value="{{ old('whatsapp_number', $settings?->whatsapp_number) }}"></label><p class="muted">Awali 62, hanya angka tanpa tanda plus atau spasi. Kosongkan jika belum tersedia.</p>
<label>Petunjuk tambahan<textarea name="instructions" maxlength="3000">{{ old('instructions', $settings?->instructions) }}</textarea></label><p class="muted">Petunjuk terlihat pada detail pesanan pelanggan.</p><p><button class="btn">Simpan pengaturan</button></p></form>
@endsection
