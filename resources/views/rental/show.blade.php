@extends('catalog.layout')
@section('title', 'Detail pesanan')
@section('content')
<p><a class="bk-back" href="{{ route('rental.orders.index') }}">← Pesanan saya</a></p>
<h1>Detail pesanan</h1>
<p class="muted">Referensi: {{ $order->reference }}</p>
<p class="muted">Batas konfirmasi: {{ $order->confirmation_deadline->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
<p><a class="btn secondary" href="{{ route('rental.receipt', $order) }}">Ringkasan cetak</a></p>
<div class="notice">Pilih unit untuk memeriksa ongkos, desain, pembayaran, dan perkembangannya. Stok dicadangkan setelah admin mengonfirmasi.</div>
<div class="items">
@forelse($order->units as $unit)
<article class="card body">
    <h2>Unit {{ $loop->iteration }} · {{ $unit->product_name }}</h2>
    <p><strong>{{ ['pending'=>'Menunggu konfirmasi', 'confirmed'=>'Dikonfirmasi', 'cancelled'=>'Dibatalkan'][$unit->status] }}</strong></p>
    <p>@if($unit->starts_on->isSameDay($unit->event_on)) Acara dan penerimaan: {{ $unit->event_on->format('d/m/Y') }} @else Penerimaan: {{ $unit->starts_on->format('d/m/Y') }} · Acara: {{ $unit->event_on->format('d/m/Y') }} @endif · Pengembalian: {{ $unit->ends_on->format('d/m/Y') }}</p>
    <p class="muted">Penerima: {{ $unit->recipient_name }}</p>
    @if($unit->status === 'pending')<p class="muted">Menunggu konfirmasi admin. Stok belum dicadangkan.</p>
    @elseif($unit->status === 'cancelled')<p class="muted">Unit dibatalkan; buka detail untuk melihat tindak lanjut.</p>
    @else
        @if($unit->transportRequired() && $unit->transport_quote !== null && !$unit->transport_approved_at)<p><strong>Ongkos menunggu persetujuan Anda.</strong></p>@endif
        @if($unit->previews->first()?->status === 'pending')<p><strong>Preview desain menunggu respons Anda.</strong></p>@endif
        @if($unit->payments->where('status', 'pending')->isNotEmpty())<p class="muted">Bukti pembayaran sedang diperiksa.</p>@endif
    @endif
    <a class="btn secondary" href="{{ route('rental.orders.unit', [$order, $unit]) }}">Lihat detail unit {{ $loop->iteration }}</a>
</article>
@empty<p>Pesanan ini belum memiliki unit.</p>@endforelse
</div>
@endsection
