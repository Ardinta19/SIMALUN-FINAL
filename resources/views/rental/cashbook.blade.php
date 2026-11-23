@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Buku kas')
@section('crumbs')<strong>Buku kas</strong>@endsection
@section('content')
@php
    $currentIncome = (int) ($current['income'] ?? 0);
    $currentExpense = (int) ($current['expense'] ?? 0);
    $previousIncome = (int) ($previous['income'] ?? 0);
    $previousExpense = (int) ($previous['expense'] ?? 0);
@endphp

<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Keuangan</span>
        <h1>Buku kas</h1>
        <p class="adm-page-sub">Pembayaran sewa tercatat otomatis; di sini Anda hanya menambah transaksi manual.</p>
                <details class="adm-page-note">
                    <summary>Selengkapnya</summary>
                    <p>Pembayaran sewa masuk otomatis pada tanggal verifikasi. Jangan catat pembayaran itu lagi secara manual. Pengembalian dana dicatat dari pembayaran terkait, begitu pula koreksi nominal.</p>
                </details>
    </div>
    <div class="adm-actions">
        <a class="btn secondary" href="{{ route('admin.rental.cashbook.export', ['period' => $period, 'date' => $date->toDateString()]) }}">Unduh CSV periode ini</a>
    </div>
</div>

<form class="adm-toolbar" method="get" action="{{ route('admin.rental.cashbook.index') }}" data-adm-reveal="filter">
    <div class="adm-field-grid">
        <div class="adm-field">
            <label for="adm-cash-period">Periode</label>
            <select id="adm-cash-period" name="period">
                <option value="day" @selected($period === 'day')>Harian</option>
                <option value="week" @selected($period === 'week')>Mingguan</option>
                <option value="month" @selected($period === 'month')>Bulanan</option>
                <option value="year" @selected($period === 'year')>Tahunan</option>
            </select>
        </div>
        <div class="adm-field">
            <label for="adm-cash-date">Tanggal acuan</label>
            <input id="adm-cash-date" type="date" name="date" value="{{ $date->toDateString() }}" required>
        </div>
    </div>
    <div class="adm-row">
        <button class="btn" type="submit">Tampilkan</button>
        <a class="btn secondary" href="{{ route('admin.rental.cashbook.index') }}">Reset</a>
    </div>
</form>

<div class="adm-grid adm-grid--2">
    <section class="adm-card" data-adm-reveal="summary">
        <div class="adm-card__head">
            <h2>Periode dipilih</h2>
            <span class="adm-chip">{{ $start->format('d/m/Y') }} – {{ $end->format('d/m/Y') }}</span>
        </div>
        <div class="adm-stats">
            <div class="adm-stat">
                <div class="adm-stat__top"><span class="adm-stat__icon adm-stat__icon--green" aria-hidden="true">↓</span></div>
                <div class="adm-stat__value" data-adm-count data-to="{{ $currentIncome }}" data-prefix="Rp ">Rp {{ number_format($currentIncome, 0, ',', '.') }}</div>
                <div class="adm-stat__label">Pemasukan</div>
            </div>
            <div class="adm-stat">
                <div class="adm-stat__top"><span class="adm-stat__icon adm-stat__icon--amber" aria-hidden="true">↑</span></div>
                <div class="adm-stat__value" data-adm-count data-to="{{ $currentExpense }}" data-prefix="Rp ">Rp {{ number_format($currentExpense, 0, ',', '.') }}</div>
                <div class="adm-stat__label">Pengeluaran</div>
            </div>
            <div class="adm-stat">
                <div class="adm-stat__top"><span class="adm-stat__icon adm-stat__icon--blue" aria-hidden="true">=</span></div>
                <div class="adm-stat__value" data-adm-count data-to="{{ $currentIncome - $currentExpense }}" data-prefix="Rp ">Rp {{ number_format($currentIncome - $currentExpense, 0, ',', '.') }}</div>
                <div class="adm-stat__label">Selisih</div>
            </div>
        </div>
    </section>

    <section class="adm-card" data-adm-reveal="summary">
        <div class="adm-card__head">
            <h2>Periode sebelumnya</h2>
            <span class="adm-chip">{{ $previousStart->format('d/m/Y') }} – {{ $previousEnd->format('d/m/Y') }}</span>
        </div>
        <div class="adm-stats">
            <div class="adm-stat adm-card--flat">
                <div class="adm-stat__value" data-adm-count data-to="{{ $previousIncome }}" data-prefix="Rp ">Rp {{ number_format($previousIncome, 0, ',', '.') }}</div>
                <div class="adm-stat__label">Pemasukan</div>
            </div>
            <div class="adm-stat adm-card--flat">
                <div class="adm-stat__value" data-adm-count data-to="{{ $previousExpense }}" data-prefix="Rp ">Rp {{ number_format($previousExpense, 0, ',', '.') }}</div>
                <div class="adm-stat__label">Pengeluaran</div>
            </div>
            <div class="adm-stat adm-card--flat">
                <div class="adm-stat__value" data-adm-count data-to="{{ $previousIncome - $previousExpense }}" data-prefix="Rp ">Rp {{ number_format($previousIncome - $previousExpense, 0, ',', '.') }}</div>
                <div class="adm-stat__label">Selisih</div>
            </div>
        </div>
    </section>
