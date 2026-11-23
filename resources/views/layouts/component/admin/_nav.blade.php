{{--
|--------------------------------------------------------------------------
| Menu utama panel admin (dipakai sidebar shell admin)
|--------------------------------------------------------------------------
| Setiap tautan memakai aria-current="page" saat rutenya aktif agar pembaca
| layar dan gaya .adm-nav a[aria-current] menandai posisi pengguna.
| Ikon memakai partial _icon yang sudah ada (Lucide) — tidak menambah aset.
--}}
@php
    /* Sumber tunggal daftar menu: app/Support/AdminNav.php. */
    $admNavGroups = \App\Support\AdminNav::groups();
@endphp

<nav class="adm-nav" aria-label="Navigasi panel admin">
    @foreach($admNavGroups as $group)
        @continue(($group['sidebar'] ?? true) === false)
        <span class="adm-nav__group" @if(!empty($group['note'])) title="{{ $group['note'] }}" @endif>{{ $group['label'] }}</span>
        @foreach($group['items'] as $item)
            @php $isActive = \App\Support\AdminNav::isActive($item); @endphp
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
