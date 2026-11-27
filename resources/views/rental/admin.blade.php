@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Pesanan sewa')
@section('crumbs')<strong>Pesanan sewa</strong>@endsection
@section('content')
<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Operasional</span>
        <h1>Pesanan sewa</h1>
        <p class="adm-page-sub">Konfirmasi mencadangkan stok. Periode sewa termasuk hari penerimaan dan pengembalian. Buka detail pesanan untuk menangani setiap unit.</p>
    </div>
    <div class="adm-actions">
        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.schedule') }}">Jadwal sewa</a>
        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.payment-settings.edit') }}">Petunjuk pembayaran</a>
    </div>
</div>

<div class="adm-note adm-note--warn" role="status" data-adm-reveal="note">
    <span class="adm-note__title">Mode pengujian</span>
    Pemesanan dan pembayaran belum lengkap; gunakan halaman ini hanya untuk pengujian.
</div>

@php $activeStatus = $filters['status'] ?? ''; @endphp
<nav class="adm-chipbar" aria-label="Antrean cepat" data-adm-reveal="filters">
    <span class="adm-chipbar__label">Antrean cepat</span>
    <a class="adm-chip adm-chip--link {{ $activeStatus === 'pending' ? 'is-active' : '' }}" @if($activeStatus === 'pending') aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'pending']) }}">Menunggu konfirmasi</a>
    <a class="adm-chip adm-chip--link {{ $activeStatus === 'payment_pending' ? 'is-active' : '' }}" @if($activeStatus === 'payment_pending') aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'payment_pending']) }}">Bukti pembayaran</a>
    <a class="adm-chip adm-chip--link {{ in_array($activeStatus, ['transport_quote', 'transport_approval'], true) ? 'is-active' : '' }}" @if(in_array($activeStatus, ['transport_quote', 'transport_approval'], true)) aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'transport_quote']) }}">Ongkos</a>
    <a class="adm-chip adm-chip--link {{ in_array($activeStatus, ['design_initial', 'design_revision', 'design_pending'], true) ? 'is-active' : '' }}" @if(in_array($activeStatus, ['design_initial', 'design_revision', 'design_pending'], true)) aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'design_initial']) }}">Desain</a>
    <a class="adm-chip adm-chip--link {{ $activeStatus === 'review' ? 'is-active' : '' }}" @if($activeStatus === 'review') aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'review']) }}">Tinjau manual</a>
    <a class="adm-chip adm-chip--link {{ $activeStatus === 'return_overdue' ? 'is-active' : '' }}" @if($activeStatus === 'return_overdue') aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'return_overdue']) }}">Lewat kembali</a>
</nav>

