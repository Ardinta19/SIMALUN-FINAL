@extends('catalog.layout')
@section('title', 'Katalog papan bunga')
@section('hero')<div class="intro"><h1>Rayakan momen, sampaikan cerita.</h1><p>Temukan papan bunga untuk orang tersayang. Bentuk papan dipilih dari katalog, desain dan ucapan disesuaikan dengan permintaanmu.</p></div>@endsection
@section('content')
<form class="card body stack" method="get" action="{{ route('welcome') }}">
    <h2>Cari papan untuk tanggalmu</h2>
    <div class="row"><label>Cari papan<input type="search" name="q" maxlength="160" value="{{ request('q') }}" placeholder="Nama papan bunga"></label>
    <label>Kategori<select name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label></div>
    <div class="row"><label>Tanggal acara dan penerimaan<input type="date" name="starts_on" value="{{ request('starts_on') }}" min="{{ now('Asia/Jakarta')->toDateString() }}"></label>
    <label>Tanggal pengembalian<input type="date" name="ends_on" value="{{ request('ends_on') }}" min="{{ now('Asia/Jakarta')->toDateString() }}"></label></div>
    <p class="muted">Tanggal bersifat opsional. Isi keduanya untuk menyaring papan yang diperkirakan tersedia selama seluruh periode. Untuk sewa satu hari, pilih tanggal yang sama.</p>
    <p><button class="btn" type="submit">Cari papan</button> <a href="{{ route('welcome') }}">Hapus filter</a></p>
</form>
<h2>Jelajahi katalog</h2>
@if($dates)<p class="notice" role="status">Menampilkan papan yang diperkirakan tersedia pada {{ \Carbon\Carbon::parse($dates['starts_on'])->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($dates['ends_on'])->format('d/m/Y') }}. Stok belum dicadangkan dan dapat berubah sampai admin mengonfirmasi.</p>@else<p class="muted">Foto merupakan contoh; desain akhir mengikuti permintaan pelanggan. Pilih tanggal di atas untuk memeriksa ketersediaan.</p>@endif
<div class="grid">
@forelse($products as $product)
<article class="card">
    @if($product->image_path)<img class="photo" src="{{ asset('storage/'.$product->image_path) }}" alt="Contoh {{ $product->name }}">@else<div class="photo placeholder" aria-hidden="true">✿</div>@endif
    <div class="body"><p class="muted">{{ $product->category->name }}</p><h3>{{ $product->name }}</h3>
    <p class="price">Rp {{ number_format($product->base_price, 0, ',', '.') }} <span class="muted">/ {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</span></p>
    @if($dates)<p class="muted">Perkiraan {{ $product->available_for_period }} unit tersedia</p>@elseif($product->stock < 1)<p class="muted">Belum tersedia</p>@endif
    <a class="btn secondary" href="{{ route('catalog.show', array_merge(['product' => $product->id], $dates ?? [])) }}">Lihat detail</a></div>
</article>
@empty<div class="card body"><p>Tidak ada papan yang cocok atau tersedia untuk pilihan ini.</p><a href="{{ route('welcome') }}">Lihat semua papan</a></div>@endforelse
</div><div class="pagination">{{ $products->links() }}</div>
@endsection
