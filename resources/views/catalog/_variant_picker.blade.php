{{--
|--------------------------------------------------------------------------
| Pemilih warna (varian) papan
|--------------------------------------------------------------------------
| Dipakai di kartu katalog dan halaman detail. Satu tombol per warna:
|   · cincin warna memakai `--bk-swatch` (kode heksa dari admin),
|   · klik langsung mengganti pratinjau foto (bila varian punya foto) atau
|     warna latar contoh, nama warna, dan batas jumlah unit,
|   · pilihan diteruskan ke formulir keranjang lewat input tersembunyi
|     `variant_id` yang diisi `public/js/berkah-variants.js`.
|
| Tanpa JavaScript pemilih tetap terlihat: warna pertama (yang berstok)
| sudah ditandai aktif dan sudah menjadi nilai awal `variant_id`, jadi
| formulir tetap bisa dikirim.
|--------------------------------------------------------------------------
--}}
@php
    $vkInitial = $variants->first(fn ($variant) => $variant->stock > 0) ?? $variants->first();
@endphp
<div class="bk-variants" data-bk-variants role="group" aria-label="Warna {{ $product->name }}">
    @foreach($variants as $variant)
        @php
            // Nilai disiapkan di luar tag: `->id` di dalam tag terbaca sebagai
            // atribut `id` oleh pemindai atribut ganda (FormPlaceholderTest).
            $vkId = $variant->getKey();
            $vkActive = $vkInitial && $vkInitial->is($variant);
            $vkHabis = $variant->stock < 1;
        @endphp
        <button type="button"
                class="bk-variant{{ $vkActive ? ' is-active' : '' }}{{ $vkHabis ? ' is-sold-out' : '' }}"
                data-variant-id="{{ $vkId }}"
                data-variant-name="{{ $variant->name }}"
                data-variant-color="{{ $variant->color }}"
                data-variant-stock="{{ (int) $variant->stock }}"
                data-variant-image="{{ $variant->image_path ? asset('storage/'.$variant->image_path) : '' }}"
                aria-pressed="{{ $vkActive ? 'true' : 'false' }}"
                @if($vkHabis) disabled aria-disabled="true" @endif
                title="{{ $variant->name }}{{ $vkHabis ? ' — stok habis' : '' }}">
            <span class="bk-swatch" style="--bk-swatch:{{ $variant->color }}" aria-hidden="true"></span>
            <span class="bk-variant__name">{{ $variant->name }}</span>
        </button>
    @endforeach
</div>
<p class="bk-variant-label muted" data-bk-variant-label>Warna: {{ $vkInitial?->name }}</p>
