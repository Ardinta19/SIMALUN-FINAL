@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Jadwal sewa')
@section('crumbs')<a href="{{ route('admin.rental.orders') }}">Pesanan sewa</a><span class="adm-crumbs__sep" aria-hidden="true">/</span><strong>Jadwal sewa</strong>@endsection
@section('content')
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
                    <p>Unit menunggu sudah menahan tanggal sampai tenggat konfirmasi; unit terkonfirmasi menahannya secara tetap. Jadwal ini perkiraan, bukan bukti serah terima aktual.</p>
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
    <p class="adm-toolbar__hint">Bulan, status, dan kegiatan langsung berlaku saat diubah.</p>
</form>

@php
    $hariIni = now('Asia/Jakarta')->toDateString();
    $hariTerisi = collect($days)->filter(fn ($day) => count($day['units']) > 0)->values();
    $hariKosong = collect($days)->reject(fn ($day) => count($day['units']) > 0)->values();
    $jumlahUnit = collect($days)->sum(fn ($day) => count($day['units']));
    $hariIniAda = collect($days)->first(fn ($day) => $day['date']->toDateString() === $hariIni);
    $hapusJadwal = fn (string $kunci) => route('admin.rental.schedule', array_filter(array_merge($input, ['month' => $month->format('Y-m'), $kunci => null])));
    $chipJadwal = [];
    if (! empty($input['status'])) {
        $chipJadwal[] = ['label' => 'Status: '.(\App\Support\RentalLabels::SCHEDULE_STATUS[$input['status']] ?? $input['status']), 'remove' => $hapusJadwal('status')];
    }
    if (! empty($input['activity'])) {
        $labelKegiatan = ['receiving' => 'Penerimaan', 'event' => 'Acara', 'returning' => 'Pengembalian'][$input['activity']] ?? $input['activity'];
        $chipJadwal[] = ['label' => 'Kegiatan: '.$labelKegiatan, 'remove' => $hapusJadwal('activity')];
    }
@endphp
@include('layouts.component.admin._filter_state', ['chips' => $chipJadwal, 'resetUrl' => route('admin.rental.schedule')])

<div class="adm-row adm-row--between adm-month-row" data-adm-reveal="month">
    <h2>{{ $month->locale('id')->translatedFormat('F Y') }}</h2>
    <span class="adm-row adm-row--tight">
        @if($hariIniAda)
            <a class="adm-btn adm-btn--ghost adm-btn--sm" href="#hari-ini">Hari ini</a>
        @endif
        <span class="adm-chip">{{ $jumlahUnit }} unit terjadwal · {{ $hariTerisi->count() }} tanggal terisi</span>
    </span>
</div>

@if($hariTerisi->isEmpty())
    <div class="adm-empty" data-adm-reveal="empty">
        <span class="adm-empty__icon" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'calendar', 'size' => 24])</span></span>
        <span class="adm-empty__title">Belum ada unit di {{ $month->locale('id')->translatedFormat('F Y') }}</span>
        <p>Tanggal baru terisi setelah pesanan dikonfirmasi. Lihat bulan lain, atau buka antrean pesanan yang menunggu konfirmasi.</p>
        <span class="adm-row adm-row--center adm-row--tight">
            <a class="btn secondary" href="{{ route('admin.rental.schedule', ['month' => $prevMonth]) }}">← {{ $month->copy()->subMonth()->locale('id')->translatedFormat('F Y') }}</a>
            <a class="btn secondary" href="{{ route('admin.rental.schedule', ['month' => $nextMonth]) }}">{{ $month->copy()->addMonth()->locale('id')->translatedFormat('F Y') }} →</a>
            <a class="btn" href="{{ route('admin.rental.orders', ['status' => 'pending']) }}">Pesanan menunggu konfirmasi</a>
        </span>
    </div>
