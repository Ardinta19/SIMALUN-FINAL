@extends('layouts.admin')

@section('back', route('admin.profile'))
@section('title', 'Ubah profil')
@section('crumbs')
<a href="{{ route('admin.profile') }}">Profil</a><span class="adm-crumbs__sep" aria-hidden="true">/</span><strong>Ubah</strong>
@endsection

{{-- Dulu halaman mandiri dengan `<header class="appbar">` sendiri. Sekarang
     memakai shell admin: topbar, sidebar, toast, dan kontrol formulir bersama
     (`_form_controls`) dari layout. --}}
@section('content')
@php
    // Nilai lama bisa berupa larik bila permintaan dikirim dengan nama field
    // ganda (mis. name[]=...). Tanpa penjaga ini halaman mencetak teks larik.
    $oldText = function (string $key, $fallback = '') {
        $value = old($key, $fallback);
        return is_scalar($value) ? (string) $value : '';
    };
@endphp

<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Akun administrator</span>
        <h1>Ubah profil</h1>
        <p class="adm-page-sub">Perbarui identitas yang tampil di panel, lalu ganti kata sandi bila perlu.</p>
    </div>
    <div class="adm-actions">
        <a class="adm-btn adm-btn--ghost" href="{{ route('admin.profile') }}">Lihat profil</a>
    </div>
</div>

@if(session('status') === 'profile-updated')
    <div class="adm-note adm-note--ok" role="status" data-adm-reveal="status">
        <span class="adm-note__title">Tersimpan</span>
        Profil admin berhasil diperbarui.
    </div>
@endif

<section class="adm-card" data-adm-reveal="identity" aria-labelledby="adm-identity-title" style="margin-bottom:var(--adm-gap)">
    <div class="adm-card__head">
        <h2 id="adm-identity-title">Data identitas</h2>
        <span class="adm-chip">Berlaku di seluruh panel</span>
    </div>

    <form class="adm-stack" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="adm-row" style="gap:16px;align-items:flex-start">
            <img data-adm-avatar-preview
                 src="{{ $user->avatar ? asset('storage/'.$user->avatar) : asset('images/logo-104.png') }}"
                 alt="Foto profil {{ $user->name }}"
                 width="72" height="72"
                 style="width:72px;height:72px;flex:none;border-radius:50%;object-fit:cover;border:1px solid var(--adm-line);background:var(--adm-surface-3)">
            <div class="adm-field" style="flex:1 1 220px;min-width:0">
                <label for="avatar">Foto profil</label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" data-adm-avatar aria-describedby="avatar-hint" @error('avatar') aria-invalid="true" @enderror>
                <p class="adm-help" id="avatar-hint">JPG, PNG, atau WebP, maksimal 5 MB. Kosongkan bila tidak diganti. <span data-adm-avatar-name></span></p>
                @error('avatar')<p class="adm-err" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="adm-field-grid">
            <div class="adm-field">
                <label for="name">Nama lengkap <span class="adm-req">(wajib)</span></label>
                <input type="text" id="name" name="name" autocomplete="name" required
                       placeholder="Contoh: Sari Wulandari" value="{{ $oldText('name', $user->name) }}" @error('name') aria-invalid="true" @enderror>
                @error('name')<p class="adm-err" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="adm-field">
                <label for="email">Alamat email <span class="adm-req">(wajib)</span></label>
                <input type="email" id="email" name="email" autocomplete="email" required
                       placeholder="Contoh: nama@email.com" value="{{ $oldText('email', $user->email) }}" @error('email') aria-invalid="true" @enderror>
                @error('email')<p class="adm-err" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="adm-field">
                <label for="phone">Nomor WhatsApp</label>
                <input type="tel" id="phone" name="phone" inputmode="tel" autocomplete="tel"
                       placeholder="Contoh: 0812 3456 7890" value="{{ $oldText('phone', $user->phone) }}" @error('phone') aria-invalid="true" @enderror>
                @error('phone')<p class="adm-err" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>

        <div><button class="adm-btn" type="submit">Simpan perubahan</button></div>
    </form>
</section>

<section class="adm-card" data-adm-reveal="password" aria-labelledby="adm-password-title">
    <div class="adm-card__head">
        <h2 id="adm-password-title">Ganti kata sandi</h2>
        <span class="adm-chip">Minimal 8 karakter</span>
    </div>

    <form class="adm-stack" method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="adm-field-grid">
            <div class="adm-field">
                <label for="current_password">Kata sandi saat ini</label>
                <input type="password" id="current_password" name="current_password"
                       autocomplete="current-password" placeholder="Masukkan kata sandi saat ini"
                       @error('current_password', 'updatePassword') aria-invalid="true" aria-describedby="current_password-error" @enderror>
                {{-- Validasi kata sandi memakai kantong `updatePassword`; tanpa nama
                     kantong ini pesan tidak pernah tampil di sini. --}}
                @error('current_password', 'updatePassword')<p class="adm-err" role="alert" id="current_password-error">{{ $message }}</p>@enderror
            </div>
            <div class="adm-field">
                <label for="password">Kata sandi baru</label>
                <input type="password" id="password" name="password"
                       autocomplete="new-password" placeholder="Minimal 8 karakter"
                       @error('password', 'updatePassword') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password', 'updatePassword')<p class="adm-err" role="alert" id="password-error">{{ $message }}</p>@enderror
            </div>
            <div class="adm-field">
                <label for="password_confirmation">Ulangi kata sandi baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       autocomplete="new-password" placeholder="Ulangi kata sandi baru">
            </div>
        </div>

        <div><button class="adm-btn adm-btn--ghost" type="submit">Perbarui kata sandi</button></div>
    </form>
</section>
@endsection

@push('scripts')
<script>
/* Pratinjau foto sebelum disimpan. Ditulis sebagai listener (bukan atribut
   `onchange`) supaya tidak ada fungsi global dan tetap aman bila elemen tidak
   ada. Tanpa FileReader, nama berkas tetap tampil sebagai umpan balik. */
(function () {
    'use strict';
    var input = document.querySelector('[data-adm-avatar]');
    if (!input) return;
    var preview = document.querySelector('[data-adm-avatar-preview]');
    var name = document.querySelector('[data-adm-avatar-name]');
    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) return;
        if (name) name.textContent = 'Dipilih: ' + file.name;
        if (!preview || typeof window.FileReader !== 'function') return;
        var reader = new FileReader();
        reader.onload = function (event) { preview.src = event.target.result; };
        reader.readAsDataURL(file);
    });
})();
</script>
@endpush
