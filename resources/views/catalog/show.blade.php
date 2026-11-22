@extends('catalog.layout')
@section('title', $product->name)
@section('content')
<p><a href="{{ route('welcome') }}">← Kembali ke katalog</a></p><div class="details"><div class="card">@if($product->image_path)<img class="photo" src="{{ asset('storage/'.$product->image_path) }}" alt="Contoh {{ $product->name }}">@else<div class="photo placeholder" aria-hidden="true">✿</div>@endif</div><div><p class="muted">{{ $product->category->name }}</p><h1>{{ $product->name }}</h1><p class="price">Rp {{ number_format($product->base_price, 0, ',', '.') }} / {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</p>@if($product->size)<p>Ukuran: {{ $product->size }}</p>@endif<p class="muted">{{ $product->description }}</p><div class="notice">Foto hanya contoh. Desain, nama, dan ucapan disesuaikan dengan permintaanmu. Pemesanan online sedang disiapkan.</div><a class="btn secondary" href="{{ route('welcome') }}">Lihat papan lainnya</a></div></div>
@endsection
