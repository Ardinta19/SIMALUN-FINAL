@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Jadwal sewa')
@section('crumbs')<a href="{{ route('admin.rental.orders') }}">Pesanan sewa</a><span class="adm-crumbs__sep" aria-hidden="true">/</span><strong>Jadwal sewa</strong>@endsection
@section('content')
<a data-smart-back class="bk-back" href="{{ route('admin.rental.orders') }}">← Pesanan sewa</a>
@php
    $prevMonth = $month->copy()->subMonth()->format('Y-m');
    $nextMonth = $month->copy()->addMonth()->format('Y-m');
@endphp

<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Operasional</span>
        <h1>Jadwal sewa</h1>
        <p class="adm-page-sub">Semua hari dalam periode sewa, termasuk penerimaan dan pengembalian.</p>
                <details class="adm-page-note">
                    <summary>Selengkapnya</summary>
                    <p>Setiap hari dalam periode sewa ditampilkan, termasuk hari penerimaan dan pengembalian. Unit menunggu sudah menahan tanggal sampai tenggat konfirmasi; unit terkonfirmasi menahannya secara tetap. Perkiraan jadwal bukan bukti serah terima aktual.</p>
                </details>
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
                @foreach(\App\Support\RentalLabels::SCHEDULE_STATUS as $value => $label)
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
        <a class="btn secondary" href="{{ route('admin.rental.schedule') }}">Atur ulang filter</a>
        <span class="adm-row adm-push adm-row--tight">
            <a class="btn secondary adm-btn--sm" href="{{ route('admin.rental.schedule', array_merge($input, ['month' => $prevMonth])) }}">← Bulan sebelumnya</a>
            <a class="btn secondary adm-btn--sm" href="{{ route('admin.rental.schedule', array_merge($input, ['month' => $nextMonth])) }}">Bulan berikutnya →</a>
        </span>
    </div>
</form>

<div class="adm-row adm-row--between adm-month-row" data-adm-reveal="month">
    <h2>{{ $month->locale('id')->translatedFormat('F Y') }}</h2>
    <span class="adm-chip">{{ collect($days)->sum(fn ($day) => count($day['units'])) }} unit terjadwal</span>
</div>

<div class="adm-grid adm-grid--auto">
    @foreach($days as $day)
        @php $today = $day['date']->toDateString(); @endphp
        <section class="adm-card adm-card--hover" data-adm-reveal="days">
            <div class="adm-card__head">
                <div>
                    <span class="adm-kicker">{{ $day['date']->locale('id')->translatedFormat('l') }}</span>
                    <h3 class="adm-title--tight">{{ $day['date']->locale('id')->translatedFormat('d F Y') }}</h3>
                </div>
                <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.schedule.daily-sheet', ['date' => $today]) }}">Lembar serah terima</a>
            </div>

            @forelse($day['units'] as $unit)
                <div class="adm-divider-row">
                    <div class="adm-row adm-row--between adm-row--tight">
                        <strong>{{ $unit->product_name }}</strong>
                        <span class="adm-badge {{ $unit->status === 'confirmed' ? 'adm-badge--confirmed' : 'adm-badge--pending' }}">
                            {{ $unit->status === 'confirmed' ? 'Dikonfirmasi · stok dicadangkan' : 'Menunggu · tanggal ditahan' }}
                        </span>
                    </div>
                    <p class="muted adm-meta">
                        {{ $unit->order->customer?->name ?? 'Pelanggan tidak tersedia' }} · {{ $unit->recipient_name }} · Acara {{ $unit->event_on->format('d/m/Y') }}
                    </p>
                    @if($unit->starts_on->toDateString() === $today)
                        <p class="adm-meta">Penerimaan hari ini: {{ $unit->delivery_method === 'delivery' ? 'Diantar penjual · '.$unit->delivery_address : 'Diambil sendiri' }}</p>
                    @endif
                    @if($unit->event_on->toDateString() === $today)
                        <p class="adm-meta">Acara hari ini</p>
                    @endif
                    @if($unit->ends_on->toDateString() === $today)
                        <p class="adm-meta">Pengembalian hari ini: {{ $unit->return_method === 'collection' ? 'Diambil kembali penjual · '.$unit->return_address : 'Dikembalikan sendiri' }}</p>
                    @endif
                    <div class="adm-row adm-row--between adm-mt-6">
                        <span class="adm-chip">{{ \App\Support\RentalLabels::progress($unit->progress) }}</span>
                        <a href="{{ route('admin.rental.orders.show', $unit->order) }}">Buka pesanan</a>
                    </div>
                </div>
            @empty
                <p class="muted adm-inline-note adm-inline-note--top">Tidak ada unit terjadwal.</p>
            @endforelse
        </section>
    @endforeach
</div>
@endsection
