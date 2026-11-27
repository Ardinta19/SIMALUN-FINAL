@extends('layouts.admin')
@section('back', route('dashboard.admin'))
@section('title', 'Pesanan sewa')
@section('crumbs')<strong>Pesanan sewa</strong>@endsection
@section('content')
<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Operasional</span>
        <h1>Pesanan sewa</h1>
        <p class="adm-page-sub">Konfirmasi mencadangkan stok. Periode sewa termasuk hari penerimaan dan pengembalian. Buka detail pesanan untuk menangani setiap unit.</p>
    </div>
    <div class="adm-actions">
        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.schedule') }}">Jadwal sewa</a>
        <a class="adm-btn adm-btn--ghost adm-btn--sm" href="{{ route('admin.rental.payment-settings.edit') }}">Petunjuk pembayaran</a>
    </div>
</div>

<div class="adm-note adm-note--warn" role="status" data-adm-reveal="note">
    <span class="adm-note__title">Mode pengujian</span>
    Pemesanan dan pembayaran belum lengkap; gunakan halaman ini hanya untuk pengujian.
</div>

@php $activeStatus = $filters['status'] ?? ''; @endphp
<nav class="adm-chipbar" aria-label="Antrean cepat" data-adm-reveal="filters">
    <span class="adm-chipbar__label">Antrean cepat</span>
    <a class="adm-chip adm-chip--link {{ $activeStatus === 'pending' ? 'is-active' : '' }}" @if($activeStatus === 'pending') aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'pending']) }}">Menunggu konfirmasi</a>
    <a class="adm-chip adm-chip--link {{ $activeStatus === 'payment_pending' ? 'is-active' : '' }}" @if($activeStatus === 'payment_pending') aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'payment_pending']) }}">Bukti pembayaran</a>
    <a class="adm-chip adm-chip--link {{ in_array($activeStatus, ['transport_quote', 'transport_approval'], true) ? 'is-active' : '' }}" @if(in_array($activeStatus, ['transport_quote', 'transport_approval'], true)) aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'transport_quote']) }}">Ongkos</a>
    <a class="adm-chip adm-chip--link {{ in_array($activeStatus, ['design_initial', 'design_revision', 'design_pending'], true) ? 'is-active' : '' }}" @if(in_array($activeStatus, ['design_initial', 'design_revision', 'design_pending'], true)) aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'design_initial']) }}">Desain</a>
    <a class="adm-chip adm-chip--link {{ $activeStatus === 'review' ? 'is-active' : '' }}" @if($activeStatus === 'review') aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'review']) }}">Tinjau manual</a>
    <a class="adm-chip adm-chip--link {{ $activeStatus === 'return_overdue' ? 'is-active' : '' }}" @if($activeStatus === 'return_overdue') aria-current="page" @endif href="{{ route('admin.rental.orders', ['status' => 'return_overdue']) }}">Lewat kembali</a>
</nav>

