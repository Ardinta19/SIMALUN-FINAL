<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Lembar harian · Berkah Kreatif</title>
<style>*{box-sizing:border-box}body{margin:0;background:#faf4f3;color:#301722;font-family:system-ui,sans-serif;padding:24px}.sheet{max-width:850px;margin:auto;background:#fff;padding:28px;border-radius:16px}.actions{display:flex;gap:12px;flex-wrap:wrap}button,.action{background:#85092d;color:#fff;border:0;border-radius:10px;padding:10px 16px;font:inherit;text-decoration:none;cursor:pointer}.unit{border:1px solid #e8d6dc;border-radius:12px;padding:16px;margin:12px 0;break-inside:avoid}h1{font-size:1.5rem;color:#85092d}h2{font-size:1.15rem}p{line-height:1.5;overflow-wrap:anywhere}.notice{padding:12px;background:#fff3e8;border-radius:10px}@media(max-width:480px){body{padding:8px}.sheet{padding:16px}}@media print{body{background:#fff;padding:0}.sheet{padding:0}.actions{display:none}.unit{border-color:#999}}</style></head>
<body><main class="sheet"><div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button><a class="action" href="{{ route('admin.rental.schedule', ['month' => $date->format('Y-m')]) }}">Kembali ke jadwal</a></div>
<h1>Berkah Kreatif · Lembar serah terima</h1><p><strong>{{ $date->locale('id')->translatedFormat('l, d F Y') }}</strong> · WIB</p>
<p class="notice">Hanya unit berstatus dikonfirmasi yang ditampilkan. Daftar ini adalah rencana, bukan bukti serah terima atau pembayaran. Periksa status dan pembayaran terbaru sebelum bertindak. Dokumen memuat data pribadi: simpan dengan aman.</p>
@foreach(['Penerimaan' => $receiving, 'Pengembalian' => $returning] as $heading => $items)
<section><h2>{{ $heading }} · {{ $items->count() }} unit</h2>
@forelse($items as $unit)
<div class="unit"><strong>{{ $unit->product_name }} · unit #{{ $unit->id }}</strong><p>Referensi: {{ $unit->order->reference }}<br>Pelanggan: {{ $unit->order->customer?->name ?? 'Pelanggan tidak tersedia' }}<br>Penerima: {{ $unit->recipient_name }}</p>
@if($heading === 'Penerimaan')<p>Metode: {{ $unit->delivery_method === 'delivery' ? 'Diantar penjual' : 'Diambil di outlet' }}@if($unit->delivery_method === 'delivery')<br>Alamat: {{ $unit->delivery_address }}@endif</p>
@else<p>Metode: {{ $unit->return_method === 'collection' ? 'Diambil kembali penjual' : 'Dikembalikan ke outlet' }}@if($unit->return_method === 'collection')<br>Alamat: {{ $unit->return_address }}@endif</p>@endif
<p>Perkembangan tercatat: {{ ['waiting'=>'Menunggu pengerjaan', 'production'=>'Dalam pengerjaan', 'ready'=>'Siap diserahkan', 'handed_over'=>'Sudah diserahkan', 'returned'=>'Sudah dikembalikan'][$unit->progress] ?? $unit->progress }}</p></div>
@empty<p>Tidak ada unit terkonfirmasi untuk kegiatan ini.</p>@endforelse
</section>
@endforeach
</main></body></html>
