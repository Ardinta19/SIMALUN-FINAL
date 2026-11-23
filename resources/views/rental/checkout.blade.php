@extends('catalog.layout')
@section('title', 'Checkout')
@section('content')
<div class="bk-checkout-heading"><a class="bk-back" href="{{ route('cart.index') }}">← Kembali ke keranjang</a><span class="bk-kicker">DETAIL PESANAN</span><h1>Detail penyewaan</h1><p class="muted">Siapkan informasi untuk setiap papan, lalu periksa ketentuannya sebelum mengirim. Butuh unit lain? <a href="{{ route('cart.index') }}">Tambah atau kurangi unit di keranjang</a> dulu agar isian unit di halaman ini tidak hilang.</p><ol class="bk-checkout-steps" aria-label="Tahapan penyewaan"><li><span>1</span>Isi detail unit</li><li><span>2</span>Baca ketentuan</li><li><span>3</span>Kirim pesanan</li></ol></div><p class="muted">Isi kebutuhan untuk setiap unit. Ketersediaan diperiksa saat pesanan dikirim dan tanggalnya langsung ditahan sampai admin mengonfirmasi (paling lama 24 jam). Ongkos lokasi lain ditawarkan admin; tarif universitas mengikuti daftar saat checkout.</p>
@if(count($cart) > 1)
<nav class="bk-jump" aria-label="Lompat ke unit checkout"><strong>Langsung ke</strong><div>@foreach($cart as $key => $entry)<a href="#checkout-unit-{{ $loop->iteration }}">Unit {{ $loop->iteration }}</a>@endforeach <a href="#checkout-terms">Ketentuan</a></div></nav>
@endif
<form class="stack checkout-form" data-checkout-form method="post" action="{{ route('rental.checkout.store') }}">@csrf
@foreach($cart as $key => $entry)
@php
    $product = $products->get($entry['product_id']);
    $unitNumber = $loop->iteration;
    $fieldLabels = ['event_on' => 'Tanggal acara dan penerimaan', 'ends_on' => 'Tanggal pengembalian', 'recipient_name' => 'Nama penerima', 'greeting' => 'Ucapan', 'design_notes' => 'Permintaan desain', 'delivery_method' => 'Penerimaan', 'delivery_address' => 'Alamat pengantaran', 'delivery_university_id' => 'Universitas pengantaran', 'return_method' => 'Pengembalian', 'return_address' => 'Alamat pengambilan kembali', 'return_university_id' => 'Universitas pengambilan kembali'];
    if ($product->packages->isNotEmpty()) $fieldLabels['package_id'] = 'Pilihan harga';
    $oldUnit = fn (string $field, $fallback = '') => is_scalar($value = old('units.'.$key.'.'.$field, $fallback)) ? (string) $value : (string) $fallback;
    $today = now('Asia/Jakarta')->toDateString();
    $chosenEvent = $oldUnit('event_on', $entry['starts_on'] ?? '');
    $returnMin = preg_match('/^\d{4}-\d{2}-\d{2}$/', $chosenEvent) && $chosenEvent > $today ? $chosenEvent : $today;
