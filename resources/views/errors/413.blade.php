@php
    // Tanpa `url()->previous()`: halaman galat tidak boleh mengandalkan referer.
    $code = 413; $icon = 'package'; $title = 'Unggahan terlalu besar'; $accent = '#c98a3f';
    $message = 'Berkas yang dikirim melebihi batas server. Perkecil foto (misalnya lewat galeri ponsel) atau unggah lebih sedikit foto, lalu kirim ulang pesanan.';
    $primary = ['url' => route('cart.index'), 'label' => 'Kembali ke keranjang', 'icon' => 'arrow-left'];
    $secondary = ['url' => url('/'), 'label' => 'Ke beranda', 'icon' => 'home'];
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','secondary'))
