{{--
    Partial: _bottom_nav.blade.php
    Navigasi bawah KHUSUS PONSEL (<=700px) untuk shell admin.

    Tiga wajah navigasi admin:
      ponsel  (<=700px)  : lima tujuan utama di bawah + sisanya lewat drawer
      tablet  (701–1079) : sidebar menyusut menjadi rel ikon
      desktop (>=1080)   : sidebar penuh 252px

    Butir di sini sengaja hanya lima: yang paling sering dibuka saat berdiri
    di depan meja. Sisanya (katalog, voucher, universitas, pengaturan, audit,
    panduan, modul laundry) tetap terjangkau dari tombol menu di topbar.
--}}
@php
    $bottomItems = [
        ['label' => 'Dasbor', 'route' => 'dashboard.admin', 'match' => ['dashboard.admin', 'admin.dashboard'], 'icon' => 'dashboard'],
        ['label' => 'Pesanan', 'route' => 'admin.rental.orders', 'match' => ['admin.rental.orders', 'admin.rental.orders.show'], 'icon' => 'orders'],
        ['label' => 'Jadwal', 'route' => 'admin.rental.schedule', 'match' => ['admin.rental.schedule', 'admin.rental.schedule.daily-sheet'], 'icon' => 'calendar'],
        ['label' => 'Buku kas', 'route' => 'admin.rental.cashbook.index', 'match' => ['admin.rental.cashbook.*'], 'icon' => 'finance'],
        ['label' => 'Profil', 'route' => 'admin.profile', 'match' => ['admin.profile', 'profile.edit'], 'icon' => 'user'],
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
</nav>
