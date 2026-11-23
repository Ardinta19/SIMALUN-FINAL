@extends('catalog.layout')
@section('title', 'Jadwal sewa')
@section('content')
<p><a class="bk-back" href="{{ route('admin.rental.orders') }}">← Pesanan sewa</a></p>
<h1>Jadwal sewa</h1>
<p class="muted">Jadwal menampilkan setiap hari dalam periode sewa termasuk hari penerimaan dan pengembalian. Unit menunggu belum mencadangkan stok; unit terkonfirmasi sudah dicadangkan. Perkiraan jadwal bukan bukti serah terima aktual.</p>
<form class="stack card body" method="get" action="{{ route('admin.rental.schedule') }}">
<label>Bulan<input type="month" name="month" value="{{ $month->format('Y-m') }}" required></label>
<div class="row"><label>Status unit<select name="status"><option value="">Semua status aktif</option>@foreach(['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi'] as $value => $label)<option value="{{ $value }}" @selected(($input['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
<label>Kegiatan<select name="activity"><option value="">Seluruh periode sewa</option>@foreach(['receiving' => 'Penerimaan', 'event' => 'Acara', 'returning' => 'Pengembalian'] as $value => $label)<option value="{{ $value }}" @selected(($input['activity'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label></div>
<p><button class="btn">Tampilkan</button> <a class="btn secondary" href="{{ route('admin.rental.schedule') }}">Reset</a></p>
<div class="row"><a class="btn secondary" href="{{ route('admin.rental.schedule', array_merge($input, ['month' => $month->subMonth()->format('Y-m')])) }}">← Bulan sebelumnya</a><a class="btn secondary" href="{{ route('admin.rental.schedule', array_merge($input, ['month' => $month->addMonth()->format('Y-m')])) }}">Bulan berikutnya →</a></div></form>
<h2>{{ $month->locale('id')->translatedFormat('F Y') }}</h2>
<div class="items">@foreach($days as $day)<section class="card body"><h3>{{ $day['date']->locale('id')->translatedFormat('l, d F Y') }}</h3><p><a class="btn secondary" href="{{ route('admin.rental.schedule.daily-sheet', ['date' => $day['date']->toDateString()]) }}">Lembar serah terima hari ini</a></p>@forelse($day['units'] as $unit)
@php $today = $day['date']->toDateString(); @endphp
<div style="padding:10px 0;border-bottom:1px solid #f0dfe1"><p><strong>{{ $unit->product_name }}</strong> · {{ $unit->status === 'confirmed' ? 'Dikonfirmasi · stok dicadangkan' : 'Menunggu · belum dicadangkan' }}</p><p class="muted">{{ $unit->order->customer?->name ?? 'Pelanggan tidak tersedia' }} · {{ $unit->recipient_name }} · Acara {{ $unit->event_on->format('d/m/Y') }}</p>
@if($unit->starts_on->toDateString() === $today)<p>Penerimaan hari ini: {{ $unit->delivery_method === 'delivery' ? 'Diantar penjual · '.$unit->delivery_address : 'Diambil di outlet' }}</p>@endif
@if($unit->event_on->toDateString() === $today)<p>Acara hari ini</p>@endif
@if($unit->ends_on->toDateString() === $today)<p>Pengembalian hari ini: {{ $unit->return_method === 'collection' ? 'Diambil kembali penjual · '.$unit->return_address : 'Dikembalikan ke outlet' }}</p>@endif
<p class="muted">{{ ['waiting'=>'Menunggu pengerjaan', 'production'=>'Dalam pengerjaan', 'ready'=>'Siap diserahkan', 'handed_over'=>'Sudah diserahkan', 'returned'=>'Sudah dikembalikan'][$unit->progress] ?? $unit->progress }}</p><a href="{{ route('admin.rental.orders.show', $unit->order) }}">Buka pesanan</a></div>@empty<p class="muted">Tidak ada unit terjadwal.</p>@endforelse</section>@endforeach</div>
@endsection
