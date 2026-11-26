@extends('catalog.layout')
@section('title', 'Detail unit')
@section('content')
<p><a data-smart-back class="bk-back" href="{{ route('rental.orders.show', $order) }}">← Kembali ke unit pesanan</a></p>
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
    $designPolicy = app(\App\Services\RentalDesignDeadline::class);
    $designDeadline = $designPolicy->deadline($unit);
    $designOverridden = $unit->designOverrideActive();
    $graceDeadline = $unit->cancel_code === 'payment_timeout' ? $deadlinePolicy->graceDeadline($unit) : null;
@endphp
<nav class="bk-jump bk-unit-jump" aria-label="Lompat ke bagian unit"><strong>Lihat bagian</strong><div><a href="#unit-info-{{ $unit->id }}">Jadwal &amp; ucapan</a>@if($unit->transportRequired())<a href="#transport-{{ $unit->id }}">Ongkos</a>@endif<a href="#unit-progress-{{ $unit->id }}">Perkembangan</a><a href="#payment-{{ $unit->id }}">Pembayaran</a><a href="#design-{{ $unit->id }}">Desain</a><a href="#rating-{{ $unit->id }}">Rating</a></div></nav><noscript><p class="muted">Setelah menuju bagian, buka judul bagian untuk melihat isinya.</p></noscript>
<article class="card body"><h2>Unit {{ $unitNumber }} · {{ $unit->product_name }}</h2>
@php $waAdmin = \App\Support\RentalWhatsApp::adminLink($order, $unit); @endphp
@if($waAdmin)<p><a class="btn secondary" href="{{ $waAdmin }}" target="_blank" rel="noopener noreferrer">Tanya admin via WhatsApp</a></p>@endif
<section class="notice" aria-label="Langkah berikutnya untuk unit {{ $unitNumber }}"><strong>Langkah berikutnya</strong>
@if($unit->status === 'pending')<p>Menunggu konfirmasi admin. Tanggal sudah ditahan sampai tenggat konfirmasi.</p>
@elseif($unit->status === 'cancelled')<p>Unit ini dibatalkan. Periksa alasan dan tindak lanjut di bawah.</p>@if($lateEligible && $payable !== null && $netPaid + $pendingAmount < $payable)<p><a class="btn secondary" href="#payment-{{ $unit->id }}">Lihat pilihan pembayaran terlambat</a></p>@endif
<form method="post" action="{{ route('rental.unit.reorder', $unit) }}">@csrf<p><button type="submit" class="btn secondary">Pesan ulang unit ini</button></p><p class="muted">Isian unit disalin ke keranjang; pilih tanggal baru di checkout.</p></form>
@else
@if($unit->transportRequired() && !$unit->transport_approved_at)
<p>{{ $unit->transport_quote === null ? 'Menunggu penawaran ongkos dari admin.' : 'Penawaran ongkos siap diperiksa dan disetujui.' }}</p><p><a class="btn secondary" href="#transport-{{ $unit->id }}">Lihat ongkos</a></p>
@endif
@if($unit->progress === 'waiting')
@if(!$latestPreview)<p>Menunggu pratinjau desain dari admin.</p>
@elseif($latestPreview->status === 'pending')@if($designOverridden)<p>Admin memulai pengerjaan dengan pratinjau ini tanpa persetujuan karena batas respons terlewati. Kamu tetap dapat menyetujui atau meminta revisi.</p>@else<p>Pratinjau desain siap diperiksa. Setujui atau minta revisi.</p>@if($designDeadline)<p class="muted">Batas respons desain: {{ $designDeadline->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB.</p>@endif
@endif<p><a class="btn secondary" href="#design-{{ $unit->id }}">Periksa pratinjau</a></p>
@elseif($latestPreview->status === 'revision')<p>Permintaan revisi sudah dikirim; tunggu pratinjau berikutnya.</p>
@elseif($latestPreview->status === 'approved')<p>Desain disetujui; tunggu admin memulai pengerjaan.</p>@endif
@endif
@if($payable !== null && $netPaid < $payable)
@if($pendingAmount > 0)<p>Ada bukti pembayaran menunggu pemeriksaan admin.</p>@endif
@if($canSubmit && $netPaid + $pendingAmount < $payable)<p>Anda dapat membayar bertahap. Periksa sisa dan batas pelunasan.</p><p><a class="btn secondary" href="#payment-{{ $unit->id }}">Lihat pembayaran</a></p>@endif
@endif
@if($unit->progress !== 'waiting')<p>{{ ['production' => 'Papan sedang dikerjakan.', 'ready' => 'Papan siap diserahkan.', 'handed_over' => 'Papan sudah diserahkan.', 'returned' => 'Papan sudah dikembalikan.'][$unit->progress] ?? 'Perkembangan unit dapat dilihat di bawah.' }}</p><p><a class="btn secondary" href="#unit-progress-{{ $unit->id }}">Lihat perkembangan</a>@if($unit->progress === 'returned' && !$productRating) · <a href="#rating-{{ $unit->id }}">Beri rating produk</a>@endif</p>@endif
@endif
</section><details class="unit-section" id="unit-info-{{ $unit->id }}"><summary>Jadwal, ucapan, dan serah terima</summary><p>@if($unit->starts_on->isSameDay($unit->event_on)) Acara dan penerimaan: {{ $unit->event_on->format('d/m/Y') }} @else Penerimaan: {{ $unit->starts_on->format('d/m/Y') }} · Acara: {{ $unit->event_on->format('d/m/Y') }} @endif · Pengembalian: {{ $unit->ends_on->format('d/m/Y') }}</p><p>Untuk {{ $unit->recipient_name }} · {{ $unit->greeting }}</p><p>Permintaan desain: {{ $unit->design_notes ?: 'Tidak ada catatan tambahan' }}</p><p>Penerimaan: {{ $unit->delivery_method === 'delivery' ? 'Diantar penjual' : 'Diambil di outlet' }}@if($unit->delivery_method === 'delivery') · {{ $unit->delivery_address }}@endif</p><p>Pengembalian: {{ $unit->return_method === 'collection' ? 'Diambil kembali penjual' : 'Dikembalikan ke outlet' }}@if($unit->return_method === 'collection') · {{ $unit->return_address }}@endif</p>@if($unit->package_name)<p>Paket: {{ $unit->package_name }} · {{ $unit->package_days }} hari</p>@endif<p>Biaya sewa: Rp {{ number_format($unit->rental_amount, 0, ',', '.') }} (belum termasuk ongkos)</p><p>Status: {{ ['pending'=>'Menunggu konfirmasi','confirmed'=>'Dikonfirmasi','cancelled'=>'Dibatalkan'][$unit->status] ?? $unit->status }}</p>@if($unit->cancel_reason)<p>Alasan: {{ $unit->cancel_reason }}</p>@if($unit->cancel_responsibility === 'seller')<p>Jika ada pembayaran, admin akan menindaklanjuti pengembalian dana.</p>@endif @endif
@if($unit->delivery_university)<p>Universitas pengantaran: {{ $unit->delivery_university }} · Rp {{ number_format($unit->delivery_university_fee, 0, ',', '.') }}</p>@endif @if($unit->return_university)<p>Universitas pengambilan kembali: {{ $unit->return_university }} · Rp {{ number_format($unit->return_university_fee, 0, ',', '.') }}</p>@endif
@if($unit->status === 'confirmed' && $unit->progress === 'waiting' && (int) $netPaid === 0)
<details><summary>Perbaiki nama, ucapan, atau alamat</summary><p class="muted">Perubahan dicatat di riwayat unit dan dikirim ke admin. Setelah ada pembayaran terverifikasi atau desain disetujui, perubahan nama/ucapan/permintaan desain harus melalui admin.</p>
<form class="stack" method="post" action="{{ route('rental.unit.update', $unit) }}">@csrf
<label>Nama penerima<input placeholder="Contoh: Ibu Sari Wulandari" name="recipient_name" maxlength="160" value="{{ is_scalar(old('recipient_name', $unit->recipient_name)) ? old('recipient_name', $unit->recipient_name) : '' }}" required @error('recipient_name') aria-invalid="true" aria-describedby="unit-recipient-error" @enderror>@error('recipient_name')<p class="bk-field-error" id="unit-recipient-error" role="alert">{{ $message }}</p>@enderror</label>
<label>Ucapan<textarea placeholder="Contoh: Turut berduka cita, semoga keluarga yang ditinggalkan diberi ketabahan." name="greeting" maxlength="1000" required @error('greeting') aria-invalid="true" aria-describedby="unit-greeting-error" @enderror>{{ is_scalar(old('greeting', $unit->greeting)) ? old('greeting', $unit->greeting) : '' }}</textarea>@error('greeting')<p class="bk-field-error" id="unit-greeting-error" role="alert">{{ $message }}</p>@enderror</label>
<label>Permintaan desain (opsional)<textarea placeholder="Contoh: Dominan putih dan emas, tanpa bunga plastik." name="design_notes" maxlength="2000">{{ is_scalar(old('design_notes', $unit->design_notes)) ? old('design_notes', $unit->design_notes) : '' }}</textarea></label>
@if($unit->delivery_method === 'delivery')<label>Alamat pengantaran<textarea placeholder="Contoh: Jl. Merdeka No. 12, RT 03, Palembang" name="delivery_address" maxlength="1000" required @error('delivery_address') aria-invalid="true" aria-describedby="unit-delivery-error" @enderror>{{ is_scalar(old('delivery_address', $unit->delivery_address)) ? old('delivery_address', $unit->delivery_address) : '' }}</textarea>@error('delivery_address')<p class="bk-field-error" id="unit-delivery-error" role="alert">{{ $message }}</p>@enderror</label>@endif
@if($unit->return_method === 'collection')<label>Alamat pengambilan kembali<textarea placeholder="Contoh: Jl. Merdeka No. 12, RT 03, Palembang" name="return_address" maxlength="1000" required @error('return_address') aria-invalid="true" aria-describedby="unit-return-error" @enderror>{{ is_scalar(old('return_address', $unit->return_address)) ? old('return_address', $unit->return_address) : '' }}</textarea>@error('return_address')<p class="bk-field-error" id="unit-return-error" role="alert">{{ $message }}</p>@enderror</label>@endif
@error('unit')<p class="bk-field-error" role="alert">{{ $message }}</p>@enderror
<p><button type="submit" class="btn secondary">Simpan perubahan data unit</button></p>
</form></details>
@endif
</details>
@if(in_array($unit->status, ['pending', 'confirmed'], true) && $unit->progress === 'waiting')
<details class="unit-section" id="cancel-{{ $unit->id }}" @if($errors->has('cancel')) open @endif><summary>Batalkan unit ini</summary>
<p class="muted">Pembatalan langsung melepas tanggal. DP mengikuti ketentuan dan tidak dikembalikan otomatis; bila sudah ada pembayaran, admin akan menindaklanjuti pengembalian dana secara manual.</p>
<form class="stack" method="post" action="{{ route('rental.cancel.customer', $unit) }}">@csrf
<label>Alasan pembatalan<textarea placeholder="Contoh: Acara ditunda ke bulan depan." name="reason" maxlength="500" required @error('reason') aria-invalid="true" aria-describedby="cancel-reason-error" @enderror>{{ is_scalar(old('reason')) ? old('reason') : '' }}</textarea></label>@error('reason')<p class="bk-field-error" id="cancel-reason-error" role="alert">{{ $message }}</p>@enderror
@error('cancel')<p class="bk-field-error" role="alert">{{ $message }}</p>@enderror
<label><input type="checkbox" name="confirmed" value="1" required> Saya memahami DP tidak dikembalikan dan tanggal akan dilepas.</label>
<p><button type="submit" class="btn secondary">Batalkan unit</button></p>
</form></details>
@endif
@if($unit->transportRequired())<details class="unit-section" id="transport-{{ $unit->id }}" @if(!$unit->transport_approved_at || $errors->hasAny(['transport', 'version'])) open @endif><summary>Ongkos antar dan/atau penjemputan</summary>
@php $lastTransportEvent = $unit->transportQuoteEvents->last(); @endphp
@if($unit->transport_quote === null)
<p>Menunggu penawaran admin.</p>
@if($lastTransportEvent && $lastTransportEvent->action === 'reject')<p class="muted">Penawaran versi {{ $lastTransportEvent->version }} ditolak pada {{ $lastTransportEvent->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB. Alasanmu: {{ $lastTransportEvent->note }}</p>@endif
@else
<p>Penawaran versi {{ $unit->transport_quote_version }}: Rp {{ number_format($unit->transport_quote, 0, ',', '.') }} · {{ $unit->transport_approved_at ? 'Disetujui' : 'Menunggu persetujuan' }}</p>
@if($unit->transport_quote_note)<p>{{ $unit->transport_quote_note }}</p>@endif
@if(!$unit->transport_approved_at && $unit->status !== 'cancelled')
<form class="stack" method="post" action="{{ route('rental.transport.approve', $unit) }}">@csrf<input type="hidden" name="version" value="{{ $unit->transport_quote_version }}"><p><button type="submit" class="btn">Setujui ongkos</button></p></form>
<form class="stack" method="post" action="{{ route('rental.transport.reject', $unit) }}">@csrf<input type="hidden" name="version" value="{{ $unit->transport_quote_version }}"><label>Ajukan keberatan atau tolak ongkos<textarea placeholder="Contoh: Ongkos ke lokasi ini di luar perkiraan saya." name="note" maxlength="500" required @error('transport') aria-invalid="true" aria-describedby="transport-error" @enderror>{{ is_scalar(old('note')) ? old('note') : '' }}</textarea></label>@error('transport')<p class="bk-field-error" id="transport-error" role="alert">{{ $message }}</p>@enderror<p class="muted">Setelah ditolak, admin dapat mengirim penawaran baru. Pembayaran belum dapat dikirim selama ongkos belum disetujui.</p><p><button type="submit" class="btn secondary">Tolak dan minta penawaran baru</button></p></form>
@endif
@endif
@if($unit->transportQuoteEvents->isNotEmpty())<h4>Riwayat penawaran</h4><ul>@foreach($unit->transportQuoteEvents as $event)<li>{{ ['quote' => 'Penawaran dikirim', 'approve' => 'Disetujui', 'reject' => 'Ditolak'][$event->action] ?? $event->action }} · versi {{ $event->version }}@if($event->amount !== null) · Rp {{ number_format($event->amount, 0, ',', '.') }}@endif · {{ $event->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
@if($event->note) — {{ $event->note }}@endif</li>@endforeach</ul>@endif
</details>@endif
@if($unit->payment_review_hold)<p class="notice">Bukti pembayaran atau jadwal khusus sedang ditinjau manual oleh admin.</p>@endif
@if($unit->cancel_code === 'payment_timeout')<p class="notice">Unit dibatalkan karena tenggat pelunasan. @if($graceDeadline)Anda masih dapat mengirim bukti paling lambat {{ $graceDeadline->format('d/m/Y H:i') }} WIB; kelanjutan memerlukan pembayaran terverifikasi dan pemeriksaan stok oleh admin. Setelah batas itu DP hangus dan unit tidak dapat dilanjutkan.@else Masa toleransi sudah berakhir; DP hangus dan unit tidak dapat dilanjutkan.@endif</p>@endif
<details class="unit-section" id="unit-progress-{{ $unit->id }}"><summary>Riwayat dan perkembangan</summary>
@include('rental.cancellation-history')
@include('rental.progress', ['admin' => false])
</details>
<details class="unit-section" id="payment-{{ $unit->id }}" @if(($canSubmit && $payable !== null && $netPaid + $pendingAmount < $payable) || $errors->hasAny(['amount', 'proof', 'payment'])) open @endif><summary>Pembayaran</summary><p>Batas pelunasan: {{ $unit->plannedPaymentDeadline()->format('d/m/Y H:i') }} WIB. Bukti yang dikirim tepat waktu dapat menahan pembatalan sambil menunggu verifikasi. Bila tenggat terlewati, bukti masih dapat dikirim sampai {{ $deadlinePolicy->graceDeadline($unit)->format('d/m/Y H:i') }} WIB untuk melanjutkan unit.</p>@if($canSubmit && $payable !== null && $netPaid + $pendingAmount < $payable)
<h4>1. Bayar melalui QRIS</h4>
@if($paymentSettings?->qris_path)<p>Pindai QRIS berikut melalui aplikasi pembayaran Anda. Pastikan nama penerima benar. Simpan bukti untuk dikirim pada unit ini.</p><img class="bk-qris" src="{{ route('rental.payment.qris') }}" alt="QRIS Berkah Kreatif" loading="lazy" decoding="async">@else<p class="notice">QRIS belum tersedia. Hubungi admin sebelum melakukan pembayaran.</p>@endif
@if($paymentSettings?->whatsapp_number)<p><a class="btn secondary" href="https://wa.me/{{ $paymentSettings->whatsapp_number }}?text={{ rawurlencode('Halo Berkah Kreatif, saya ingin menanyakan metode pembayaran lain untuk pesanan '.$order->reference.'.') }}" target="_blank" rel="noopener noreferrer">Tanya metode lain via WhatsApp</a></p>@else<p class="muted">Kontak WhatsApp untuk metode lain belum diatur oleh admin.</p>@endif
@if($paymentSettings?->instructions)<p>Petunjuk tambahan:</p><p style="white-space:pre-wrap">{{ $paymentSettings->instructions }}</p>@endif
<p><strong>2. Kirim bukti pembayaran di bawah ini.</strong> Pembayaran baru dinyatakan lunas setelah admin memverifikasi bukti.</p>
@elseif($unit->status === 'pending')<p class="muted">Tunggu konfirmasi admin sebelum melakukan pembayaran.</p>@elseif($payable === null)<p class="muted">Setujui ongkos terlebih dahulu sebelum mengirim pembayaran.</p>@endif<p>Tagihan: {{ $unit->payableAmount() === null ? 'Menunggu persetujuan ongkos' : 'Rp '.number_format($unit->payableAmount(), 0, ',', '.') }} · Terverifikasi bersih: Rp {{ number_format($netPaid, 0, ',', '.') }} · Bukti menunggu: Rp {{ number_format($pendingAmount, 0, ',', '.') }}</p>
@if($unit->addons->isNotEmpty())
@php
    $addonText = $unit->addons->map(fn ($addon) => $addon->label())->implode(', ');
@endphp
<p>Item tambahan: {{ $addonText }} · Rp {{ number_format((int) $unit->addon_amount, 0, ',', '.') }}</p>
@endif
@if($unit->totalDiscount() > 0)
<p>Potongan voucher{{ $order->voucher_code ? ' '.$order->voucher_code : '' }}: −Rp {{ number_format($unit->totalDiscount(), 0, ',', '.') }} · Harga sewa setelah potongan Rp {{ number_format($unit->rentalAfterDiscount(), 0, ',', '.') }}@if($unit->transport_discount_amount > 0) · Ongkos setelah potongan Rp {{ number_format($unit->transportAfterDiscount(), 0, ',', '.') }}@endif</p>
@endif
@if($payable !== null)<p>Status pembayaran: @if($payable === 0) Tidak ada tagihan @elseif($netPaid >= $payable) Lunas terverifikasi @else Belum lunas · Sisa terverifikasi Rp {{ number_format(max(0, $payable - $netPaid), 0, ',', '.') }} @endif</p>@endif
@forelse($unit->payments as $payment)<div class="card body bk-row-card"><p>Rp {{ number_format($payment->amount, 0, ',', '.') }} · {{ ['pending'=>'Menunggu verifikasi','verified'=>'Terverifikasi','rejected'=>'Ditolak'][$payment->status] ?? $payment->status }}</p><a class="btn secondary" href="{{ route('rental.payment.proof', $payment) }}">Lihat bukti</a> @foreach($payment->corrections as $correction)<p>Koreksi nominal: Rp {{ number_format($correction->old_amount, 0, ',', '.') }} → Rp {{ number_format($correction->new_amount, 0, ',', '.') }} · {{ $correction->reason }}</p>@endforeach
@foreach($payment->refunds as $refund)<p>Pengembalian Rp {{ number_format($refund->amount, 0, ',', '.') }} · {{ $refund->refunded_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB · {{ $refund->reason }}</p>@endforeach
@if($payment->admin_note)<p>Alasan: {{ $payment->admin_note }}</p>@endif</div>@empty<p class="muted">Belum ada pembayaran.</p>@endforelse
@if($canSubmit && $payable !== null && $netPaid + $pendingAmount < $payable)@php $remaining = $payable - $netPaid - $pendingAmount; @endphp
<form class="stack" method="post" action="{{ route('rental.payment.store', $unit) }}" enctype="multipart/form-data">@csrf<p class="muted">Sisa tagihan unit ini: <strong>Rp {{ number_format($remaining, 0, ',', '.') }}</strong>. Pembayaran sebagian (DP) diperbolehkan; isi nominal yang kamu kirim.</p><label for="payment-amount">Nominal pembayaran (Rp)</label><input placeholder="Contoh: 250000" id="payment-amount" type="number" name="amount" value="{{ is_scalar(old('amount')) ? old('amount') : '' }}" min="1" max="{{ min(100000000, $payable - $netPaid - $pendingAmount) }}" required @error('amount') aria-invalid="true" aria-describedby="amount-error" @enderror>@error('amount')<p class="bk-field-error" id="amount-error" role="alert">{{ $message }}</p>@enderror<p><button type="button" class="btn secondary" data-fill-remaining="{{ min(100000000, $remaining) }}" hidden>Isi sisa tagihan</button></p><label for="payment-proof">Bukti pembayaran (JPG, PNG, WebP; maksimal 5 MB)</label><input id="payment-proof" type="file" name="proof" accept="image/jpeg,image/png,image/webp" required @error('proof') aria-invalid="true" aria-describedby="proof-error" @enderror>@error('proof')<p class="bk-field-error" id="proof-error" role="alert">{{ $message }}</p>@enderror @if($errors->hasAny(['amount', 'proof', 'payment']))<p class="muted">Pilih ulang berkas bukti sebelum mengirim kembali; browser tidak menyimpannya setelah halaman dimuat ulang.</p>@endif<p><button type="submit" class="btn">Kirim bukti</button></p></form>@endif
</details>
<details class="unit-section" id="design-{{ $unit->id }}" @if(($latestPreview && $latestPreview->status === 'pending') || $errors->hasAny(['decision', 'note', 'design'])) open @endif><summary>Pratinjau desain</summary>
@if($designDeadline && $latestPreview && $latestPreview->status === 'pending')<p>Batas respons desain: {{ $designDeadline->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB. Setelah batas ini admin boleh memulai pengerjaan dengan catatan tanpa persetujuan.</p>@endif
@if($designOverridden)<p class="notice">Admin memulai pengerjaan dengan pratinjau versi {{ $unit->design_override_version }} tanpa persetujuan karena batas respons terlewati. Catatan admin: {{ $unit->design_override_note }}</p>@endif
@forelse($unit->previews as $preview)<div class="card body bk-row-card"><p>Versi {{ $preview->version }} · {{ ['pending'=>'Menunggu respons','revision'=>'Revisi diminta','approved'=>'Disetujui','superseded'=>'Diganti versi baru'][$preview->status] ?? $preview->status }}</p><img src="{{ route('rental.design.image', $preview) }}" alt="Pratinjau desain versi {{ $preview->version }}" loading="lazy" decoding="async" style="max-width:100%;max-height:360px"><p>{{ $preview->admin_note }}</p>@if($preview->customer_note)<p>Catatan: {{ $preview->customer_note }}</p>@endif
@if($preview->status === 'pending' && $unit->status === 'confirmed')<form class="stack" method="post" action="{{ route('rental.design.respond', $preview) }}" data-design-form>@csrf<label for="design-note-{{ $preview->id }}">Catatan revisi (wajib jika meminta revisi)</label><textarea id="design-note-{{ $preview->id }}" placeholder="Contoh: Tolong ganti pita jadi hitam dan tambahkan nama &quot;Bapak Ahmad&quot;." name="note" maxlength="2000" @error('note') aria-invalid="true" aria-describedby="design-note-error-{{ $preview->id }}" @enderror>{{ is_scalar(old('note')) ? old('note') : '' }}</textarea>@error('note')<p class="bk-field-error" id="design-note-error-{{ $preview->id }}" role="alert">{{ $message }}</p>@enderror<div class="row"><button type="submit" class="btn" name="decision" value="approved">Setujui desain</button><button type="submit" class="btn secondary" name="decision" value="revision" data-needs-note>Minta revisi</button></div></form>@endif</div>@empty<p class="muted">Belum ada pratinjau.</p>@endforelse</details>
<details class="unit-section" id="rating-{{ $unit->id }}" @if((!$productRating && $unit->status === 'confirmed' && $unit->progress === 'returned') || $errors->has('rating')) open @endif><summary>Rating produk</summary>
@if($productRating)<p>Rating kamu untuk unit ini: {{ $productRating }} dari 5 bintang.</p>
@elseif($unit->status === 'confirmed' && $unit->progress === 'returned')<p class="muted">Berikan penilaian setelah unit dikembalikan. Rating produk akan ditampilkan sebagai rata-rata tanpa identitas.</p><form method="post" action="{{ route('rental.product.rating', [$order, $unit]) }}">@csrf<fieldset class="bk-star-rating" @error('rating') aria-invalid="true" aria-describedby="rating-error" @enderror><legend>Nilai produk ini</legend><div class="bk-star-rating__options">@for($i = 5; $i >= 1; $i--)<input type="radio" id="rating-{{ $unit->id }}-{{ $i }}" name="rating" value="{{ $i }}" @checked(is_scalar(old('rating')) && (string) old('rating') === (string) $i) @if($i === 5) required @endif><label for="rating-{{ $unit->id }}-{{ $i }}"><span class="bk-i" aria-hidden="true">@include('layouts.component._icon', ['name' => 'star', 'size' => 24])</span><span class="bk-sr-only">{{ $i }} bintang</span></label>@endfor</div></fieldset>@error('rating')<p class="bk-field-error" id="rating-error" role="alert">{{ $message }}</p>@enderror<p class="muted">Pilih 1 sampai 5 bintang. Ulasanmu membantu pelanggan lain.</p><p><button type="submit" class="btn secondary">Simpan rating produk</button></p></form>
@else<p class="muted">Rating tersedia setelah unit selesai dan dikembalikan.</p>@endif</details>
</article>@endforeach</div><script>
(() => {
    document.querySelectorAll('[data-design-form]').forEach(form => {
    const note = form.querySelector('textarea[name="note"]');
    if (!note) return;
    const approve = form.querySelector('[value="approved"]');
    const revision = form.querySelector('[data-needs-note]');
    if (revision) revision.addEventListener('click', () => { note.required = true; note.setAttribute('aria-required', 'true'); });
    if (approve) approve.addEventListener('click', () => { note.required = false; note.removeAttribute('aria-required'); });
});
document.querySelectorAll('[data-fill-remaining]').forEach(button => {
        const amount = document.getElementById('payment-amount');
        if (!amount) return;
        button.hidden = false;
        button.addEventListener('click', () => {
            amount.value = button.dataset.fillRemaining;
            amount.dispatchEvent(new Event('input', { bubbles: true }));
            amount.focus();
        });
    });
})();
</script>
@endsection
