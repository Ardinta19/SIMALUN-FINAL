@extends('catalog.layout')
@section('title', 'Pesanan saya')
@section('content')
<h1>Pesanan sewa</h1>
<form class="stack card body" method="get" action="{{ route('rental.orders.index') }}">
    <label>Cari referensi, nama papan, atau penerima<input name="q" maxlength="160" value="{{ $filters['q'] ?? '' }}"></label>
    <label>Status unit<select name="status"><option value="">Semua status</option>@foreach(['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
    <p><button class="btn">Cari pesanan</button> <a href="{{ route('rental.orders.index') }}">Reset</a></p>
    <p class="muted">Filter status menampilkan pesanan yang memiliki setidaknya satu unit dengan status tersebut.</p>
</form>
<div class="items">@forelse($orders as $order)<article class="card body"><h2>Pesanan {{ $order->reference }}</h2><p>{{ $order->units_count }} unit · {{ $order->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p><p>Menunggu: {{ $order->pending_units_count }} · Dikonfirmasi: {{ $order->confirmed_units_count }} · Dibatalkan: {{ $order->cancelled_units_count }}</p><a class="btn secondary" href="{{ route('rental.orders.show', $order) }}">Lihat detail</a></article>@empty<p>Tidak ada pesanan yang cocok. <a href="{{ route('welcome') }}">Lihat katalog</a></p>@endforelse</div><div class="pagination">{{ $orders->links() }}</div>
@endsection
