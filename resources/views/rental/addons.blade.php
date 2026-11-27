@extends('layouts.admin')
@section('back', route('admin.catalog.index'))
@section('title', 'Item tambahan')
@section('crumbs')<a href="{{ route('admin.catalog.index') }}">Katalog</a><span class="adm-crumbs__sep" aria-hidden="true">/</span><strong>Item tambahan</strong>@endsection
@section('content')
<a data-smart-back class="bk-back" href="{{ route('admin.catalog.index') }}">← Kelola katalog</a>
<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Katalog &amp; penawaran</span>
        <h1>Item tambahan</h1>
        <p class="adm-page-sub">Pelengkap yang bisa dicentang pelanggan saat checkout — misalnya lilin, kartu ucapan, atau bunga ekstra.</p>
                <details class="adm-page-note">
                    <summary>Selengkapnya</summary>
                    <p>Berlaku untuk semua papan; pelanggan memilih jumlahnya per unit di checkout.</p>
                    <ul>
                        <li>Harga item masuk ke tagihan unit, jadi ikut DP, pelunasan, dan pengingat otomatis.</li>
                        <li>Nama dan harga disalin saat checkout — mengubah harga di sini tidak menulis ulang tagihan lama.</li>
                    </ul>
                </details>
    </div>
    <div class="adm-actions">
        <a class="btn secondary" href="{{ route('admin.catalog.index') }}">Kembali ke katalog</a>
    </div>
</div>

@if($errors->any())
    <div class="adm-card" role="alert" data-adm-reveal="errors">
        <h2>Periksa isian</h2>
        <ul>
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="adm-card" data-adm-reveal="form">
    <div class="adm-card__head">
        <h2>Tambah item tambahan</h2>
        <span class="adm-chip">Berlaku untuk semua papan</span>
    </div>
    <form class="adm-stack" method="post" action="{{ route('admin.rental.addons.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="adm-field-grid">
            <div class="adm-field adm-field--wide">
                <label for="addon-name">Nama item <span class="adm-req">(wajib)</span></label>
                <input id="addon-name" name="name" maxlength="120" required value="{{ old('name') }}" placeholder="Contoh: Kartu ucapan cetak" @error('name') aria-invalid="true" @enderror>
                @error('name')<p class="adm-err" role="alert" id="addon-name-error">{{ $message }}</p>@enderror
            </div>
            <div class="adm-field">
                <label for="addon-price">Harga per item (Rp) <span class="adm-req">(wajib)</span></label>
                <input id="addon-price" type="number" inputmode="numeric" name="price" min="0" max="100000000" step="500" required value="{{ old('price') }}" placeholder="Contoh: 15000" @error('price') aria-invalid="true" @enderror>
                @error('price')<p class="adm-err" role="alert" id="addon-price-error">{{ $message }}</p>@enderror
            </div>
            <div class="adm-field">
                <label for="addon-sort">Urutan tampil</label>
                <input placeholder="Contoh: 0" id="addon-sort" type="number" inputmode="numeric" name="sort_order" min="0" max="999" value="{{ old('sort_order', 0) }}">
                <p class="muted">Angka kecil tampil lebih dulu.</p>
            </div>
        </div>

        <div class="adm-field">
            <label for="addon-description">Keterangan singkat</label>
            <input id="addon-description" name="description" maxlength="500" value="{{ old('description') }}" placeholder="Contoh: Kartu A5 dengan nama penerima.">
        </div>

        <div class="adm-field">
            <label for="addon-image">Foto item</label>
            <input id="addon-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-describedby="addon-image-hint" @error('image') aria-invalid="true" @enderror>
            <p class="muted" id="addon-image-hint">JPG, PNG, atau WebP, maksimal 5 MB. Opsional.</p>
            @error('image')<p class="adm-err" role="alert" id="addon-image-error">{{ $message }}</p>@enderror
        </div>

        <div><button class="btn" type="submit">Tambah item</button></div>
    </form>
</section>

<div class="adm-row adm-row--between adm-head-row" data-adm-reveal="list-title">
    <h2>Daftar item tambahan</h2>
    <span class="adm-chip">{{ $addons->count() }} item</span>
