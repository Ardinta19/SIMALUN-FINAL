@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Katalog papan')
@section('crumbs')<strong>Katalog papan</strong>@endsection
@section('content')
@unless(file_exists(public_path('storage')))
    {{-- Tautan folder unggahan belum dibuat: semua foto produk tampil rusak
         tanpa pesan apa pun. Beri tahu admin langkah satu baris ini. --}}
    <div class="notice" role="status">Foto unggahan belum tertaut ke folder publik. Jalankan <code>php artisan storage:link</code> sekali di server, lalu muat ulang halaman ini.</div>
@endunless
<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Katalog &amp; penawaran</span>
        <h1>Kelola katalog</h1>
        <p class="adm-page-sub">Tambahkan kategori lalu papan; harga di sini adalah data dasar, bukan ketersediaan tanggal.</p>
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
        <p class="adm-help adm-mt-3">Kategori nonaktif dan seluruh papannya tersembunyi dari katalog; riwayat pesanan tetap tersimpan.</p>

        @foreach($categories as $category)
            <div class="adm-divider-top">
                <div class="adm-row adm-row--between">
                    <strong>{{ $category->name }}</strong>
                    <span class="adm-badge {{ $category->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">Status: {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <details class="adm-subsection">
                    <summary class="bk-disclosure">Ubah kategori</summary>
                <form class="adm-stack adm-mt-2" method="post" action="{{ route('admin.catalog.categories.update', $category) }}">
                    @csrf
                    @method('PUT')
                    <div class="adm-field">
                        <label for="category-name-{{ $category->id }}">Nama kategori</label>
                        <input id="category-name-{{ $category->id }}" name="name" value="{{ $category->name }}" maxlength="120" required>
                    </div>
                    <div><button class="btn secondary adm-btn--sm" type="submit">Simpan nama</button></div>
                </form>
                <form class="adm-mt-2" method="post" action="{{ route('admin.catalog.categories.toggle', $category) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn secondary adm-btn--sm">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }} kategori</button>
                </form>
                </details>
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
            <p class="adm-help adm-mt-2">Buat kategori terlebih dahulu.</p>
        @endif
    </section>
</div>

<div class="adm-row adm-row--between adm-head-row" data-adm-reveal="list-title">
    <h2>Papan di katalog</h2>
    <span class="adm-chip">{{ $products->total() }} papan</span>
</div>

