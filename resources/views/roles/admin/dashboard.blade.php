@extends('layouts.admin')

@section('back', route('dashboard.admin'))
@section('title', 'Dasbor')
@section('crumbs')<strong>Ringkasan</strong>@endsection

{{-- Dulu halaman mandiri `<style>` ~300 baris + `_navbar_admin`. Semua gaya
     sekarang datang dari `berkah-admin.css` (shell admin), jadi topbar,
     sidebar, toast, dan tema gelap otomatis sama dengan halaman admin lain.
     Angka dan tautan status tidak berubah. --}}
@section('content')
@php
    $fullName = trim(auth()->user()->name ?? 'Admin');
    $adminName = explode(' ', $fullName)[0] ?: 'Admin';
    $initial = mb_strtoupper(mb_substr($adminName, 0, 1));
    $jam = (int) now('Asia/Jakarta')->format('H');
    $sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));
    $tanggal = now('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM YYYY');

    /* Baris yang angkanya nol tidak ditampilkan: daftar "perlu tindakan" yang
       penuh baris kosong membuat admin memindai tujuh baris untuk menemukan dua
       tugas. Urutan mengikuti tenggat — konfirmasi paling mendesak lebih dulu. */
    $attention = array_values(array_filter([
        [
            'route' => 'admin.rental.orders', 'status' => 'pending', 'icon' => 'orders', 'count' => $rentalPending,
            'text' => 'unit menunggu konfirmasi',
            'note' => $rentalPendingNearest
                ? 'batas konfirmasi terdekat '.$rentalPendingNearest->format('d/m H:i').' WIB'
                : null,
        ],
        [
            'route' => 'admin.rental.orders', 'status' => 'payment_pending', 'icon' => 'payment', 'count' => $rentalPaymentPending,
            'text' => 'bukti menunggu pemeriksaan',
            'note' => $paymentPendingOldest
                ? 'bukti tertua '.$paymentPendingOldest->format('d/m H:i').' WIB'
                : null,
        ],
        [
            'route' => 'admin.rental.orders', 'status' => 'return_overdue', 'icon' => 'clock', 'count' => $rentalReturnOverdue,
            'text' => 'unit sudah lewat tanggal kembali · tandai sudah dikembalikan', 'note' => null,
        ],
        [
            'route' => 'admin.rental.orders', 'status' => 'review', 'icon' => 'search', 'count' => $rentalReview,
            'text' => 'unit dalam peninjauan manual', 'note' => null,
        ],
        [
            'route' => 'admin.rental.orders', 'status' => 'transport_quote', 'icon' => 'location', 'count' => $rentalQuoteNeeded,
            'text' => 'unit memerlukan penawaran ongkos', 'note' => null,
        ],
        [
            'route' => 'admin.rental.orders', 'status' => 'design_initial', 'icon' => 'package', 'count' => $rentalDesignNeeded,
            'text' => 'unit memerlukan pratinjau awal', 'note' => null,
        ],
        [
            'route' => 'admin.rental.orders', 'status' => 'seller_cancelled', 'icon' => 'arrow-left', 'count' => $rentalSellerCancelled,
            'text' => 'unit dibatalkan karena penjual · periksa tindak lanjut', 'note' => null,
        ],
    ], fn (array $item) => $item['count'] > 0));
    $attentionTotal = array_sum(array_column($attention, 'count'));
@endphp

<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">{{ $sapaan }}, {{ $adminName }}</span>
        <h1>Ringkasan operasional</h1>
        <p class="adm-page-sub">{{ $tanggal }} · WIB — pantau unit sewa dan tugas yang menunggu tindakan.</p>
    </div>
    <div class="adm-actions">
        <a class="adm-btn" href="{{ route('admin.rental.orders', ['status' => 'pending']) }}">
            <span class="adm-dot" aria-hidden="true"></span>
            {{ $rentalPending }} unit menunggu
        </a>
    </div>
</div>

