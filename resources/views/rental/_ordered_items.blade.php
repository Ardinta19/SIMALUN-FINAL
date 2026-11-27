{{--
    Daftar "Item yang dipesan" — satu partial untuk tiga halaman.
    Data dinormalkan di App\Support\OrderedItems sehingga checkout, halaman
    pesanan pelanggan, dan detail admin tidak pernah berbeda isi.

    Parameter:
      $items  array hasil OrderedItems::fromUnits()/fromCart()
      $title  judul kartu (bawaan "Item yang dipesan")
      $hint   satu kalimat penjelas (opsional)
      $class  kelas tambahan untuk kartu pembungkus (opsional)
--}}
@php
    $items = $items ?? [];
    $title = $title ?? 'Item yang dipesan';
    $hint = $hint ?? null;
    $judulId = 'bk-items-title-'.substr(md5($title.($hint ?? '')), 0, 6);
@endphp
<section class="bk-items {{ $class ?? '' }}" aria-labelledby="{{ $judulId }}">
    <div class="bk-items__head">
        <h2 id="{{ $judulId }}">{{ $title }}</h2>
        <span class="bk-items__count">{{ count($items) }} papan</span>
    </div>
    @if($hint)<p class="bk-items__hint">{{ $hint }}</p>@endif

    @if($items === [])
        <p class="bk-items__hint">Belum ada papan di pesanan ini.</p>
    @else
    <ul class="bk-items__list">
        @foreach($items as $item)
        <li class="bk-items__row">
            <span class="bk-items__num" aria-hidden="true">{{ str_pad((string) $item['no'], 2, '0', STR_PAD_LEFT) }}</span>
            <div class="bk-items__body">
                <p class="bk-items__name">
                    @if($item['url'])
                        <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                    @else
                        {{ $item['name'] }}
                    @endif
                    @if($item['variant_name'])
                        <span class="bk-items__variant">
                            <span class="bk-swatch bk-swatch--sm" style="--bk-swatch:{{ $item['variant_color'] }}" aria-hidden="true"></span>{{ $item['variant_name'] }}
                        </span>
                    @endif
                    @if($item['status_label'])
                        <span class="bk-status-chip" data-status="{{ $item['status_key'] }}">{{ $item['status_label'] }}</span>
                    @endif
                </p>
                <p class="bk-items__meta">
                    @php $adaTanggal = ! empty($item['event_on']); @endphp
                    @if($adaTanggal)
                        Acara {{ $item['event_on']->format('d/m/Y') }}@if(! empty($item['ends_on'])) · kembali {{ $item['ends_on']->format('d/m/Y') }}@endif
                    @elseif($item['dates_known'] ?? true)
                        Tanggal belum dipilih
                    @endif
                    @if($adaTanggal && ! empty($item['meta'])) · @endif
                    @if(! empty($item['meta'])){{ $item['meta'] }}@endif
                </p>
                @if(! empty($item['addons']))
                <ul class="bk-items__addons">
                    @foreach($item['addons'] as $addon)
                        <li>{{ $addon['label'] }}@if($addon['amount'] > 0) <span class="muted">Rp {{ number_format($addon['amount'], 0, ',', '.') }}</span>@endif</li>
                    @endforeach
                </ul>
                @endif
            </div>
            <span class="bk-items__price">
                Rp {{ number_format($item['price_amount'], 0, ',', '.') }}<small>/ {{ $item['price_unit'] === 'day' ? 'hari' : 'acara' }}</small>
            </span>
        </li>
        @endforeach
    </ul>
    @endif
</section>
