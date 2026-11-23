<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ringkasan pesanan · Berkah Kreatif</title>
<style>
*{box-sizing:border-box}body{font-family:system-ui,sans-serif;color:#301722;background:#faf4f3;margin:0;padding:24px}.sheet{max-width:850px;margin:auto;background:#fff;padding:28px;border-radius:16px}h1{color:#85092d;font-size:1.5rem}h2{font-size:1.1rem}p{line-height:1.5;overflow-wrap:anywhere}.unit{border:1px solid #e8d6dc;border-radius:12px;padding:16px;margin:16px 0;break-inside:avoid}.actions{display:flex;gap:12px;flex-wrap:wrap}button,a.action{font:inherit;background:#85092d;color:#fff;border:0;border-radius:10px;padding:10px 16px;text-decoration:none;cursor:pointer}.muted{color:#715963}.note{background:#fff3e8;padding:12px;border-radius:10px}@media(max-width:480px){body{padding:8px}.sheet{padding:16px}}@media print{body{background:white;padding:0}.sheet{max-width:none;padding:0}.actions{display:none}.unit{border-color:#999}}
</style>@include('layouts.component._theme_head')
</head><body><main class="sheet"><div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button><a class="action" href="{{ auth()->user()->role === 'admin' ? route('admin.rental.orders') : route('rental.orders.show', $order) }}">Kembali</a></div>
<h1>Berkah Kreatif · Ringkasan pesanan</h1><p><strong>Referensi:</strong> {{ $order->reference }}<br><strong>Pelanggan:</strong> {{ $order->customer?->name ?? 'Pelanggan tidak tersedia' }}<br><strong>Dibuat:</strong> {{ $order->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
@if($order->voucher_code)<br><strong>Voucher:</strong> {{ $order->voucher_code }} · potongan Rp {{ number_format((int) $order->discount_amount, 0, ',', '.') }}@endif</p><p class="note">Dokumen ini adalah ringkasan uji coba, bukan bukti pembayaran atau jaminan ketersediaan. Status dapat berubah; periksa detail pesanan terbaru.</p>
@foreach($order->units as $unit)
@php
  $verified = $unit->payments->where('status', 'verified')->sum('amount') - $unit->payments->sum(fn ($payment) => $payment->refunds->sum('amount'));
  $pending = $unit->payments->where('status', 'pending')->sum('amount');
  $total = $unit->payableAmount();
@endphp
<section class="unit"><h2>Unit {{ $loop->iteration }} · {{ $unit->product_name }}</h2>
<p><strong>Status:</strong> {{ ['pending'=>'Menunggu konfirmasi', 'confirmed'=>'Dikonfirmasi', 'cancelled'=>'Dibatalkan'][$unit->status] }}<br>@if($unit->starts_on->isSameDay($unit->event_on))<strong>Acara dan penerimaan:</strong> {{ $unit->event_on->format('d/m/Y') }}@else<strong>Penerimaan:</strong> {{ $unit->starts_on->format('d/m/Y') }}<br><strong>Acara:</strong> {{ $unit->event_on->format('d/m/Y') }}@endif<br><strong>Pengembalian:</strong> {{ $unit->ends_on->format('d/m/Y') }}</p>
<p><strong>Penerima:</strong> {{ $unit->recipient_name }}<br><strong>Ucapan:</strong> {{ $unit->greeting }}<br><strong>Permintaan desain:</strong> {{ $unit->design_notes ?: '-' }}</p>
<p><strong>Penerimaan:</strong> {{ $unit->delivery_method === 'delivery' ? 'Diantar penjual · '.$unit->delivery_address : 'Diambil di outlet' }}<br><strong>Pengembalian:</strong> {{ $unit->return_method === 'collection' ? 'Diambil kembali penjual · '.$unit->return_address : 'Dikembalikan ke outlet' }}</p>
@if($unit->package_name)<p><strong>Paket:</strong> {{ $unit->package_name }} · {{ $unit->package_days }} hari</p>@endif
<p><strong>Biaya sewa:</strong> Rp {{ number_format($unit->rental_amount, 0, ',', '.') }}@if($unit->discount_amount > 0)<br><strong>Potongan voucher (sewa):</strong> −Rp {{ number_format($unit->discount_amount, 0, ',', '.') }}@endif<br><strong>Ongkos:</strong> @if(!$unit->transportRequired()) Rp 0 @elseif($unit->transport_quote === null) Menunggu penawaran @else Rp {{ number_format($unit->transport_quote, 0, ',', '.') }} · {{ $unit->transport_approved_at ? 'Disetujui' : 'Menunggu persetujuan' }} @endif
@if($unit->transport_discount_amount > 0)<br><strong>Potongan voucher (ongkos):</strong> −Rp {{ number_format($unit->transport_discount_amount, 0, ',', '.') }}@endif<br><strong>Tagihan:</strong> {{ $total === null ? 'Belum disepakati' : 'Rp '.number_format($total, 0, ',', '.') }}<br><strong>Pembayaran terverifikasi bersih:</strong> Rp {{ number_format($verified, 0, ',', '.') }}<br><strong>Bukti menunggu verifikasi:</strong> Rp {{ number_format($pending, 0, ',', '.') }}<br><strong>Batas pelunasan:</strong> {{ $unit->plannedPaymentDeadline()->format('d/m/Y H:i') }} WIB</p>
@if($unit->status === 'cancelled')<p class="muted">Unit dibatalkan: {{ $unit->cancel_reason }}</p>@endif
</section>
@endforeach
<p class="muted">Waktu menggunakan WIB. Pembayaran dinyatakan lunas hanya setelah diverifikasi; pengembalian dana mengurangi jumlah terverifikasi bersih.</p></main>@include('layouts.component._ui_kit')
</body></html>