@endphp
<section class="card body" style="margin-bottom:16px" id="checkout-unit-{{ $loop->iteration }}" data-checkout-unit aria-labelledby="checkout-heading-{{ $unitNumber }}"><h2 id="checkout-heading-{{ $unitNumber }}">Unit {{ $loop->iteration }} dari {{ count($cart) }} · {{ $product->name }}</h2>
@if(collect(array_keys($fieldLabels))->contains(fn ($field) => $errors->has('units.'.$key.'.'.$field)))
<div class="bk-unit-errors" id="checkout-errors-{{ $unitNumber }}"><strong>Periksa isian unit {{ $unitNumber }}:</strong><ul>@foreach($fieldLabels as $field => $label)@if($errors->has('units.'.$key.'.'.$field))<li><a href="#checkout-{{ $unitNumber }}-{{ $field }}">{{ $label }}</a></li>@endif @endforeach</ul></div>
@endif
<p class="price">Harga dasar Rp {{ number_format($product->base_price, 0, ',', '.') }} / {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</p><p class="muted">Harga sewa dihitung berdasarkan tanggal atau paket yang dipilih, belum termasuk ongkos. Harga dan paket diperiksa kembali saat pesanan dikirim.</p><p><a class="btn secondary" href="{{ route('catalog.show', $product) }}" target="_blank" rel="noopener noreferrer">Periksa perkiraan ketersediaan (buka tab baru)</a></p>
@if(!$loop->first)<button type="button" class="btn secondary" data-copy-previous hidden>Salin detail dari unit sebelumnya</button><p class="muted" data-copy-help hidden>Menyalin jadwal, penerima, ucapan, desain, dan serah terima; pilihan paket tidak disalin. Periksa kembali sebelum mengirim.</p><p class="muted" role="status" aria-live="polite" data-copy-status></p>@endif
<h3>Jadwal sewa</h3><p class="muted">Tanggal acara sekaligus menjadi tanggal papan diterima, baik diantar maupun diambil di outlet. Jika sewa hanya satu hari, pilih tanggal pengembalian yang sama.</p><div class="row"><label for="checkout-{{ $unitNumber }}-event_on">Tanggal acara dan penerimaan <span class="bk-req">(wajib)</span><input type="date" id="checkout-{{ $unitNumber }}-event_on" name="units[{{ $key }}][event_on]" data-fc-range="unit-{{ $key }}" data-fc-unit="{{ $key }}" data-fc-product="{{ $product->id }}" data-fc-stock="{{ (int) $product->stock }}" data-fc-refresh-url="{{ route('catalog.availability', $product) }}" @error('units.'.$key.'.event_on') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror value="{{ $oldUnit('event_on', $entry['starts_on'] ?? '') }}" min="{{ now('Asia/Jakarta')->toDateString() }}" max="{{ now('Asia/Jakarta')->addDays(119)->toDateString() }}" data-fc-disabled='@json($bookedDates[$product->id] ?? [])' required></label><label for="checkout-{{ $unitNumber }}-ends_on">Tanggal pengembalian <span class="bk-req">(wajib)</span><input type="date" id="checkout-{{ $unitNumber }}-ends_on" name="units[{{ $key }}][ends_on]" data-fc-range="unit-{{ $key }}" data-fc-unit="{{ $key }}" data-fc-product="{{ $product->id }}" data-fc-stock="{{ (int) $product->stock }}" data-fc-refresh-url="{{ route('catalog.availability', $product) }}" @error('units.'.$key.'.ends_on') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror value="{{ $oldUnit('ends_on', $entry['ends_on'] ?? '') }}" min="{{ $returnMin }}" max="{{ now('Asia/Jakarta')->addDays(119)->toDateString() }}" data-fc-disabled='@json($bookedDates[$product->id] ?? [])' required></label></div>
@php
    $sameProductUnits = collect($cart)->where('product_id', $product->id)->count();
    $hasLockedDates = !empty($bookedDates[$product->id] ?? []) || $sameProductUnits > 1;
@endphp
@if($hasLockedDates)<p class="bk-cal-hint" id="checkout-{{ $unitNumber }}-booked-hint">Tanggal yang sudah dibooking tidak bisa dipilih; kalender menandainya dengan garis. Tanggal yang sudah dipakai unit lain dengan papan yang sama di keranjang ini juga terkunci.</p>@endif
<p class="bk-date-shortcut"><button type="button" class="btn secondary" data-same-day hidden>Pakai tanggal yang sama (sewa satu hari)</button><span class="muted" data-same-day-status role="status" aria-live="polite"></span></p>
@if($product && $product->packages->isNotEmpty())<label>Pilihan harga <span class="bk-muted-mark">(opsional)</span><select id="checkout-{{ $unitNumber }}-package_id" name="units[{{ $key }}][package_id]" @error('units.'.$key.'.package_id') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror><option value="">Harga dasar (per hari/acara)</option>@foreach($product->packages as $package)<option value="{{ $package->id }}" @selected((string) old('units.'.$key.'.package_id') === (string) $package->id)>{{ $package->name }} · {{ $package->days }} hari · Rp {{ number_format($package->price, 0, ',', '.') }}</option>@endforeach</select></label><p class="muted">Jumlah hari dihitung termasuk tanggal penerimaan dan pengembalian; durasi harus sama dengan paket.</p>@endif
<h3>Ucapan dan desain</h3><label for="checkout-{{ $unitNumber }}-recipient_name">Nama penerima <span class="bk-req">(wajib)</span><input autocomplete="off" id="checkout-{{ $unitNumber }}-recipient_name" name="units[{{ $key }}][recipient_name]" @error('units.'.$key.'.recipient_name') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror value="{{ $oldUnit('recipient_name') }}" maxlength="160" placeholder="Contoh: Ibu Sari" required></label><label for="checkout-{{ $unitNumber }}-greeting">Ucapan <span class="bk-req">(wajib)</span><textarea id="checkout-{{ $unitNumber }}-greeting" name="units[{{ $key }}][greeting]" @error('units.'.$key.'.greeting') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror maxlength="1000" placeholder="Contoh: Selamat atas wisudamu, semoga sukses selalu." required>{{ $oldUnit('greeting') }}</textarea></label><label for="checkout-{{ $unitNumber }}-design_notes">Permintaan desain <span class="bk-muted-mark">(opsional)</span><textarea id="checkout-{{ $unitNumber }}-design_notes" name="units[{{ $key }}][design_notes]" @error('units.'.$key.'.design_notes') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror maxlength="2000" placeholder="Contoh: Nuansa putih dan merah muda, tambahkan nama penerima.">{{ $oldUnit('design_notes') }}</textarea></label>
<h3>Serah terima</h3><label for="checkout-{{ $unitNumber }}-delivery_method">Penerimaan <span class="bk-req">(wajib)</span><select id="checkout-{{ $unitNumber }}-delivery_method" name="units[{{ $key }}][delivery_method]" aria-describedby="{{ $errors->has('units.'.$key.'.delivery_method') ? 'checkout-errors-'.$unitNumber.' checkout-'.$unitNumber.'-delivery-hint' : 'checkout-'.$unitNumber.'-delivery-hint' }}" @error('units.'.$key.'.delivery_method') aria-invalid="true" @enderror required><option value="pickup" @selected(old('units.'.$key.'.delivery_method') === 'pickup')>Diambil di outlet</option><option value="delivery" @selected(old('units.'.$key.'.delivery_method') === 'delivery')>Diantar penjual</option></select></label><div data-delivery-fields>
@if($savedAddresses->isNotEmpty())<div data-saved-address hidden><label>Pakai alamat tersimpan untuk pengantaran<select data-address-choice><option value="">Pilih alamat tersimpan</option>@foreach($savedAddresses as $address)<option value="{{ $address->id }}" data-address="{{ $address->full_address }}">{{ $address->label }}{{ $address->is_primary ? ' · utama' : '' }}</option>@endforeach</select></label><p class="muted">Pilihan akan menyalin alamat ke kolom di bawah; periksa dan lengkapi jika perlu.</p></div>@endif
<label for="checkout-{{ $unitNumber }}-delivery_address">Alamat pengantaran <span class="bk-muted-mark">(wajib jika diantar)</span><textarea autocomplete="street-address" id="checkout-{{ $unitNumber }}-delivery_address" name="units[{{ $key }}][delivery_address]" @error('units.'.$key.'.delivery_address') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror maxlength="1000">{{ $oldUnit('delivery_address') }}</textarea></label>
<label>Universitas tujuan pengantaran (opsional)<select id="checkout-{{ $unitNumber }}-delivery_university_id" name="units[{{ $key }}][delivery_university_id]" @error('units.'.$key.'.delivery_university_id') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror><option value="">Lokasi lain / tidak diantar</option>@foreach($universities as $university)<option value="{{ $university->id }}" @selected((string) old('units.'.$key.'.delivery_university_id') === (string) $university->id)>{{ $university->name }} · antar Rp {{ number_format($university->delivery_fee, 0, ',', '.') }}</option>@endforeach</select></label>
</div><label for="checkout-{{ $unitNumber }}-return_method">Pengembalian <span class="bk-req">(wajib)</span><select id="checkout-{{ $unitNumber }}-return_method" name="units[{{ $key }}][return_method]" aria-describedby="{{ $errors->has('units.'.$key.'.return_method') ? 'checkout-errors-'.$unitNumber.' checkout-'.$unitNumber.'-return-hint' : 'checkout-'.$unitNumber.'-return-hint' }}" @error('units.'.$key.'.return_method') aria-invalid="true" @enderror required><option value="dropoff" @selected(old('units.'.$key.'.return_method') === 'dropoff')>Dikembalikan ke outlet</option><option value="collection" @selected(old('units.'.$key.'.return_method') === 'collection')>Diambil kembali penjual</option></select></label><div data-return-fields>
@if($savedAddresses->isNotEmpty())<div data-saved-address hidden><label>Pakai alamat tersimpan untuk penjemputan<select data-address-choice><option value="">Pilih alamat tersimpan</option>@foreach($savedAddresses as $address)<option value="{{ $address->id }}" data-address="{{ $address->full_address }}">{{ $address->label }}{{ $address->is_primary ? ' · utama' : '' }}</option>@endforeach</select></label><p class="muted">Pilihan akan menyalin alamat ke kolom di bawah; periksa dan lengkapi jika perlu.</p></div>@endif
<label for="checkout-{{ $unitNumber }}-return_address">Alamat pengambilan kembali <span class="bk-muted-mark">(wajib jika dijemput)</span><textarea autocomplete="street-address" id="checkout-{{ $unitNumber }}-return_address" name="units[{{ $key }}][return_address]" @error('units.'.$key.'.return_address') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror maxlength="1000">{{ $oldUnit('return_address') }}</textarea></label>
<label>Universitas lokasi pengambilan kembali (opsional)<select id="checkout-{{ $unitNumber }}-return_university_id" name="units[{{ $key }}][return_university_id]" @error('units.'.$key.'.return_university_id') aria-invalid="true" aria-describedby="checkout-errors-{{ $unitNumber }}" @enderror><option value="">Lokasi lain / tidak dijemput</option>@foreach($universities as $university)<option value="{{ $university->id }}" @selected((string) old('units.'.$key.'.return_university_id') === (string) $university->id)>{{ $university->name }} · jemput Rp {{ number_format($university->collection_fee, 0, ',', '.') }}</option>@endforeach</select></label></div><p class="muted" id="checkout-{{ $unitNumber }}-delivery-hint">Diantar penjual: lengkapi alamat tujuan. Diambil di outlet: alamat tidak perlu diisi.</p><p class="muted" id="checkout-{{ $unitNumber }}-return-hint">Dikembalikan ke outlet: alamat tidak perlu diisi. Diambil penjual: lengkapi alamat penjemputan.</p><p class="muted">Pilihan universitas hanya digunakan jika metode terkait adalah diantar atau dijemput. Isi alamat lengkap agar lokasi dapat ditemukan. Tarif akan ditampilkan kembali untuk disetujui.</p>
</section>@endforeach
<div class="notice"><strong>Masih dalam pengujian.</strong> Setelah dikirim, pesanan menunggu konfirmasi admin. Penjadwal perlu berjalan untuk memproses tenggat; jangan gunakan data atau pembayaran nyata.</div>
<section class="card body checkout-terms" id="checkout-terms">
<h2>Ketentuan penyewaan</h2>
<ul>
<li><strong>Konfirmasi dan stok.</strong> Tanggal langsung ditahan saat pesanan dikirim, paling lama 24 jam sampai admin mengonfirmasi. Bila belum dikonfirmasi sampai batas itu, unit dibatalkan dan tanggalnya dilepas kembali. Jadwal dekat membutuhkan persetujuan khusus.</li>
<li><strong>DP dan pelunasan.</strong> DP tidak memiliki minimum atau tenggat awal. Setiap unit harus lunas paling lambat H-1 pukul 23.59 WIB sebelum penerimaan. Waktu pengiriman bukti menjadi acuan ketepatan; bukti pelunasan yang dikirim tepat waktu menahan pembatalan sambil menunggu verifikasi. Pembayaran dinyatakan lunas hanya setelah diverifikasi.</li>
<li><strong>Jika tenggat terlewati.</strong> Unit tanpa pelunasan atau bukti pelunasan tepat waktu direncanakan batal dan stok dilepas. Pada hari penerimaan, admin dapat melanjutkan setelah pembayaran diverifikasi dan stok dicek; ada risiko terlambat. Mulai pukul 00.00 WIB hari berikutnya, unit tidak dapat dilanjutkan.</li>
<li><strong>DP dan pengembalian.</strong> DP lama dihitung jika unit dilanjutkan; jika tidak dilanjutkan hingga batas itu, DP hangus. Jika kesalahan penjual menyebabkan pembatalan, pembayaran dikembalikan. Tindak lanjut pengembalian masih manual.</li>
</ul><p><strong>Alur ini masih diuji. Jangan kirim pembayaran nyata.</strong></p></section>
<label class="bk-consent"><input style="width:auto" type="checkbox" name="terms" value="1" @checked(is_scalar(old('terms')) && old('terms')) @error('terms') aria-invalid="true" aria-describedby="checkout-terms-error" @enderror required><span>Saya telah membaca dan menyetujui ketentuan penyewaan di atas, serta memahami pesanan ini masih dalam pengujian dan belum merupakan reservasi.</span></label>@error('terms')<p class="bk-field-error" id="checkout-terms-error">{{ $message }}</p>@enderror<p><button class="btn" type="submit" data-checkout-submit>Kirim pesanan uji coba</button></p><p class="muted" data-checkout-status role="status" aria-live="polite"></p></form>
<script>
const checkoutForm = document.querySelector('[data-checkout-form]');
const checkoutSubmit = document.querySelector('[data-checkout-submit]');
const checkoutStatus = document.querySelector('[data-checkout-status]');
checkoutForm.addEventListener('submit', event => {
    if (checkoutSubmit.disabled) { event.preventDefault(); return; }
    checkoutSubmit.disabled = true;
    checkoutSubmit.textContent = 'Mengirim pesanan...';
    checkoutForm.setAttribute('aria-busy', 'true');
    checkoutStatus.textContent = 'Mohon tunggu. Jangan kirim ulang atau tutup halaman.';
});
const firstInvalid = checkoutForm.querySelector('[aria-invalid="true"]');
if (firstInvalid) {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    firstInvalid.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
    firstInvalid.focus({ preventScroll: true });
}

window.addEventListener('pageshow', () => {
    checkoutSubmit.disabled = false;
    checkoutSubmit.textContent = 'Kirim pesanan uji coba';
    checkoutForm.removeAttribute('aria-busy');
    checkoutStatus.textContent = '';
});

document.querySelectorAll('[data-checkout-unit]').forEach((unit, index, units) => {
    const button = unit.querySelector('[data-copy-previous]');
    const eventDate = unit.querySelector('[name$="[event_on]"]');
    const returnDate = unit.querySelector('[name$="[ends_on]"]');
    const updateDates = () => {
        returnDate.min = eventDate.value;
        if (eventDate.value && !returnDate.value) returnDate.value = eventDate.value;
    };
    eventDate.addEventListener('change', updateDates);
    updateDates();
    const sameDay = unit.querySelector('[data-same-day]');
    const sameDayStatus = unit.querySelector('[data-same-day-status]');
    if (sameDay) {
        sameDay.hidden = false;
        sameDay.addEventListener('click', () => {
            if (!eventDate.value) {
                sameDayStatus.textContent = 'Isi tanggal acara terlebih dahulu.';
                eventDate.focus();
                return;
            }
            returnDate.value = eventDate.value;
            returnDate.dispatchEvent(new Event('change', { bubbles: true }));
            sameDayStatus.textContent = 'Tanggal pengembalian disamakan dengan tanggal acara.';
        });
    }
    const toggleFields = (selectName, expected, fieldsSelector) => {
        const select = unit.querySelector('[name$="[' + selectName + ']"]');
        const fields = unit.querySelector(fieldsSelector);
        const update = () => {
            // Keep server-invalid fields reachable even if the method is currently inactive.
            const visible = select.value === expected || !!fields.querySelector('[aria-invalid="true"]');
            fields.hidden = !visible;
            fields.querySelectorAll('input, select, textarea').forEach(input => { input.disabled = !visible; });
            fields.querySelector('textarea').required = select.value === expected;
        };
        select.addEventListener('change', update);
        update();
    };
    unit.querySelectorAll('[data-saved-address]').forEach(wrapper => {
        const choice = wrapper.querySelector('[data-address-choice]');
        const target = wrapper.parentElement.querySelector('textarea');
        wrapper.hidden = false;
        choice.addEventListener('change', () => {
            const option = choice.options[choice.selectedIndex];
            if (!option.value) return;
            const address = option.dataset.address;
            if (target.value && target.value !== address && !window.confirm('Ganti alamat yang sudah diisi dengan alamat tersimpan?')) {
                choice.value = '';
                return;
            }
            target.value = address;
        });
    });
    toggleFields('delivery_method', 'delivery', '[data-delivery-fields]');
    toggleFields('return_method', 'collection', '[data-return-fields]');
    if (!button) return;
    button.hidden = false;
    unit.querySelector('[data-copy-help]').hidden = false;
    button.addEventListener('click', () => {
        if (!window.confirm('Salin detail dari unit sebelumnya? Isian unit ini akan diganti, kecuali pilihan paket.')) return;
        const previous = units[index - 1];
        const fields = ['event_on', 'ends_on', 'recipient_name', 'greeting', 'design_notes', 'delivery_method', 'delivery_address', 'delivery_university_id', 'return_method', 'return_address', 'return_university_id'];
        fields.forEach(field => {
            const source = previous.querySelector('[name$="[' + field + ']"]');
            const target = unit.querySelector('[name$="[' + field + ']"]');
            if (!source || !target) return;
            if (target.tagName === 'SELECT' && ![...target.options].some(option => option.value === source.value)) return;
            target.value = source.value;
            target.dispatchEvent(new Event('change', { bubbles: true }));
        });
        const status = unit.querySelector('[data-copy-status]');
        if (status) status.textContent = 'Detail unit sebelumnya disalin. Pilihan paket tidak berubah; periksa semua isian sebelum mengirim.';
    });
});
</script>
@endsection