@forelse($products as $product)
    <details class="adm-card" data-adm-reveal="produk">
        <summary class="adm-summary-inline">
            <strong>{{ $product->name }}</strong>
            <span class="adm-badge {{ $product->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            <span class="adm-chip adm-push">{{ $product->category->name }} · {{ $product->stock }} unit @if($product->variants->isNotEmpty()) · {{ $product->variants->count() }} warna @endif</span>
        </summary>

        <div class="adm-mt-4">
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
                <p class="adm-help adm-block-gap">Stok papan dipakai selama papan belum punya warna. Bila sudah ada warna di bawah, stok yang berlaku adalah stok tiap warna.</p>
                <div><button class="btn" type="submit">Simpan perubahan</button></div>
            </form>

            <form class="adm-mt-2" method="post" action="{{ route('admin.catalog.toggle', $product) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn secondary adm-btn--sm">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>

            <h3 class="adm-subhead">Warna &amp; foto (varian)</h3>
            <p class="adm-help">Tiap warna punya stok sendiri dan pratinjau foto ikut berganti. Harga sama untuk semua warna; selama papan belum punya warna, stok papan yang dipakai.</p>

            @forelse($product->variants as $variant)
                <div class="adm-note adm-mt-3">
                    <div class="adm-row adm-row--between adm-row--tight adm-row--wrap">
                        <strong class="adm-row adm-row--tight">
                            <span class="bk-swatch" style="--bk-swatch:{{ $variant->color }}" aria-hidden="true"></span>
                            {{ $variant->name }}
                        </strong>
                        <span class="adm-chip">{{ $variant->stock }} unit</span>
                        <span class="adm-badge {{ $variant->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">{{ $variant->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    <details class="adm-subsection">
                        <summary class="bk-disclosure">Ubah warna ini</summary>
                    <form class="adm-stack adm-mt-3" method="post" action="{{ route('admin.catalog.variants.update', $variant) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="adm-field-grid">
                            <div class="adm-field">
                                <label for="variant-name-{{ $variant->id }}">Nama warna</label>
                                <input id="variant-name-{{ $variant->id }}" name="name" maxlength="60" value="{{ $variant->name }}" required>
                            </div>
                            <div class="adm-field">
                                <label for="variant-color-{{ $variant->id }}">Warna</label>
                                <input id="variant-color-{{ $variant->id }}" type="color" name="color" value="{{ $variant->color }}" required>
                            </div>
                            <div class="adm-field">
                                <label for="variant-stock-{{ $variant->id }}">Stok (unit)</label>
                                <input id="variant-stock-{{ $variant->id }}" type="number" inputmode="numeric" name="stock" min="0" max="10000" value="{{ $variant->stock }}" required>
                            </div>
                            <div class="adm-field">
                                <label for="variant-order-{{ $variant->id }}">Urutan</label>
                                <input id="variant-order-{{ $variant->id }}" type="number" inputmode="numeric" name="sort_order" min="0" max="999" value="{{ $variant->sort_order }}">
                            </div>
                            <div class="adm-field">
                                <label for="variant-image-{{ $variant->id }}">Foto contoh (opsional)</label>
                                <input id="variant-image-{{ $variant->id }}" type="file" name="image" accept="image/png,image/jpeg,image/webp">
                            </div>
                        </div>
                        @if($variant->image_path)
                            <div class="admin-photo adm-mt-2"><img src="{{ asset('storage/'.$variant->image_path) }}" alt="Foto warna {{ $variant->name }}" loading="lazy" decoding="async"></div>
                        @endif
                        <div><button class="btn secondary adm-btn--sm" type="submit">Simpan warna</button></div>
                    </form>
                    <div class="adm-row adm-row--tight adm-mt-6 adm-row--wrap">
                        <form method="post" action="{{ route('admin.catalog.variants.toggle', $variant) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn secondary adm-btn--sm">{{ $variant->is_active ? 'Nonaktifkan warna' : 'Aktifkan warna' }}</button>
                        </form>
                        <form method="post" action="{{ route('admin.catalog.variants.destroy', $variant) }}" data-variant-delete>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn secondary adm-btn--sm">Hapus warna</button>
                        </form>
                    </div>
                    </details>
                </div>
            @empty
                <p class="adm-help">Belum ada warna untuk papan ini.</p>
            @endforelse

            <details class="adm-subsection">
                <summary class="bk-disclosure">Tambah warna baru</summary>
            <form class="adm-stack adm-mt-4" method="post" action="{{ route('admin.catalog.variants.store', $product) }}" enctype="multipart/form-data">
                @csrf
                <div class="adm-field-grid">
                    <div class="adm-field">
                        <label for="variant-new-name-{{ $product->id }}">Nama warna baru</label>
                        <input id="variant-new-name-{{ $product->id }}" name="name" maxlength="60" placeholder="Contoh: Merah Marun" required>
                    </div>
                    <div class="adm-field">
                        <label for="variant-new-color-{{ $product->id }}">Warna</label>
                        <input id="variant-new-color-{{ $product->id }}" type="color" name="color" value="#85092d" required>
                    </div>
                    <div class="adm-field">
                        <label for="variant-new-stock-{{ $product->id }}">Stok (unit)</label>
                        <input id="variant-new-stock-{{ $product->id }}" type="number" inputmode="numeric" name="stock" min="0" max="10000" value="1" required>
                    </div>
                    <div class="adm-field">
                        <label for="variant-new-order-{{ $product->id }}">Urutan</label>
                        <input id="variant-new-order-{{ $product->id }}" type="number" inputmode="numeric" name="sort_order" min="0" max="999" value="0">
                    </div>
                    <div class="adm-field">
                        <label for="variant-new-image-{{ $product->id }}">Foto contoh (opsional)</label>
                        <input id="variant-new-image-{{ $product->id }}" type="file" name="image" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>
                <div><button class="btn secondary adm-btn--sm" type="submit">Tambah warna</button></div>
            </form>
            </details>

            <h3 class="adm-subhead">Paket durasi</h3>
            <p class="adm-help">Paket berlaku untuk jumlah hari yang tepat, termasuk hari penerimaan dan pengembalian.</p>

            @foreach($product->packages as $package)
                <div class="adm-note adm-mt-3">
                    <div class="adm-row adm-row--between">
                        <strong>{{ $package->name }} · {{ $package->days }} hari</strong>
                        <span class="adm-badge {{ $package->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">{{ $package->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    <details class="adm-subsection">
                        <summary class="bk-disclosure">Ubah paket ini</summary>
                    <form class="adm-stack adm-mt-3" method="post" action="{{ route('admin.catalog.packages.update', $package) }}">
                        @csrf
                        @method('PUT')
                        <div class="adm-field-grid">
                            <div class="adm-field">
                                <label for="package-name-{{ $package->id }}">Nama paket</label>
                                <input id="package-name-{{ $package->id }}" name="name" maxlength="120" value="{{ $package->name }}" required>
                            </div>
                            <div class="adm-field">
                                <label for="package-days-{{ $package->id }}">Durasi (hari)</label>
                                <input id="package-days-{{ $package->id }}" type="number" inputmode="numeric" name="days" min="1" max="366" value="{{ $package->days }}" required>
                            </div>
                            <div class="adm-field">
                                <label for="package-price-{{ $package->id }}">Harga paket (Rp)</label>
                                <input id="package-price-{{ $package->id }}" type="number" inputmode="numeric" name="price" min="0" max="100000000" value="{{ $package->price }}" required>
                            </div>
                        </div>
                        <div><button class="btn secondary adm-btn--sm" type="submit">Simpan paket</button></div>
                    </form>
                    <form class="adm-mt-6" method="post" action="{{ route('admin.catalog.packages.toggle', $package) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn secondary adm-btn--sm">{{ $package->is_active ? 'Nonaktifkan paket' : 'Aktifkan paket' }}</button>
                    </form>
                    </details>
                </div>
            @endforeach

            <details class="adm-subsection">
                <summary class="bk-disclosure">Tambah paket baru</summary>
            <form class="adm-stack adm-mt-4" method="post" action="{{ route('admin.catalog.packages.store', $product) }}">
                @csrf
                <div class="adm-field-grid">
                    <div class="adm-field">
                        <label for="package-new-name-{{ $product->id }}">Nama paket baru</label>
                        <input id="package-new-name-{{ $product->id }}" placeholder="Contoh: Paket 3 hari" name="name" maxlength="120" required>
                    </div>
                    <div class="adm-field">
                        <label for="package-new-days-{{ $product->id }}">Durasi (hari)</label>
                        <input id="package-new-days-{{ $product->id }}" placeholder="Contoh: 3" type="number" inputmode="numeric" name="days" min="1" max="366" required>
                    </div>
                    <div class="adm-field">
                        <label for="package-new-price-{{ $product->id }}">Harga paket (Rp)</label>
                        <input id="package-new-price-{{ $product->id }}" placeholder="Contoh: 450000" type="number" inputmode="numeric" name="price" min="0" max="100000000" required>
                    </div>
                </div>
                <div><button class="btn secondary adm-btn--sm" type="submit">Tambah paket</button></div>
            </form>
            </details>
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

@push('scripts')
<script>
/* Hapus warna memakai modal konfirmasi bersama, bukan balon bawaan peramban. */
(function () {
    'use strict';
    document.querySelectorAll('form[data-variant-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (typeof window.showConfirmModal !== 'function') return;
            event.preventDefault();
            window.showConfirmModal({
                title: 'Hapus warna ini?',
                message: 'Warna akan hilang dari katalog. Pesanan lama tetap menyimpan nama warnanya.',
                confirmText: 'Ya, hapus',
                cancelText: 'Batal',
                type: 'danger',
                onConfirm: function () { form.submit(); }
            });
        });
    });
})();
</script>
@endpush
