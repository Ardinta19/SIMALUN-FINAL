@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Pengaturan pembayaran')
@section('crumbs')<strong>Petunjuk pembayaran</strong>@endsection
@section('content')
<a data-smart-back class="bk-back" href="{{ route('admin.rental.orders') }}">← Pesanan sewa</a>
@php
    $oldText = function (string $key, $fallback = '') {
        $value = old($key, $fallback);
        return is_scalar($value) ? (string) $value : '';
    };
    $hasQris = (bool) $settings?->qris_path;
@endphp

<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Keuangan</span>
        <h1>Pengaturan pembayaran</h1>
        <p class="adm-page-sub">Atur cara pelanggan membayar: QRIS, nomor WhatsApp, dan petunjuk transfer.</p>
                <details class="adm-page-note">
                    <summary>Selengkapnya</summary>
                    <p>QRIS adalah pilihan utama. Siapkan QRIS usaha yang benar dan nomor WhatsApp untuk metode lain. Pelanggan tetap mengirim bukti dan admin memverifikasinya secara manual; sistem tidak memeriksa transfer secara otomatis.</p>
                </details>
    </div>
    <div class="adm-actions">
        <span class="adm-badge {{ $hasQris ? 'adm-badge--confirmed' : 'adm-badge--pending' }}">
            {{ $hasQris ? 'QRIS siap' : 'QRIS belum diatur' }}
        </span>
    </div>
</div>

<div class="adm-note" data-adm-reveal="note">
    <span class="adm-note__title">Jangan masukkan data rahasia</span>
    Hanya unggah gambar QRIS dan tulis nomor WhatsApp usaha. Jangan pernah menyimpan PIN, kata sandi, atau kode rahasia di halaman ini.
</div>

<form class="adm-card adm-stack" style="margin-top:var(--adm-gap)" method="post" action="{{ route('admin.rental.payment-settings.update') }}" enctype="multipart/form-data" data-adm-reveal="form">
    @csrf
    @method('PUT')

    <div class="adm-card__head">
        <h2>QRIS usaha</h2>
        <span class="adm-chip">JPG, PNG, WebP · maksimal 5 MB</span>
    </div>

    @if($hasQris)
        <div class="adm-row" style="align-items:flex-start;gap:16px">
            <img src="{{ route('rental.payment.qris') }}" alt="QRIS yang saat ini digunakan" style="max-width:220px;width:100%;height:auto;border-radius:var(--adm-r-md);border:1px solid var(--adm-line);background:#fff;padding:8px">
            <div class="adm-field" style="max-width:320px">
                <label class="adm-switch">
                    <input type="checkbox" name="remove_qris" value="1">
                    <span class="adm-switch__track" aria-hidden="true"></span>
                    <span>Hapus QRIS saat ini</span>
                </label>
                <span class="adm-help">Jangan sekaligus memilih hapus dan mengganti gambar.</span>
            </div>
        </div>
    @else
        <p class="adm-note adm-note--warn" style="margin:0">QRIS belum diatur.</p>
    @endif

    <div class="adm-field">
        <label for="pay-qris">{{ $hasQris ? 'Ganti gambar QRIS' : 'Unggah gambar QRIS' }}</label>
        <input id="pay-qris" type="file" name="qris" accept="image/jpeg,image/png,image/webp">
        <span class="adm-help">Unggah gambar yang terbaca dan pastikan nama penerima sesuai usaha.</span>
    </div>

    <hr class="adm-divider">

    <div class="adm-field-grid">
        <div class="adm-field">
            <label for="pay-wa">Nomor WhatsApp untuk metode lain</label>
            <input id="pay-wa" type="tel" name="whatsapp_number" inputmode="numeric" maxlength="15" placeholder="6281234567890" value="{{ $oldText('whatsapp_number', $settings?->whatsapp_number) }}">
            <span class="adm-help">Awali 62, hanya angka tanpa tanda plus atau spasi. Kosongkan jika belum tersedia.</span>
        </div>
    </div>

    <div class="adm-field">
        <label for="pay-instructions">Petunjuk tambahan</label>
        <textarea id="pay-instructions" name="instructions" maxlength="3000">{{ $oldText('instructions', $settings?->instructions) }}</textarea>
        <span class="adm-help">Petunjuk ini terlihat pada detail pesanan pelanggan, bersama QRIS.</span>
    </div>

    <div><button class="btn" type="submit">Simpan pengaturan</button></div>
</form>
@endsection
