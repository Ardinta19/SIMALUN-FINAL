@extends('catalog.layout')
@section('title', 'Keranjang')
@section('content')
<h1>Keranjang</h1>
<p class="muted">Setiap papan ditampilkan terpisah agar nantinya dapat memiliki jadwal dan desain berbeda. Penambahan ke keranjang belum mencadangkan stok.</p>
<div class="items">
@forelse($cart as $id => $item)
    @php $product = $products->get($item['product_id']); @endphp
    <article class="card body">
        @if($product && $product->is_active && $product->category && $product->category->is_active && $product->stock > 0)
            <h2>{{ $product->name }}</h2>
            <p class="muted">{{ $product->category->name }} · {{ $product->size ?: 'Ukuran sesuai katalog' }}</p>
            <p class="price">Harga dasar Rp {{ number_format($product->base_price, 0, ',', '.') }} / {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</p>
        @else
            <h2>Papan tidak tersedia</h2><p class="muted">Produk ini tidak lagi tersedia. Silakan hapus dari keranjang.</p>
        @endif
        <form method="post" action="{{ route('cart.destroy', $id) }}">@csrf @method('DELETE')<button class="btn secondary">Hapus unit</button></form>
    </article>
@empty
    <div class="card body"><p>Keranjangmu masih kosong.</p><a class="btn" href="{{ route('welcome') }}">Jelajahi katalog</a></div>
@endforelse
</div>
@if(count($cart))<div class="notice">Harga dasar belum termasuk durasi maupun ongkos. Stok baru dicadangkan setelah admin mengonfirmasi.</div><a class="btn" href="{{ route('rental.checkout') }}">Lanjut ke checkout</a>@endif
@endsection
