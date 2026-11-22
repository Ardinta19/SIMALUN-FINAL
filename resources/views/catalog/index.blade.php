@extends('catalog.layout')
@section('title', 'Katalog papan bunga')
@section('hero')
<div class="intro bk-atelier-hero">
    <div class="bk-atelier-content"><span class="bk-atelier-label">PAPAN BUNGA · BERKAH KREATIF</span><h1>Setiap momen punya cerita.</h1><p>Temukan papan bunga untuk menyampaikan ucapanmu. Pilih bentuknya, lalu ceritakan desain yang kamu inginkan.</p><a href="#koleksi" class="bk-hero-link">Jelajahi koleksi <span aria-hidden="true">↗</span></a><span class="bk-hero-note">Foto dekoratif ilustrasi suasana</span></div>
</div>
@endsection
@section('content')
@php $filterValue = fn ($key) => is_scalar(old($key, request($key))) ? (string) old($key, request($key)) : ''; @endphp
<div class="bk-how" aria-label="Cara memesan"><div><span>01</span><p><strong>Pilih papan</strong><small>Temukan bentuk yang cocok</small></p></div><div><span>02</span><p><strong>Tulis ceritamu</strong><small>Atur tanggal dan ucapan</small></p></div><div><span>03</span><p><strong>Pantau pesanan</strong><small>Tunggu konfirmasi admin</small></p></div></div>
<div id="koleksi" class="bk-filter-bar"><div><span class="bk-kicker">Temukan yang tepat</span><h2>Papan untuk momenmu</h2><p class="muted">Cari berdasarkan nama, kategori, atau tanggal.</p></div></div>
<dialog class="bk-dialog bk-filter-sheet" id="bk-filter" aria-labelledby="bk-filter-title"><div class="bk-dialog-head"><div><span class="bk-eyebrow">PILIHAN KATALOG</span><h2 id="bk-filter-title">Cari papan</h2></div><button type="button" data-close-dialog aria-label="Tutup pencarian">×</button></div><div data-filter-slot></div></dialog>
<form id="catalog-filter" class="card body stack bk-filter-form" data-filter-form data-filter-errors="{{ $errors->hasAny(['q', 'category', 'starts_on', 'ends_on']) ? '1' : '0' }}" method="get" action="{{ route('welcome') }}">
    <h2>Cari papan untuk tanggalmu</h2>
    <div class="row"><div><label for="catalog-search">Cari papan</label><input id="catalog-search" type="search" name="q" maxlength="160" value="{{ $filterValue('q') }}" placeholder="Nama papan bunga" @error('q') aria-invalid="true" aria-describedby="catalog-search-error" @enderror>@error('q')<p class="bk-field-error" id="catalog-search-error">{{ $message }}</p>@enderror</div>
    <div><label for="catalog-category">Kategori</label><select id="catalog-category" name="category" @error('category') aria-invalid="true" aria-describedby="catalog-category-error" @enderror><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) $filterValue('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category')<p class="bk-field-error" id="catalog-category-error">{{ $message }}</p>@enderror</div></div>
    <div class="row"><div><label for="catalog-start">Tanggal acara dan penerimaan</label><input id="catalog-start" type="date" name="starts_on" value="{{ $filterValue('starts_on') }}" min="{{ now('Asia/Jakarta')->toDateString() }}" @error('starts_on') aria-invalid="true" aria-describedby="catalog-start-error" @enderror>@error('starts_on')<p class="bk-field-error" id="catalog-start-error">{{ $message }}</p>@enderror</div>
    <div><label for="catalog-end">Tanggal pengembalian</label><input id="catalog-end" type="date" name="ends_on" value="{{ $filterValue('ends_on') }}" min="{{ now('Asia/Jakarta')->toDateString() }}" @error('ends_on') aria-invalid="true" aria-describedby="catalog-end-error" @enderror>@error('ends_on')<p class="bk-field-error" id="catalog-end-error">{{ $message }}</p>@enderror</div></div>
    <p class="muted">Tanggal bersifat opsional. Isi keduanya untuk menyaring papan yang diperkirakan tersedia selama seluruh periode. Untuk sewa satu hari, pilih tanggal yang sama.</p>
    <p><button class="btn" type="submit">Cari papan</button> <a href="{{ route('welcome') }}">Hapus filter</a></p>
