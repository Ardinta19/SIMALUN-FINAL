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
                ? 'batas konfirmasi terdekat '.\Carbon\Carbon::parse($rentalPendingNearest)->timezone('Asia/Jakarta')->format('d/m H:i').' WIB'
                : null,
        ],
        [
            'route' => 'admin.rental.orders', 'status' => 'payment_pending', 'icon' => 'payment', 'count' => $rentalPaymentPending,
            'text' => 'bukti menunggu pemeriksaan',
            'note' => $paymentPendingOldest
                ? 'bukti tertua '.\Carbon\Carbon::parse($paymentPendingOldest)->timezone('Asia/Jakarta')->format('d/m H:i').' WIB'
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

<section class="adm-card adm-attention" data-adm-reveal="attention" aria-labelledby="adm-attention-title" style="margin-bottom:var(--adm-gap)">
    <div class="adm-card__head">
        <h2 id="adm-attention-title">Perlu tindakanmu</h2>
        <span class="adm-chip">{{ $attentionTotal }} tugas</span>
    </div>
    @if($attention === [])
        <p class="adm-help" style="margin:0">Tidak ada yang menunggu tindakan. Semua pesanan sudah ditangani.</p>
    @else
        <div class="adm-stack" style="gap:8px">
            @foreach($attention as $item)
                <a class="adm-listlink" href="{{ route($item['route'], ['status' => $item['status']]) }}">
                    <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => $item['icon'], 'size' => 18])</span>
                    <span class="adm-attention__text"><strong>{{ $item['count'] }}</strong> {{ $item['text'] }}
                        @if($item['note'])<small>{{ $item['note'] }}</small>@endif
                    </span>
                    <span style="margin-left:auto;flex:none;color:var(--adm-ink-3)" aria-hidden="true">@include('layouts.component._icon', ['name' => 'next', 'size' => 16])</span>
                </a>
            @endforeach
        </div>
    @endif
    <p class="adm-help" style="margin:10px 0 0">Pembatalan karena penjual mencakup seluruh riwayat, termasuk yang sudah ditindaklanjuti.</p>
</section>

<div class="adm-stats" data-adm-reveal="stats" style="margin-bottom:var(--adm-gap)">
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

<section class="adm-card" data-adm-reveal="revenue" aria-labelledby="adm-revenue-title" style="margin-bottom:var(--adm-gap)">
    <div class="adm-card__head">
        <h2 id="adm-revenue-title">Pemasukan hari ini</h2>
        <span class="adm-chip">Seluruh buku kas</span>
    </div>
    <div class="adm-row adm-row--between" style="gap:12px;flex-wrap:wrap">
        <p style="margin:0;font-size:clamp(26px,7vw,34px);font-weight:900;letter-spacing:-.02em;line-height:1.1">
            <span style="font-size:.5em;font-weight:800;color:var(--adm-ink-3)">Rp</span>
            {{ number_format($pemasukanHari ?? 0, 0, ',', '.') }}
        </p>
        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.cashbook.index') }}">Buku kas</a>
    </div>
    <p class="adm-help" style="margin:6px 0 0">Bulan ini <strong>Rp {{ number_format($pemasukanBulan ?? 0, 0, ',', '.') }}</strong></p>
</section>

<div class="adm-grid adm-grid--split" style="margin-bottom:var(--adm-gap)">
    <div class="adm-grid">
        <section class="adm-card" data-adm-reveal="waiting" aria-labelledby="adm-waiting-title">
            <div class="adm-card__head">
                <h2 id="adm-waiting-title">Menunggu pelanggan</h2>
                <span class="adm-chip">Bukan tugas admin</span>
            </div>
            <div class="adm-stack" style="gap:8px">
                <a class="adm-listlink" href="{{ route('admin.rental.orders', ['status' => 'design_pending']) }}">
                    <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'package', 'size' => 18])</span>
                    <span style="min-width:0;overflow-wrap:anywhere"><strong>{{ $rentalWaitingDesign }}</strong> unit menunggu persetujuan desain
                        @if($rentalWaitingDesignSince)· menunggu sejak {{ \Carbon\Carbon::parse($rentalWaitingDesignSince)->timezone('Asia/Jakarta')->format('d/m H:i') }} WIB @endif
                    </span>
                </a>
                <a class="adm-listlink" href="{{ route('admin.rental.orders', ['status' => 'transport_approval']) }}">
                    <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'location', 'size' => 18])</span>
                    <span style="min-width:0;overflow-wrap:anywhere"><strong>{{ $rentalWaitingTransport }}</strong> unit menunggu persetujuan ongkos
                        @if($rentalWaitingTransportSince)· penawaran tertua {{ \Carbon\Carbon::parse($rentalWaitingTransportSince)->timezone('Asia/Jakarta')->format('d/m H:i') }} WIB @endif
                    </span>
                </a>
                <a class="adm-listlink" href="{{ route('admin.rental.orders', ['status' => 'grace']) }}">
                    <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'clock', 'size' => 18])</span>
                    <span style="min-width:0;overflow-wrap:anywhere"><strong>{{ $rentalWaitingGrace }}</strong> unit dalam toleransi pembayaran
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
            <div class="adm-stack" style="gap:8px">
                @forelse($recentRentalOrders as $order)
                    <a class="adm-listlink" href="{{ route('admin.rental.orders', ['q' => $order->reference]) }}">
                        <span class="adm-stat__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'package', 'size' => 18])</span>
                        <span style="min-width:0;overflow-wrap:anywhere">
                            <strong>{{ $order->customer?->name ?? 'Pelanggan tidak tersedia' }}</strong> · {{ $order->units_count }} unit
                            <span class="adm-help" style="display:block">{{ $order->reference }}</span>
                        </span>
                    </a>
                @empty
                    <div class="adm-empty" style="padding:18px 0">
                        <p class="adm-empty__title">Belum ada pesanan sewa</p>
                        <p class="adm-help" style="margin:0">Pesanan baru akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <div class="adm-note adm-note--warn" style="margin:0">
            <span class="adm-note__title">Mode pengujian</span>
            Sistem masih dalam pengujian dan belum untuk transaksi nyata. Ringkasan buku kas dapat mencakup data lama.
        </div>
    </div>
</div>
@endsection

