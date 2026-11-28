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

<div class="adm-grid adm-grid--2 adm-mb-gap">
    {{-- Uang hari ini: pertanyaan pertama admin saat membuka panel. Pemasukan
         dan pengeluaran ditampilkan berdampingan supaya "masuk besar" tidak
         menyembunyikan "keluar juga besar". --}}
    <section class="adm-card adm-money" data-adm-reveal="uang" aria-labelledby="adm-uang-title">
        @php
            $netHari = (int) $pemasukanHari - (int) $pengeluaranHari;
            $selisihKemarin = (int) $pemasukanHari - (int) $arusKemarin;
        @endphp
        <div class="adm-card__head">
            <h2 id="adm-uang-title">Uang hari ini</h2>
            <span class="adm-chip">{{ $hariIniLabel }}</span>
        </div>

        <div>
            <span class="adm-money__label">Pemasukan</span>
            <p class="adm-amount-hero"><span class="adm-amount-hero__unit">Rp</span>{{ number_format((int) $pemasukanHari, 0, ',', '.') }}</p>
            @if($arusKemarin > 0)
                <p class="adm-delta {{ $selisihKemarin >= 0 ? 'adm-delta--up' : 'adm-delta--down' }}">
                    {{ $selisihKemarin >= 0 ? '▲' : '▼' }}
                    Rp {{ number_format(abs($selisihKemarin), 0, ',', '.') }}
                    {{ $selisihKemarin >= 0 ? 'lebih tinggi' : 'lebih rendah' }} dari kemarin
                    (Rp {{ number_format((int) $arusKemarin, 0, ',', '.') }})
                </p>
            @else
                <p class="adm-delta">Belum ada pemasukan kemarin untuk dibandingkan.</p>
            @endif
        </div>

        <div class="adm-money__pair">
            <div class="adm-money__cell">
                <span>Pengeluaran</span>
                <strong>Rp {{ number_format((int) $pengeluaranHari, 0, ',', '.') }}</strong>
            </div>
            <div class="adm-money__cell adm-money__cell--net">
                <span>Selisih hari ini</span>
                <strong>{{ $netHari < 0 ? '− ' : '' }}Rp {{ number_format(abs($netHari), 0, ',', '.') }}</strong>
            </div>
        </div>

        <p class="adm-help adm-flat">
            Bulan ini: masuk Rp {{ number_format((int) $pemasukanBulan, 0, ',', '.') }} ·
            keluar Rp {{ number_format((int) $pengeluaranBulan, 0, ',', '.') }}
        </p>
        <div><a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.cashbook.index') }}">Buka buku kas</a></div>
    </section>

    {{-- Jam pemakaian: seberapa penuh stok hari ini. Angka persen tanpa konteks
         tidak berarti, jadi selalu disertai "X dari Y papan". --}}
    <section class="adm-card" data-adm-reveal="jam" aria-labelledby="adm-jam-title">
        <div class="adm-card__head">
            <h2 id="adm-jam-title">Papan sedang keluar</h2>
            <span class="adm-chip">{{ $papanKeluar }} dari {{ $stokTotal }}</span>
        </div>
        <div class="adm-gauge" style="--arc:{{ $pemakaianPersen }}">
            <svg class="adm-gauge__svg" viewBox="0 0 200 116" role="img"
                 aria-label="Pemakaian stok hari ini {{ $pemakaianPersen }} persen: {{ $papanKeluar }} dari {{ $stokTotal }} papan">
                <path class="adm-gauge__track" d="M16 104 A84 84 0 0 1 184 104"></path>
                <path class="adm-gauge__value" d="M16 104 A84 84 0 0 1 184 104"></path>
            </svg>
            <p class="adm-gauge__num">{{ $pemakaianPersen }}%</p>
            <p class="adm-gauge__foot">
                @if($stokTotal > 0)
                    {{ $papanKeluar }} papan sedang di luar, {{ max(0, $stokTotal - $papanKeluar) }} masih di tempat.
                @else
                    Belum ada papan aktif di katalog, jadi pemakaian belum bisa dihitung.
                @endif
                <br>Unit aktif seluruhnya: {{ $rentalActive }}.
            </p>
        </div>
    </section>