@else
    <div class="adm-grid adm-grid--auto">
        @foreach($hariTerisi as $day)
            @php
                $tanggal = $day['date']->toDateString();
                $terima = $day['units']->filter(fn ($unit) => $unit->starts_on->toDateString() === $tanggal)->count();
                $kembali = $day['units']->filter(fn ($unit) => $unit->ends_on->toDateString() === $tanggal)->count();
                $acara = $day['units']->filter(fn ($unit) => $unit->event_on->toDateString() === $tanggal)->count();
            @endphp
            <section class="adm-card adm-card--hover" id="{{ $tanggal === $hariIni ? 'hari-ini' : 'hari-'.$tanggal }}" data-adm-reveal="days">
                <div class="adm-card__head">
                    <div>
                        <span class="adm-kicker">{{ $day['date']->locale('id')->translatedFormat('l') }}{{ $tanggal === $hariIni ? ' · hari ini' : '' }}</span>
                        <h3 class="adm-title--tight">{{ $day['date']->locale('id')->translatedFormat('d F Y') }}</h3>
                    </div>
                    <span class="adm-chip">{{ count($day['units']) }} unit</span>
                </div>

                @if($terima || $kembali || $acara)
                    <p class="adm-row adm-row--tight adm-flat">
                        @if($terima)<span class="adm-badge adm-badge--confirmed">{{ $terima }} serah terima</span>@endif
                        @if($acara)<span class="adm-badge adm-badge--info">{{ $acara }} acara</span>@endif
                        @if($kembali)<span class="adm-badge adm-badge--pending">{{ $kembali }} pengembalian</span>@endif
                    </p>
                @endif

                @foreach($day['units'] as $unit)
                    @php
                        $keadaan = \App\Support\RentalLabels::unitState($unit->status, $unit->progress);
                        $antar = $unit->delivery_method === 'delivery'
                            ? 'Diantar penjual'.($unit->delivery_address ? ' · '.$unit->delivery_address : '')
                            : 'Diambil sendiri';
                        $jemput = $unit->return_method === 'collection'
                            ? 'Diambil kembali penjual'.($unit->return_address ? ' · '.$unit->return_address : '')
                            : 'Dikembalikan sendiri';
                    @endphp
                    <div class="adm-divider-row">
                        <div class="adm-row adm-row--between adm-row--top adm-row--tight">
                            <div class="adm-unitline">
                                <span class="adm-unitline__name">{{ $unit->product_name }}</span>
                                <p class="adm-unitline__meta">{{ $unit->order->customer?->name ?? 'Pelanggan tidak tersedia' }} · Penerima {{ $unit->recipient_name }}</p>
                                <p class="adm-unitline__meta">Acara {{ $unit->event_on->format('d/m') }} · kembali {{ $unit->ends_on->format('d/m') }}</p>
                            </div>
                            <span class="adm-state" data-tone="{{ $keadaan['tone'] }}">{{ $keadaan['label'] }}</span>
                        </div>

                        {{-- Hanya kegiatan yang benar-benar terjadi pada tanggal kartu
                             ini. Dulu ada baris "Acara hari ini" yang mengulang
                             tanggal di baris atas tanpa menambah informasi. --}}
                        @if($unit->starts_on->toDateString() === $tanggal)
                            <p class="adm-unitline__today"><b>Serah terima</b><span>{{ $antar }}</span></p>
                        @endif
                        @if($unit->ends_on->toDateString() === $tanggal)
                            <p class="adm-unitline__today"><b>Pengembalian</b><span>{{ $jemput }}</span></p>
                        @endif

                        <div class="adm-row adm-row--between adm-mt-3">
                            <span class="adm-help">Tahap: {{ \App\Support\RentalLabels::progress($unit->progress) }}</span>
                            <a href="{{ route('admin.rental.orders.show', $unit->order) }}#unit-{{ $unit->id }}">Buka unit</a>
                        </div>
                    </div>
                @endforeach

                {{-- Lembar itu hanya berisi serah terima. Menawarkannya pada
                     hari yang isinya cuma pengembalian membuat admin membuka
                     halaman kosong dan mengira datanya hilang. --}}
                @if($terima)
                    <div class="adm-card__foot">
                        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.schedule.daily-sheet', ['date' => $tanggal]) }}">Lembar serah terima ({{ $terima }} unit)</a>
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    @if($hariKosong->isNotEmpty())
        <div class="adm-quietdays adm-mt-4" data-adm-reveal="quiet">
            <details>
                <summary>{{ $hariKosong->count() }} tanggal lain tanpa unit di bulan ini</summary>
                <ul>
                    @foreach($hariKosong as $day)
                        <li>{{ $day['date']->locale('id')->translatedFormat('d M') }}</li>
                    @endforeach
                </ul>
            </details>
        </div>
    @endif
@endif
@endsection
