@extends('catalog.layout')
@section('title', 'Profil saya')
@section('content')
<div class="bk-profile">
    <a data-smart-back class="bk-back" href="{{ route('customer.dashboard') }}">← Kembali ke beranda</a>
    @if(session('status') === 'profile-updated')<div class="notice" role="status">Profil berhasil diperbarui.</div>
    @elseif(session('status') === 'address-saved')<div class="notice" role="status">Alamat utama berhasil disimpan.</div>@endif
    <div class="bk-page-heading"><div><span class="bk-kicker">AKUN PELANGGAN</span><h1>Profil saya</h1><p class="muted">Kelola informasi dan alamat untuk pesananmu.</p></div><a class="btn secondary" href="{{ route('profile.edit') }}">Edit profil ↗</a></div>
    <div class="bk-profile-grid">
        <section class="bk-profile-identity" aria-label="Identitas akun">
            <div class="bk-profile-avatar">@if($user->avatar)<img src="{{ asset('storage/'.$user->avatar) }}" alt="Foto profil {{ $user->name }}">@else<span aria-hidden="true">@include('layouts.component._icon', ['name' => 'user', 'size' => 32])</span>@endif</div>
            <span class="bk-kicker">PELANGGAN BERKAH KREATIF</span><h2>{{ $user->name }}</h2><p class="muted">Bergabung {{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}</p>
        </section>
        <div class="bk-profile-sections">
            <section class="bk-profile-card"><div class="bk-profile-card__head"><div><span class="bk-kicker">DATA PRIBADI</span><h2>Informasi akun</h2></div><a href="{{ route('profile.edit') }}">Ubah ↗</a></div>
                <dl class="bk-profile-data"><div><dt>Email</dt><dd>{{ $user->email ?: 'Belum diisi' }}</dd></div><div><dt>Nomor HP</dt><dd>{{ $user->phone ?: 'Belum diisi' }}</dd></div></dl>
            </section>
            <section class="bk-profile-card"><div class="bk-profile-card__head"><div><span class="bk-kicker">UNTUK SERAH TERIMA</span><h2>Alamat utama</h2></div><a href="{{ route('customer.addresses.index') }}">Kelola semua ↗</a></div>
                @if($primaryAddress)
                    <p><strong>{{ $primaryAddress->label }}</strong> · {{ $primaryAddress->recipient_name }}</p><p>{{ $primaryAddress->full_address }}</p>@if($primaryAddress->notes)<p class="muted">Catatan: {{ $primaryAddress->notes }}</p>@endif @if($primaryAddress->phone)<p class="muted">{{ $primaryAddress->phone }}</p>@endif
                    <div class="bk-profile-actions"><a class="btn secondary" href="{{ route('customer.addresses.edit', $primaryAddress) }}">Ubah alamat utama</a><a class="btn secondary" href="{{ route('customer.addresses.create') }}">Tambah alamat</a></div>
                @else
                    <p class="muted">Belum ada alamat utama. Alamat tersimpan dapat dipilih saat checkout sewa.</p><a class="btn secondary" href="{{ route('customer.addresses.create') }}">Tambah alamat utama</a>
                @endif
                @if(($totalAddresses ?? 0) > 1)<p class="muted">Total alamat tersimpan: {{ $totalAddresses }}</p>@endif
            </section>
            <details class="bk-profile-signout"><summary>Keluar dari akun</summary><p>Kamu akan keluar dari sesi ini. Lanjutkan?</p><form method="post" action="{{ route('logout') }}">@csrf<button class="btn secondary" type="submit">Ya, keluar</button></form></details>
        </div>
    </div>
</div>
@endsection
