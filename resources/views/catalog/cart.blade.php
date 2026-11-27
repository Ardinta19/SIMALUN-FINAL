@extends('catalog.layout')
@section('title', 'Keranjang')
@section('content')
<div class="cart-page">
<a data-smart-back class="bk-back" href="{{ route('welcome') }}">← Kembali ke katalog</a>
<div class="bk-cart-heading" data-bk-reveal="hero"><span class="bk-kicker">Pilihanmu</span><h1>Keranjang</h1></div>
<p class="muted">Atur jumlah dengan <strong>−</strong> dan <strong>+</strong>; angka berubah langsung tanpa memuat ulang. Warna atau tanggal berbeda menjadi baris tersendiri.</p>
@if(count($cart))<div class="bk-cart-count"><strong data-cart-total-count aria-live="polite" aria-atomic="true">{{ count($cart) }} unit</strong><span>di keranjang</span><a href="#cart-next">Ke langkah berikutnya ↓</a></div>@endif
<div class="items" data-bk-reveal-group="cart-items" data-bk-reveal-stagger="0.05">
@forelse($groups as $group)
    @php
        $product = $products->get($group['product_id']);
        $variant = $variants[$group['key']] ?? null;
        $qty = count($group['ids']);
        $first = $group['ids'][0];
        $last = $group['ids'][$qty - 1];
        $hasDates = ! empty($group['starts_on']) && ! empty($group['ends_on']);
        $datesExpired = $hasDates && $group['starts_on'] < now('Asia/Jakarta')->toDateString();
        $available = $product && $product->is_active && $product->category && $product->category->is_active
            && ($variant ? $variant->stock > 0 : $product->stock > 0);
        // Varian yang dipilih menentukan foto contoh yang tampil.
        $imagePath = $variant?->image_path ?: $product?->image_path;
        // Kelompok baru bisa ditambah bila stok varian (atau papan) masih ada
        // untuk satu unit lagi, meski tanggalnya belum dipilih.
        $variantFull = $variant && $qty >= $variant->stock;
    @endphp
    {{-- Satu id per kelompok, bukan per papan: papan yang sama bisa muncul
         beberapa kali dengan warna atau tanggal berbeda. --}}
    <article class="card body cart-item" id="cart-group-{{ substr(md5($group['key']), 0, 8) }}" data-cart-group="{{ $group['key'] }}">
        @if($available && $imagePath)
            <img class="cart-image" src="{{ asset('storage/'.$imagePath) }}" alt="Contoh {{ $product->name }}{{ $variant ? ' warna '.$variant->name : '' }}" loading="lazy" decoding="async">
        @else
            <div class="cart-image cart-placeholder" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></div>
        @endif
        <div>
            @if($available)
                <h2>{{ $product->name }}</h2>
                @if($variant)
                    <p class="cart-variant"><span class="bk-swatch bk-swatch--sm" style="--bk-swatch:{{ $variant->color }}" aria-hidden="true"></span>Warna <strong>{{ $variant->name }}</strong></p>
                @endif
                <p class="cart-meta">{{ $product->category->name }} · {{ $product->size ?: 'Ukuran sesuai katalog' }} · Rp {{ number_format($product->base_price, 0, ',', '.') }} / {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</p>

                <div class="cart-qty" role="group" aria-label="Jumlah unit {{ $product->name }}">
                    <form method="post" action="{{ route('cart.decrement', $last) }}" data-cart-live>@csrf
                        <button type="submit" class="bk-qty-btn" aria-label="Kurangi satu unit {{ $product->name }}">&minus;</button>
                    </form>
                    <span class="bk-qty-num" aria-live="polite" data-cart-count>{{ $qty }}</span>
                    <span class="bk-qty-label">unit</span>
                    @if($datesExpired)
                        {{-- Tanggal jadwal ini sudah lewat: menambah unit dengan tanggal yang sama
                             tidak mungkin diproses, jadi aksinya tidak dirender sama sekali
                             (tombol tetap tampil agar bentuk stepper tidak berubah). --}}
                        <button type="button" class="bk-qty-btn" aria-label="Tambah satu unit {{ $product->name }}, tidak tersedia karena tanggal sudah lewat" disabled>+</button>
                    @elseif($variantFull)
                        <button type="button" class="bk-qty-btn" aria-label="Stok warna {{ $variant->name }} sudah dipakai semua" disabled>+</button>
                    @else
                        <form method="post" action="{{ route('cart.duplicate', $first) }}" data-cart-live>@csrf
                            <button type="submit" class="bk-qty-btn" aria-label="Tambah satu unit {{ $product->name }}">+</button>
                        </form>
                    @endif
                </div>

                <p class="cart-subtotal">Subtotal harga dasar <strong data-cart-subtotal>Rp {{ number_format($product->base_price * $qty, 0, ',', '.') }}</strong> <span class="muted">· belum termasuk durasi dan ongkos</span></p>

                @if($hasDates && ! $datesExpired)
                    <p class="cart-meta">Tanggal pilihan: {{ \Carbon\Carbon::parse($group['starts_on'])->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($group['ends_on'])->format('d/m/Y') }} (bisa diubah saat checkout)</p>
                @elseif($datesExpired)
                    <p class="notice">Tanggal pilihan sudah lewat. Ubah tanggal unit ini saat checkout, atau buat unit baru dengan tanggal lain dari halaman papan.</p>
                @else
                    <p class="cart-meta">Tanggal belum dipilih; tentukan tanggal acara dan pengembalian saat checkout.</p>
                @endif

                <div class="cart-unit-actions">
                    <a href="{{ route('catalog.show', array_merge(['product' => $product->id], $hasDates && ! $datesExpired ? ['starts_on' => $group['starts_on'], 'ends_on' => $group['ends_on']] : [])) }}">Lihat detail papan</a>
                    <form method="post" action="{{ route('cart.clear', $first) }}" data-cart-live>@csrf
                        <button type="submit" class="bk-del" aria-label="Hapus semua unit {{ $product->name }} dari keranjang"><span class="bk-del__icon" aria-hidden="true"><svg viewBox="0 0 16 16" fill="currentColor"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"></path></svg></span><span class="bk-del__text">Hapus</span></button>
                    </form>
                </div>
            @else
                <h2>{{ $variant ? 'Warna '.$variant->name.' tidak tersedia' : 'Papan tidak tersedia' }}</h2>
                <p class="cart-meta">{{ $variant && $product && $product->is_active ? 'Stok warna ini sudah habis. Pilih warna lain dari halaman papan, atau hapus unitnya.' : 'Papan ini sudah tidak dijual. Hapus '.$qty.' unit ini sebelum melanjutkan.' }}</p>
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
    {{-- Total seluruh keranjang. Angkanya diperbarui langsung oleh
         public/js/berkah-cart-live.js setiap kali jumlah unit berubah.
         Dulu ada dua blok terpisah (total + catatan `notice`) yang mengulang
         kalimat yang sama; sekarang satu kartu memuat angka dan satu catatan,
         dan `id="cart-next"` tetap di sini agar tautan "Ke langkah berikutnya"
         tidak kehilangan tujuannya. --}}
    <div class="cart-total" id="cart-next">
        <span>Total harga dasar</span>
        <strong data-cart-total aria-live="polite" aria-atomic="true">Rp {{ number_format($cartTotal, 0, ',', '.') }}</strong>
        <small>Tanggal ditahan setelah pesanan dikirim, bukan saat masuk keranjang. Durasi, item tambahan, dan ongkos dihitung di checkout.</small>
    </div>
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
    @php
        // Jalur cepat untuk orang yang lebih nyaman chat: rincian keranjang
        // sudah terisi di pesan, jadi admin tidak perlu menanyakan ulang
        // papan, jumlah, dan tanggal. Tautan biasa, tanpa API berbayar.
        $cartLines = collect($groups)->map(function ($group) use ($products, $variants) {
            $product = $products->get($group['product_id']);
            if (! $product) return null;
            $variant = $variants[$group['key']] ?? null;
            $line = count($group['ids']).'× '.$product->name.($variant ? ' ('.$variant->name.')' : '');
            if (! empty($group['starts_on']) && ! empty($group['ends_on'])) {
                $line .= ' — '.\Carbon\Carbon::parse($group['starts_on'])->format('d/m/Y').' s.d. '.\Carbon\Carbon::parse($group['ends_on'])->format('d/m/Y');
            }
            return $line;
        })->filter()->values();
        $cartWa = $cartLines->isEmpty() ? null : \App\Support\WhatsApp::link(
            \App\Support\Laundry::whatsapp(),
            "Halo, saya mau tanya ketersediaan untuk keranjang ini:\n- ".$cartLines->implode("\n- ")."\nApakah bisa dipesan?"
        );
    @endphp
    @if($cartWa)
        <a class="btn secondary" href="{{ $cartWa }}" target="_blank" rel="noopener noreferrer">Tanya keranjang ini lewat WhatsApp</a>
    @endif
    </div>
@endif
</div>
@endsection
