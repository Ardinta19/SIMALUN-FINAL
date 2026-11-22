@extends('catalog.layout')
@section('title', 'Keranjang')
@section('content')
<div class="cart-page">
<a class="bk-back" href="{{ route('welcome') }}">← Kembali ke katalog</a>
<div class="bk-cart-heading"><span class="bk-kicker">PILIHANMU</span><h1>Keranjang</h1></div>
<p class="muted">Setiap unit dapat memiliki jadwal dan desain yang berbeda. Menambahkan papan ke keranjang belum mencadangkan stok.</p>
@if(count($cart))<div class="bk-cart-count"><strong>{{ count($cart) }} unit</strong><span>di keranjang</span><a href="#cart-next">Ke langkah berikutnya ↓</a></div>@endif
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
                @if(!empty($item['starts_on']) && !empty($item['ends_on']))<p class="muted">Tanggal pilihan: {{ \Carbon\Carbon::parse($item['starts_on'])->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($item['ends_on'])->format('d/m/Y') }}; dapat diubah saat checkout.</p>@endif
                <a href="{{ route('catalog.show', array_merge(['product' => $product->id], !empty($item['starts_on']) && !empty($item['ends_on']) ? ['starts_on' => $item['starts_on'], 'ends_on' => $item['ends_on']] : [])) }}">Lihat detail papan</a>
            @else
                <h2>Papan tidak tersedia</h2><p class="muted">Papan ini tidak lagi tersedia. Hapus unit ini sebelum melanjutkan.</p>
            @endif
            <div class="cart-unit-actions">
                <form method="post" action="{{ route('cart.destroy', $id) }}">@csrf @method('DELETE')<button class="btn secondary" aria-label="Kurangi atau hapus unit {{ $loop->iteration }}{{ $available ? ' '.$product->name : ' yang tidak tersedia' }}">− Hapus unit</button></form>
                @if($available)<form method="post" action="{{ route('cart.duplicate', $id) }}">@csrf<button class="btn secondary" aria-label="Tambah satu unit {{ $product->name }} dengan tanggal pilihan yang sama">+ Tambah unit</button></form>@endif
            </div>
        </div>
    </article>
@empty
    <div class="card body"><h2>Keranjangmu masih kosong</h2><p class="muted">Pilih papan dari katalog untuk memulai pesanan.</p><a class="btn" href="{{ route('welcome') }}">Jelajahi katalog</a></div>
@endforelse
</div>
@if(count($cart))
    <div class="notice" id="cart-next">Harga dasar belum termasuk durasi dan ongkos. Stok baru dicadangkan setelah admin mengonfirmasi.</div>
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
