@extends('catalog.layout')
@section('title', 'Keranjang')
@section('content')
<div class="cart-page">
<a data-smart-back class="bk-back" href="{{ route('welcome') }}">← Kembali ke katalog</a>
<div class="bk-cart-heading"><span class="bk-kicker">Pilihanmu</span><h1>Keranjang</h1></div>
<p class="muted">Atur jumlah tiap papan dengan tombol <strong>−</strong> dan <strong>+</strong>. Unit yang tanggalnya berbeda tampil sebagai baris tersendiri karena tiap unit punya jadwal dan desain sendiri. Menambah papan belum menahan stok.</p>
@if(count($cart))<div class="bk-cart-count"><strong>{{ count($cart) }} unit</strong><span>di keranjang</span><a href="#cart-next">Ke langkah berikutnya ↓</a></div>@endif
<div class="items">
@forelse($groups as $group)
    @php
        $product = $products->get($group['product_id']);
        $qty = count($group['ids']);
        $first = $group['ids'][0];
        $last = $group['ids'][$qty - 1];
        $hasDates = ! empty($group['starts_on']) && ! empty($group['ends_on']);
        $datesExpired = $hasDates && $group['starts_on'] < now('Asia/Jakarta')->toDateString();
        $available = $product && $product->is_active && $product->category && $product->category->is_active && $product->stock > 0;
    @endphp
    <article class="card body cart-item" id="cart-unit-{{ $group['product_id'] }}">
        @if($available && $product->image_path)
            <img class="cart-image" src="{{ asset('storage/'.$product->image_path) }}" alt="Contoh {{ $product->name }}" loading="lazy" decoding="async">
        @else
            <div class="cart-image cart-placeholder" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></div>
        @endif
        <div>
            @if($available)
                <h2>{{ $product->name }}</h2>
                <p class="cart-meta">{{ $product->category->name }} · {{ $product->size ?: 'Ukuran sesuai katalog' }} · Rp {{ number_format($product->base_price, 0, ',', '.') }} / {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</p>

                <div class="cart-qty" role="group" aria-label="Jumlah unit {{ $product->name }}">
                    <form method="post" action="{{ route('cart.decrement', $last) }}">@csrf
                        <button type="submit" class="bk-qty-btn" aria-label="Kurangi satu unit {{ $product->name }}">&minus;</button>
                    </form>
                    <span class="bk-qty-num" aria-live="polite">{{ $qty }}</span>
                    <span class="bk-qty-label">unit</span>
                    <form method="post" action="{{ route('cart.duplicate', $first) }}">@csrf
                        <button type="submit" class="bk-qty-btn" aria-label="Tambah satu unit {{ $product->name }}" @if($datesExpired) disabled @endif>+</button>
                    </form>
                </div>

                <p class="cart-subtotal">Subtotal harga dasar Rp {{ number_format($product->base_price * $qty, 0, ',', '.') }} · belum termasuk durasi dan ongkos</p>

                @if($hasDates && ! $datesExpired)
                    <p class="cart-meta">Tanggal pilihan: {{ \Carbon\Carbon::parse($group['starts_on'])->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($group['ends_on'])->format('d/m/Y') }} (bisa diubah saat checkout)</p>
                @elseif($datesExpired)
                    <p class="notice">Tanggal pilihan sudah lewat. Ubah tanggal unit ini saat checkout, atau buat unit baru dengan tanggal lain dari halaman papan.</p>
                @else
                    <p class="cart-meta">Tanggal belum dipilih; tentukan tanggal acara dan pengembalian saat checkout.</p>
                @endif

                <div class="cart-unit-actions">
                    <a href="{{ route('catalog.show', array_merge(['product' => $product->id], $hasDates && ! $datesExpired ? ['starts_on' => $group['starts_on'], 'ends_on' => $group['ends_on']] : [])) }}">Lihat detail papan</a>
                    <form method="post" action="{{ route('cart.clear', $first) }}">@csrf
                        <button type="submit" class="bk-del" aria-label="Hapus semua unit {{ $product->name }} dari keranjang"><span class="bk-del__icon" aria-hidden="true"><svg viewBox="0 0 16 16" fill="currentColor"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"></path></svg></span><span class="bk-del__text">Hapus</span></button>
                    </form>
                </div>
            @else
                <h2>Papan tidak tersedia</h2>
                <p class="cart-meta">Papan ini sudah tidak dijual. Hapus {{ $qty }} unit ini sebelum melanjutkan.</p>
                <div class="cart-unit-actions">
                    <form method="post" action="{{ route('cart.clear', $first) }}">@csrf
                        <button type="submit" class="bk-del" aria-label="Hapus semua unit papan yang tidak tersedia dari keranjang"><span class="bk-del__icon" aria-hidden="true"><svg viewBox="0 0 16 16" fill="currentColor"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"></path></svg></span><span class="bk-del__text">Hapus</span></button>
                    </form>
                </div>
            @endif
        </div>
    </article>
@empty
    <div class="card body"><h2>Keranjangmu masih kosong</h2><p class="muted">Pilih papan dari katalog untuk memulai pesanan.</p><a class="btn" href="{{ route('welcome') }}">Jelajahi katalog</a></div>
@endforelse
</div>
@if(count($cart))
    <div class="notice" id="cart-next">Harga dasar belum termasuk durasi dan ongkos. Tanggal ditahan setelah pesanan dikirim, bukan saat masuk keranjang.</div>
    <div class="cart-actions">
    @if($unavailableCount)
        <p role="status">Hapus {{ $unavailableCount }} unit yang tidak tersedia sebelum melanjutkan.</p>
    @elseif(auth()->check() && auth()->user()->role !== 'customer')
        <p>Checkout hanya tersedia untuk akun pelanggan. Masuk sebagai pelanggan untuk menguji pemesanan.</p>
    @else
        <a class="btn" href="{{ route('rental.checkout') }}">Lanjut ke checkout</a>
    @guest<p class="muted">Kamu akan diminta masuk atau daftar; keranjang ini tetap tersimpan di peramban yang sama.</p>@endguest
    @endif
    <a class="btn secondary" href="{{ route('welcome') }}">Tambah papan lain</a>
    </div>
@endif
</div>
@endsection
