@extends('layouts.admin')

@section('back', route('dashboard.admin'))
@section('title', 'Profil')
@section('crumbs')<strong>Profil</strong>@endsection

{{-- Dulu halaman mandiri (`<header class="profile-header">` + `_navbar_admin`).
     Sekarang memakai shell admin, jadi sidebar/topbar/remah roti sama dengan
     halaman admin lainnya. Isi dan tautannya tidak berubah. --}}
@section('content')
@php
    $adminUnread = \App\Support\NavCounts::unreadNotifications();
    $adminPhone = trim((string) ($user->phone ?? ''));
@endphp

<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Akun administrator</span>
        <h1>Profil admin</h1>
        <p class="adm-page-sub">Identitas akun, ringkasan operasional, dan pintasan menu harian.</p>
    </div>
    <div class="adm-actions">
        <a class="adm-btn adm-btn--ghost" href="{{ route('admin.notifications') }}">
            Notifikasi
            @if($adminUnread > 0)<span class="adm-badge adm-badge--pending">{{ $adminUnread > 99 ? '99+' : $adminUnread }}</span>@endif
        </a>
        <a class="adm-btn" href="{{ route('profile.edit') }}">Ubah profil</a>
    </div>
</div>

@if(session('status') === 'profile-updated')
    <div class="adm-note adm-note--ok" role="status" data-adm-reveal="status">
        <span class="adm-note__title">Tersimpan</span>
        Profil berhasil diperbarui.
    </div>
@endif

<div class="adm-grid adm-grid--split" style="margin-bottom:var(--adm-gap)">
    <section class="adm-card" data-adm-reveal="identity" aria-labelledby="adm-profile-name">
        <div class="adm-row" style="gap:14px;align-items:center">
            <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : asset('images/logo-104.png') }}"
                 alt="Foto profil {{ $user->name }}" width="64" height="64"
                 style="width:64px;height:64px;flex:none;border-radius:50%;object-fit:cover;border:1px solid var(--adm-line);background:var(--adm-surface-3)">
            <div style="min-width:0">
                <h2 id="adm-profile-name" class="adm-title--lg">{{ $user->name }}</h2>
                <p class="adm-help" style="margin:2px 0 6px">Administrator · Berkah Kreatif</p>
                <span class="adm-badge adm-badge--live"><span class="adm-dot" aria-hidden="true"></span> Mode pengujian</span>
            </div>
        </div>

        <hr class="adm-divider">

        <dl class="adm-stack" style="gap:10px;margin:0">
            <div>
                <dt class="adm-help" style="margin:0">Email</dt>
                <dd style="margin:0;font-weight:700;overflow-wrap:anywhere">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="adm-help" style="margin:0">Nomor WhatsApp</dt>
                <dd style="margin:0;font-weight:700">{{ $adminPhone !== '' ? $adminPhone : 'Belum diisi' }}</dd>
            </div>
        </dl>

        <hr class="adm-divider">

        <form method="POST" action="{{ route('logout') }}" id="form-logout">
            @csrf
            <button type="button" class="adm-btn adm-btn--ghost" data-adm-logout>
                @include('layouts.component._icon', ['name' => 'logout', 'size' => 16])
                Keluar dari akun
            </button>
        </form>
    </section>

    <div class="adm-grid">
        <div class="adm-stats">
            <a class="adm-stat" href="{{ route('admin.rental.orders') }}">
                <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'truck', 'size' => 20])</span>
                <span class="adm-stat__value">{{ $rentalPending }}</span>
                <span class="adm-stat__label">Menunggu konfirmasi</span>
                <span class="adm-stat__hint">Pesanan yang belum dikonfirmasi</span>
            </a>
            <a class="adm-stat" href="{{ route('admin.rental.schedule') }}">
                <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'orders', 'size' => 20])</span>
                <span class="adm-stat__value">{{ $rentalActive }}</span>
                <span class="adm-stat__label">Unit aktif</span>
                <span class="adm-stat__hint">Sedang berjalan atau siap diantar</span>
            </a>
        </div>

        <div class="adm-note adm-note--warn" style="margin:0">
            <span class="adm-note__title">Optimalkan operasi</span>
            Cek performa usaha dan kelola semua pesanan dari panel ini.
        </div>
    </div>
</div>

<section class="adm-card" data-adm-reveal="menu" aria-labelledby="adm-profile-menu">
    <div class="adm-card__head">
        <h2 id="adm-profile-menu">Pintasan operasional</h2>
        <span class="adm-chip">Semua menu admin</span>
    </div>

    @php
        $admShortcuts = [
            ['route' => 'admin.rental.orders', 'icon' => 'orders', 'label' => 'Pesanan sewa', 'sub' => 'Periksa konfirmasi, desain, dan pembayaran'],
            ['route' => 'admin.catalog.index', 'icon' => 'add-user', 'label' => 'Kelola katalog', 'sub' => 'Atur papan, kategori, dan paket'],
            ['route' => 'admin.rental.cashbook.index', 'icon' => 'finance', 'label' => 'Buku kas', 'sub' => 'Rekap pemasukan dan pengeluaran'],
            ['route' => 'admin.rental.schedule', 'icon' => 'report', 'label' => 'Jadwal sewa', 'sub' => 'Lihat rencana serah terima'],
            ['route' => 'admin.help', 'icon' => 'help', 'label' => 'Panduan admin', 'sub' => 'Langkah harian dan jawaban operasional'],
            ['route' => 'admin.notifications', 'icon' => 'bell', 'label' => 'Notifikasi admin', 'sub' => 'Pemberitahuan pesanan dan tenggat'],
            ['route' => 'admin.audit.index', 'icon' => 'audit', 'label' => 'Jejak audit', 'sub' => 'Jejak tindakan admin terakhir'],
            ['route' => 'profile.edit', 'icon' => 'user', 'label' => 'Ubah profil', 'sub' => 'Nama, nomor telepon, dan foto akun'],
        ];
    @endphp

    <div class="adm-grid adm-grid--auto">
        @foreach($admShortcuts as $shortcut)
            <a class="adm-card adm-card--hover" href="{{ route($shortcut['route']) }}" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit">
                <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => $shortcut['icon'], 'size' => 20])</span>
                <span style="min-width:0">
                    <span style="display:block;font-weight:800">{{ $shortcut['label'] }}</span>
                    <span class="adm-help" style="display:block;margin-top:2px">{{ $shortcut['sub'] }}</span>
                </span>
                <span style="margin-left:auto;flex:none;color:var(--adm-ink-3)" aria-hidden="true">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
            </a>
        @endforeach
    </div>

    <p class="adm-help" style="margin:16px 0 0">Berkah Kreatif · Panel admin</p>
</section>
@endsection

@push('scripts')
<script>
/* Konfirmasi keluar memakai modal bersama (`_ui_kit` sudah memuat
   `showConfirmModal` di setiap halaman admin). */
(function () {
    'use strict';
    var button = document.querySelector('[data-adm-logout]');
    var form = document.getElementById('form-logout');
    if (!button || !form) return;
    button.addEventListener('click', function () {
        if (typeof window.showConfirmModal !== 'function') { form.submit(); return; }
        window.showConfirmModal({
            title: 'Keluar dari akun?',
            message: 'Kamu akan keluar dari sesi ini. Yakin ingin melanjutkan?',
            confirmText: 'Ya, keluar',
            cancelText: 'Batal',
            type: 'danger',
            onConfirm: function () { form.submit(); }
        });
    });
})();
</script>
@endpush
