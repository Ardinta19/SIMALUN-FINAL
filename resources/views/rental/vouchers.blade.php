@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Voucher sewa')
@section('crumbs')<strong>Voucher sewa</strong>@endsection
@section('content')
<a data-smart-back class="bk-back" href="{{ route('dashboard.admin') }}">← Beranda admin</a>
<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Katalog &amp; penawaran</span>
        <h1>Voucher sewa</h1>
        <p class="adm-page-sub">Kode diskon dipasang pelanggan saat checkout; besaran dan kuotanya kamu tentukan di sini.</p>
                <details class="adm-page-note">
                    <summary>Selengkapnya</summary>
                    <p>Pelanggan memasukkan kode ini di halaman checkout. Admin menentukan besaran potongan, apakah potongan ikut menutup ongkos, kuota, dan berapa kali satu pelanggan boleh memakainya. Pesanan yang sudah dibuat tidak berubah saat voucher dinonaktifkan.</p>
                </details>
    </div>
</div>

<section class="adm-card" data-adm-reveal="form">
    <div class="adm-card__head">
        <h2>Buat voucher</h2>
        <span class="adm-chip">Berlaku untuk sewa</span>
    </div>

    <form class="adm-stack" method="post" action="{{ route('admin.rental.vouchers.store') }}">
        @csrf
        <div class="adm-field-grid">
            <div class="adm-field">
                <label for="voucher-code">Kode voucher <span class="adm-req">(wajib)</span></label>
                <input id="voucher-code" name="code" maxlength="30" required autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="Contoh: WISUDA10" value="{{ old('code') }}" @error('code') aria-invalid="true" aria-describedby="voucher-code-error" @enderror>
                @error('code')<p class="adm-err" role="alert" id="voucher-code-error">{{ $message }}</p>@enderror
            </div>
            <div class="adm-field">
                <label for="voucher-description">Keterangan singkat <span class="adm-req">(wajib)</span></label>
                <input id="voucher-description" name="description" maxlength="200" required value="{{ old('description') }}" placeholder="Contoh: Potongan wisuda Desember" @error('description') aria-invalid="true" @enderror>
            </div>
        </div>

        <div class="adm-field-grid">
            <div class="adm-field">
                <label for="voucher-type">Jenis potongan</label>
                <select id="voucher-type" name="type">
                    @foreach(['percent' => 'Persen dari tagihan', 'fixed' => 'Nominal tetap'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', 'percent') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="adm-field">
                <label for="voucher-value">Nilai potongan <span class="adm-req">(wajib)</span></label>
                <input placeholder="Contoh: 10" id="voucher-value" type="number" inputmode="numeric" name="value" min="1" max="100000000" required value="{{ old('value') }}" @error('value') aria-invalid="true" aria-describedby="voucher-value-error" @enderror>
                <span class="adm-help">Persen (1–100) atau rupiah.</span>
                @error('value')<p class="adm-err" role="alert" id="voucher-value-error">{{ $message }}</p>@enderror
            </div>
            <div class="adm-field">
                <label for="voucher-scope">Potongan berlaku untuk</label>
                <select id="voucher-scope" name="scope">
                    <option value="rental" @selected(old('scope', 'rental') === 'rental')>Hanya harga sewa</option>
                    <option value="rental_transport" @selected(old('scope') === 'rental_transport')>Harga sewa + ongkos antar/jemput</option>
                </select>
                <span class="adm-help">Bila ongkos ikut dipotong, potongan ongkos dihitung setelah pelanggan menyetujui ongkos.</span>
            </div>
        </div>

        <div class="adm-field-grid">
            <div class="adm-field">
                <label for="voucher-min">Minimum belanja (Rp)</label>
                <input placeholder="Contoh: 100000" id="voucher-min" type="number" inputmode="numeric" name="min_order" min="0" max="400000000" value="{{ old('min_order', 0) }}">
                <span class="adm-help">0 berarti tanpa minimum.</span>
            </div>
            <div class="adm-field">
                <label for="voucher-max">Batas potongan persen (Rp)</label>
                <input placeholder="Contoh: 50000" id="voucher-max" type="number" inputmode="numeric" name="max_discount" min="1" max="400000000" value="{{ old('max_discount') }}">
                <span class="adm-help">Kosongkan bila tanpa batas. Hanya untuk jenis persen.</span>
            </div>
        </div>

        <div class="adm-field-grid">
            <div class="adm-field">
                <label for="voucher-quota">Kuota total pemakaian</label>
                <input placeholder="Contoh: 20" id="voucher-quota" type="number" inputmode="numeric" name="usage_limit" min="1" max="1000000" value="{{ old('usage_limit') }}">
                <span class="adm-help">Kosongkan bila tanpa batas.</span>
            </div>
            <div class="adm-field">
                <label for="voucher-per-customer">Batas per pelanggan</label>
                <select id="voucher-per-customer" name="per_customer_limit">
                    <option value="1" @selected(old('per_customer_limit', '1') === '1')>Sekali per pelanggan</option>
                    @foreach([3, 5, 10] as $limit)
                        <option value="{{ $limit }}" @selected((string) old('per_customer_limit') === (string) $limit)>{{ $limit }} kali per pelanggan</option>
                    @endforeach
                    <option value="" @selected(old('per_customer_limit') === '')>Tanpa batas per pelanggan</option>
                </select>
            </div>
        </div>

        <div class="adm-field-grid">
            <div class="adm-field">
                <label for="voucher-from">Berlaku mulai</label>
                <input id="voucher-from" type="date" name="valid_from" value="{{ old('valid_from') }}">
                <span class="adm-help">Kosongkan bila berlaku sekarang.</span>
            </div>
            <div class="adm-field">
                <label for="voucher-until">Berlaku sampai</label>
                <input id="voucher-until" type="date" name="valid_until" value="{{ old('valid_until') }}" @error('valid_until') aria-invalid="true" aria-describedby="voucher-until-error" @enderror>
                <span class="adm-help">Kosongkan bila tanpa batas tanggal.</span>
                @error('valid_until')<p class="adm-err" role="alert" id="voucher-until-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <label class="adm-switch">
            <input type="checkbox" name="is_active" value="1" checked>
            <span class="adm-switch__track" aria-hidden="true"></span>
            <span>Langsung aktifkan voucher ini.</span>
        </label>

        <div class="adm-row">
            <button class="btn" type="submit">Simpan voucher</button>
            <a class="btn secondary" href="{{ route('admin.rental.vouchers.index') }}">Bersihkan isian</a>
        </div>
    </form>
</section>

<div class="adm-row adm-row--between adm-head-row" data-adm-reveal="list-title">
    <h2>Daftar voucher</h2>
    <span class="adm-chip">{{ $vouchers->count() }} voucher</span>
</div>

@forelse($vouchers as $voucher)
    @php
        $quotaUsed = (int) $voucher->used_count;
        $quotaLimit = $voucher->usage_limit !== null ? (int) $voucher->usage_limit : null;
        $quotaPercent = $quotaLimit ? min(100, (int) round($quotaUsed / max(1, $quotaLimit) * 100)) : 0;
    @endphp
    <article class="adm-card adm-card--hover" data-adm-reveal="vouchers">
        <div class="adm-row adm-row--between adm-row--top">
            <div class="adm-min0">
                <div class="adm-row adm-row--tight">
                    <h3 class="adm-title--code">{{ $voucher->code }}</h3>
                    <span class="adm-badge {{ $voucher->is_active ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">
                        {{ $voucher->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <p class="muted adm-meta">{{ $voucher->description }}</p>
            </div>
            <form method="post" action="{{ route('admin.rental.vouchers.toggle', $voucher) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn secondary adm-btn--sm">{{ $voucher->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
        </div>

        <div class="adm-grid adm-grid--3 adm-mt-3">
            <div>
                <span class="adm-help">Potongan</span>
                <p class="adm-note-line adm-strong">{{ $voucher->display_value }}</p>
                <p class="muted adm-flat">{{ $voucher->scopeLabel() }}</p>
            </div>
            <div>
                <span class="adm-help">Minimum belanja</span>
                <p class="adm-note-line adm-strong">{{ (int) $voucher->min_order > 0 ? 'Rp '.number_format((int) $voucher->min_order, 0, ',', '.') : 'tanpa minimum' }}</p>
                <p class="muted adm-flat">Per pelanggan: {{ $voucher->per_customer_limit === null ? 'tanpa batas' : (int) $voucher->per_customer_limit.' kali' }}</p>
            </div>
            <div>
                <span class="adm-help">Masa berlaku</span>
                <p class="adm-note-line adm-strong">{{ $voucher->valid_from?->format('d/m/Y') ?? 'sekarang' }} – {{ $voucher->valid_until?->format('d/m/Y') ?? 'tanpa batas' }}</p>
                <p class="muted adm-flat">Dipakai pada {{ (int) $voucher->rental_usages_count }} pesanan sewa</p>
            </div>
        </div>

        <div class="adm-bar adm-mt-3">
            <div class="adm-bar__meta">
                <span>Kuota: {{ $quotaLimit === null ? 'tanpa batas' : $quotaUsed.' dari '.$quotaLimit.' terpakai' }}</span>
                @if($quotaLimit)<span>{{ $quotaPercent }}%</span>@endif
            </div>
            @if($quotaLimit)
                <div class="adm-bar__track">
                    <div class="adm-bar__fill" data-adm-progress="{{ $quotaPercent }}"></div>
                </div>
            @endif
        </div>
    </article>
@empty
    <div class="adm-empty" data-adm-reveal="empty">
        <span class="adm-empty__icon" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'ticket', 'size' => 24])</span></span>
        <span class="adm-empty__title">Belum ada voucher</span>
        <p>Buat kode pertama pada formulir di atas; pelanggan memakainya saat checkout.</p>
    </div>
@endforelse
@endsection
