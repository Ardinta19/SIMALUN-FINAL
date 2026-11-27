@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Universitas dan tarif')
@section('crumbs')<a href="{{ route('admin.catalog.index') }}">Katalog</a><span class="adm-crumbs__sep" aria-hidden="true">/</span><strong>Universitas &amp; tarif</strong>@endsection
@section('content')
<a data-smart-back class="bk-back" href="{{ route('admin.catalog.index') }}">← Kelola katalog</a>
<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Katalog &amp; penawaran</span>
        <h1>Universitas dan tarif</h1>
        <p class="adm-page-sub">Universitas dan tarif antar/jemput per unit — keduanya boleh berbeda.</p>
                <details class="adm-page-note">
                    <summary>Selengkapnya</summary>
                    <p>Masukkan sendiri universitas dan tarif per unit. Tarif antar dan jemput dapat berbeda. Pesanan yang sudah dibuat menyimpan tarif saat checkout.</p>
                </details>
    </div>
    <div class="adm-actions">
        <a class="btn secondary" href="{{ route('admin.catalog.index') }}">Kembali ke katalog</a>
    </div>
</div>

<section class="adm-card" data-adm-reveal="form">
    <div class="adm-card__head">
        <h2>Tambah universitas</h2>
        <span class="adm-chip">Tarif per unit</span>
    </div>
    <form class="adm-stack" method="post" action="{{ route('admin.rental.universities.store') }}">
        @csrf
        <div class="adm-field-grid">
            <div class="adm-field adm-field--wide">
                <label for="uni-name">Nama universitas</label>
                <input id="uni-name" name="name" maxlength="160" required placeholder="Contoh: Universitas Sriwijaya">
            </div>
            <div class="adm-field">
                <label for="uni-delivery">Tarif antar (Rp)</label>
                <input placeholder="Contoh: 15000" id="uni-delivery" type="number" inputmode="numeric" name="delivery_fee" min="0" max="100000000" required>
            </div>
            <div class="adm-field">
                <label for="uni-collection">Tarif jemput kembali (Rp)</label>
                <input placeholder="Contoh: 20000" id="uni-collection" type="number" inputmode="numeric" name="collection_fee" min="0" max="100000000" required>
            </div>
        </div>
        <div><button class="btn" type="submit">Tambah</button></div>
    </form>
</section>

<div class="adm-row adm-row--between adm-head-row" data-adm-reveal="list-title">
    <h2>Daftar universitas</h2>
    <span class="adm-chip">{{ $universities->count() }} universitas</span>
</div>

@forelse($universities as $university)
    <article class="adm-card adm-card--hover" data-adm-reveal="unis">
        <div class="adm-row adm-row--between adm-row--top">
            <div>
                <h3>{{ $university->name }}</h3>
                <div class="adm-row adm-row--tight adm-mt-6">
                    <span class="adm-badge {{ $university->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">
                        {{ $university->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <span class="adm-chip">Antar Rp {{ number_format($university->delivery_fee, 0, ',', '.') }}</span>
                    <span class="adm-chip">Jemput Rp {{ number_format($university->collection_fee, 0, ',', '.') }}</span>
                </div>
            </div>
            <form method="post" action="{{ route('admin.rental.universities.toggle', $university) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn secondary adm-btn--sm">{{ $university->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
        </div>

        <details class="adm-mt-3">
            <summary class="bk-disclosure">Ubah nama dan tarif</summary>
            <form class="adm-stack adm-mt-3" method="post" action="{{ route('admin.rental.universities.update', $university) }}">
                @csrf
                @method('PUT')
                <div class="adm-field-grid">
                    <div class="adm-field adm-field--wide">
                        <label for="uni-name-{{ $university->id }}">Nama</label>
                        <input id="uni-name-{{ $university->id }}" name="name" value="{{ $university->name }}" maxlength="160" required>
                    </div>
                    <div class="adm-field">
                        <label for="uni-delivery-{{ $university->id }}">Tarif antar (Rp)</label>
                        <input id="uni-delivery-{{ $university->id }}" type="number" inputmode="numeric" name="delivery_fee" min="0" max="100000000" value="{{ $university->delivery_fee }}" required>
                    </div>
                    <div class="adm-field">
                        <label for="uni-collection-{{ $university->id }}">Tarif jemput kembali (Rp)</label>
                        <input id="uni-collection-{{ $university->id }}" type="number" inputmode="numeric" name="collection_fee" min="0" max="100000000" value="{{ $university->collection_fee }}" required>
                    </div>
                </div>
                <div><button class="btn" type="submit">Simpan</button></div>
            </form>
        </details>
    </article>
@empty
    <div class="adm-empty" data-adm-reveal="empty">
        <span class="adm-empty__icon" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'graduation-cap', 'size' => 24])</span></span>
        <span class="adm-empty__title">Belum ada universitas</span>
        <p>Tambahkan universitas agar ongkos antar dan jemput bisa dihitung otomatis saat checkout.</p>
    </div>
@endforelse
@endsection
