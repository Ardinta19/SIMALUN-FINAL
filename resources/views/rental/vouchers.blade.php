@extends('catalog.layout')
@section('title', 'Voucher sewa')
@section('content')
<p><a class="bk-back" href="{{ route('dashboard.admin') }}">← Beranda admin</a></p>
<h1>Voucher sewa</h1>
<p class="muted">Pelanggan memasukkan kode ini di halaman checkout. Admin menentukan besaran potongan, apakah potongan ikut menutup ongkos, kuota, dan berapa kali satu pelanggan boleh memakainya. Pesanan yang sudah dibuat tidak berubah saat voucher dinonaktifkan.</p>

<section class="card body"><h2>Buat voucher</h2>
<form class="stack" method="post" action="{{ route('admin.rental.vouchers.store') }}">@csrf
<div class="row">
<label>Kode voucher <span class="bk-req">(wajib)</span><input name="code" maxlength="30" required autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="Contoh: WISUDA10" value="{{ old('code') }}" @error('code') aria-invalid="true" aria-describedby="voucher-code-error" @enderror></label>
<label>Keterangan singkat <span class="bk-req">(wajib)</span><input name="description" maxlength="200" required value="{{ old('description') }}" placeholder="Contoh: Potongan wisuda Desember" @error('description') aria-invalid="true" @enderror></label>
</div>
@error('code')<p class="bk-field-error" id="voucher-code-error">{{ $message }}</p>@enderror
<div class="row">
<label>Jenis potongan<select name="type">@foreach(['percent' => 'Persen dari tagihan', 'fixed' => 'Nominal tetap'] as $value => $label)<option value="{{ $value }}" @selected(old('type', 'percent') === $value)>{{ $label }}</option>@endforeach</select></label>
<label>Nilai potongan <span class="bk-req">(wajib)</span><input type="number" name="value" min="1" max="100000000" required value="{{ old('value') }}" @error('value') aria-invalid="true" aria-describedby="voucher-value-error" @enderror><span class="muted">Persen (1–100) atau rupiah.</span></label>
</div>
@error('value')<p class="bk-field-error" id="voucher-value-error">{{ $message }}</p>@enderror
<label>Potongan berlaku untuk<select name="scope"><option value="rental" @selected(old('scope', 'rental') === 'rental')>Hanya harga sewa</option><option value="rental_transport" @selected(old('scope') === 'rental_transport')>Harga sewa + ongkos antar/jemput</option></select><span class="muted">Bila ongkos ikut dipotong, potongan ongkos dihitung setelah pelanggan menyetujui ongkos.</span></label>
<div class="row">
<label>Minimum belanja (Rp)<input type="number" name="min_order" min="0" max="400000000" value="{{ old('min_order', 0) }}"><span class="muted">0 berarti tanpa minimum.</span></label>
<label>Batas potongan persen (Rp)<input type="number" name="max_discount" min="1" max="400000000" value="{{ old('max_discount') }}"><span class="muted">Kosongkan bila tanpa batas. Hanya untuk jenis persen.</span></label>
</div>
<div class="row">
<label>Kuota total pemakaian<input type="number" name="usage_limit" min="1" max="1000000" value="{{ old('usage_limit') }}"><span class="muted">Kosongkan bila tanpa batas.</span></label>
<label>Batas per pelanggan<select name="per_customer_limit"><option value="1" @selected(old('per_customer_limit', '1') === '1')>Sekali per pelanggan</option>@foreach([3, 5, 10] as $limit)<option value="{{ $limit }}" @selected((string) old('per_customer_limit') === (string) $limit)>{{ $limit }} kali per pelanggan</option>@endforeach<option value="" @selected(old('per_customer_limit') === '')>Tanpa batas per pelanggan</option></select></label>
</div>
<div class="row">
<label>Berlaku mulai<input type="date" name="valid_from" value="{{ old('valid_from') }}"><span class="muted">Kosongkan bila berlaku sekarang.</span></label>
<label>Berlaku sampai<input type="date" name="valid_until" value="{{ old('valid_until') }}" @error('valid_until') aria-invalid="true" aria-describedby="voucher-until-error" @enderror><span class="muted">Kosongkan bila tanpa batas tanggal.</span></label>
</div>
@error('valid_until')<p class="bk-field-error" id="voucher-until-error">{{ $message }}</p>@enderror
<label class="bk-consent"><input style="width:auto" type="checkbox" name="is_active" value="1" checked><span>Langsung aktifkan voucher ini.</span></label>
<p><button class="btn">Simpan voucher</button></p>
</form></section>

<h2>Daftar voucher</h2>
<div class="items">
@forelse($vouchers as $voucher)
<section class="card body">
<h3>{{ $voucher->code }} · {{ $voucher->is_active ? 'Aktif' : 'Nonaktif' }}</h3>
<p>{{ $voucher->description }}</p>
<ul>
<li>Potongan: {{ $voucher->display_value }} · {{ $voucher->scopeLabel() }}</li>
<li>Minimum belanja: {{ (int) $voucher->min_order > 0 ? 'Rp '.number_format((int) $voucher->min_order, 0, ',', '.') : 'tanpa minimum' }}</li>
<li>Kuota: {{ $voucher->usage_limit === null ? 'tanpa batas' : (int) $voucher->used_count.' dari '.(int) $voucher->usage_limit.' terpakai' }}</li>
<li>Per pelanggan: {{ $voucher->per_customer_limit === null ? 'tanpa batas' : (int) $voucher->per_customer_limit.' kali' }}</li>
<li>Masa berlaku: {{ $voucher->valid_from?->format('d/m/Y') ?? 'sekarang' }} – {{ $voucher->valid_until?->format('d/m/Y') ?? 'tanpa batas' }}</li>
<li>Dipakai pada pesanan sewa: {{ (int) $voucher->rental_usages_count }} pesanan</li>
</ul>
<form method="post" action="{{ route('admin.rental.vouchers.toggle', $voucher) }}">@csrf @method('PATCH')
<button class="btn secondary">{{ $voucher->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
</form>
</section>
@empty
<p>Belum ada voucher. Buat kode pertama pada formulir di atas.</p>
@endforelse
</div>
@endsection