<form class="adm-toolbar" method="get" action="{{ route('admin.rental.orders') }}" data-adm-reveal="filter">
    <div class="adm-field-grid">
        <div class="adm-field adm-field--wide">
            <label for="adm-order-q">Cari referensi, pelanggan, penerima, atau papan</label>
            <input id="adm-order-q" type="search" autocomplete="off" name="q" maxlength="160" value="{{ $filters['q'] ?? '' }}" data-adm-search placeholder="Contoh: papan mawar / nama penerima">
        </div>
        <div class="adm-field">
            <label for="adm-order-status">Status atau tugas</label>
            <select id="adm-order-status" name="status">
                <option value="">Semua</option>
                @foreach(\App\Support\RentalLabels::UNIT_STATUS as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="adm-field">
            <label for="adm-order-from">Tanggal acara mulai</label>
            <input id="adm-order-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="adm-field">
            <label for="adm-order-to">Tanggal acara sampai</label>
            <input id="adm-order-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>
        @php $urutan = $filters['sort'] ?? ''; @endphp
        <div class="adm-field">
            <label for="adm-order-sort">Urutkan</label>
            <select id="adm-order-sort" name="sort">
                {{-- Tanpa pilihan = perilaku bawaan halaman (antrean konfirmasi
                     memakai tenggat terdekat, selebihnya pesanan terbaru). --}}
                <option value="" @selected($urutan === '')>Bawaan</option>
                <option value="tenggat" @selected($urutan === 'tenggat')>Tenggat konfirmasi terdekat</option>
                <option value="acara" @selected($urutan === 'acara')>Tanggal acara terdekat</option>
            </select>
        </div>
    </div>
    <div class="adm-row">
        <button class="btn" type="submit">Tampilkan</button>
        <a class="btn secondary" href="{{ route('admin.rental.orders') }}">Atur ulang filter</a>
        <span class="adm-help adm-push">{{ $orders->total() }} pesanan ditemukan</span>
    </div>
    <p class="adm-toolbar__hint">Pilihan langsung berlaku saat diubah. Daftar memuat pesanan yang punya minimal satu unit sesuai filter; antrean menunggu diurutkan menurut batas konfirmasi terdekat.</p>
</form>

@php
    $hapusFilter = fn (string $kunci) => route('admin.rental.orders', array_filter(array_merge($filters, [$kunci => null])));
    $chipFilter = [];
    if (! empty($filters['status'])) {
        $chipFilter[] = ['label' => 'Status: '.(\App\Support\RentalLabels::UNIT_STATUS[$filters['status']] ?? $filters['status']), 'remove' => $hapusFilter('status')];
    }
    if (! empty($filters['q'])) {
        $chipFilter[] = ['label' => 'Cari: '.$filters['q'], 'remove' => $hapusFilter('q')];
    }
    if (! empty($filters['from'])) {
        $chipFilter[] = ['label' => 'Acara mulai '.\Carbon\Carbon::parse($filters['from'])->format('d/m/Y'), 'remove' => $hapusFilter('from')];
    }
    if (! empty($filters['to'])) {
        $chipFilter[] = ['label' => 'Acara sampai '.\Carbon\Carbon::parse($filters['to'])->format('d/m/Y'), 'remove' => $hapusFilter('to')];
    }
    if (! empty($filters['sort'])) {
        $labelUrutan = ['tenggat' => 'Tenggat konfirmasi', 'acara' => 'Tanggal acara'][$filters['sort']] ?? $filters['sort'];
        $chipFilter[] = ['label' => 'Urutan: '.$labelUrutan, 'remove' => $hapusFilter('sort')];
    }
@endphp
@include('layouts.component.admin._filter_state', ['chips' => $chipFilter, 'resetUrl' => route('admin.rental.orders')])

@forelse($orders as $order)
    @php
        $unitCount = $order->units->count();
        $pending = $order->units->where('status', 'pending')->count();
        $confirmed = $order->units->where('status', 'confirmed')->count();
        $cancelled = $order->units->where('status', 'cancelled')->count();

        /* Satu baris tugas per pesanan: apa yang menunggu admin, dan ke bagian
           mana harus pergi. Dulu kartu hanya menampilkan badge status, jadi
           admin harus membuka tiap pesanan untuk tahu mana yang mendesak. */
        $tugas = [];
        // Tenggat konfirmasi lewat = tugas paling mendesak; beri nada peringatan.
        $tenggatLewat = $order->confirmation_deadline->isPast();
        foreach ($order->units as $unit) {
            $bukti = $unit->status === 'cancelled' ? 0 : $unit->payments->where('status', 'pending')->count();
            if ($unit->payment_review_hold) {
                $tugas[] = ['Tinjau manual unit '.$unit->product_name, 'pembayaran-'.$unit->id, 'warn'];
            }
            if ($bukti > 0) {
                $tugas[] = [$bukti.' bukti bayar diperiksa', 'pembayaran-'.$unit->id, 'warn'];
            }
            if ($unit->status === 'pending') {
                $tugas[] = ['Konfirmasi '.$unit->product_name, 'unit-'.$unit->id, $tenggatLewat ? 'warn' : 'info'];
            }
            if ($unit->status === 'confirmed' && $unit->progress === 'waiting' && $unit->previews->isEmpty()) {
                $tugas[] = ['Pratinjau awal '.$unit->product_name, 'desain-'.$unit->id, 'info'];
            }
            if ($unit->status === 'confirmed' && $unit->transportRequired() && $unit->transport_quote === null) {
                $tugas[] = ['Ongkos '.$unit->product_name.' belum ditawar', 'ongkos-'.$unit->id, 'info'];
            }
            if ($unit->status === 'confirmed' && $unit->progress === 'handed_over' && $unit->ends_on->isBefore(now('Asia/Jakarta')->startOfDay())) {
                $tugas[] = ['Tandai kembali '.$unit->product_name, 'riwayat-'.$unit->id, 'warn'];
            }
            if ($unit->previews->first()?->status === 'pending') {
                $tugas[] = ['Pelanggan meninjau desain', 'desain-'.$unit->id, 'calm'];
            }
        }
        $tugasTeratas = array_slice($tugas, 0, 3);
        $sisaTugas = count($tugas) - count($tugasTeratas);
    @endphp
    <article class="adm-card adm-card--hover" data-adm-reveal="stores" aria-labelledby="adm-order-{{ $order->id }}">
        <div class="adm-row adm-row--between adm-row--top">
            <div class="adm-min0">
                <span class="adm-kicker">Pesanan</span>
                <h2 id="adm-order-{{ $order->id }}">{{ $order->customer?->name ?? 'Pelanggan tidak tersedia' }}</h2>
                <p class="muted adm-note-line">Referensi: <strong>{{ $order->reference }}</strong></p>
            </div>
            <div class="adm-row adm-row--end adm-row--tight">
                <span class="adm-chip">{{ $unitCount }} unit</span>
                @if($pending)<span class="adm-badge adm-badge--pending">{{ $pending }} menunggu</span>@endif
                @if($confirmed)<span class="adm-badge adm-badge--confirmed">{{ $confirmed }} dikonfirmasi</span>@endif
                @if($cancelled)<span class="adm-badge adm-badge--cancelled">{{ $cancelled }} dibatalkan</span>@endif

            </div>
        </div>

        <div class="adm-row adm-row--between adm-mt-3">
            <div class="adm-min0">
                @php
                    $namaPapan = $order->units->pluck('product_name')->filter()->unique();
                    $sisaPapan = max(0, $namaPapan->count() - 2);
                @endphp
                <p class="muted adm-flat">
                    {{ $namaPapan->take(2)->implode(' · ') }}{{ $sisaPapan > 0 ? ' · dan '.$sisaPapan.' papan lain' : '' }}
                </p>
                @php
                    // Tahap pengerjaan per pesanan: admin bisa memilah pekerjaan
                    // tanpa membuka satu per satu. Urut sesuai alur, dan unit
                    // yang sudah dikembalikan diringkas di ujung.
                    $tahapUnit = [];
                    foreach (\App\Support\RentalLabels::PROGRESS_STEPS as $kunciTahap) {
                        $jumlah = $order->units->where('status', 'confirmed')->where('progress', $kunciTahap)->count();
                        if ($jumlah > 0) {
                            $tahapUnit[] = [$kunciTahap, $jumlah];
                        }
                    }
                @endphp
                @if($tahapUnit)
                    <ul class="adm-stages" aria-label="Tahap pengerjaan tiap unit">
                        @foreach(array_slice($tahapUnit, 0, 3) as [$kunciTahap, $jumlah])
                            <li class="adm-stages__item" data-state="{{ $kunciTahap === 'returned' ? 'done' : 'jalan' }}">
                                <b>{{ $jumlah }}</b> {{ \App\Support\RentalLabels::progress($kunciTahap) }}
                            </li>
                        @endforeach
                        @if(count($tahapUnit) > 3)
                            <li class="adm-stages__item">+{{ count($tahapUnit) - 3 }} tahap lain</li>
                        @endif
                    </ul>
                @endif
                <p class="adm-help adm-meta">
                    @if($pending)
                        Batas konfirmasi: {{ $order->confirmation_deadline->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                    @else
                        Dibuat {{ $order->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                    @endif
                </p>
            </div>
            <a class="btn secondary adm-btn--sm" href="{{ route('admin.rental.orders.show', $order) }}">Lihat detail pesanan</a>
        </div>

        @if($tugasTeratas)
            <ul class="adm-tasklist" aria-label="Tugas yang menunggu pada pesanan ini">
                @foreach($tugasTeratas as [$label, $anchor, $tone])
                    <li class="adm-tasklist__item" data-tone="{{ $tone }}">
                        <a href="{{ route('admin.rental.orders.show', $order) }}#{{ $anchor }}">{{ $label }}</a>
                    </li>
                @endforeach
                @if($sisaTugas > 0)
                    <li class="adm-tasklist__more">+{{ $sisaTugas }} tugas lain</li>
                @endif
            </ul>
        @else
            <p class="adm-help adm-flat adm-mt-2">Tidak ada yang menunggu admin di pesanan ini.</p>
        @endif
    </article>
@empty
    <div class="adm-empty" data-adm-reveal="empty">
        <span class="adm-empty__icon" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'folder-open', 'size' => 24])</span></span>
        <span class="adm-empty__title">Belum ada pesanan yang cocok</span>
        <p>Coba longgarkan filter tanggal atau status, atau mulai dari pesanan terbaru.</p>
        <a class="btn secondary" href="{{ route('admin.rental.orders') }}">Tampilkan semua pesanan</a>
    </div>
@endforelse

<div class="pagination">{{ $orders->links() }}</div>
@endsection
