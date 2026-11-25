@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Katalog papan')
@section('crumbs')<strong>Katalog papan</strong>@endsection
@push('head')
<style>
    /* Bingkai pratinjau foto: geometri saja (warna mengikuti token admin). */
    .admin-photo { display: grid; place-items: center; width: min(100%, 220px); aspect-ratio: 4 / 3; margin: 0 0 14px; padding: 6px; border-radius: 14px; overflow: hidden; background: var(--adm-surface-2, #fbf3f4); border: 1px dashed var(--adm-line, #eadbdd); }
    .admin-photo img { display: block; width: 100%; height: 100%; object-fit:contain; object-position:center; }
</style>
@endpush
@section('content')
<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Katalog &amp; penawaran</span>
        <h1>Kelola katalog</h1>
        <p class="adm-page-sub">Tambahkan kategori lalu papan; harga di sini adalah data dasar, bukan ketersediaan tanggal.</p>
                <details class="adm-page-note">
                    <summary>Selengkapnya</summary>
                    <p>Tambahkan kategori dan papan. Harga dan stok di sini adalah data dasar, bukan ketersediaan pada tanggal tertentu.</p>
                </details>
    </div>
    <div class="adm-actions">
        <a class="btn secondary" href="{{ route('admin.rental.universities.index') }}">Kelola universitas dan tarif</a>
        <a class="btn secondary" href="{{ route('welcome') }}" target="_blank" rel="noopener">Lihat katalog publik</a>
    </div>
</div>

<div class="adm-grid adm-grid--split">
    <section class="adm-card" data-adm-reveal="kategori">
        <div class="adm-card__head">
            <h2>Kategori</h2>
            <span class="adm-chip">{{ $categories->count() }} kategori</span>
        </div>
        <form class="adm-stack" method="post" action="{{ route('admin.catalog.categories.store') }}">
            @csrf
            <div class="adm-field">
                <label for="category-name">Nama kategori</label>
                <input id="category-name" name="name" maxlength="120" required placeholder="Contoh: Wisuda">
            </div>
            <div><button class="btn" type="submit">Tambah kategori</button></div>
        </form>
        <p class="adm-help" style="margin-top:10px">Kategori nonaktif dan seluruh papannya tersembunyi dari katalog; riwayat pesanan tetap tersimpan.</p>

        @foreach($categories as $category)
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--adm-line)">
                <div class="adm-row" style="justify-content:space-between">
                    <strong>{{ $category->name }}</strong>
                    <span class="adm-badge {{ $category->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">Status: {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <form class="adm-stack" style="margin-top:8px" method="post" action="{{ route('admin.catalog.categories.update', $category) }}">
                    @csrf
                    @method('PUT')
                    <div class="adm-field">
                        <label for="category-name-{{ $category->id }}">Nama kategori</label>
                        <input id="category-name-{{ $category->id }}" name="name" value="{{ $category->name }}" maxlength="120" required>
                    </div>
                    <div><button class="btn secondary adm-btn--sm" type="submit">Simpan nama</button></div>
                </form>
                <form method="post" action="{{ route('admin.catalog.categories.toggle', $category) }}" style="margin-top:8px">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn secondary adm-btn--sm">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }} kategori</button>
                </form>
            </div>
        @endforeach
    </section>

    <section class="adm-card" data-adm-reveal="tambah">
        <div class="adm-card__head">
            <h2>Tambah papan</h2>
            <span class="adm-chip">Data dasar</span>
        </div>
        <form class="adm-stack" method="post" action="{{ route('admin.catalog.store') }}" enctype="multipart/form-data">
            @csrf
            @include('catalog.product-fields', ['product' => null])
            <div><button type="submit" class="btn" @disabled($categories->isEmpty())>Simpan papan</button></div>
        </form>
        @if($categories->isEmpty())
            <p class="adm-help" style="margin-top:8px">Buat kategori terlebih dahulu.</p>
        @endif
    </section>
</div>

<div class="adm-row" style="justify-content:space-between;margin:26px 0 12px" data-adm-reveal="list-title">
    <h2 style="margin:0">Papan di katalog</h2>
    <span class="adm-chip">{{ $products->total() }} papan</span>
</div>

@forelse($products as $product)
    <details class="adm-card" data-adm-reveal="produk">
        <summary style="cursor:pointer;display:flex;align-items:center;gap:10px;list-style:none">
            <strong>{{ $product->name }}</strong>
            <span class="adm-badge {{ $product->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            <span class="adm-chip" style="margin-left:auto">{{ $product->category->name }} · {{ $product->stock }} unit</span>
        </summary>

        <div style="margin-top:14px">
            <div class="admin-photo">
                @if($product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="Foto {{ $product->name }}" loading="lazy" decoding="async">
                @else
                    <span class="muted">Belum ada foto</span>
                @endif
            </div>

            <form class="adm-stack" method="post" action="{{ route('admin.catalog.update', $product) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('catalog.product-fields', ['product' => $product])
                <div><button class="btn" type="submit">Simpan perubahan</button></div>
            </form>

            <form method="post" action="{{ route('admin.catalog.toggle', $product) }}" style="margin-top:8px">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn secondary adm-btn--sm">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>

            <h3 style="margin:20px 0 4px">Paket durasi</h3>
            <p class="adm-help">Paket berlaku untuk jumlah hari yang tepat, termasuk hari penerimaan dan pengembalian.</p>

            @foreach($product->packages as $package)
                <div class="adm-note" style="margin-top:10px">
                    <div class="adm-row" style="justify-content:space-between">
                        <strong>{{ $package->name }} · {{ $package->days }} hari</strong>
                        <span class="adm-badge {{ $package->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">{{ $package->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    <form class="adm-stack" style="margin-top:10px" method="post" action="{{ route('admin.catalog.packages.update', $package) }}">
                        @csrf
                        @method('PUT')
                        <div class="adm-field-grid">
                            <div class="adm-field">
                                <label for="package-name-{{ $package->id }}">Nama paket</label>
                                <input id="package-name-{{ $package->id }}" name="name" maxlength="120" value="{{ $package->name }}" required>
                            </div>
                            <div class="adm-field">
                                <label for="package-days-{{ $package->id }}">Durasi (hari)</label>
                                <input id="package-days-{{ $package->id }}" type="number" name="days" min="1" max="366" value="{{ $package->days }}" required>
                            </div>
                            <div class="adm-field">
                                <label for="package-price-{{ $package->id }}">Harga paket (Rp)</label>
                                <input id="package-price-{{ $package->id }}" type="number" name="price" min="0" max="100000000" value="{{ $package->price }}" required>
                            </div>
                        </div>
                        <div><button class="btn secondary adm-btn--sm" type="submit">Simpan paket</button></div>
                    </form>
                    <form method="post" action="{{ route('admin.catalog.packages.toggle', $package) }}" style="margin-top:6px">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn secondary adm-btn--sm">{{ $package->is_active ? 'Nonaktifkan paket' : 'Aktifkan paket' }}</button>
                    </form>
                </div>
            @endforeach

            <form class="adm-stack" style="margin-top:14px" method="post" action="{{ route('admin.catalog.packages.store', $product) }}">
                @csrf
                <div class="adm-field-grid">
                    <div class="adm-field">
                        <label for="package-new-name-{{ $product->id }}">Nama paket baru</label>
                        <input id="package-new-name-{{ $product->id }}" placeholder="Contoh: Paket 3 hari" name="name" maxlength="120" required>
                    </div>
                    <div class="adm-field">
                        <label for="package-new-days-{{ $product->id }}">Durasi (hari)</label>
                        <input id="package-new-days-{{ $product->id }}" placeholder="Contoh: 3" type="number" name="days" min="1" max="366" required>
                    </div>
                    <div class="adm-field">
                        <label for="package-new-price-{{ $product->id }}">Harga paket (Rp)</label>
                        <input id="package-new-price-{{ $product->id }}" placeholder="Contoh: 450000" type="number" name="price" min="0" max="100000000" required>
                    </div>
                </div>
                <div><button class="btn secondary adm-btn--sm" type="submit">Tambah paket</button></div>
            </form>
        </div>
    </details>
@empty
    <div class="adm-empty" data-adm-reveal="empty">
        <span class="adm-empty__icon" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></span>
        <span class="adm-empty__title">Belum ada papan</span>
        <p>Tambahkan papan pertama lewat formulir “Tambah papan” di atas.</p>
    </div>
@endforelse

<div class="pagination">{{ $products->links() }}</div>
@endsection
