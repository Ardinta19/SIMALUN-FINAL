@extends('catalog.layout')
@section('title', 'Alamat saya')
@section('content')
<div class="bk-addresses">
    <a class="bk-back" href="{{ route('customer.profile') }}">← Kembali ke profil</a>
    @if(session('error'))<div class="notice" role="alert">{{ session('error') }}</div>@endif
    <div class="bk-page-heading"><div><span class="bk-kicker">SERAH TERIMA</span><h1>Alamat saya</h1><p class="muted">Simpan alamat agar lebih mudah dipilih saat mengisi detail sewa.</p></div><a class="btn" href="{{ route('customer.addresses.create') }}">Tambah alamat <span aria-hidden="true">＋</span></a></div>
    @if(isset($alamat) && $alamat->count() > 0)
    <div class="bk-address-grid">
        @foreach($alamat as $address)
        <article class="bk-address-card {{ $address->is_primary ? 'is-primary' : '' }}">
            <div class="bk-address-card__head"><span class="bk-address-icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'map-pin', 'size' => 20])</span><div><h2>{{ $address->label }}</h2>@if($address->is_primary)<span class="bk-address-primary">Alamat utama</span>@endif</div></div>
            <p><strong>{{ $address->recipient_name }}</strong>@if($address->phone) · {{ $address->phone }}@endif</p><p>{{ $address->full_address }}</p>
            @if($address->notes)<p class="muted">Catatan: {{ $address->notes }}</p>@endif
            <div class="bk-address-actions"><a class="btn secondary" href="{{ route('customer.addresses.edit', $address) }}">Edit alamat</a>
                @if(!$address->is_primary)<form method="post" action="{{ route('customer.addresses.set-primary', $address) }}">@csrf @method('PATCH')<button type="submit" class="btn secondary">Jadikan utama</button></form>@endif
            </div>
            <details class="bk-address-delete"><summary>Hapus alamat</summary><p>Hapus alamat ini? @if($address->is_primary)Alamat lain yang tersedia akan dipilih sebagai alamat utama.@endif</p><form method="post" action="{{ route('customer.addresses.destroy', $address) }}">@csrf @method('DELETE')<button class="bk-del" type="submit" aria-label="Hapus alamat {{ $address->label }}"><span class="bk-del__icon" aria-hidden="true"><svg viewBox="0 0 16 16" fill="currentColor"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"></path></svg></span><span class="bk-del__text">Hapus alamat</span></button></form></details>
        </article>
        @endforeach
    </div>
    @else
    <div class="bk-inbox-empty"><span aria-hidden="true">✿</span><h2>Belum ada alamat</h2><p>Tambahkan alamat agar lebih mudah mengisi lokasi pengantaran atau pengambilan.</p><a href="{{ route('customer.addresses.create') }}" class="btn secondary">Tambah alamat</a></div>
    @endif
</div>
@endsection