<section class="adm-card adm-attention adm-mb-gap" data-adm-reveal="attention" aria-labelledby="adm-attention-title">
    <div class="adm-card__head">
        <h2 id="adm-attention-title">Perlu tindakanmu</h2>
        <span class="adm-chip">{{ $attentionTotal }} tugas</span>
    </div>
    @if($attention === [])
        <p class="adm-help adm-flat">Tidak ada yang menunggu tindakan. Semua pesanan sudah ditangani.</p>
    @else
        <div class="adm-stack adm-stack--tight">
            @foreach($attention as $item)
                <a class="adm-listlink" href="{{ route($item['route'], ['status' => $item['status']]) }}">
                    <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => $item['icon'], 'size' => 18])</span>
                    <span class="adm-attention__text"><strong>{{ $item['count'] }}</strong> {{ $item['text'] }}
                        @if($item['note'])<small>{{ $item['note'] }}</small>@endif
                    </span>
                    <span class="adm-push-icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
                </a>
            @endforeach
        </div>
    @endif
    <p class="adm-help adm-inline-note adm-inline-note--top">Pembatalan karena penjual mencakup seluruh riwayat, termasuk yang sudah ditindaklanjuti.</p>
</section>

<section class="adm-card adm-mb-gap" aria-labelledby="adm-today-title" data-adm-reveal="today">
    <div class="adm-card__head">
        <h2 id="adm-today-title">Hari ini</h2>
        <span class="adm-chip">{{ $hariIniLabel }}</span>
    </div>
    <div class="adm-today">
        <a class="adm-today__cell" href="{{ route('admin.rental.schedule.daily-sheet', ['date' => $hariIni->toDateString()]) }}">
            <span class="adm-today__num">{{ $hariIniTerima }}</span>
            <span class="adm-today__label">papan diserahkan</span>
            <span class="adm-today__meta">Buka lembar serah terima hari ini</span>
        </a>
        <a class="adm-today__cell" href="{{ route('admin.rental.schedule.daily-sheet', ['date' => $hariIni->toDateString()]) }}">
            <span class="adm-today__num">{{ $hariIniKembali }}</span>
            <span class="adm-today__label">papan dikembalikan</span>
            <span class="adm-today__meta">Periksa kondisi papan yang kembali</span>
        </a>
        <a class="adm-today__cell" href="{{ route('admin.rental.schedule', ['month' => $hariIni->format('Y-m')]) }}">
            <span class="adm-today__num">{{ $rentalActive }}</span>
            <span class="adm-today__label">unit aktif</span>
            <span class="adm-today__meta">Lihat jadwal bulan ini</span>
        </a>
    </div>
</section>

<div class="adm-stats adm-mb-gap" data-adm-reveal="stats">
    <a class="adm-stat" href="{{ route('admin.rental.orders') }}">
        <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'package', 'size' => 20])</span>
        <span class="adm-stat__value" data-adm-count data-to="{{ (int) $rentalActive }}">{{ $rentalActive }}</span>
        <span class="adm-stat__label">Unit aktif</span>
        <span class="adm-stat__hint">Sedang dipakai pelanggan</span>
    </a>
    <a class="adm-stat" href="{{ route('admin.rental.orders', ['status' => 'pending']) }}">
        <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'orders', 'size' => 20])</span>
        <span class="adm-stat__value" data-adm-count data-to="{{ (int) $rentalPending }}">{{ $rentalPending }}</span>
        <span class="adm-stat__label">Menunggu</span>
        <span class="adm-stat__hint">Perlu konfirmasi admin</span>
    </a>
    <a class="adm-stat" href="{{ route('admin.rental.orders', ['status' => 'review']) }}">
        <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'search', 'size' => 20])</span>
        <span class="adm-stat__value" data-adm-count data-to="{{ (int) $rentalReview }}">{{ $rentalReview }}</span>
        <span class="adm-stat__label">Tinjau manual</span>
        <span class="adm-stat__hint">Menunggu pemeriksaan berkas</span>
    </a>
</div>

