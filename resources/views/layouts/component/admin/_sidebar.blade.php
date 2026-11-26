{{--
|--------------------------------------------------------------------------
| Sidebar shell admin: merek, navigasi, kartu bantuan, identitas pengguna
|--------------------------------------------------------------------------
| Ditampilkan sebagai kolom tetap di layar lebar dan sebagai panel geser
| (drawer) di layar kecil. Tombol pembuka berada di topbar (data-adm-nav-toggle).
--}}
@php
    $admUser = auth()->user();
    $admFullName = trim($admUser->name ?? 'Admin');
    $admFirstName = explode(' ', $admFullName)[0] ?: 'Admin';
    $admInitial = mb_strtoupper(mb_substr($admFirstName, 0, 1));
@endphp

<aside class="adm-sidebar" id="adm-sidebar" aria-label="Menu admin" data-open="false">
    <div class="adm-sidebar__head">
        <a class="adm-brand" href="{{ route('dashboard.admin') }}">
            <span class="adm-brand__logo">
                <img src="{{ asset('images/logo-104.png') }}" width="104" height="57" alt="Logo Berkah Kreatif">
            </span>
            <span class="adm-brand__text">
                <span class="adm-brand__name">Berkah Kreatif</span>
                <span class="adm-brand__tag">Panel admin</span>
            </span>
        </a>
        <button type="button" class="adm-side-close" data-adm-close aria-label="Tutup menu">
            @include('layouts.component._icon', ['name' => 'close', 'size' => 16])
        </button>
    </div>

    @include('layouts.component.admin._nav')

    <div class="adm-side-card">
        <span class="adm-side-card__label">Mode uji coba</span>
        <span class="adm-side-card__value">Belum untuk transaksi nyata</span>
        <span class="adm-side-card__hint">Setiap tindakan admin tercatat di jejak audit. Gunakan data uji sampai pemilik menyatakan siap.</span>
        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.help') }}">
            @include('layouts.component._icon', ['name' => 'help', 'size' => 16])
            Buka panduan
        </a>
        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('welcome') }}" target="_blank" rel="noopener">
            @include('layouts.component._icon', ['name' => 'eye', 'size' => 16])
            Lihat katalog publik
        </a>
    </div>

    <div class="adm-user">
        <span class="adm-avatar" aria-hidden="true">{{ $admInitial }}</span>
        <span class="adm-user__meta">
            <a class="adm-user__name" href="{{ route('admin.profile') }}">{{ $admFirstName }}</a>
            <span class="adm-user__role"><a class="adm-user__edit" href="{{ route('profile.edit') }}">Ubah profil</a></span>
        </span>
        <form method="POST" action="{{ route('logout') }}" data-adm-no-loading>
            @csrf
            <button type="submit" class="adm-user__out" aria-label="Keluar dari akun" title="Keluar">
                @include('layouts.component._icon', ['name' => 'logout', 'size' => 16])
            </button>
        </form>
    </div>
</aside>
