@extends('catalog.layout')
@section('title', 'Selesaikan pesanan')
@section('content')
@php
    // Ringkasan singkat di kepala halaman: satu papan atau beberapa, pelanggan
    // hanya perlu tahu berapa yang harus diisi dan apa yang terjadi setelahnya.
    $unitTotal = count($cart);
@endphp
<div class="bk-co" data-bk-co>
    <div class="bk-co__top">
        <a data-smart-back class="bk-back" href="{{ route('cart.index') }}">← Kembali ke keranjang</a>
        <div class="bk-co__intro">
            <span class="bk-kicker">Pesanan · {{ $unitTotal }} papan</span>
            <h1>Selesaikan pesananmu</h1>
            <p class="bk-co__lead">Isi empat hal untuk tiap papan. Tanggal langsung ditahan 24 jam dan admin mengonfirmasi — tanpa perlu chat.</p>
            <ul class="bk-co__trust">
                <li><span class="bk-i" aria-hidden="true">@include('layouts.component._icon', ['name' => 'calendar', 'size' => 18])</span>Tanggal ditahan 24 jam</li>
                <li><span class="bk-i" aria-hidden="true">@include('layouts.component._icon', ['name' => 'bell', 'size' => 18])</span>Admin mengonfirmasi jadwal</li>
                <li><span class="bk-i" aria-hidden="true">@include('layouts.component._icon', ['name' => 'lock', 'size' => 18])</span>Bukti bayar hanya untuk admin</li>
            </ul>
        </div>
        <div class="bk-co__progress" data-co-progress>
            <div class="bk-co__progress-head">
                <strong data-co-progress-label>Mulai isi pesanan</strong>
                <span class="bk-co__progress-pct" data-co-progress-pct>0%</span>
            </div>
            <div class="bk-co__bar" role="progressbar" aria-label="Kemajuan pengisian pesanan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-co-progressbar><span data-co-progress-fill></span></div>
            <ol class="bk-checkout-steps" aria-label="Tahapan penyewaan">
                <li data-co-stage="unit"><span>1</span>Detail papan</li>
                <li data-co-stage="terms"><span>2</span>Ketentuan</li>
                <li data-co-stage="send"><span>3</span>Kirim</li>
            </ol>
        </div>
    </div>
    <form class="stack checkout-form bk-co__form" data-checkout-form data-co-form data-voucher-url="{{ route('rental.voucher.check') }}" method="post" action="{{ route('rental.checkout.store') }}" enctype="multipart/form-data">@csrf
    <div class="bk-co__grid">
        <div class="bk-co__main">
        @foreach($cart as $key => $entry)
        @php
            $product = $products->get($entry['product_id']);
            $unitNumber = $loop->iteration;
            $fieldLabels = ['event_on' => 'Tanggal acara dan penerimaan', 'ends_on' => 'Tanggal pengembalian', 'recipient_name' => 'Nama penerima', 'greeting' => 'Ucapan', 'design_notes' => 'Permintaan desain', 'delivery_method' => 'Penerimaan', 'delivery_address' => 'Alamat pengantaran', 'delivery_university_id' => 'Universitas pengantaran', 'return_method' => 'Pengembalian', 'return_address' => 'Alamat pengambilan kembali', 'return_university_id' => 'Universitas pengambilan kembali', 'addons' => 'Item tambahan'];
            if ($product->packages->isNotEmpty()) $fieldLabels['package_id'] = 'Pilihan harga';
            $oldUnit = fn (string $field, $fallback = '') => is_scalar($value = old('units.'.$key.'.'.$field, $fallback)) ? (string) $value : (string) $fallback;
            $today = now('Asia/Jakarta')->toDateString();
            $chosenEvent = $oldUnit('event_on', $entry['starts_on'] ?? '');
            // Warna pilihan di katalog ditampilkan lagi supaya pelanggan yakin
            // sebelum mengirim; ia tidak bisa diubah dari halaman ini.
            $checkoutVariant = $product->findVariant($entry['variant_id'] ?? 0);
            $returnMin = preg_match('/^\d{4}-\d{2}-\d{2}$/', $chosenEvent) && $chosenEvent > $today ? $chosenEvent : $today;
            $unitHasError = collect(array_keys($fieldLabels))->contains(fn ($field) => $errors->has('units.'.$key.'.'.$field));
            $sameProductUnits = collect($cart)->where('product_id', $product->id)->count();
            $hasLockedDates = ! empty($bookedDates[$product->id] ?? []) || $sameProductUnits > 1;
        @endphp
        <section class="bk-co-unit" id="checkout-unit-{{ $unitNumber }}" data-checkout-unit data-co-unit data-unit-price="{{ (int) $product->base_price }}" data-unit-price-unit="{{ $product->price_unit }}" aria-labelledby="checkout-heading-{{ $unitNumber }}">
            <header class="bk-co-unit__head">
                <span class="bk-co-unit__num" aria-hidden="true">{{ str_pad($unitNumber, 2, '0', STR_PAD_LEFT) }}</span>
                <div class="bk-co-unit__id">
                    <h2 id="checkout-heading-{{ $unitNumber }}">Unit {{ $unitNumber }} dari {{ $unitTotal }} · {{ $product->name }}</h2>
                    <p class="bk-co-unit__meta"><span class="price">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span> <span class="muted">/ {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</span>@if($checkoutVariant) · <span class="bk-swatch bk-swatch--sm" style="--bk-swatch:{{ $checkoutVariant->color }}" aria-hidden="true"></span> Warna <strong>{{ $checkoutVariant->name }}</strong>@endif</p>
                </div>
                <span class="bk-co-unit__state" data-co-unit-state>Belum lengkap</span>
            </header>
            @if($unitHasError)
            <div class="bk-unit-errors" role="alert" id="checkout-errors-{{ $unitNumber }}"><strong>Periksa isian unit {{ $unitNumber }}:</strong><ul>@foreach($fieldLabels as $field => $label)@if($errors->has('units.'.$key.'.'.$field))<li><a href="#checkout-{{ $unitNumber }}-{{ $field }}">{{ $label }}</a></li>@endif @endforeach</ul></div>
            @endif
            <div class="bk-co-unit__tools">
                <a class="bk-co-link" href="{{ route('catalog.show', $product) }}" target="_blank" rel="noopener noreferrer">Periksa perkiraan ketersediaan ↗</a>
                @if(!$loop->first)
                    <button type="button" class="bk-co-link bk-co-link--button" data-copy-previous hidden>Salin detail dari unit sebelumnya</button>
                @endif
            </div>
            @if(!$loop->first)
                <p class="bk-co-unit__note" data-copy-help hidden>Menyalin jadwal, penerima, ucapan, desain, dan serah terima; pilihan paket tidak disalin.</p>
                <p class="bk-co-unit__note" role="status" aria-live="polite" data-copy-status></p>
            @endif
            <div class="bk-co-tabs" role="tablist" aria-label="Bagian unit {{ $unitNumber }}">
                <button type="button" class="bk-co-tab" role="tab" id="checkout-{{ $unitNumber }}-tab-1" aria-controls="checkout-{{ $unitNumber }}-panel-1" aria-selected="true" data-co-tab="1"><span>1</span>Jadwal</button>
                <button type="button" class="bk-co-tab" role="tab" id="checkout-{{ $unitNumber }}-tab-2" aria-controls="checkout-{{ $unitNumber }}-panel-2" aria-selected="false" tabindex="-1" data-co-tab="2"><span>2</span>Ucapan &amp; desain</button>
                <button type="button" class="bk-co-tab" role="tab" id="checkout-{{ $unitNumber }}-tab-3" aria-controls="checkout-{{ $unitNumber }}-panel-3" aria-selected="false" tabindex="-1" data-co-tab="3"><span>3</span>Serah terima</button>
            </div>
            <div class="bk-co-panel" role="tabpanel" id="checkout-{{ $unitNumber }}-panel-1" aria-labelledby="checkout-{{ $unitNumber }}-tab-1" data-co-panel="1">
                <p class="bk-co-panel__hint">Tanggal acara juga tanggal papan diterima.</p>
                <div class="row">
                    <label for="checkout-{{ $unitNumber }}-event_on">Tanggal acara dan penerimaan <span class="bk-req">(wajib)</span><input type="date" id="checkout-{{ $unitNumber }}-event_on" name="units[{{ $key }}][event_on]" data-fc-range="unit-{{ $key }}" data-fc-unit="{{ $key }}" data-fc-product="{{ $product->id }}" data-fc-stock="{{ (int) $product->stock }}" data-fc-refresh-url="{{ route('catalog.availability', $product) }}" @error('units.'.$key.'.event_on') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror value="{{ $oldUnit('event_on', $entry['starts_on'] ?? '') }}" min="{{ now('Asia/Jakarta')->toDateString() }}" max="{{ now('Asia/Jakarta')->addDays(\App\Services\RentalAvailability::BOOKING_HORIZON_DAYS - 1)->toDateString() }}" data-fc-disabled='@json($bookedDates[$product->id] ?? [])' required></label>
                    <label for="checkout-{{ $unitNumber }}-ends_on">Tanggal pengembalian <span class="bk-req">(wajib)</span><input type="date" id="checkout-{{ $unitNumber }}-ends_on" name="units[{{ $key }}][ends_on]" data-fc-range="unit-{{ $key }}" data-fc-unit="{{ $key }}" data-fc-product="{{ $product->id }}" data-fc-stock="{{ (int) $product->stock }}" data-fc-refresh-url="{{ route('catalog.availability', $product) }}" @error('units.'.$key.'.ends_on') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror value="{{ $oldUnit('ends_on', $entry['ends_on'] ?? '') }}" min="{{ $returnMin }}" max="{{ now('Asia/Jakarta')->addDays(\App\Services\RentalAvailability::BOOKING_HORIZON_DAYS - 1)->toDateString() }}" data-fc-disabled='@json($bookedDates[$product->id] ?? [])' required></label>
                </div>
                @if($hasLockedDates)<p class="bk-cal-hint" id="checkout-{{ $unitNumber }}-booked-hint">Tanggal yang sudah dibooking tidak bisa dipilih; kalender menandainya dengan garis.</p>@endif
                <p class="bk-date-shortcut"><button type="button" class="btn secondary" data-same-day hidden>Pakai tanggal yang sama (sewa satu hari)</button><span class="muted" data-same-day-status role="status" aria-live="polite"></span></p>
                @if($product->packages->isNotEmpty())
                <label for="checkout-{{ $unitNumber }}-package_id">Pilihan harga <span class="bk-muted-mark">(opsional)</span><select id="checkout-{{ $unitNumber }}-package_id" name="units[{{ $key }}][package_id]" @error('units.'.$key.'.package_id') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror><option value="">Harga dasar (per hari/acara)</option>@foreach($product->packages as $package)<option value="{{ $package->id }}" data-price="{{ (int) $package->price }}" @selected((string) old('units.'.$key.'.package_id') === (string) $package->id)>{{ $package->name }} · {{ $package->days }} hari · Rp {{ number_format($package->price, 0, ',', '.') }}</option>@endforeach</select></label>
                <p class="bk-co-panel__hint">Durasi paket harus sama dengan rentang tanggal yang dipilih.</p>
                @endif
            </div>
            <div class="bk-co-panel" role="tabpanel" id="checkout-{{ $unitNumber }}-panel-2" aria-labelledby="checkout-{{ $unitNumber }}-tab-2" data-co-panel="2">
                <label for="checkout-{{ $unitNumber }}-recipient_name">Nama penerima <span class="bk-req">(wajib)</span><input autocomplete="off" id="checkout-{{ $unitNumber }}-recipient_name" name="units[{{ $key }}][recipient_name]" @error('units.'.$key.'.recipient_name') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror value="{{ $oldUnit('recipient_name', $entry['recipient_name'] ?? '') }}" maxlength="160" placeholder="Contoh: Ibu Sari" required></label>
                <label for="checkout-{{ $unitNumber }}-greeting">Ucapan <span class="bk-req">(wajib)</span><textarea id="checkout-{{ $unitNumber }}-greeting" name="units[{{ $key }}][greeting]" @error('units.'.$key.'.greeting') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror maxlength="1000" placeholder="Contoh: Selamat atas wisudamu, semoga sukses selalu." required rows="3">{{ $oldUnit('greeting', $entry['greeting'] ?? '') }}</textarea></label>
                <label for="checkout-{{ $unitNumber }}-design_notes">Permintaan desain <span class="bk-muted-mark">(opsional)</span><textarea id="checkout-{{ $unitNumber }}-design_notes" name="units[{{ $key }}][design_notes]" @error('units.'.$key.'.design_notes') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror maxlength="2000" placeholder="Contoh: Nuansa putih dan merah muda, tambahkan nama penerima." rows="3">{{ $oldUnit('design_notes', $entry['design_notes'] ?? '') }}</textarea></label>
                @if($addons->isNotEmpty())
                <h3 class="bk-co-subhead">Item tambahan <span class="bk-muted-mark">(opsional)</span></h3>
                <ul class="bk-addon-list">
                @foreach($addons as $addon)
                    @php
                        $addonQty = (int) $oldUnit('addons.'.$addon->id, 0);
                        $addonField = 'units.'.$key.'.addons.'.$addon->id;
                    @endphp
                    <li class="bk-addon">
                        @if($addon->image_path)<img class="bk-addon__img" src="{{ asset('storage/'.$addon->image_path) }}" alt="" width="56" height="56" loading="lazy" decoding="async">@endif
                        <div class="bk-addon__text">
                            <strong>{{ $addon->name }}</strong>
                            <span class="bk-addon__price">Rp {{ number_format($addon->price, 0, ',', '.') }}</span>
                            @if($addon->description)<small>{{ $addon->description }}</small>@endif
                        </div>
                        <label class="bk-addon__qty" for="checkout-{{ $unitNumber }}-addon-{{ $addon->id }}">
                            <span>Jumlah</span>
                            <input type="number" inputmode="numeric" min="0" max="5" step="1" value="{{ $addonQty }}"
                                   id="checkout-{{ $unitNumber }}-addon-{{ $addon->id }}"
                                   name="units[{{ $key }}][addons][{{ $addon->id }}]"
                                   @error($addonField) aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror>
                        </label>
                    </li>
                @endforeach
                </ul>
                @endif
            </div>
            <div class="bk-co-panel" role="tabpanel" id="checkout-{{ $unitNumber }}-panel-3" aria-labelledby="checkout-{{ $unitNumber }}-tab-3" data-co-panel="3">
                <p class="bk-co-panel__hint" id="checkout-{{ $unitNumber }}-delivery-hint">Diambil sendiri atau diantar penjual. Lokasi pengambilan dikonfirmasi admin lewat WhatsApp.</p>
                <label for="checkout-{{ $unitNumber }}-delivery_method">Penerimaan <span class="bk-req">(wajib)</span><select id="checkout-{{ $unitNumber }}-delivery_method" name="units[{{ $key }}][delivery_method]" aria-describedby="{{ $errors->has('units.'.$key.'.delivery_method') ? 'checkout-errors-'.$unitNumber.' checkout-'.$unitNumber.'-delivery-hint' : 'checkout-'.$unitNumber.'-delivery-hint' }}" @error('units.'.$key.'.delivery_method') aria-invalid="true" @enderror required><option value="pickup" @selected($oldUnit('delivery_method', $entry['delivery_method'] ?? '') === 'pickup')>Diambil sendiri di lokasi kami</option><option value="delivery" @selected($oldUnit('delivery_method', $entry['delivery_method'] ?? '') === 'delivery')>Diantar penjual</option></select></label>
                <div data-delivery-fields>
                    @if($savedAddresses->isNotEmpty())
                    <div data-saved-address hidden><label>Pakai alamat tersimpan untuk pengantaran<select data-address-choice><option value="">Pilih alamat tersimpan</option>@foreach($savedAddresses as $address)<option value="{{ $address->id }}" data-address="{{ $address->full_address }}">{{ $address->label }}{{ $address->is_primary ? ' · utama' : '' }}</option>@endforeach</select></label></div>
                    @endif
                    <label for="checkout-{{ $unitNumber }}-delivery_address">Alamat pengantaran <span class="bk-muted-mark">(wajib jika diantar)</span><textarea placeholder="Contoh: Jl. Merdeka No. 12, Palembang" autocomplete="street-address" id="checkout-{{ $unitNumber }}-delivery_address" name="units[{{ $key }}][delivery_address]" @error('units.'.$key.'.delivery_address') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror maxlength="1000" rows="3">{{ $oldUnit('delivery_address', $entry['delivery_address'] ?? '') }}</textarea></label>
                    <label>Universitas tujuan pengantaran (opsional)<select id="checkout-{{ $unitNumber }}-delivery_university_id" name="units[{{ $key }}][delivery_university_id]" @error('units.'.$key.'.delivery_university_id') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror><option value="">Lokasi lain / tidak diantar</option>@foreach($universities as $university)<option value="{{ $university->id }}" @selected((string) old('units.'.$key.'.delivery_university_id') === (string) $university->id)>{{ $university->name }} · antar Rp {{ number_format($university->delivery_fee, 0, ',', '.') }}</option>@endforeach</select></label>
                </div>
                <p class="bk-co-panel__hint" id="checkout-{{ $unitNumber }}-return-hint">Dikembalikan sendiri atau dijemput penjual.</p>
                <label for="checkout-{{ $unitNumber }}-return_method">Pengembalian <span class="bk-req">(wajib)</span><select id="checkout-{{ $unitNumber }}-return_method" name="units[{{ $key }}][return_method]" aria-describedby="{{ $errors->has('units.'.$key.'.return_method') ? 'checkout-errors-'.$unitNumber.' checkout-'.$unitNumber.'-return-hint' : 'checkout-'.$unitNumber.'-return-hint' }}" @error('units.'.$key.'.return_method') aria-invalid="true" @enderror required><option value="dropoff" @selected($oldUnit('return_method', $entry['return_method'] ?? '') === 'dropoff')>Dikembalikan sendiri ke lokasi kami</option><option value="collection" @selected($oldUnit('return_method', $entry['return_method'] ?? '') === 'collection')>Diambil kembali penjual</option></select></label>
                <div data-return-fields>
                    @if($savedAddresses->isNotEmpty())
                    <div data-saved-address hidden><label>Pakai alamat tersimpan untuk penjemputan<select data-address-choice><option value="">Pilih alamat tersimpan</option>@foreach($savedAddresses as $address)<option value="{{ $address->id }}" data-address="{{ $address->full_address }}">{{ $address->label }}{{ $address->is_primary ? ' · utama' : '' }}</option>@endforeach</select></label></div>
                    @endif
                    <label for="checkout-{{ $unitNumber }}-return_address">Alamat pengambilan kembali <span class="bk-muted-mark">(wajib jika dijemput)</span><textarea placeholder="Contoh: Jl. Merdeka No. 12, Palembang" autocomplete="street-address" id="checkout-{{ $unitNumber }}-return_address" name="units[{{ $key }}][return_address]" @error('units.'.$key.'.return_address') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror maxlength="1000" rows="3">{{ $oldUnit('return_address', $entry['return_address'] ?? '') }}</textarea></label>
                    <label>Universitas lokasi pengambilan kembali (opsional)<select id="checkout-{{ $unitNumber }}-return_university_id" name="units[{{ $key }}][return_university_id]" @error('units.'.$key.'.return_university_id') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror><option value="">Lokasi lain / tidak dijemput</option>@foreach($universities as $university)<option value="{{ $university->id }}" @selected((string) old('units.'.$key.'.return_university_id') === (string) $university->id)>{{ $university->name }} · jemput Rp {{ number_format($university->collection_fee, 0, ',', '.') }}</option>@endforeach</select></label>
                </div>
                <p class="bk-co-panel__hint">Tarif universitas tampil di halaman pesanan; alamat lain ditawar admin lebih dulu.</p>
            </div>
            <footer class="bk-co-unit__nav">
                <button type="button" class="btn secondary" data-co-prev hidden>Kembali</button>
                <button type="button" class="btn" data-co-next>Lanjut: Ucapan &amp; desain</button>
            </footer>
        </section>
        @endforeach

        <details class="bk-co-card bk-co-more" id="checkout-references" @if($errors->hasAny(['reference_images', 'reference_images.*'])) open @endif>
            <summary>Foto referensi desain</summary>
            <div class="bk-co-more__body">
                <p class="bk-co-panel__hint">Berlaku untuk seluruh pesanan, bukan per papan.</p>
                @php
                    // Batas server bisa lebih kecil dari batas aplikasi (25 MB); beri tahu
                    // pelanggan sebelum mereka memilih berkas yang akan ditolak PHP.
                    $serverUploadMb = \App\Support\UploadLimit::effectiveMegabytes();
                    $serverUploadTight = $serverUploadMb > 0 && $serverUploadMb < 25;
                @endphp
                <label for="reference-images">Foto contoh<span id="reference-images" type="file" name="reference_images[]" accept="image/jpeg,image/png,image/webp" multiple aria-describedby="reference-images-hint{{ $serverUploadTight ? ' reference-images-server-hint' : '' }}"></label>
                <p class="muted" id="reference-images-hint">JPG, PNG, atau WebP. Maksimal 5 foto, 25 MB per foto. Hanya dikirim ke admin.</p>
                @if($serverUploadTight)
                <p class="muted" id="reference-images-server-hint">Server ini membatasi unggahan sekitar {{ $serverUploadMb }} MB per berkas. Perkecil dulu bila lebih besar.</p>
                @endif
                @error('reference_images')<p class="bk-field-error" id="reference-images-error" role="alert">{{ $message }}</p>@enderror
                @error('reference_images.*')<p class="bk-field-error" id="reference-images-item-error" role="alert">{{ $message }}</p>@enderror
            </div>
        </details>

        <details class="bk-co-card bk-co-more" id="checkout-voucher" @if($errors->has('voucher_code')) open @endif>
            <summary>Kode voucher</summary>
            <div class="bk-co-more__body">
                <label for="voucher_code">Kode voucher<span id="voucher_code" name="voucher_code" maxlength="40" autocomplete="off" autocapitalize="characters" spellcheck="false" value="{{ is_scalar(old('voucher_code')) ? old('voucher_code') : '' }}" @error('voucher_code') aria-invalid="true" aria-describedby="voucher-error" @enderror placeholder="Contoh: WISUDA10"></label>
                @error('voucher_code')<p class="bk-field-error" id="voucher-error" role="alert">{{ $message }}</p>@enderror
                <p><button type="button" class="btn secondary" data-voucher-check>Cek kode</button></p>
                <p class="muted" role="status" aria-live="polite" data-voucher-status>Kode diperiksa ulang saat pesanan dikirim.</p>
            </div>
        </details>

        <section class="bk-co-card bk-co-terms" id="checkout-terms" aria-labelledby="checkout-terms-title">
            <h2 id="checkout-terms-title">Ketentuan penyewaan</h2>
            <p class="bk-co-terms__summary">Tanggal ditahan paling lama 24 jam sampai admin mengonfirmasi. Pelunasan paling lambat H-1 pukul 23.59 WIB. DP hangus bila unit tidak dilanjutkan sampai H+1 pukul 12.00 WIB.</p>
            <details class="bk-co-terms__more">
                <summary>Baca lima poin lengkap</summary>
                <ul>
                    <li><strong>Konfirmasi dan stok.</strong> Tanggal langsung ditahan saat pesanan dikirim, paling lama 24 jam sampai admin mengonfirmasi. Bila belum dikonfirmasi sampai batas itu, unit dibatalkan dan tanggalnya dilepas kembali. Jadwal dekat membutuhkan persetujuan khusus.</li>
                    <li><strong>Jadwal yang bisa dipesan.</strong> Tanggal acara dan pengembalian harus dalam {{ \App\Services\RentalAvailability::BOOKING_HORIZON_DAYS }} hari ke depan dan satu unit maksimal {{ \App\Services\RentalAvailability::BOOKING_HORIZON_DAYS }} hari. Tanggal yang penuh ditandai di kalender dan tidak bisa dipilih.</li>
                    <li><strong>DP dan pelunasan.</strong> DP tidak memiliki minimum atau tenggat awal. Setiap unit harus lunas paling lambat H-1 pukul 23.59 WIB sebelum penerimaan. Waktu pengiriman bukti menjadi acuan ketepatan; pembayaran dinyatakan lunas hanya setelah diverifikasi.</li>
                    <li><strong>Jika tenggat terlewati.</strong> Unit tanpa pelunasan tepat waktu direncanakan batal dan stok dilepas. Sampai H+1 pukul 12.00 WIB bukti masih dapat dikirim dan admin dapat melanjutkan unit setelah pembayaran diverifikasi dan stok dicek. Setelah batas itu unit tidak dapat dilanjutkan dan DP hangus.</li>
                    <li><strong>DP dan pengembalian.</strong> Jika kesalahan penjual menyebabkan pembatalan, pembayaran dikembalikan. Tindak lanjut pengembalian masih manual.</li>
                </ul>
            </details>
        </section>
        </div>

        <aside class="bk-co__aside" aria-label="Kesiapan pesanan">
            <div class="bk-co-card bk-co-ready">
                <h2>Kesiapan pesanan</h2>
                <ul class="bk-co-checklist" data-co-checklist>
                    @foreach($cart as $key => $entry)
                        @php $checkProduct = $products->get($entry['product_id']); @endphp
                        <li data-co-check-unit="{{ $loop->iteration }}"><span class="bk-co-check__mark" aria-hidden="true"></span><span class="bk-co-check__text"><strong>Unit {{ $loop->iteration }} · {{ $checkProduct->name }}</strong><small data-co-check-count>Jadwal, ucapan, serah terima</small></span></li>
                    @endforeach
                    <li data-co-check="terms"><span class="bk-co-check__mark" aria-hidden="true"></span><span class="bk-co-check__text"><strong>Ketentuan</strong><small data-co-check-count>Centang persetujuan di bawah</small></span></li>
                </ul>
                <p class="bk-co-ready__note">Total dan ongkir tampil di halaman pesanan.</p>
                <label class="bk-consent"><input type="checkbox" name="terms" value="1" @checked(is_scalar(old('terms')) && old('terms')) @error('terms') aria-invalid="true" aria-describedby="checkout-terms-error" @enderror required><span>Saya telah membaca dan menyetujui ketentuan penyewaan, serta memahami pesanan ini masih dalam pengujian dan belum merupakan reservasi.</span></label>
                @error('terms')<p class="bk-field-error" id="checkout-terms-error" role="alert">{{ $message }}</p>@enderror
                <p class="bk-co-note"><strong>Masih dalam pengujian.</strong> Jangan kirim pembayaran nyata.</p>
                <button class="btn bk-co-submit" type="submit" data-checkout-submit data-bk-press>Kirim pesanan</button>
                <p class="muted" data-checkout-status role="status" aria-live="polite"></p>
            </div>
        </aside>
    </div>
    </form>
</div>
<script src="{{ asset('js/berkah-checkout.js') }}?v={{ filemtime(public_path('js/berkah-checkout.js')) }}" defer></script>
@endsection
