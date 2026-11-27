@extends('catalog.layout')
@section('title', 'Notifikasi')
@section('content')
<div class="bk-inbox">
    <a data-smart-back class="bk-back" href="{{ route('customer.dashboard') }}">← Kembali ke beranda</a>
    <div class="bk-page-heading"><div><span class="bk-kicker">Kabar untukmu</span><h1>Notifikasi</h1><p class="muted">Pembaruan pesanan dan informasi akun ada di sini.</p></div>
        @if($notifications->count() > 0)<form method="post" action="{{ route('customer.notifications.read-all') }}">@csrf<button class="btn secondary" type="submit">Tandai semua dibaca</button></form>@endif
    </div>
    @if($notifications->count() > 0)
    <div class="bk-inbox-list" aria-label="Daftar notifikasi">
        @foreach($notifications as $notif)
        <a href="{{ route('customer.notifications.open', ['id' => $notif->id]) }}" class="bk-inbox-item {{ is_null($notif->read_at) ? 'is-unread' : '' }}">
            <span class="bk-inbox-icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'bell', 'size' => 20])</span>
            <span class="bk-inbox-content"><span class="bk-inbox-title">{{ $notif->data['title'] ?? 'Notifikasi' }} @if(is_null($notif->read_at))<small>Belum dibaca</small>@endif</span><span class="bk-inbox-message">{{ $notif->data['message'] ?? '' }}</span><time datetime="{{ $notif->created_at->toIso8601String() }}">{{ $notif->created_at->diffForHumans() }}</time></span>
            <span class="bk-inbox-arrow" aria-hidden="true">↗</span>
        </a>
        @endforeach
    </div>
    <div class="pagination">{{ $notifications->links('vendor.pagination.berkah') }}</div>
    @else
    <div class="bk-inbox-empty"><span aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></span><h2>Belum ada notifikasi</h2><p>Notifikasi pesananmu akan muncul di sini.</p><a href="{{ route('welcome') }}" class="btn secondary">Lihat katalog</a></div>
    @endif
</div>
@endsection
