@extends('catalog.layout')
@section('title', 'Checkout')
@section('content')
<p><a href="{{ route('cart.index') }}">← Kembali ke keranjang</a></p><h1>Detail penyewaan</h1><p class="notice">Langkah 1: isi detail setiap unit. Langkah 2: baca dan setujui ketentuan. Langkah 3: kirim pesanan dan tunggu konfirmasi admin.</p><p class="muted">Isi kebutuhan untuk setiap unit. Stok belum dicadangkan sampai admin mengonfirmasi. Ongkos lokasi lain ditawarkan admin; tarif universitas mengikuti daftar saat checkout.</p>
<form class="stack checkout-form" data-checkout-form method="post" action="{{ route('rental.checkout.store') }}">@csrf
@foreach($cart as $key => $entry)
@php $product = $products->get($entry['product_id']); @endphp
<section class="card body" style="margin-bottom:16px" data-checkout-unit><h2>Unit {{ $loop->iteration }} dari {{ count($cart) }} · {{ $product->name }}</h2>
<p class="price">Harga dasar Rp {{ number_format($product->base_price, 0, ',', '.') }} / {{ $product->price_unit === 'day' ? 'hari' : 'acara' }}</p><p class="muted">Harga sewa dihitung berdasarkan tanggal atau paket yang dipilih, belum termasuk ongkos. Harga dan paket diperiksa kembali saat pesanan dikirim.</p><p><a href="{{ route('catalog.show', $product) }}" target="_blank" rel="noopener noreferrer">Periksa perkiraan ketersediaan (buka tab baru)</a></p>
@if(!$loop->first)<button type="button" class="btn secondary" data-copy-previous hidden>Salin detail dari unit sebelumnya</button><p class="muted" data-copy-help hidden>Menyalin jadwal, penerima, ucapan, desain, dan serah terima; pilihan paket tidak disalin. Periksa kembali sebelum mengirim.</p>@endif
<h3>Jadwal sewa</h3><p class="muted">Tanggal acara sekaligus menjadi tanggal papan diterima, baik diantar maupun diambil di outlet. Jika sewa hanya satu hari, pilih tanggal pengembalian yang sama.</p><div class="row"><label>Tanggal acara dan penerimaan<input type="date" name="units[{{ $key }}][event_on]" value="{{ old('units.'.$key.'.event_on') }}" min="{{ now('Asia/Jakarta')->toDateString() }}" required></label><label>Tanggal pengembalian<input type="date" name="units[{{ $key }}][ends_on]" value="{{ old('units.'.$key.'.ends_on') }}" required></label></div>
@if($product && $product->packages->isNotEmpty())<label>Pilihan harga<select name="units[{{ $key }}][package_id]"><option value="">Harga dasar (per hari/acara)</option>@foreach($product->packages as $package)<option value="{{ $package->id }}" @selected((string) old('units.'.$key.'.package_id') === (string) $package->id)>{{ $package->name }} · {{ $package->days }} hari · Rp {{ number_format($package->price, 0, ',', '.') }}</option>@endforeach</select></label><p class="muted">Jumlah hari dihitung termasuk tanggal penerimaan dan pengembalian; durasi harus sama dengan paket.</p>@endif
<h3>Ucapan dan desain</h3><label>Nama penerima<input name="units[{{ $key }}][recipient_name]" value="{{ old('units.'.$key.'.recipient_name') }}" maxlength="160" required></label><label>Ucapan<textarea name="units[{{ $key }}][greeting]" maxlength="1000" required>{{ old('units.'.$key.'.greeting') }}</textarea></label><label>Permintaan desain<textarea name="units[{{ $key }}][design_notes]" maxlength="2000">{{ old('units.'.$key.'.design_notes') }}</textarea></label>
<h3>Serah terima</h3><label>Penerimaan<select name="units[{{ $key }}][delivery_method]" required><option value="pickup" @selected(old('units.'.$key.'.delivery_method') === 'pickup')>Diambil di outlet</option><option value="delivery" @selected(old('units.'.$key.'.delivery_method') === 'delivery')>Diantar penjual</option></select></label><div data-delivery-fields>
@if($savedAddresses->isNotEmpty())<div data-saved-address hidden><label>Pakai alamat tersimpan untuk pengantaran<select data-address-choice><option value="">Pilih alamat tersimpan</option>@foreach($savedAddresses as $address)<option value="{{ $address->id }}" data-address="{{ $address->full_address }}">{{ $address->label }}{{ $address->is_primary ? ' · utama' : '' }}</option>@endforeach</select></label><p class="muted">Pilihan akan menyalin alamat ke kolom di bawah; periksa dan lengkapi jika perlu.</p></div>@endif
<label>Alamat pengantaran (wajib jika diantar)<textarea name="units[{{ $key }}][delivery_address]" maxlength="1000">{{ old('units.'.$key.'.delivery_address') }}</textarea></label>
<label>Universitas tujuan pengantaran (opsional)<select name="units[{{ $key }}][delivery_university_id]"><option value="">Lokasi lain / tidak diantar</option>@foreach($universities as $university)<option value="{{ $university->id }}" @selected((string) old('units.'.$key.'.delivery_university_id') === (string) $university->id)>{{ $university->name }} · antar Rp {{ number_format($university->delivery_fee, 0, ',', '.') }}</option>@endforeach</select></label>
</div><label>Pengembalian<select name="units[{{ $key }}][return_method]" required><option value="dropoff" @selected(old('units.'.$key.'.return_method') === 'dropoff')>Dikembalikan ke outlet</option><option value="collection" @selected(old('units.'.$key.'.return_method') === 'collection')>Diambil kembali penjual</option></select></label><div data-return-fields>
@if($savedAddresses->isNotEmpty())<div data-saved-address hidden><label>Pakai alamat tersimpan untuk penjemputan<select data-address-choice><option value="">Pilih alamat tersimpan</option>@foreach($savedAddresses as $address)<option value="{{ $address->id }}" data-address="{{ $address->full_address }}">{{ $address->label }}{{ $address->is_primary ? ' · utama' : '' }}</option>@endforeach</select></label><p class="muted">Pilihan akan menyalin alamat ke kolom di bawah; periksa dan lengkapi jika perlu.</p></div>@endif
<label>Alamat pengambilan kembali (wajib jika dijemput)<textarea name="units[{{ $key }}][return_address]" maxlength="1000">{{ old('units.'.$key.'.return_address') }}</textarea></label>
<label>Universitas lokasi pengambilan kembali (opsional)<select name="units[{{ $key }}][return_university_id]"><option value="">Lokasi lain / tidak dijemput</option>@foreach($universities as $university)<option value="{{ $university->id }}" @selected((string) old('units.'.$key.'.return_university_id') === (string) $university->id)>{{ $university->name }} · jemput Rp {{ number_format($university->collection_fee, 0, ',', '.') }}</option>@endforeach</select></label></div><p class="muted">Pilihan universitas hanya digunakan jika metode terkait adalah diantar atau dijemput. Isi alamat lengkap agar lokasi dapat ditemukan. Tarif akan ditampilkan kembali untuk disetujui.</p>
</section>@endforeach
<div class="notice"><strong>Masih dalam pengujian.</strong> Setelah dikirim, pesanan menunggu konfirmasi admin. Penjadwal perlu berjalan untuk memproses tenggat; jangan gunakan data atau pembayaran nyata.</div>
<section class="card body checkout-terms">
<h2>Ketentuan penyewaan</h2>
<ul>
<li><strong>Konfirmasi dan stok.</strong> Stok dicadangkan setelah admin mengonfirmasi, paling lambat 24 jam setelah pesanan dikirim. Unit yang belum dikonfirmasi setelah batas tersebut dibatalkan bila penjadwal berjalan. Jadwal dekat membutuhkan persetujuan khusus.</li>
<li><strong>DP dan pelunasan.</strong> DP tidak memiliki minimum atau tenggat awal. Setiap unit harus lunas paling lambat H-1 pukul 23.59 WIB sebelum penerimaan. Waktu pengiriman bukti menjadi acuan ketepatan; bukti pelunasan yang dikirim tepat waktu menahan pembatalan sambil menunggu verifikasi. Pembayaran dinyatakan lunas hanya setelah diverifikasi.</li>
<li><strong>Jika tenggat terlewati.</strong> Unit tanpa pelunasan atau bukti pelunasan tepat waktu direncanakan batal dan stok dilepas. Pada hari penerimaan, admin dapat melanjutkan setelah pembayaran diverifikasi dan stok dicek; ada risiko terlambat. Mulai pukul 00.00 WIB hari berikutnya, unit tidak dapat dilanjutkan.</li>
<li><strong>DP dan pengembalian.</strong> DP lama dihitung jika unit dilanjutkan; jika tidak dilanjutkan hingga batas itu, DP hangus. Jika kesalahan penjual menyebabkan pembatalan, pembayaran dikembalikan. Tindak lanjut pengembalian masih manual.</li>
</ul><p><strong>Alur ini masih diuji. Jangan kirim pembayaran nyata.</strong></p></section>
<label><input style="width:auto" type="checkbox" name="terms" value="1" @checked(old('terms')) required> Saya memahami pesanan ini masih dalam tahap pengembangan dan belum merupakan reservasi.</label><p><button class="btn" type="submit" data-checkout-submit>Kirim pesanan uji coba</button></p><p class="muted" data-checkout-status role="status" aria-live="polite"></p></form>
<script>
const checkoutForm = document.querySelector('[data-checkout-form]');
const checkoutSubmit = document.querySelector('[data-checkout-submit]');
const checkoutStatus = document.querySelector('[data-checkout-status]');
checkoutForm.addEventListener('submit', event => {
    if (checkoutSubmit.disabled) { event.preventDefault(); return; }
    checkoutSubmit.disabled = true;
    checkoutSubmit.textContent = 'Mengirim pesanan...';
    checkoutStatus.textContent = 'Mohon tunggu. Jangan kirim ulang atau tutup halaman.';
});
window.addEventListener('pageshow', () => {
    checkoutSubmit.disabled = false;
    checkoutSubmit.textContent = 'Kirim pesanan uji coba';
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
    const toggleFields = (selectName, expected, fieldsSelector) => {
        const select = unit.querySelector('[name$="[' + selectName + ']"]');
        const fields = unit.querySelector(fieldsSelector);
        const update = () => {
            const visible = select.value === expected;
            fields.hidden = !visible;
            fields.querySelectorAll('input, select, textarea').forEach(input => { input.disabled = !visible; });
            fields.querySelector('textarea').required = visible;
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
            if (target.tagName === 'SELECT' && !Array.from(target.options).some(option => option.value === source.value)) return;
            target.value = source.value;
            target.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
});
</script>
@endsection
