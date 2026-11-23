@extends('layouts.admin')
@section('title', 'Jadwal sewa')
@section('crumbs')<a href="{{ route('admin.rental.orders') }}">Pesanan sewa</a><span class="adm-crumbs__sep" aria-hidden="true">/</span><strong>Jadwal sewa</strong>@endsection
@section('content')
@php
    $prevMonth = $month->copy()->subMonth()->format('Y-m');
    $nextMonth = $month->copy()->addMonth()->format('Y-m');
    $progressLabels = ['waiting'=>'Menunggu pengerjaan', 'production'=>'Dalam pengerjaan', 'ready'=>'Siap diserahkan', 'handed_over'=>'Sudah diserahkan', 'returned'=>'Sudah dikembalikan'];
@endphp

<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Operasional</span>
        <h1>Jadwal sewa</h1>
        <p class="adm-page-sub">Setiap hari dalam periode sewa ditampilkan, termasuk hari penerimaan dan pengembalian. Unit menunggu sudah menahan tanggal sampai tenggat konfirmasi; unit terkonfirmasi menahannya secara tetap. Perkiraan jadwal bukan bukti serah terima aktual.</p>
    </div>
    <div class="adm-actions">
        <a class="btn secondary" href="{{ route('admin.rental.orders') }}">Pesanan sewa</a>
    </div>
</div>

<form class="adm-toolbar" method="get" action="{{ route('admin.rental.schedule') }}" data-adm-reveal="filter">
    <div class="adm-field-grid">
        <div class="adm-field">
            <label for="adm-schedule-month">Bulan</label>
            <input id="adm-schedule-month" type="month" name="month" value="{{ $month->format('Y-m') }}" required>
        </div>
        <div class="adm-field">
            <label for="adm-schedule-status">Status unit</label>
            <select id="adm-schedule-status" name="status">
                <option value="">Semua status aktif</option>
                @foreach(['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi'] as $value => $label)
                    <option value="{{ $value }}" @selected(($input['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="adm-field">
            <label for="adm-schedule-activity">Kegiatan</label>
            <select id="adm-schedule-activity" name="activity">
                <option value="">Seluruh periode sewa</option>
                @foreach(['receiving' => 'Penerimaan', 'event' => 'Acara', 'returning' => 'Pengembalian'] as $value => $label)
                    <option value="{{ $value }}" @selected(($input['activity'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="adm-row">
        <button class="btn" type="submit">Tampilkan</button>
        <a class="btn secondary" href="{{ route('admin.rental.schedule') }}">Reset</a>
        <span class="adm-row" style="margin-left:auto;gap:8px">
            <a class="btn secondary adm-btn--sm" href="{{ route('admin.rental.schedule', array_merge($input, ['month' => $prevMonth])) }}">← Bulan sebelumnya</a>
            <a class="btn secondary adm-btn--sm" href="{{ route('admin.rental.schedule', array_merge($input, ['month' => $nextMonth])) }}">Bulan berikutnya →</a>
        </span>
    </div>
</form>

<div class="adm-row" style="justify-content:space-between;margin:6px 0 14px" data-adm-reveal="month">
    <h2 style="margin:0">{{ $month->locale('id')->translatedFormat('F Y') }}</h2>
    <span class="adm-chip">{{ collect($days)->sum(fn ($day) => count($day['units'])) }} unit terjadwal</span>
</div>

<div class="adm-grid adm-grid--auto">
    @foreach($days as $day)
        @php $today = $day['date']->toDateString(); @endphp
        <section class="adm-card adm-card--hover" data-adm-reveal="days">
            <div class="adm-card__head">
                <div>
                    <span class="adm-kicker">{{ $day['date']->locale('id')->translatedFormat('l') }}</span>
                    <h3 style="margin:2px 0 0">{{ $day['date']->locale('id')->translatedFormat('d F Y') }}</h3>
                </div>
                <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.schedule.daily-sheet', ['date' => $today]) }}">Lembar serah terima</a>
            </div>

            @forelse($day['units'] as $unit)
                <div style="padding:10px 0;border-top:1px solid var(--adm-line)">
                    <div class="adm-row" style="justify-content:space-between;gap:8px">
                        <strong>{{ $unit->product_name }}</strong>
                        <span class="adm-badge {{ $unit->status === 'confirmed' ? 'adm-badge--confirmed' : 'adm-badge--pending' }}">
                            {{ $unit->status === 'confirmed' ? 'Dikonfirmasi · stok dicadangkan' : 'Menunggu · tanggal ditahan' }}
                        </span>
                    </div>
                    <p class="muted" style="margin:4px 0 0">
                        {{ $unit->order->customer?->name ?? 'Pelanggan tidak tersedia' }} · {{ $unit->recipient_name }} · Acara {{ $unit->event_on->format('d/m/Y') }}
                    </p>
                    @if($unit->starts_on->toDateString() === $today)
                        <p style="margin:4px 0 0">Penerimaan hari ini: {{ $unit->delivery_method === 'delivery' ? 'Diantar penjual · '.$unit->delivery_address : 'Diambil di outlet' }}</p>
                    @endif
                    @if($unit->event_on->toDateString() === $today)
                        <p style="margin:4px 0 0">Acara hari ini</p>
                    @endif
                    @if($unit->ends_on->toDateString() === $today)
                        <p style="margin:4px 0 0">Pengembalian hari ini: {{ $unit->return_method === 'collection' ? 'Diambil kembali penjual · '.$unit->return_address : 'Dikembalikan ke outlet' }}</p>
                    @endif
                    <div class="adm-row" style="justify-content:space-between;margin-top:6px">
                        <span class="adm-chip">{{ $progressLabels[$unit->progress] ?? $unit->progress }}</span>
                        <a href="{{ route('admin.rental.orders.show', $unit->order) }}">Buka pesanan</a>
                    </div>
                </div>
            @empty
                <p class="muted" style="margin:10px 0 0">Tidak ada unit terjadwal.</p>
            @endforelse
        </section>
    @endforeach
</div>
@endsection
