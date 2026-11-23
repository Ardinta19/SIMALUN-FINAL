{{--
    Partial: _bottom_nav.blade.php
    Navigasi bawah KHUSUS PONSEL (<=700px) untuk shell admin.

    Tiga wajah navigasi admin:
      ponsel  (<=700px)  : empat tujuan harian + tombol "Lainnya" (laci menu)
      tablet  (701–1079) : sidebar menyusut menjadi rel ikon
      desktop (>=1080)   : sidebar penuh 252px

    Empat butir pertama adalah tujuan harian; butir kelima "Lainnya" membuka
    laci menu yang sama dengan tombol di topbar, sehingga seluruh tujuan
    (katalog, voucher, universitas, pengaturan, audit, panduan)
    tetap terjangkau tanpa memadatkan bilah.
--}}
@php
    $bottomItems = [
        ['label' => 'Beranda', 'route' => 'dashboard.admin', 'match' => ['dashboard.admin', 'admin.dashboard'], 'icon' => 'dashboard'],
        ['label' => 'Pesanan', 'route' => 'admin.rental.orders', 'match' => ['admin.rental.orders', 'admin.rental.orders.show'], 'icon' => 'orders'],
        ['label' => 'Jadwal', 'route' => 'admin.rental.schedule', 'match' => ['admin.rental.schedule', 'admin.rental.schedule.daily-sheet'], 'icon' => 'calendar'],
        ['label' => 'Buku kas', 'route' => 'admin.rental.cashbook.index', 'match' => ['admin.rental.cashbook.*'], 'icon' => 'finance'],
    ];
@endphp

<nav class="adm-bottomnav" aria-label="Navigasi cepat admin">
    @foreach($bottomItems as $item)
        @php $isActive = request()->routeIs(...$item['match']); @endphp
        <a href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif>
            @include('layouts.component._icon', ['name' => $item['icon'], 'size' => 20])
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach

    {{-- Slot kelima membuka seluruh menu. Pola yang sama dipakai halaman admin
         mandiri, jadi susunan navigasi terasa identik di seluruh panel. --}}
    <button type="button" data-adm-nav-toggle aria-controls="adm-sidebar" aria-expanded="false">
        @include('layouts.component._icon', ['name' => 'menu', 'size' => 20])
        <span>Lainnya</span>
    </button>
</nav>
