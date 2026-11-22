@extends('catalog.layout')
@section('title', 'Petunjuk pembayaran')
@section('content')
<p><a href="{{ route('admin.rental.orders') }}">← Pesanan sewa</a></p>
<h1>Petunjuk pembayaran</h1>
<p class="muted">Masukkan cara pembayaran yang benar, termasuk informasi rekening bila diperlukan. Petunjuk ini dapat dilihat pelanggan pada detail pesanan. Jangan masukkan kata sandi, PIN, atau kode rahasia.</p>
<form class="stack card body" method="post" action="{{ route('admin.rental.payment-settings.update') }}">@csrf @method('PUT')<label>Petunjuk pembayaran<textarea name="instructions" maxlength="3000">{{ old('instructions', $settings?->instructions) }}</textarea></label><p class="muted">Kosongkan untuk menyembunyikan petunjuk.</p><p><button class="btn">Simpan</button></p></form>
@endsection