<form class="adm-toolbar" method="get" action="{{ route('admin.rental.orders') }}" data-adm-reveal="filter">
    <div class="adm-field-grid">
        <div class="adm-field adm-field--wide">
            <label for="adm-order-q">Cari referensi, pelanggan, penerima, atau papan</label>
            <input id="adm-order-q" type="search" autocomplete="off" name="q" maxlength="160" value="{{ $filters['q'] ?? '' }}" data-adm-search placeholder="Contoh: papan mawar / nama penerima">
        </div>
        <div class="adm-field">
            <label for="adm-order-status">Status atau tugas</label>
            <select id="adm-order-status" name="status">
                <option value="">Semua</option>
                @foreach(\App\Support\RentalLabels::UNIT_STATUS as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="adm-field">
            <label for="adm-order-from">Tanggal acara mulai</label>
            <input id="adm-order-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="adm-field">
            <label for="adm-order-to">Tanggal acara sampai</label>
            <input id="adm-order-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>
    </div>
    <div class="adm-row">
        <button class="btn" type="submit">Tampilkan</button>
        <a class="btn secondary" href="{{ route('admin.rental.orders') }}">Atur ulang filter</a>
        <span class="adm-help adm-push">{{ $orders->total() }} pesanan ditemukan</span>
    </div>
    <p class="adm-toolbar__hint">Pilihan langsung berlaku saat diubah. Daftar memuat pesanan yang punya minimal satu unit sesuai filter; antrean menunggu diurutkan menurut batas konfirmasi terdekat.</p>
</form>

@php
    $hapusFilter = fn (string $kunci) => route('admin.rental.orders', array_filter(array_merge($filters, [$kunci => null])));
    $chipFilter = [];
    if (! empty($filters['status'])) {
        $chipFilter[] = ['label' => 'Status: '.(\App\Support\RentalLabels::UNIT_STATUS[$filters['status']] ?? $filters['status']), 'remove' => $hapusFilter('status')];
    }
    if (! empty($filters['q'])) {
        $chipFilter[] = ['label' => 'Cari: '.$filters['q'], 'remove' => $hapusFilter('q')];
    }
    if (! empty($filters['from'])) {
        $chipFilter[] = ['label' => 'Acara mulai '.\Carbon\Carbon::parse($filters['from'])->format('d/m/Y'), 'remove' => $hapusFilter('from')];
    }
    if (! empty($filters['to'])) {
        $chipFilter[] = ['label' => 'Acara sampai '.\Carbon\Carbon::parse($filters['to'])->format('d/m/Y'), 'remove' => $hapusFilter('to')];
    }
@endphp
@include('layouts.component.admin._filter_state', ['chips' => $chipFilter, 'resetUrl' => route('admin.rental.orders')])

@forelse($orders as $order)
    @php
        $unitCount = $order->units->count();
        $pending = $order->units->where('status', 'pending')->count();
        $confirmed = $order->units->where('status', 'confirmed')->count();
        $cancelled = $order->units->where('status', 'cancelled')->count();
        $needsAttention = $order->units->contains(fn ($unit) => $unit->status === 'pending' || $unit->payment_review_hold);
    @endphp
    <article class="adm-card adm-card--hover" data-adm-reveal="stores" aria-labelledby="adm-order-{{ $order->id }}">
        <div class="adm-row adm-row--between adm-row--top">
            <div class="adm-min0">
                <span class="adm-kicker">Pesanan</span>
                <h2 id="adm-order-{{ $order->id }}">{{ $order->customer?->name ?? 'Pelanggan tidak tersedia' }}</h2>
                <p class="muted adm-note-line">Referensi: <strong>{{ $order->reference }}</strong></p>
            </div>
            <div class="adm-row adm-row--end adm-row--tight">
                <span class="adm-chip">{{ $unitCount }} unit</span>
                @if($pending)<span class="adm-badge adm-badge--pending">{{ $pending }} menunggu</span>@endif
                @if($confirmed)<span class="adm-badge adm-badge--confirmed">{{ $confirmed }} dikonfirmasi</span>@endif
                @if($cancelled)<span class="adm-badge adm-badge--cancelled">{{ $cancelled }} dibatalkan</span>@endif
                @if($needsAttention)<span class="adm-badge adm-badge--info adm-badge--live">perlu tindakan</span>@endif
            </div>
        </div>

        <div class="adm-row adm-row--between adm-mt-3">
            <div class="adm-min0">
                @php
                    $namaPapan = $order->units->pluck('product_name')->filter()->unique();
                    $sisaPapan = max(0, $namaPapan->count() - 2);
                @endphp
                <p class="muted adm-flat">
                    {{ $namaPapan->take(2)->implode(' · ') }}{{ $sisaPapan > 0 ? ' · dan '.$sisaPapan.' papan lain' : '' }}
                </p>
                <p class="adm-help adm-meta">
                    Batas konfirmasi: {{ $order->confirmation_deadline->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                </p>
            </div>
            <a class="btn secondary adm-btn--sm" href="{{ route('admin.rental.orders.show', $order) }}">Lihat detail pesanan</a>
        </div>
    </article>
@empty
    <div class="adm-empty" data-adm-reveal="empty">
        <span class="adm-empty__icon" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'folder-open', 'size' => 24])</span></span>
        <span class="adm-empty__title">Belum ada pesanan yang cocok</span>
        <p>Coba longgarkan filter tanggal atau status, atau mulai dari pesanan terbaru.</p>
        <a class="btn secondary" href="{{ route('admin.rental.orders') }}">Tampilkan semua pesanan</a>
    </div>
@endforelse

<div class="pagination">{{ $orders->links() }}</div>
@endsection