</form>
@if($errors->hasAny(['q', 'category', 'starts_on', 'ends_on']))<p class="notice">Pencarian belum diterapkan. Daftar di bawah masih mengikuti filter sebelumnya; perbaiki isian dan cari lagi.</p>@endif
@if(request()->filled('q') || request()->filled('category') || $dates)
<div class="bk-applied-filters" aria-label="Filter yang digunakan"><span>Filter aktif</span>
    @if(request()->filled('q'))<a href="{{ route('welcome', request()->only(['category', 'starts_on', 'ends_on'])) }}" aria-label="Hapus pencarian {{ request('q') }}">{{ request('q') }} <b aria-hidden="true">×</b></a>@endif
    @if(request()->filled('category'))<a href="{{ route('welcome', request()->only(['q', 'starts_on', 'ends_on'])) }}" aria-label="Hapus filter kategori">{{ $categories->firstWhere('id', (int) request('category'))?->name ?? 'Kategori dipilih' }} <b aria-hidden="true">×</b></a>@endif
    @if($dates)<a href="{{ route('welcome', request()->only(['q', 'category'])) }}" aria-label="Hapus filter tanggal">{{ \Carbon\Carbon::parse($dates['starts_on'])->format('d/m') }}–{{ \Carbon\Carbon::parse($dates['ends_on'])->format('d/m/Y') }} <b aria-hidden="true">×</b></a>@endif
    <a class="bk-filter-clear" href="{{ route('welcome') }}">Hapus semua</a>
</div>
@endif
@if($categories->isNotEmpty())
<nav class="bk-category-strip" aria-label="Kategori papan bunga">
    <a href="{{ route('welcome', request()->only(['q', 'starts_on', 'ends_on'])) }}" @if(!request('category')) aria-current="page" @endif>Semua</a>
    @foreach($categories as $category)<a href="{{ route('welcome', array_merge(request()->only(['q', 'starts_on', 'ends_on']), ['category' => $category->id])) }}" @if((string) request('category') === (string) $category->id) aria-current="page" @endif>{{ $category->name }}</a>@endforeach
</nav>
@endif
<div class="bk-section-heading"><div><span class="bk-kicker">KOLEKSI BERKAH KREATIF</span><h2>Jelajahi katalog</h2></div><span class="muted">{{ $products->total() }} pilihan</span></div>
@if($dates)<p class="notice" role="status">Menampilkan papan yang diperkirakan tersedia pada {{ \Carbon\Carbon::parse($dates['starts_on'])->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($dates['ends_on'])->format('d/m/Y') }}. Stok belum dicadangkan dan dapat berubah sampai admin mengonfirmasi.</p>@else<p class="muted">Foto merupakan contoh; desain akhir mengikuti permintaan pelanggan. Pilih tanggal di atas untuk memeriksa ketersediaan.</p>@endif
<div class="grid" data-catalog-products>
@forelse($products as $product)
<article class="card bk-product-card">
    <a class="bk-product-image-link" href="{{ route('catalog.show', array_merge(['product' => $product->id], $dates ?? [])) }}" aria-label="Lihat detail {{ $product->name }}">
    @if($product->image_path)<img loading="lazy" decoding="async" class="photo" src="{{ asset('storage/'.$product->image_path) }}" alt="">@else<div class="photo placeholder" aria-hidden="true">✿</div>@endif</a>
    <div class="body"><p class="muted">{{ $product->category->name }}</p><h3><a class="bk-product-name" href="{{ route('catalog.show', array_merge(['product' => $product->id], $dates ?? [])) }}">{{ $product->name }}</a></h3>
    <p class="price">Rp {{ number_format($product->base_price, 0, ',', '.') }} <span class="muted">/ {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</span></p>
    @include('catalog.product-feedback', ['product' => $product])
    @if($dates)<p class="muted">Perkiraan {{ $product->available_for_period }} unit tersedia</p>@elseif($product->stock < 1)<p class="muted">Belum tersedia</p>@endif
    @if($errors->hasAny(['starts_on', 'ends_on']))
    <p class="muted">Perbaiki tanggal dan cari kembali sebelum menambahkan papan.</p>
    @elseif($product->stock > 0)
    <form method="post" action="{{ route('cart.store', $product) }}" class="bk-product-add">@csrf<input type="hidden" name="quantity" value="1">@if($dates)<input type="hidden" name="starts_on" value="{{ $dates['starts_on'] }}"><input type="hidden" name="ends_on" value="{{ $dates['ends_on'] }}">@endif<button class="btn secondary" type="submit" aria-label="Tambah {{ $product->name }} ke keranjang">Tambah ke keranjang</button></form>
    @else<p class="bk-product-unavailable">Belum tersedia untuk dipesan</p>@endif</div>
</article>
@empty<div class="card body"><p>Tidak ada papan yang cocok atau tersedia untuk pilihan ini.</p><a href="{{ route('welcome') }}">Lihat semua papan</a></div>@endforelse
</div><div class="pagination">{{ $products->links() }}</div>
@endsection
