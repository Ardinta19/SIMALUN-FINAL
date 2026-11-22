@extends('catalog.layout')
@section('title', 'Katalog papan bunga')
@section('hero')
<div class="intro bk-atelier-hero">
    <div class="bk-atelier-content"><span class="bk-atelier-label">PAPAN BUNGA · BERKAH KREATIF</span><h1>Setiap momen punya cerita.</h1><p>Temukan papan bunga untuk menyampaikan ucapanmu. Pilih bentuknya, lalu ceritakan desain yang kamu inginkan.</p><a href="#koleksi" class="bk-hero-link">Jelajahi koleksi <span aria-hidden="true">↗</span></a><span class="bk-hero-note">Foto dekoratif ilustrasi suasana</span></div>
</div>
@endsection
@section('content')
<div class="bk-how" aria-label="Cara memesan"><div><span>01</span><p><strong>Pilih papan</strong><small>Temukan bentuk yang cocok</small></p></div><div><span>02</span><p><strong>Tulis ceritamu</strong><small>Atur tanggal dan ucapan</small></p></div><div><span>03</span><p><strong>Pantau pesanan</strong><small>Tunggu konfirmasi admin</small></p></div></div>
<div id="koleksi" class="bk-filter-bar"><div><span class="bk-kicker">Temukan yang tepat</span><h2>Papan untuk momenmu</h2><p class="muted">Cari berdasarkan nama, kategori, atau tanggal.</p></div><button class="btn secondary" type="button" data-open-dialog="bk-filter" hidden>⌕ &nbsp; Cari &amp; filter</button></div>
<dialog class="bk-dialog bk-filter-sheet" id="bk-filter" aria-labelledby="bk-filter-title"><div class="bk-dialog-head"><div><span class="bk-eyebrow">PILIHAN KATALOG</span><h2 id="bk-filter-title">Cari papan</h2></div><button type="button" data-close-dialog aria-label="Tutup pencarian">×</button></div><div data-filter-slot></div></dialog>
<form class="card body stack bk-filter-form" data-filter-form method="get" action="{{ route('welcome') }}">
    <h2>Cari papan untuk tanggalmu</h2>
    <div class="row"><label>Cari papan<input type="search" name="q" maxlength="160" value="{{ request('q') }}" placeholder="Nama papan bunga"></label>
    <label>Kategori<select name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label></div>
    <div class="row"><label>Tanggal acara dan penerimaan<input type="date" name="starts_on" value="{{ request('starts_on') }}" min="{{ now('Asia/Jakarta')->toDateString() }}"></label>
    <label>Tanggal pengembalian<input type="date" name="ends_on" value="{{ request('ends_on') }}" min="{{ now('Asia/Jakarta')->toDateString() }}"></label></div>
    <p class="muted">Tanggal bersifat opsional. Isi keduanya untuk menyaring papan yang diperkirakan tersedia selama seluruh periode. Untuk sewa satu hari, pilih tanggal yang sama.</p>
    <p><button class="btn" type="submit">Cari papan</button> <a href="{{ route('welcome') }}">Hapus filter</a></p>
</form>
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
    @if($product->image_path)<img loading="lazy" decoding="async" class="photo" src="{{ asset('storage/'.$product->image_path) }}" alt="Contoh {{ $product->name }}">@else<div class="photo placeholder" aria-hidden="true">✿</div>@endif
    <div class="body"><p class="muted">{{ $product->category->name }}</p><h3>{{ $product->name }}</h3>
    <p class="price">Rp {{ number_format($product->base_price, 0, ',', '.') }} <span class="muted">/ {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</span></p>
    @if($dates)<p class="muted">Perkiraan {{ $product->available_for_period }} unit tersedia</p>@elseif($product->stock < 1)<p class="muted">Belum tersedia</p>@endif
    <a class="btn secondary" href="{{ route('catalog.show', array_merge(['product' => $product->id], $dates ?? [])) }}">Lihat detail</a></div>
</article>
@empty<div class="card body"><p>Tidak ada papan yang cocok atau tersedia untuk pilihan ini.</p><a href="{{ route('welcome') }}">Lihat semua papan</a></div>@endforelse
</div><div class="pagination">{{ $products->links() }}</div>
@endsection