</div>

<section class="adm-card" style="margin-top:var(--adm-gap)" data-adm-reveal="form">
    <div class="adm-card__head">
        <h2>Tambah catatan manual</h2>
        <span class="adm-chip">Bukan pembayaran sewa</span>
    </div>
    <form class="adm-stack" method="post" action="{{ route('admin.rental.cashbook.store') }}">
        @csrf
        @include('rental.cashbook-fields', ['entry' => null])
        <div><button class="btn" type="submit">Simpan catatan</button></div>
    </form>
</section>

<h2 style="margin:24px 0 12px" data-adm-reveal="table-title">Daftar transaksi</h2>

@if($entries->isEmpty())
    <div class="adm-empty" data-adm-reveal="empty">
        <span class="adm-empty__icon" aria-hidden="true">📒</span>
        <span class="adm-empty__title">Belum ada catatan pada periode ini</span>
        <p>Ubah periode di atas, atau tambahkan catatan manual lewat formulir.</p>
    </div>
@else
    <div class="adm-card adm-card--pad-0" data-adm-reveal="table">
        <div class="adm-scroll-x">
            <table class="adm-table adm-table--stack">
                <thead>
                    <tr>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Jenis</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Nominal</th>
                        <th scope="col">Sumber</th>
                        <th scope="col">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        <tr>
                            <td data-label="Tanggal" class="adm-table__num">{{ $entry->entry_date->format('d/m/Y') }}</td>
                            <td data-label="Jenis">
                                <span class="adm-badge {{ $entry->entry_type === 'income' ? 'adm-badge--confirmed' : 'adm-badge--cancelled' }}">
                                    {{ $entry->entry_type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                </span>
                            </td>
                            <td data-label="Kategori">{{ $entry->category ?: '-' }}</td>
                            <td data-label="Nominal" class="adm-table__num"><strong>Rp {{ number_format($entry->amount, 0, ',', '.') }}</strong></td>
                            <td data-label="Sumber">{{ $entry->source_type ?: 'Tidak diketahui' }}</td>
                            <td data-label="Catatan">
                                <span>{{ $entry->notes }}</span>
                                @if($entry->source_type === 'manual' && !$entry->source_id)
                                    <details style="margin-top:6px">
                                        <summary style="cursor:pointer;font-weight:700;font-size:.82rem">Ubah catatan manual</summary>
                                        <form class="adm-stack" style="margin-top:10px" method="post" action="{{ route('admin.rental.cashbook.update', $entry) }}">
                                            @csrf
                                            @method('PUT')
                                            @include('rental.cashbook-fields', ['entry' => $entry])
                                            <div><button class="btn secondary adm-btn--sm" type="submit">Simpan perubahan</button></div>
                                        </form>
                                    </details>
                                @else
                                    <p class="adm-help" style="margin:6px 0 0">Catatan terkait transaksi tidak dapat diubah dari buku kas.</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="pagination">{{ $entries->links() }}</div>
@endsection