<section class="adm-card adm-mb-gap" data-adm-reveal="revenue" aria-labelledby="adm-revenue-title">
    <div class="adm-card__head">
        <h2 id="adm-revenue-title">Pemasukan hari ini</h2>
        <span class="adm-chip">Seluruh buku kas</span>
    </div>
    <div class="adm-row adm-row--between adm-row--gap3 adm-row--wrap">
        <p class="adm-amount-hero">
            <span class="adm-amount-hero__unit">Rp</span>
            {{ number_format($pemasukanHari ?? 0, 0, ',', '.') }}
        </p>
        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.cashbook.index') }}">Buku kas</a>
    </div>
    <p class="adm-help adm-block-gap--sm">Bulan ini <strong>Rp {{ number_format($pemasukanBulan ?? 0, 0, ',', '.') }}</strong></p>
</section>

<div class="adm-grid adm-grid--2 adm-mb-gap">
    <section class="adm-card" data-adm-reveal="waiting" aria-labelledby="adm-waiting-title">
        <div class="adm-card__head">
            <h2 id="adm-waiting-title">Menunggu pelanggan</h2>
            <span class="adm-chip">Bukan tugas admin</span>
        </div>
        <div class="adm-stack adm-stack--tight">
            <a class="adm-listlink" href="{{ route('admin.rental.orders', ['status' => 'design_pending']) }}">
                <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'package', 'size' => 18])</span>
                <span class="adm-min0"><strong>{{ $rentalWaitingDesign }}</strong> unit menunggu persetujuan desain
                    @if($rentalWaitingDesignSince)· menunggu sejak {{ $rentalWaitingDesignSince->format('d/m H:i') }} WIB @endif
                </span>
            </a>
            <a class="adm-listlink" href="{{ route('admin.rental.orders', ['status' => 'transport_approval']) }}">
                <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'location', 'size' => 18])</span>
                <span class="adm-min0"><strong>{{ $rentalWaitingTransport }}</strong> unit menunggu persetujuan ongkos
                    @if($rentalWaitingTransportSince)· penawaran tertua {{ $rentalWaitingTransportSince->format('d/m H:i') }} WIB @endif
                </span>
            </a>
            <a class="adm-listlink" href="{{ route('admin.rental.orders', ['status' => 'grace']) }}">
                <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'clock', 'size' => 18])</span>
                <span class="adm-min0"><strong>{{ $rentalWaitingGrace }}</strong> unit dalam toleransi pembayaran
                    @if($rentalWaitingGraceNearest)· batas terdekat {{ $rentalWaitingGraceNearest->copy()->timezone('Asia/Jakarta')->format('d/m H:i') }} WIB @else· sampai H+1 pukul 12.00 WIB @endif
                </span>
            </a>
        </div>
    </section>

    <section class="adm-card" data-adm-reveal="recent" aria-labelledby="adm-recent-title">
        <div class="adm-card__head">
            <h2 id="adm-recent-title">Pesanan sewa terbaru</h2>
            <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.orders') }}">Semua pesanan</a>
        </div>
        <div class="adm-stack adm-stack--tight">
            @forelse($recentRentalOrders as $order)
                <a class="adm-listlink" href="{{ route('admin.rental.orders', ['q' => $order->reference]) }}">
                    <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'package', 'size' => 18])</span>
                    <span class="adm-min0">
                        <strong>{{ $order->customer?->name ?? 'Pelanggan tidak tersedia' }}</strong> · {{ $order->units_count }} unit
                        <span class="adm-help adm-block">{{ $order->reference }}</span>
                    </span>
                </a>
            @empty
                <div class="adm-empty adm-pad-y">
                    <p class="adm-empty__title">Belum ada pesanan sewa</p>
                    <p class="adm-help adm-flat">Pesanan baru akan muncul di sini.</p>
                </div>
            @endforelse
        </div>
    </section>

    <div class="adm-note adm-note--warn adm-flat">
        <span class="adm-note__title">Mode pengujian</span>
        Sistem masih dalam pengujian dan belum untuk transaksi nyata. Ringkasan buku kas dapat mencakup data lama.
    </div>
</div>
@endsection

