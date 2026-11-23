@extends('catalog.layout')
@section('title', 'Detail pesanan')
@section('content')
<p><a class="bk-back" href="{{ route('rental.orders.index') }}">← Pesanan saya</a></p>
<h1>Detail pesanan</h1>
<p class="muted">Referensi: {{ $order->reference }}</p>
@if($order->voucher_code)<p class="muted">Voucher {{ $order->voucher_code }} · potongan Rp {{ number_format((int) $order->discount_amount, 0, ',', '.') }} sudah termasuk pada tagihan tiap unit.</p>@endif
<p class="muted">Batas konfirmasi: {{ $order->confirmation_deadline->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
<p><a class="btn secondary" href="{{ route('rental.receipt', $order) }}">Ringkasan cetak</a></p>
<div class="notice">Pilih unit untuk memeriksa ongkos, desain, pembayaran, dan perkembangannya. Tanggal ditahan sejak pesanan dikirim sampai admin mengonfirmasi.</div>
<section class="card body bk-feedback-card"><h2>Rating pesanan</h2>
@php
    // Samakan dengan aturan server: unit tuntas bila kembali, dibatalkan, atau
    // tanggal acaranya sudah lewat lebih dari masa tenggang penutupan otomatis.
    $ratingGrace = now('Asia/Jakarta')->startOfDay()->subDays(\App\Services\RentalAutoClose::GRACE_DAYS);
    $ratingSettled = fn ($u) => $u->status === 'cancelled'
        || ($u->status === 'confirmed' && $u->progress === 'returned')
        || ($u->status === 'confirmed' && $u->event_on !== null && $u->event_on->lessThanOrEqualTo($ratingGrace));
@endphp
@if($rating)<p>Rating kamu: {{ $rating }} dari 5 bintang.</p>@elseif($order->units->contains(fn ($u) => $u->status !== 'cancelled') && $order->units->every($ratingSettled))
<form method="post" action="{{ route('rental.order.rating', $order) }}">@csrf<fieldset class="bk-star-rating" @error('rating') aria-invalid="true" aria-describedby="order-rating-error" @enderror><legend>Nilai pengalaman pesanan</legend><div class="bk-star-rating__options">@for($i = 5; $i >= 1; $i--)<input type="radio" id="order-rating-{{ $i }}" name="rating" value="{{ $i }}" @checked(is_scalar(old('rating')) && (string) old('rating') === (string) $i) @if($i === 5) required @endif><label for="order-rating-{{ $i }}"><span aria-hidden="true">★</span><span class="bk-sr-only">{{ $i }} bintang</span></label>@endfor</div></fieldset>@error('rating')<p class="bk-field-error" id="order-rating-error">{{ $message }}</p>@enderror<p><button class="btn secondary">Simpan rating pesanan</button></p></form>
@else<p class="muted">Rating tersedia setelah semua unit selesai atau dibatalkan, dan setidaknya satu unit telah dikembalikan.</p>@endif
</section>
<section class="card body bk-feedback-card"><h2>Suka publik</h2><p>{{ $likeCount }} suka. Tautan hanya menampilkan jumlah suka, tanpa data pesanan atau identitasmu.</p>
@if($likeShare)<div class="bk-share-link"><label for="share-link">Tautan untuk dibagikan</label><div class="bk-share-actions"><input id="share-link" readonly value="{{ route('rental.likes.public', $likeShare->token) }}"><button type="button" class="btn secondary" data-copy-share hidden>Salin tautan</button></div><p class="muted" role="status" data-copy-status>Salin tautan secara manual jika diperlukan.</p></div><p class="muted">Siapa pun yang memiliki tautan dapat melihat jumlah suka. Pelanggan yang masuk dapat memberi atau membatalkan suka.</p><form method="post" action="{{ route('rental.order.share', $order) }}">@csrf<input type="hidden" name="enabled" value="0"><button class="btn secondary">Nonaktifkan tautan</button></form>
@else<p class="muted">Tautan belum aktif. Kamu dapat menonaktifkannya kapan saja. Jumlah suka tetap tersimpan jika tautan dinonaktifkan.</p><form method="post" action="{{ route('rental.order.share', $order) }}">@csrf<input type="hidden" name="enabled" value="1"><button class="btn secondary">Buat tautan suka</button></form>@endif</section>
<div class="items">
@forelse($order->units as $unit)
<article class="card body">
    <h2>Unit {{ $loop->iteration }} · {{ $unit->product_name }}</h2>
    <p><strong>{{ ['pending'=>'Menunggu konfirmasi', 'confirmed'=>'Dikonfirmasi', 'cancelled'=>'Dibatalkan'][$unit->status] }}</strong></p>
    <p>@if($unit->starts_on->isSameDay($unit->event_on)) Acara dan penerimaan: {{ $unit->event_on->format('d/m/Y') }} @else Penerimaan: {{ $unit->starts_on->format('d/m/Y') }} · Acara: {{ $unit->event_on->format('d/m/Y') }} @endif · Pengembalian: {{ $unit->ends_on->format('d/m/Y') }}</p>
    <p class="muted">Penerima: {{ $unit->recipient_name }}</p>
    @if($unit->status === 'pending')<p class="muted">Menunggu konfirmasi admin. Tanggal sudah ditahan sampai tenggat konfirmasi.</p>
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
<script src="{{ asset('js/rental-share.js') }}" defer></script>
@endsection
