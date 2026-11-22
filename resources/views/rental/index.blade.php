@extends('catalog.layout')
@section('title', 'Pesanan saya')
@section('content')
<div class="bk-orders">
    <a class="bk-back" href="{{ route('customer.dashboard') }}">← Kembali ke beranda</a>
    <div class="bk-page-heading"><div><span class="bk-kicker">RUANG PELANGGAN</span><h1>Pesanan sewa</h1><p class="muted">Lihat status dan langkah berikutnya untuk setiap pesanan.</p></div><a href="{{ route('welcome') }}" class="btn secondary">Jelajahi katalog ↗</a></div>
    <details class="bk-order-filter" @if(!empty($filters['q']) || !empty($filters['status']) || $errors->any()) open @endif>
        <summary><span>⌕ &nbsp; Cari dan filter pesanan</span><span class="bk-order-filter__hint">{{ !empty($filters['q']) || !empty($filters['status']) ? 'Filter aktif' : 'Nama, referensi, status' }}</span></summary>
        <form class="stack" method="get" action="{{ route('rental.orders.index') }}">
            <div class="row"><label>Cari referensi, nama papan, atau penerima<input name="q" maxlength="160" value="{{ $filters['q'] ?? '' }}"></label>
            <label>Status unit<select name="status"><option value="">Semua status</option>@foreach(['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label></div>
            <p class="muted">Filter status menampilkan pesanan yang memiliki setidaknya satu unit dengan status tersebut.</p><div class="row bk-filter-actions"><button class="btn" type="submit">Cari pesanan</button><a class="btn secondary" href="{{ route('rental.orders.index') }}">Reset filter</a></div>
        </form>
    </details>
    <div class="bk-section-heading"><h2>Daftar pesanan</h2><span class="muted">{{ $orders->total() }} pesanan</span></div>
    <div class="bk-order-cards">
    @forelse($orders as $order)
        <article class="bk-order-card">
            <div class="bk-order-card__head"><span class="bk-order-card__mark" aria-hidden="true">✿</span><div><span class="bk-order-card__label">REFERENSI PESANAN</span><h2>{{ $order->reference }}</h2></div></div>
            <p class="muted">{{ $order->units_count }} unit · {{ $order->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
            <div class="bk-order-status"><span>Menunggu: {{ $order->pending_units_count }}</span><span>Dikonfirmasi: {{ $order->confirmed_units_count }}</span><span>Dibatalkan: {{ $order->cancelled_units_count }}</span></div>
            <a class="bk-order-card__link" href="{{ route('rental.orders.show', $order) }}">Lihat detail <span aria-hidden="true">↗</span></a>
        </article>
    @empty
        <div class="bk-inbox-empty"><span aria-hidden="true">✿</span><h2>Tidak ada pesanan yang cocok</h2><p>Coba ubah filter atau temukan papan untuk momenmu.</p><a href="{{ route('welcome') }}" class="btn secondary">Lihat katalog</a></div>
    @endforelse
    </div>
    <div class="pagination">{{ $orders->links('vendor.pagination.berkah') }}</div>
</div>
@endsection
