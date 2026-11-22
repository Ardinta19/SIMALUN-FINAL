@extends('catalog.layout')
@section('title', 'Beranda')
@section('content')
<div class="bk-dashboard">
<section class="bk-dashboard-welcome">
  <div><span class="bk-kicker">RUANG PELANGGAN</span><h1>Halo, {{ explode(' ', auth()->user()->name)[0] }}</h1><p>Semua yang kamu butuhkan untuk menyiapkan dan memantau momen istimewa, di satu tempat.</p><a href="{{ route('welcome') }}" class="btn">Jelajahi papan bunga <span aria-hidden="true">↗</span></a></div>
  <div class="bk-dashboard-flower" aria-hidden="true"><span>✿</span></div>
</section>
<div class="bk-dashboard-grid">
<section class="bk-dashboard-main">
  <div class="bk-section-heading"><div><span class="bk-kicker">PESANANMU</span><h2>Aktivitas terbaru</h2></div><a href="{{ route('rental.orders.index') }}">Lihat semua →</a></div>
  <div class="bk-order-list">
    @forelse($orders as $order)
      <a href="{{ route('rental.orders.show', $order) }}" class="bk-order-row"><span class="bk-order-mark" aria-hidden="true">✿</span><span class="bk-order-info"><strong>{{ $order->units_count }} unit papan bunga</strong><small>{{ $order->reference }}</small></span><span class="bk-order-date">{{ $order->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y') }} <b aria-hidden="true">→</b></span></a>
    @empty
      <div class="bk-empty"><span aria-hidden="true">✿</span><h3>Belum ada pesanan</h3><p>Mulai dengan memilih papan yang cocok untuk momenmu.</p><a href="{{ route('welcome') }}">Lihat katalog →</a></div>
    @endforelse
  </div>
</section>
<aside class="bk-dashboard-side">
  <div class="bk-stats"><div><strong>{{ $totalOrders }}</strong><span>Pesanan</span></div><div><strong>{{ $activeUnits }}</strong><span>Unit aktif</span></div></div>
  <section class="bk-quick"><h2>Akses cepat</h2><a href="{{ route('cart.index') }}">Keranjang <span>→</span></a><a href="{{ route('customer.notifications') }}">Notifikasi @if(isset($unreadNotif) && $unreadNotif > 0)<small>{{ $unreadNotif }} belum dibaca</small>@endif <span>→</span></a><a href="{{ route('customer.profile') }}">Profil <span>→</span></a><a href="{{ route('customer.help') }}">Pusat bantuan <span>→</span></a></section>
</aside>
</div>
<p class="muted">Sistem masih dalam pengujian dan belum untuk transaksi nyata.</p>
</div>
@endsection
