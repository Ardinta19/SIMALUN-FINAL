@extends('catalog.layout')
@section('title', 'Keranjang')
@section('content')
<div class="cart-page">
<p><a href="{{ route('welcome') }}">← Kembali ke katalog</a></p>
<h1>Keranjang</h1>
<p class="muted">Setiap unit dapat memiliki jadwal dan desain yang berbeda. Menambahkan papan ke keranjang belum mencadangkan stok.</p>
@if(count($cart))<p><strong>{{ count($cart) }} unit</strong> di keranjang</p>@endif
<div class="items">
@forelse($cart as $id => $item)
    @php
        $product = $products->get($item['product_id']);
        $available = $product && $product->is_active && $product->category && $product->category->is_active && $product->stock > 0;
    @endphp
    <article class="card body cart-item">
        @if($available && $product->image_path)
            <img class="cart-image" src="{{ asset('storage/'.$product->image_path) }}" alt="Contoh {{ $product->name }}">
        @else
            <div class="cart-image cart-placeholder" aria-hidden="true">✿</div>
        @endif
        <div>
            <p class="cart-number">UNIT {{ $loop->iteration }}</p>
            @if($available)
                <h2>{{ $product->name }}</h2>
                <p class="muted">{{ $product->category->name }} · {{ $product->size ?: 'Ukuran sesuai katalog' }}</p>
                <p class="price">Harga dasar Rp {{ number_format($product->base_price, 0, ',', '.') }} / {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</p>
                <a href="{{ route('catalog.show', $product) }}">Lihat detail papan</a>
            @else
                <h2>Papan tidak tersedia</h2><p class="muted">Papan ini tidak lagi tersedia. Hapus unit ini sebelum melanjutkan.</p>
            @endif
            <form method="post" action="{{ route('cart.destroy', $id) }}">@csrf @method('DELETE')<button class="btn secondary" aria-label="Hapus unit {{ $loop->iteration }}{{ $available ? ' '.$product->name : ' yang tidak tersedia' }}">Hapus unit</button></form>
        </div>
    </article>
@empty
    <div class="card body"><h2>Keranjangmu masih kosong</h2><p class="muted">Pilih papan dari katalog untuk memulai pesanan.</p><a class="btn" href="{{ route('welcome') }}">Jelajahi katalog</a></div>
@endforelse
</div>
@if(count($cart))
    <div class="notice">Harga dasar belum termasuk durasi dan ongkos. Stok baru dicadangkan setelah admin mengonfirmasi.</div>
    <div class="cart-actions">
    @if($unavailableCount)
        <p role="status">Hapus {{ $unavailableCount }} unit yang tidak tersedia sebelum melanjutkan.</p>
    @elseif(auth()->check() && auth()->user()->role !== 'customer')
        <p>Checkout hanya tersedia untuk akun pelanggan. Masuk sebagai pelanggan untuk menguji pemesanan.</p>
    @else
        <a class="btn" href="{{ route('rental.checkout') }}">Lanjut ke checkout</a>
    @endif
    <a class="btn secondary" href="{{ route('welcome') }}">Tambah papan lain</a>
    </div>
@endif
</div>
@endsection