</div>

{{-- Arus tujuh hari: menjawab "minggu ini naik atau turun", bukan sekadar
     angka hari ini. Angka tiap hari tetap tersedia dalam tabel di bawah grafik. --}}
<section class="adm-card adm-mb-gap" data-adm-reveal="arus" aria-labelledby="adm-arus-title">
    <div class="adm-card__head">
        <h2 id="adm-arus-title">Arus kas tujuh hari</h2>
        <span class="adm-legend">
            <span class="adm-legend__dot adm-legend__dot--in" aria-hidden="true"></span>Masuk
            <span class="adm-legend__dot adm-legend__dot--out" aria-hidden="true"></span>Keluar
        </span>
    </div>

    <div class="adm-bars" role="img"
         aria-label="Grafik batang arus kas tujuh hari. Masuk Rp {{ number_format((int) $arusMasukTujuhHari, 0, ',', '.') }}, keluar Rp {{ number_format((int) $arusKeluarTujuhHari, 0, ',', '.') }}.">
        @foreach($arusHarian as $i => $hari)
            <div class="adm-bars__day">
                <div class="adm-bars__pair">
                    <span class="adm-bars__fill adm-bars__fill--in"
                          style="height:{{ $hari['masuk'] > 0 ? max(3, (int) round($hari['masuk'] / $arusPuncak * 100)) : 0 }}%;--i:{{ $i }}"></span>
                    <span class="adm-bars__fill adm-bars__fill--out"
                          style="height:{{ $hari['keluar'] > 0 ? max(3, (int) round($hari['keluar'] / $arusPuncak * 100)) : 0 }}%;--i:{{ $i }}"></span>
                </div>
                <span class="adm-bars__label">{{ $hari['label'] }}</span>
            </div>
        @endforeach
    </div>

    <details class="adm-mt-3">
        <summary class="bk-disclosure">Lihat angka tiap hari</summary>
        <table class="adm-table adm-mt-2">
            <caption class="bk-sr-only">Arus kas masuk dan keluar per hari selama tujuh hari terakhir</caption>
            <thead>
                <tr><th scope="col">Tanggal</th><th scope="col">Masuk</th><th scope="col">Keluar</th></tr>
            </thead>
            <tbody>
                @foreach($arusHarian as $hari)
                    <tr>
                        <td>{{ $hari['tanggal'] }}</td>
                        <td class="adm-table__num">Rp {{ number_format($hari['masuk'], 0, ',', '.') }}</td>
                        <td class="adm-table__num">Rp {{ number_format($hari['keluar'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>

    <p class="adm-help adm-flat">
        Tujuh hari: masuk Rp {{ number_format((int) $arusMasukTujuhHari, 0, ',', '.') }} ·
        keluar Rp {{ number_format((int) $arusKeluarTujuhHari, 0, ',', '.') }}
    </p>
</section>

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
        {{-- Pengembalian menuju jadwal dengan kegiatan "pengembalian" aktif:
             lembar harian hanya memuat serah terima, jadi tautan lama membawa
             admin ke halaman yang tidak memuat angka di atasnya. --}}
        <a class="adm-today__cell" href="{{ route('admin.rental.schedule', ['month' => $hariIni->format('Y-m'), 'activity' => 'returning']) }}{{ $hariIniKembali > 0 ? '#hari-ini' : '' }}">
            <span class="adm-today__num">{{ $hariIniKembali }}</span>
            <span class="adm-today__label">papan dikembalikan</span>
            <span class="adm-today__meta">Periksa kondisi papan yang kembali</span>
        </a>
        <a class="adm-today__cell" href="{{ route('admin.rental.schedule', ['month' => $hariIni->format('Y-m'), 'activity' => 'event']) }}{{ $hariIniAcara > 0 ? '#hari-ini' : '' }}">
            <span class="adm-today__num">{{ $hariIniAcara }}</span>
            <span class="adm-today__label">papan dipakai acara</span>
            <span class="adm-today__meta">Pastikan papan sudah di tangan pelanggan</span>
        </a>
    </div>
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

