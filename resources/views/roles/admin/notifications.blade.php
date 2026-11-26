@extends('layouts.admin')

@section('back', route('dashboard.admin'))
@section('title', 'Notifikasi')
@section('crumbs')<strong>Notifikasi</strong>@endsection

{{-- Halaman ini dulu berdiri sendiri (header "hero" + `_navbar_admin`). Sekarang
     memakai shell admin yang sama dengan halaman lain: sidebar, topbar, remah
     roti, dan toast dari `layouts.admin`. Isi dan tindakannya tidak berubah. --}}
@section('content')
<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Kotak masuk admin</span>
        <h1>Notifikasi</h1>
        <p class="adm-page-sub">Antrian operasional yang menunggu konfirmasi dan kabar sistem terbaru.</p>
    </div>
    <div class="adm-actions">
        @if(auth()->user()->unreadNotifications()->exists())
            <form method="post" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <button class="adm-btn adm-btn--ghost" type="submit">Tandai semua dibaca</button>
            </form>
        @endif
    </div>
</div>

<section class="adm-card" data-adm-reveal="queue" aria-labelledby="notif-queue-title" style="margin-bottom:var(--adm-gap)">
    <div class="adm-card__head">
        <h2 id="notif-queue-title" class="adm-row" style="gap:8px;margin:0">
            @include('layouts.component._icon', ['name' => 'orders', 'size' => 20])
            Antrian operasional terbaru
        </h2>
        <span class="adm-chip">{{ $notifikasiOperasional->count() }} pesanan</span>
    </div>

    @forelse($notifikasiOperasional as $order)
        <div class="adm-row adm-row--between" style="gap:12px;padding:12px 0;border-top:1px solid var(--adm-line)">
            <div style="min-width:0">
                <strong style="display:block;overflow-wrap:anywhere">{{ $order->product_name }} · {{ $order->order->reference }}</strong>
                <p class="adm-help" style="margin:2px 0 8px">
                    {{ $order->order->customer?->name ?? 'Pelanggan tidak tersedia' }} ·
                    {{ $order->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                </p>
                <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.orders.show', $order->order) }}">Lihat pesanan</a>
            </div>
            <span class="adm-badge adm-badge--pending">Menunggu konfirmasi</span>
        </div>
    @empty
        <div class="adm-empty">
            <span class="adm-empty__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'orders', 'size' => 24])</span>
            <p class="adm-empty__title">Belum ada aktivitas pesanan</p>
            <p class="adm-help" style="margin:0">Pesanan baru akan muncul di sini begitu pelanggan mengirim checkout.</p>
        </div>
    @endforelse
</section>

<section class="adm-card" data-adm-reveal="system" aria-labelledby="notif-system-title">
    <div class="adm-card__head">
        <h2 id="notif-system-title" class="adm-row" style="gap:8px;margin:0">
            @include('layouts.component._icon', ['name' => 'notifikasi', 'size' => 20])
            Notifikasi sistem
        </h2>
        <span class="adm-chip">{{ $notifications->total() }} kabar</span>
    </div>

    @forelse($notifications as $notif)
        <a href="{{ route('admin.notifications.open', $notif->id) }}"
           class="adm-notif {{ is_null($notif->read_at) ? 'is-unread' : '' }}"
           @if(is_null($notif->read_at)) aria-label="{{ $notif->data['title'] ?? 'Notifikasi' }} (belum dibaca)" @endif>
            <span class="adm-notif__icon" aria-hidden="true">
                @include('layouts.component._icon', ['name' => 'notifikasi', 'size' => 16])
            </span>
            <span style="min-width:0">
                <span class="adm-notif__title">{{ $notif->data['title'] ?? 'Notifikasi' }}</span>
                <span class="adm-notif__msg">{{ $notif->data['message'] ?? 'Tidak ada rincian.' }}</span>
            </span>
            <span class="adm-notif__time">{{ $notif->created_at->diffForHumans() }}</span>
        </a>
    @empty
        <div class="adm-empty">
            <span class="adm-empty__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'notifikasi', 'size' => 24])</span>
            <p class="adm-empty__title">Belum ada notifikasi sistem</p>
            <p class="adm-help" style="margin:0">Kabar pembayaran, pratinjau desain, dan perubahan pesanan akan tampil di sini.</p>
        </div>
    @endforelse

    @if($notifications->hasPages())
        <div class="adm-row" style="justify-content:center;margin-top:16px">
            {{ $notifications->links('vendor.pagination.token-simple') }}
        </div>
    @endif
</section>
@endsection