</div>

@forelse($addons as $addon)
    @php $addonUsed = in_array((int) $addon->id, array_map('intval', $usedIds), true); @endphp
    <article class="adm-card adm-card--hover" data-adm-reveal="addons">
        <div class="adm-row adm-row--between adm-row--top">
            <div class="adm-row adm-row--gap3 adm-row--top">
                @if($addon->image_path)
                    <img class="adm-thumb" src="{{ asset('storage/'.$addon->image_path) }}" alt="Foto {{ $addon->name }}" width="72" height="72" loading="lazy" decoding="async"
                        >
                @endif
                <div>
                    <h3>{{ $addon->name }}</h3>
                    <div class="adm-row adm-row--tight adm-mt-6 adm-row--wrap">
                        <span class="adm-badge {{ $addon->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">
                            {{ $addon->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <span class="adm-chip">Rp {{ number_format($addon->price, 0, ',', '.') }}</span>
                        <span class="adm-chip">Urutan {{ $addon->sort_order }}</span>
                        @if($addonUsed)<span class="adm-chip">Dipakai pesanan</span>@endif
                    </div>
                    @if($addon->description)
                        <p class="muted adm-block-gap">{{ $addon->description }}</p>
                    @endif
                </div>
            </div>
            <div class="adm-row adm-row--tight adm-row--wrap">
                <form method="post" action="{{ route('admin.rental.addons.toggle', $addon) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn secondary adm-btn--sm">{{ $addon->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>
                @unless($addonUsed)
                    <form method="post" action="{{ route('admin.rental.addons.destroy', $addon) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn secondary adm-btn--sm">Hapus</button>
                    </form>
                @endunless
            </div>
        </div>

        <details class="adm-mt-3">
            <summary class="bk-disclosure">Ubah item</summary>
            <form class="adm-stack adm-mt-3" method="post" action="{{ route('admin.rental.addons.update', $addon) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="adm-field-grid">
                    <div class="adm-field adm-field--wide">
                        <label for="addon-name-{{ $addon->id }}">Nama</label>
                        <input id="addon-name-{{ $addon->id }}" name="name" value="{{ $addon->name }}" maxlength="120" required @error('name') aria-invalid="true" @enderror>
                    </div>
                    <div class="adm-field">
                        <label for="addon-price-{{ $addon->id }}">Harga (Rp)</label>
                        <input id="addon-price-{{ $addon->id }}" type="number" inputmode="numeric" name="price" min="0" max="100000000" step="500" value="{{ $addon->price }}" required @error('price') aria-invalid="true" @enderror>
                    </div>
                    <div class="adm-field">
                        <label for="addon-sort-{{ $addon->id }}">Urutan tampil</label>
                        <input id="addon-sort-{{ $addon->id }}" type="number" inputmode="numeric" name="sort_order" min="0" max="999" value="{{ $addon->sort_order }}">
                    </div>
                </div>
                <div class="adm-field">
                    <label for="addon-description-{{ $addon->id }}">Keterangan singkat</label>
                    <input id="addon-description-{{ $addon->id }}" name="description" maxlength="500" value="{{ $addon->description }}">
                </div>
                <div class="adm-field">
                    <label for="addon-image-{{ $addon->id }}">Ganti foto</label>
                    <input id="addon-image-{{ $addon->id }}" type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-describedby="addon-image-hint-{{ $addon->id }}" @error('image') aria-invalid="true" @enderror>
                    <p class="muted" id="addon-image-hint-{{ $addon->id }}">Biarkan kosong bila foto tidak diganti. JPG, PNG, atau WebP, maksimal 5 MB.</p>
                </div>
                <div><button class="btn" type="submit">Simpan</button></div>
            </form>
        </details>
    </article>
@empty
    <div class="adm-empty" data-adm-reveal="empty">
        <span class="adm-empty__icon" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'sparkles', 'size' => 24])</span></span>
        <span class="adm-empty__title">Belum ada item tambahan</span>
        <p>Tambahkan item agar pelanggan bisa mencentangnya saat checkout. Harga item ikut masuk tagihan unit.</p>
    </div>
@endforelse
@endsection
