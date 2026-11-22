@extends('catalog.layout')
@section('title', 'Detail unit')
@section('content')
<p><a href="{{ route('rental.orders.show', $order) }}">← Kembali ke unit pesanan</a></p>
<h1>Detail unit</h1><p class="muted">Pesanan {{ $order->reference }}</p>
<div class="notice">Penjadwal harus berjalan untuk memproses tenggat. Jangan gunakan transaksi nyata selama pengujian.</div><div class="items">@foreach(collect([$unit]) as $unit)
@php
    $netPaid = $unit->payments->where('status', 'verified')->sum('amount') - $unit->payments->sum(fn ($payment) => $payment->refunds->sum('amount'));
    $pendingAmount = $unit->payments->where('status', 'pending')->sum('amount');
    $payable = $unit->payableAmount();
    $deadlinePolicy = app(\App\Services\RentalPaymentDeadline::class);
    $lateEligible = $deadlinePolicy->eligibleForLatePayment($unit);
    $canSubmit = $lateEligible || ($unit->status === 'confirmed' && (! $deadlinePolicy->isDue($unit) || $unit->payment_review_hold));
    $latestPreview = $unit->previews->first();
@endphp
<article class="card body"><h2>Unit {{ $unitNumber }} · {{ $unit->product_name }}</h2>
<section class="notice" aria-label="Langkah berikutnya untuk unit {{ $unitNumber }}"><strong>Langkah berikutnya</strong>
@if($unit->status === 'pending')<p>Menunggu konfirmasi admin. Stok belum dicadangkan.</p>
@elseif($unit->status === 'cancelled')<p>Unit ini dibatalkan. Periksa alasan dan tindak lanjut di bawah.</p>@if($lateEligible && $payable !== null && $netPaid + $pendingAmount < $payable)<p><a href="#payment-{{ $unit->id }}">Lihat pilihan pembayaran terlambat</a></p>@endif
@else
@if($unit->transportRequired() && !$unit->transport_approved_at)
<p>{{ $unit->transport_quote === null ? 'Menunggu penawaran ongkos dari admin.' : 'Penawaran ongkos siap diperiksa dan disetujui.' }}</p><p><a href="#transport-{{ $unit->id }}">Lihat ongkos</a></p>
@endif
@if($unit->progress === 'waiting')
@if(!$latestPreview)<p>Menunggu preview desain dari admin.</p>
@elseif($latestPreview->status === 'pending')<p>Preview desain siap diperiksa. Setujui atau minta revisi.</p><p><a href="#design-{{ $unit->id }}">Periksa preview</a></p>
@elseif($latestPreview->status === 'revision')<p>Permintaan revisi sudah dikirim; tunggu preview berikutnya.</p>
@elseif($latestPreview->status === 'approved')<p>Desain disetujui; tunggu admin memulai pengerjaan.</p>@endif
@endif
@if($payable !== null && $netPaid < $payable)
@if($pendingAmount > 0)<p>Ada bukti pembayaran menunggu pemeriksaan admin.</p>@endif
@if($canSubmit && $netPaid + $pendingAmount < $payable)<p>Anda dapat membayar bertahap. Periksa sisa dan batas pelunasan.</p><p><a href="#payment-{{ $unit->id }}">Lihat pembayaran</a></p>@endif
@endif
@if($unit->progress !== 'waiting')<p>Perkembangan unit dapat dilihat di bawah.</p>@endif
@endif
</section><details class="unit-section"><summary>Jadwal, ucapan, dan serah terima</summary><p>@if($unit->starts_on->isSameDay($unit->event_on)) Acara dan penerimaan: {{ $unit->event_on->format('d/m/Y') }} @else Penerimaan: {{ $unit->starts_on->format('d/m/Y') }} · Acara: {{ $unit->event_on->format('d/m/Y') }} @endif · Pengembalian: {{ $unit->ends_on->format('d/m/Y') }}</p><p>Untuk {{ $unit->recipient_name }} · {{ $unit->greeting }}</p><p>Permintaan desain: {{ $unit->design_notes ?: 'Tidak ada catatan tambahan' }}</p><p>Penerimaan: {{ $unit->delivery_method === 'delivery' ? 'Diantar penjual' : 'Diambil di outlet' }}@if($unit->delivery_method === 'delivery') · {{ $unit->delivery_address }}@endif</p><p>Pengembalian: {{ $unit->return_method === 'collection' ? 'Diambil kembali penjual' : 'Dikembalikan ke outlet' }}@if($unit->return_method === 'collection') · {{ $unit->return_address }}@endif</p>@if($unit->package_name)<p>Paket: {{ $unit->package_name }} · {{ $unit->package_days }} hari</p>@endif<p>Biaya sewa: Rp {{ number_format($unit->rental_amount, 0, ',', '.') }} (belum termasuk ongkos)</p><p>Status: {{ ['pending'=>'Menunggu konfirmasi','confirmed'=>'Dikonfirmasi','cancelled'=>'Dibatalkan'][$unit->status] }}</p>@if($unit->cancel_reason)<p>Alasan: {{ $unit->cancel_reason }}</p>@if($unit->cancel_responsibility === 'seller')<p>Jika ada pembayaran, admin akan menindaklanjuti pengembalian dana.</p>@endif @endif
@if($unit->delivery_university)<p>Universitas pengantaran: {{ $unit->delivery_university }} · Rp {{ number_format($unit->delivery_university_fee, 0, ',', '.') }}</p>@endif @if($unit->return_university)<p>Universitas pengambilan kembali: {{ $unit->return_university }} · Rp {{ number_format($unit->return_university_fee, 0, ',', '.') }}</p>@endif
</details>
@if($unit->transportRequired())<details class="unit-section" id="transport-{{ $unit->id }}" @if(!$unit->transport_approved_at || $errors->any()) open @endif><summary>Ongkos antar dan/atau penjemputan</summary>@if($unit->transport_quote === null)<p>Menunggu penawaran admin.</p>@else<p>Penawaran: Rp {{ number_format($unit->transport_quote, 0, ',', '.') }} · {{ $unit->transport_approved_at ? 'Disetujui' : 'Menunggu persetujuan' }}</p>@if($unit->transport_quote_note)<p>{{ $unit->transport_quote_note }}</p>@endif @if(!$unit->transport_approved_at && $unit->status !== 'cancelled')<form method="post" action="{{ route('rental.transport.approve', $unit) }}">@csrf<input type="hidden" name="version" value="{{ $unit->transport_quote_version }}"><button class="btn">Setujui ongkos</button></form>@endif @endif </details>@endif
@if($unit->payment_review_hold)<p class="notice">Bukti pembayaran atau jadwal khusus sedang ditinjau manual oleh admin.</p>@endif
@if($unit->cancel_code === 'payment_timeout')<p class="notice">Unit dibatalkan karena tenggat. Pada hari penerimaan Anda dapat mengirim bukti; kelanjutan memerlukan pembayaran terverifikasi dan pemeriksaan stok oleh admin. Setelah hari itu tidak dapat dilanjutkan.</p>@endif
<details class="unit-section"><summary>Riwayat dan perkembangan</summary>
@include('rental.cancellation-history')
@include('rental.progress', ['admin' => false])
</details>
<details class="unit-section" id="payment-{{ $unit->id }}" @if(($canSubmit && $payable !== null && $netPaid + $pendingAmount < $payable) || $errors->any()) open @endif><summary>Pembayaran</summary><p>Batas pelunasan: {{ $unit->plannedPaymentDeadline()->format('d/m/Y H:i') }} WIB. Bukti yang dikirim tepat waktu dapat menahan pembatalan sambil menunggu verifikasi.</p>@if($canSubmit && $payable !== null && $netPaid + $pendingAmount < $payable)
<h4>1. Bayar melalui QRIS</h4>
@if($paymentSettings?->qris_path)<p>Pindai QRIS berikut melalui aplikasi pembayaran Anda. Pastikan nama penerima benar. Simpan bukti untuk dikirim pada unit ini.</p><img src="{{ route('rental.payment.qris') }}" alt="QRIS Berkah Kreatif" style="width:100%;max-width:260px;height:auto">@else<p class="notice">QRIS belum tersedia. Hubungi admin sebelum melakukan pembayaran.</p>@endif
@if($paymentSettings?->whatsapp_number)<p><a class="btn secondary" href="https://wa.me/{{ $paymentSettings->whatsapp_number }}?text={{ rawurlencode('Halo Berkah Kreatif, saya ingin menanyakan metode pembayaran lain untuk pesanan '.$order->reference.'.') }}" target="_blank" rel="noopener noreferrer">Tanya metode lain via WhatsApp</a></p>@else<p class="muted">Kontak WhatsApp untuk metode lain belum diatur oleh admin.</p>@endif
@if($paymentSettings?->instructions)<p>Petunjuk tambahan:</p><p style="white-space:pre-wrap">{{ $paymentSettings->instructions }}</p>@endif
<p><strong>2. Kirim bukti pembayaran di bawah ini.</strong> Pembayaran baru dinyatakan lunas setelah admin memverifikasi bukti.</p>
@elseif($unit->status === 'pending')<p class="muted">Tunggu konfirmasi admin sebelum melakukan pembayaran.</p>@elseif($payable === null)<p class="muted">Setujui ongkos terlebih dahulu sebelum mengirim pembayaran.</p>@endif<p>Tagihan: {{ $unit->payableAmount() === null ? 'Menunggu persetujuan ongkos' : 'Rp '.number_format($unit->payableAmount(), 0, ',', '.') }} · Terverifikasi bersih: Rp {{ number_format($netPaid, 0, ',', '.') }} · Bukti menunggu: Rp {{ number_format($pendingAmount, 0, ',', '.') }}</p>
@if($payable !== null)<p>Status pembayaran: @if($payable === 0) Tidak ada tagihan @elseif($netPaid >= $payable) Lunas terverifikasi @else Belum lunas · Sisa terverifikasi Rp {{ number_format(max(0, $payable - $netPaid), 0, ',', '.') }} @endif</p>@endif
@forelse($unit->payments as $payment)<div class="card body" style="margin:10px 0"><p>Rp {{ number_format($payment->amount, 0, ',', '.') }} · {{ ['pending'=>'Menunggu verifikasi','verified'=>'Terverifikasi','rejected'=>'Ditolak'][$payment->status] }}</p><a href="{{ route('rental.payment.proof', $payment) }}">Lihat bukti</a> @foreach($payment->corrections as $correction)<p>Koreksi nominal: Rp {{ number_format($correction->old_amount, 0, ',', '.') }} → Rp {{ number_format($correction->new_amount, 0, ',', '.') }} · {{ $correction->reason }}</p>@endforeach
@foreach($payment->refunds as $refund)<p>Pengembalian Rp {{ number_format($refund->amount, 0, ',', '.') }} · {{ $refund->refunded_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB · {{ $refund->reason }}</p>@endforeach
@if($payment->admin_note)<p>Alasan: {{ $payment->admin_note }}</p>@endif</div>@empty<p class="muted">Belum ada pembayaran.</p>@endforelse
@if($canSubmit && $payable !== null && $netPaid + $pendingAmount < $payable)<form class="stack" method="post" action="{{ route('rental.payment.store', $unit) }}" enctype="multipart/form-data">@csrf<label>Nominal pembayaran (Rp)<input type="number" name="amount" min="1" max="{{ min(100000000, $payable - $netPaid - $pendingAmount) }}" required></label><label>Bukti pembayaran (JPG, PNG, WebP; maksimal 5 MB)<input type="file" name="proof" accept="image/jpeg,image/png,image/webp" required></label><p><button class="btn">Kirim bukti</button></p></form>@endif
</details>
<details class="unit-section" id="design-{{ $unit->id }}" @if(($latestPreview && $latestPreview->status === 'pending') || $errors->any()) open @endif><summary>Preview desain</summary>@forelse($unit->previews as $preview)<div class="card body" style="margin:10px 0"><p>Versi {{ $preview->version }} · {{ ['pending'=>'Menunggu respons','revision'=>'Revisi diminta','approved'=>'Disetujui','superseded'=>'Diganti versi baru'][$preview->status] }}</p><img src="{{ route('rental.design.image', $preview) }}" alt="Preview desain versi {{ $preview->version }}" style="max-width:100%;max-height:360px"><p>{{ $preview->admin_note }}</p>@if($preview->customer_note)<p>Catatan: {{ $preview->customer_note }}</p>@endif
@if($preview->status === 'pending' && $unit->status === 'confirmed')<form class="stack" method="post" action="{{ route('rental.design.respond', $preview) }}">@csrf<label>Catatan revisi (wajib jika meminta revisi)<textarea name="note" maxlength="2000"></textarea></label><div class="row"><button class="btn" name="decision" value="approved">Setujui desain</button><button class="btn secondary" name="decision" value="revision">Minta revisi</button></div></form>@endif</div>@empty<p class="muted">Belum ada preview.</p>@endforelse</details></article>@endforeach</div>@endsection
