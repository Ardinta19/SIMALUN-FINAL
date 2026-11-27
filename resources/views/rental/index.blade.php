@extends('catalog.layout')
@section('title', 'Pesanan saya')
@section('content')
@php $orderFilterValue = fn ($key) => is_scalar(old($key, $filters[$key] ?? '')) ? (string) old($key, $filters[$key] ?? '') : ''; @endphp
<div class="bk-orders">
    <a data-smart-back class="bk-back" href="{{ route('customer.dashboard') }}">← Kembali ke beranda</a>
    <div class="bk-page-heading"><div><span class="bk-kicker">Ruang pelanggan</span><h1>Pesanan sewa</h1><p class="muted">Lihat status dan langkah berikutnya untuk setiap pesanan.</p></div><a href="{{ route('welcome') }}" class="btn secondary">Jelajahi katalog ↗</a></div>
    <details class="bk-order-filter" @if(!empty($filters['q']) || !empty($filters['status']) || $errors->hasAny(['q', 'status'])) open @endif>
        <summary><span>⌕ &nbsp; Cari dan filter pesanan</span><span class="bk-order-filter__hint">{{ !empty($filters['q']) || !empty($filters['status']) ? 'Filter aktif' : 'Nama, referensi, status' }}</span></summary>
        <form class="stack" method="get" action="{{ route('rental.orders.index') }}">
            <div class="row"><div><label for="order-search">Cari referensi, nama papan, atau penerima</label><input placeholder="Cari nama pelanggan atau nomor referensi" type="search" autocomplete="off" id="order-search" name="q" maxlength="160" value="{{ $orderFilterValue('q') }}" @error('q') aria-invalid="true" aria-describedby="order-search-error" @enderror>@error('q')<p class="bk-field-error" id="order-search-error" role="alert">{{ $message }}</p>@enderror</div>
            <div><label for="order-status">Status unit</label><select id="order-status" name="status" @error('status') aria-invalid="true" aria-describedby="order-status-error" @enderror><option value="">Semua status</option>@foreach(['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected($orderFilterValue('status') === $value)>{{ $label }}</option>@endforeach</select>@error('status')<p class="bk-field-error" id="order-status-error" role="alert">{{ $message }}</p>@enderror</div></div>
            <p class="muted">Filter status menampilkan pesanan yang memiliki setidaknya satu unit dengan status tersebut.</p><div class="row bk-filter-actions"><button class="btn" type="submit">Cari pesanan</button><a class="btn secondary" href="{{ route('rental.orders.index') }}">Atur ulang filter</a></div>
        </form>
    </details>
    @if($errors->hasAny(['q', 'status']))<p class="notice">Pencarian belum diterapkan. Daftar berikut masih mengikuti filter sebelumnya.</p>@endif
    <div class="bk-section-heading"><h2>Daftar pesanan</h2><span class="muted">{{ $orders->total() }} pesanan</span></div>
    <div class="bk-order-cards" data-bk-reveal-group="orders" data-bk-reveal-stagger="0.06">
    @forelse($orders as $order)
        <article class="bk-order-card">
            <div class="bk-order-card__head"><span class="bk-order-card__mark" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></span><div><span class="bk-order-card__label">Referensi pesanan</span><h2>{{ $order->reference }}</h2></div></div>
            <p class="muted">{{ $order->units_count }} unit · {{ $order->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
            <div class="bk-order-status"><span>Menunggu: {{ $order->pending_units_count }}</span><span>Dikonfirmasi: {{ $order->confirmed_units_count }}</span><span>Dibatalkan: {{ $order->cancelled_units_count }}</span></div>
            <a class="bk-order-card__link" href="{{ route('rental.orders.show', $order) }}">Lihat detail <span aria-hidden="true">↗</span></a>
        </article>
    @empty
        <div class="bk-inbox-empty"><span aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></span><h2>Tidak ada pesanan yang cocok</h2><p>Coba ubah filter atau temukan papan untuk momenmu.</p><a href="{{ route('welcome') }}" class="btn secondary">Lihat katalog</a></div>
    @endforelse
    </div>
    <div class="pagination">{{ $orders->links('vendor.pagination.berkah') }}</div>
</div>
@endsection
