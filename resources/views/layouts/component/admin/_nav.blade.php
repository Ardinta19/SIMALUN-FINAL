{{--
|--------------------------------------------------------------------------
| Menu utama panel admin (dipakai sidebar shell admin)
|--------------------------------------------------------------------------
| Setiap tautan memakai aria-current="page" saat rutenya aktif agar pembaca
| layar dan gaya .adm-nav a[aria-current] menandai posisi pengguna.
| Ikon memakai partial _icon yang sudah ada (Lucide) — tidak menambah aset.
--}}
@php
    $admNavGroups = [
        [
            'label' => 'Operasional',
            'items' => [
                ['label' => 'Dasbor', 'route' => 'dashboard.admin', 'match' => ['dashboard.admin', 'admin.dashboard'], 'icon' => 'dashboard'],
                ['label' => 'Pesanan sewa', 'route' => 'admin.rental.orders', 'match' => ['admin.rental.orders', 'admin.rental.orders.show'], 'icon' => 'orders'],
                ['label' => 'Jadwal sewa', 'route' => 'admin.rental.schedule', 'match' => ['admin.rental.schedule', 'admin.rental.schedule.daily-sheet'], 'icon' => 'calendar'],
            ],
        ],
        [
            'label' => 'Katalog & penawaran',
            'items' => [
                ['label' => 'Katalog papan', 'route' => 'admin.catalog.index', 'match' => ['admin.catalog.*'], 'icon' => 'package'],
                ['label' => 'Universitas & tarif', 'route' => 'admin.rental.universities.index', 'match' => ['admin.rental.universities.*'], 'icon' => 'location'],
                ['label' => 'Voucher sewa', 'route' => 'admin.rental.vouchers.index', 'match' => ['admin.rental.vouchers.*'], 'icon' => 'voucher'],
            ],
        ],
        [
            'label' => 'Keuangan',
            'items' => [
                ['label' => 'Buku kas', 'route' => 'admin.rental.cashbook.index', 'match' => ['admin.rental.cashbook.*'], 'icon' => 'finance'],
                ['label' => 'Petunjuk pembayaran', 'route' => 'admin.rental.payment-settings.edit', 'match' => ['admin.rental.payment-settings.*'], 'icon' => 'payment'],
            ],
        ],
        [
            'label' => 'Modul laundry lama',
            'note' => 'Masih dipakai; tampilan belum diseragamkan.',
            'items' => [
                ['label' => 'Pesanan laundry', 'route' => 'admin.orders', 'match' => ['admin.orders'], 'icon' => 'laundry'],
                ['label' => 'Kasir walk-in', 'route' => 'admin.walkin.form', 'match' => ['admin.walkin.*'], 'icon' => 'order-create'],
                ['label' => 'Keuangan laundry', 'route' => 'admin.finance.index', 'match' => ['admin.finance.*'], 'icon' => 'money'],
                ['label' => 'Laporan', 'route' => 'admin.reports', 'match' => ['admin.reports'], 'icon' => 'reports'],
                ['label' => 'Kategori laundry', 'route' => 'admin.categories.index', 'match' => ['admin.categories.*'], 'icon' => 'label'],
                ['label' => 'Voucher laundry', 'route' => 'admin.vouchers.index', 'match' => ['admin.vouchers.*'], 'icon' => 'discount'],
            ],
        ],
        [
            'label' => 'Pemantauan',
            'items' => [
                ['label' => 'Notifikasi', 'route' => 'admin.notifications', 'match' => ['admin.notifications', 'admin.notifications.*'], 'icon' => 'notifikasi'],
                ['label' => 'Audit trail', 'route' => 'admin.audit.index', 'match' => ['admin.audit.*'], 'icon' => 'audit'],
                ['label' => 'Panduan admin', 'route' => 'admin.help', 'match' => ['admin.help'], 'icon' => 'help'],
            ],
        ],
    ];
@endphp

<nav class="adm-nav" aria-label="Navigasi panel admin">
    @foreach($admNavGroups as $group)
        <span class="adm-nav__group" @if(!empty($group['note'])) title="{{ $group['note'] }}" @endif>{{ $group['label'] }}</span>
        @foreach($group['items'] as $item)
            @php
                $isActive = collect($item['match'])->contains(fn ($pattern) => request()->routeIs($pattern));
            @endphp
            <a href="{{ route($item['route']) }}"
               title="{{ $item['label'] }}"
               @if($isActive) aria-current="page" @endif>
                <span class="adm-nav__icon" aria-hidden="true">
                    @include('layouts.component._icon', ['name' => $item['icon'], 'size' => 16])
                </span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    @endforeach
</nav>
