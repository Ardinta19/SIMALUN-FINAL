@extends('layouts.admin')
@section('back', route('admin.rental.orders'))
@section('title', 'Detail pesanan admin')
@section('crumbs')<a href="{{ route('admin.rental.orders') }}">Pesanan sewa</a><span class="adm-crumbs__sep" aria-hidden="true">/</span><strong>Detail pesanan</strong>@endsection
@section('content')
@php
    $unitStatusLabels = \App\Support\RentalLabels::UNIT_STATUS;
    $unitStatusTones = ['pending' => 'adm-badge--pending', 'confirmed' => 'adm-badge--confirmed', 'cancelled' => 'adm-badge--cancelled'];
    $paymentStatusLabels = \App\Support\RentalLabels::PAYMENT_STATUS;
    $paymentStatusTones = ['pending' => 'adm-badge--pending', 'verified' => 'adm-badge--confirmed', 'rejected' => 'adm-badge--cancelled'];
@endphp

<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Pesanan</span>
        <h1>Detail pesanan</h1>
        <p class="adm-page-sub">Buka unit yang ingin ditangani. Tutup kembali bagian yang tidak diperlukan.</p>
    </div>
    <div class="adm-actions">
        <a class="btn secondary" href="{{ route('admin.rental.orders') }}">← Daftar pesanan</a>
        <a class="btn" href="{{ route('rental.receipt', $order) }}">Ringkasan cetak</a>
    </div>
</div>

<section class="adm-card" data-adm-reveal="customer">
    <div class="adm-row adm-row--between adm-row--top">
        <div class="adm-min0">
            <h2>{{ $order->customer?->name ?? 'Pelanggan tidak tersedia' }}</h2>
            <p class="muted adm-meta">Referensi: <strong>{{ $order->reference }}</strong></p>
        </div>
        <div class="adm-row adm-row--end adm-row--tight">
            <span class="adm-chip">{{ $order->units->count() }} unit</span>
            <span class="adm-badge adm-badge--info">Batas konfirmasi: {{ $order->confirmation_deadline->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span>
        </div>
    </div>
    @if($order->voucher_code)
        <p class="muted adm-inline-note adm-inline-note--top">Voucher dipakai: <strong>{{ $order->voucher_code }}</strong> · total potongan Rp {{ number_format((int) $order->discount_amount, 0, ',', '.') }}. Potongan dibagi ke setiap unit dan tidak mengubah tarif dasar.</p>
    @endif

    @if($order->references->isNotEmpty())
        <div class="adm-mt-4">
            <h3 class="adm-title--sm">Foto referensi desain ({{ $order->references->count() }})</h3>
            <ul class="bk-reference-list">
                @foreach($order->references as $reference)
                    <li><a href="{{ route('rental.reference.image', $reference) }}" target="_blank" rel="noopener"><img src="{{ route('rental.reference.image', $reference) }}" alt="Foto referensi {{ $loop->iteration }}" width="96" height="96" loading="lazy" decoding="async"></a></li>
                @endforeach
            </ul>
        </div>
    @endif
</section>

@include('rental._ordered_items', [
    'items' => \App\Support\OrderedItems::fromUnits($order->units, fn ($unit) => '#unit-'.$unit->id),
    'hint' => 'Daftar ini untuk persiapan; tindakan per papan ada di bagian di bawahnya.',
    'class' => 'adm-card',
])

<div class="adm-stack adm-mt-gap">
@foreach($order->units as $unit)
    @php
        $unitDeadlinePolicy = app(\App\Services\RentalPaymentDeadline::class);
        $unitDesignPolicy = app(\App\Services\RentalDesignDeadline::class);
        $latestDesignPreview = $unit->previews->first();
        $designDeadline = $unitDesignPolicy->deadline($unit);
        $unitTone = $unitStatusTones[$unit->status] ?? 'adm-badge--info';
        $pendingPayments = $unit->payments->where('status', 'pending')->count();
    @endphp

    <article class="adm-card" data-adm-reveal="units" aria-labelledby="unit-{{ $unit->id }}">
        <div class="adm-row adm-row--between adm-row--top">
            <div class="adm-min0">
                <span class="adm-kicker">Unit {{ $loop->iteration }}</span>
                <h2 class="adm-note-line" id="unit-{{ $unit->id }}">{{ $unit->product_name }}{{ $unit->variant_name ? ' · warna '.$unit->variant_name : '' }}</h2>
            </div>
            <div class="adm-row adm-row--end adm-row--tight">
                <span class="adm-badge {{ $unitTone }}">{{ $unitStatusLabels[$unit->status] ?? $unit->status }}</span>
                @php
                    // Data impor lama bisa punya `progress` kosong; pakai langkah
                    // pertama supaya pil tahap tidak berakhir dengan label hampa.
                    $tahapKunci = $unit->progress ?: 'waiting';
                    $tahapUnit = \App\Support\RentalLabels::progressStep($tahapKunci);
                @endphp
                <span class="adm-chip adm-chip--stage">Tahap {{ ($tahapUnit ?? 0) + 1 }}/{{ count(\App\Support\RentalLabels::PROGRESS_STEPS) }} · {{ \App\Support\RentalLabels::progress($tahapKunci) }}</span>
            </div>
        </div>

        @include('rental.admin-next-step', ['unit' => $unit])

        <div class="adm-grid adm-grid--3 adm-mt-4">
            <div>
                <span class="adm-help">Tanggal</span>
                <p class="adm-note-line adm-strong">
                    @if($unit->starts_on->isSameDay($unit->event_on))
                        Acara dan penerimaan: {{ $unit->event_on->format('d/m/Y') }}
                    @else
                        Penerimaan: {{ $unit->starts_on->format('d/m/Y') }} · Acara: {{ $unit->event_on->format('d/m/Y') }}
                    @endif
                </p>
                <p class="muted adm-flat">Pengembalian: {{ $unit->ends_on->format('d/m/Y') }}</p>
                @if($unit->special_confirmation)
                    <p class="muted adm-meta">Disetujui dengan penanganan khusus jadwal dekat.</p>
                @endif
            </div>
            <div>
                <span class="adm-help">Penerima</span>
                <p class="adm-note-line adm-strong">{{ $unit->recipient_name }}</p>
                <p class="muted adm-flat">{{ $unit->greeting }}</p>
            </div>
            <div>
                <span class="adm-help">Biaya sewa</span>
                <p class="adm-note-line adm-strong">Rp {{ number_format($unit->rental_amount, 0, ',', '.') }}</p>
                @if($unit->package_name)
                    <p class="muted adm-flat">Paket: {{ $unit->package_name }} · {{ $unit->package_days }} hari</p>
                @endif
            </div>
        </div>

        <div class="adm-grid adm-grid--3 adm-mt-3">
            <div>
                <span class="adm-help">Desain</span>
                <p class="adm-note-line">{{ $unit->design_notes ?: '-' }}</p>
            </div>
            <div>
                <span class="adm-help">Penerimaan</span>
                <p class="adm-note-line">{{ $unit->delivery_method === 'delivery' ? 'Diantar penjual' : 'Diambil sendiri' }} · {{ $unit->delivery_address ?: 'Lokasi dikonfirmasi admin' }}</p>
                @if($unit->delivery_university)
                    <p class="muted adm-flat">Universitas pengantaran: {{ $unit->delivery_university }} · Rp {{ number_format($unit->delivery_university_fee, 0, ',', '.') }}</p>
                @endif
            </div>
            <div>
                <span class="adm-help">Pengembalian</span>
                <p class="adm-note-line">{{ $unit->return_method === 'collection' ? 'Diambil kembali penjual' : 'Dikembalikan sendiri' }} · {{ $unit->return_address ?: 'Lokasi dikonfirmasi admin' }}</p>
                @if($unit->return_university)
                    <p class="muted adm-flat">Universitas pengambilan kembali: {{ $unit->return_university }} · Rp {{ number_format($unit->return_university_fee, 0, ',', '.') }}</p>
                @endif
            </div>
        </div>

        @php $waCustomer = \App\Support\RentalWhatsApp::customerLink($order, $unit); @endphp
        @if($waCustomer)
            <div class="adm-row adm-mt-3">
                <a class="btn secondary adm-btn--sm" href="{{ $waCustomer }}" target="_blank" rel="noopener noreferrer">Chat pelanggan via WhatsApp</a>
                <span class="adm-help">{{ \App\Support\RentalWhatsApp::state($unit) }}</span>
            </div>
        @endif


        <details class="adm-card adm-card--flat adm-mt-4" id="ongkos-{{ $unit->id }}" data-adm-reveal="details" @if($errors->any() || $unit->payment_review_hold || in_array($filters['status'] ?? '', ['transport_quote', 'transport_approval', 'review'])) open @endif>
            <summary>Ongkos dan tagihan</summary>
            <div class="adm-pt-3">
                @if($unit->transportRequired())
                    <h3>Ongkos antar dan/atau penjemputan</h3>
                    @if($unit->transport_quote !== null)
                        <p>Versi {{ $unit->transport_quote_version }} · Rp {{ number_format($unit->transport_quote, 0, ',', '.') }} · {{ $unit->transport_approved_at ? ((int) $unit->transport_quote === 0 ? 'Gratis' : 'Disetujui') : 'Menunggu persetujuan' }}</p>
                        <p class="muted">{{ $unit->transport_quote_note }}</p>
                    @endif
                    @php $lastTransport = $unit->transportQuoteEvents->last(); @endphp
                    @if($lastTransport && $lastTransport->action === 'reject')
                        <p class="adm-note adm-note--warn adm-inline-note">Pelanggan menolak penawaran versi {{ $lastTransport->version }}: {{ $lastTransport->note }}. Kirim penawaran baru.</p>
                    @endif
                    @if($unit->status !== 'cancelled' && $unit->payments->isEmpty())
                        <form class="adm-stack" method="post" action="{{ route('admin.rental.transport.quote', $unit) }}">
                            @csrf
                            <div class="adm-field-grid">
                                <div class="adm-field">
                                    <label for="transport-amount-{{ $unit->id }}">Ongkos total (Rp)</label>
                                    <input id="transport-amount-{{ $unit->id }}" type="number" inputmode="numeric" name="amount" min="0" max="200000000" value="{{ $unit->transport_quote }}" required>
                                </div>
                            </div>
                            <div class="adm-field">
                                <label for="transport-note-{{ $unit->id }}">Rincian ongkos</label>
                                <textarea id="transport-note-{{ $unit->id }}" placeholder="Contoh: Antar 15.000 dan jemput kembali 20.000, sudah termasuk tol." name="note" maxlength="1000" rows="4">{{ $unit->transport_quote_note }}</textarea>
                            </div>
                            <p class="adm-help">Isi 0 bila pengantaran gratis — pelanggan tidak perlu menyetujui apa pun dan bisa langsung membayar. Untuk angka lain, versi baru membatalkan persetujuan lama sehingga pelanggan wajib menyetujui versi terbaru. Tarif universitas yang sudah tampil saat checkout tidak perlu ditawar ulang.</p>
                            <div><button class="btn secondary" type="submit">Kirim penawaran</button></div>
                        </form>
                    @elseif($unit->payments->isNotEmpty())
                        <p class="adm-help">Ongkos terkunci karena sudah ada pembayaran. Selesaikan atau kembalikan pembayaran sebelum mengubah ongkos.</p>
                    @endif
                    @if($unit->transportQuoteEvents->isNotEmpty())
                        <h4>Riwayat penawaran</h4>
                        <ul class="adm-timeline">
                            @foreach($unit->transportQuoteEvents as $event)
                                <li class="adm-timeline__item is-done">
                                    <span class="adm-timeline__time">{{ $event->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span>
                                    <span class="adm-timeline__title">{{ ['quote' => 'Penawaran dikirim', 'approve' => 'Disetujui', 'reject' => 'Ditolak'][$event->action] ?? $event->action }} · versi {{ $event->version }}</span>
                                    <span class="adm-timeline__note">Rp {{ number_format($event->amount ?? 0, 0, ',', '.') }} · {{ $event->recorder?->name ?? 'Sistem' }}@if($event->note) — {{ $event->note }}@endif</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif

                @if($unit->addons->isNotEmpty())
                    <ul class="adm-list-indent">
                        @foreach($unit->addons as $addon)
                            <li>{{ $addon->label() }} · Rp {{ number_format($addon->total, 0, ',', '.') }}</li>
                        @endforeach
                    </ul>
                @endif

                <p class="adm-mt-3"><strong>Total tagihan:</strong> {{ $unit->payableAmount() === null ? 'Menunggu persetujuan ongkos' : 'Rp '.number_format($unit->payableAmount(), 0, ',', '.') }}@if($unit->totalDiscount() > 0) · potongan voucher Rp {{ number_format($unit->totalDiscount(), 0, ',', '.') }}@endif</p>

                @if($unit->payment_review_hold)
                    <div class="adm-note adm-note--warn">
                        <span class="adm-note__title">Ditahan untuk penanganan manual</span>
                        Pembayaran/jadwal khusus ditahan untuk penanganan manual. Periksa bukti, perbaikan, dan ongkos sebelum melepas peninjauan.
                        <form class="adm-stack adm-mt-3" method="post" action="{{ route('admin.rental.payment.resolve-review', $unit) }}">
                            @csrf
                            <label class="adm-switch">
                                <input type="checkbox" name="confirmed" value="1" required>
                                <span class="adm-switch__track" aria-hidden="true"></span>
                                <span>Saya memahami bahwa setelah dilepas penjadwal akan memeriksa kembali tenggat.</span>
                            </label>
                            <div><button class="btn secondary adm-btn--sm" type="submit">Selesaikan peninjauan</button></div>
                        </form>
                    </div>
                @endif
            </div>
        </details>

        @if($unit->cancel_code === 'payment_timeout')
            <p class="adm-note adm-note--danger adm-mt-3">
                Dibatalkan karena tenggat pelunasan. {{ $unit->deposit_forfeited_at ? 'Masa kelanjutan berakhir; DP yang tersisa hangus sesuai ketentuan.' : 'Toleransi verifikasi sampai '.$unitDeadlinePolicy->graceDeadline($unit)->format('d/m/Y H:i').' WIB. Lanjutkan hanya setelah pembayaran lunas terverifikasi dan stok dicek.' }}
            </p>
        @endif


        <details class="adm-card adm-card--flat adm-mt-4" id="riwayat-{{ $unit->id }}" data-adm-reveal="details" @if($errors->any()) open @endif>
            <summary>Riwayat dan perkembangan</summary>
            <div class="adm-pt-3">
                @include('rental.admin-cancellation-history')
                @include('rental.admin-progress', ['admin' => true, 'actions' => false])
            </div>
        </details>

        <details class="adm-card adm-card--flat adm-mt-3" id="pembayaran-{{ $unit->id }}" data-adm-reveal="details" @if($errors->any() || $pendingPayments > 0 || in_array($filters['status'] ?? '', ['payment_pending', 'review'])) open @endif>
            <summary>Pembayaran · {{ $pendingPayments }} bukti menunggu</summary>
            <div class="adm-pt-3">
                <h3>Pembayaran</h3>
                <p class="adm-help">Batas pelunasan: {{ $unit->plannedPaymentDeadline()->format('d/m/Y H:i') }} WIB. Toleransi verifikasi bukti terlambat: {{ $unitDeadlinePolicy->graceDeadline($unit)->format('d/m/Y H:i') }} WIB.</p>

                @forelse($unit->payments as $payment)
                    <div class="adm-card adm-card--flat adm-inline-note">
                        <div class="adm-row adm-row--between adm-row--top">
                            <div>
                                <p class="adm-flat adm-strong">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                                <p class="muted adm-note-line">Dikirim {{ $payment->submitted_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
                            </div>
                            <span class="adm-badge {{ $paymentStatusTones[$payment->status] ?? 'adm-badge--info' }}">{{ $paymentStatusLabels[$payment->status] ?? $payment->status }}</span>
                        </div>

                        <div class="adm-row adm-mt-2">
                            <a class="btn secondary adm-btn--sm" href="{{ route('rental.payment.proof', $payment) }}">Lihat bukti</a>
                        </div>

                        @foreach($payment->refunds as $refund)
                            <p class="muted adm-block-gap--sm">Pengembalian Rp {{ number_format($refund->amount, 0, ',', '.') }} · {{ $refund->refunded_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB · {{ $refund->reason }}</p>
                        @endforeach
                        @foreach($payment->corrections as $correction)
                            <p class="muted adm-block-gap--sm">Koreksi Rp {{ number_format($correction->old_amount, 0, ',', '.') }} → Rp {{ number_format($correction->new_amount, 0, ',', '.') }} · {{ $correction->reason }}</p>
                        @endforeach

                        @if($payment->status === 'verified' && $payment->finance_entry_id)
                            <details class="adm-mt-3">
                                <summary class="bk-disclosure">Koreksi nominal pembayaran</summary>
                                <p class="adm-help">Gunakan hanya jika nominal pembayaran terverifikasi salah. Catatan kas terkait dan sisa tagihan akan berubah; tanggal kas tetap tanggal verifikasi semula. Alasan dapat dilihat pelanggan.</p>
                                <form class="adm-stack" method="post" action="{{ route('admin.rental.payment.correct', $payment) }}">
                                    @csrf
                                    <input type="hidden" name="expected_amount" value="{{ $payment->amount }}">
                                    <div class="adm-field-grid">
                                        <div class="adm-field">
                                            <label for="correct-amount-{{ $payment->id }}">Nominal yang benar (Rp)</label>
                                            <input id="correct-amount-{{ $payment->id }}" type="number" inputmode="numeric" name="amount" min="1" max="100000000" value="{{ $payment->amount }}" required>
                                        </div>
                                        <div class="adm-field">
                                            <label for="correct-reason-{{ $payment->id }}">Alasan koreksi</label>
                                            <input id="correct-reason-{{ $payment->id }}" placeholder="Contoh: Bukti terbaca 500.000, seharusnya 550.000." name="reason" maxlength="500" required>
                                        </div>
                                    </div>
                                    <div><button class="btn secondary adm-btn--sm" type="submit">Simpan koreksi</button></div>
                                </form>
                            </details>
                        @endif

                        @if($payment->status === 'verified' && $payment->amount > $payment->refunds->sum('amount'))
                            <details class="adm-mt-3">
                                <summary class="bk-disclosure">Catat pengembalian dana</summary>
                                <p class="adm-help">Lakukan pengembalian kepada pelanggan terlebih dahulu. Form ini hanya mencatat pengembalian yang sudah dilakukan, bukan mengirim uang. Maksimal Rp {{ number_format($payment->amount - $payment->refunds->sum('amount'), 0, ',', '.') }}.</p>
                                <form class="adm-stack" method="post" action="{{ route('admin.rental.payment.refund', $payment) }}">
                                    @csrf
                                    <input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                    <div class="adm-field-grid">
                                        <div class="adm-field">
                                            <label for="refund-amount-{{ $payment->id }}">Nominal (Rp)</label>
                                            <input id="refund-amount-{{ $payment->id }}" placeholder="Contoh: 150000" type="number" inputmode="numeric" name="amount" min="1" max="{{ $payment->amount - $payment->refunds->sum('amount') }}" required>
                                        </div>
                                        <div class="adm-field">
                                            <label for="refund-reason-{{ $payment->id }}">Alasan dan keterangan</label>
                                            <input id="refund-reason-{{ $payment->id }}" placeholder="Contoh: Kelebihan bayar dikembalikan lewat transfer BCA." name="reason" maxlength="500" required>
                                        </div>
                                    </div>
                                    <label class="adm-switch">
                                        <input type="checkbox" name="confirmed" value="1" required>
                                        <span class="adm-switch__track" aria-hidden="true"></span>
                                        <span>Dana sudah dikembalikan kepada pelanggan.</span>
                                    </label>
                                    <div><button class="btn secondary adm-btn--sm" type="submit">Catat pengembalian</button></div>
                                </form>
                            </details>
                        @endif

                        @if($payment->admin_note)
                            <p class="muted adm-block-gap">Alasan: {{ $payment->admin_note }}</p>
                        @endif

                        @if($payment->status === 'pending' && in_array($unit->status, ['confirmed', 'cancelled'], true))
                            <div class="adm-row adm-mt-3 adm-row--top">
                                <form method="post" action="{{ route('admin.rental.payment.verify', $payment) }}">
                                    @csrf
                                    <button class="btn" type="submit">Verifikasi pembayaran</button>
                                </form>
                                <form class="adm-stack adm-grow--min" method="post" action="{{ route('admin.rental.payment.reject', $payment) }}">
                                    @csrf
                                    <div class="adm-field">
                                        <label for="reject-reason-{{ $payment->id }}">Alasan penolakan</label>
                                        <input id="reject-reason-{{ $payment->id }}" placeholder="Contoh: Bukti bukan untuk pesanan ini." name="reason" maxlength="500" required>
                                    </div>
                                    <div><button class="btn secondary" type="submit">Tolak bukti</button></div>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="adm-help">Belum ada bukti pembayaran.</p>
                @endforelse
            </div>
        </details>

        <details class="adm-card adm-card--flat adm-mt-3" id="desain-{{ $unit->id }}" data-adm-reveal="details" @if($errors->any() || in_array($filters['status'] ?? '', ['design_pending', 'design_initial', 'design_revision'])) open @endif>
            <summary>Pratinjau desain · {{ $unit->previews->count() }} versi</summary>
            <div class="adm-pt-3">
                <h3>Pratinjau desain</h3>
                @if($designDeadline && $latestDesignPreview && $latestDesignPreview->status === 'pending')
                    <p class="adm-help">Batas respons desain versi {{ $latestDesignPreview->version }}: {{ $designDeadline->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB {{ $unitDesignPolicy->reached($unit) ? '· sudah terlewati' : '· belum terlewati' }}</p>
                @endif
                @if($unit->designOverrideActive())
                    <p class="adm-note adm-note--warn">Pengerjaan dimulai tanpa persetujuan pada {{ $unit->design_override_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB. Catatan: {{ $unit->design_override_note }}</p>
                @endif

                @if($unit->status === 'confirmed' && $unit->progress === 'waiting' && $latestDesignPreview && $latestDesignPreview->status === 'pending' && $unitDesignPolicy->reached($unit) && ! $unit->designOverrideActive())
                    <form class="adm-stack" method="post" action="{{ route('admin.rental.design.override', $unit) }}">
                        @csrf
                        <p class="adm-note adm-note--warn adm-flat">Pelanggan belum merespons sampai batas. Pengerjaan dapat dimulai tanpa persetujuan; keputusan ini tercatat dan pelanggan tetap dapat menyetujui atau meminta revisi.</p>
                        <div class="adm-field">
                            <label for="override-note-{{ $unit->id }}">Alasan melanjutkan tanpa persetujuan (wajib, minimal 10 karakter)</label>
                            <textarea id="override-note-{{ $unit->id }}" placeholder="Contoh: Pelanggan belum membalas sejak kemarin, pengerjaan dimulai sesuai tenggat." name="note" minlength="10" maxlength="2000" required rows="4">{{ is_scalar(old('note')) ? old('note') : '' }}</textarea>
                        </div>
                        <label class="adm-switch">
                            <input type="checkbox" name="confirmed" value="1" required>
                            <span class="adm-switch__track" aria-hidden="true"></span>
                            <span>Saya sudah menghubungi pelanggan dan menanggung keputusan ini.</span>
                        </label>
                        <div><button class="btn" type="submit">Lanjutkan dengan catatan</button></div>
                    </form>
                @endif

                @foreach($unit->previews as $preview)
                    <div class="adm-card adm-card--flat adm-inline-note">
                        <div class="adm-row adm-row--between">
                            <strong>Versi {{ $preview->version }}</strong>
                            <span class="adm-badge adm-badge--info">{{ \App\Support\RentalLabels::designStatus($preview->status) }}</span>
                        </div>
                        <img class="adm-preview-img" src="{{ route('rental.design.image', $preview) }}" alt="Pratinjau desain versi {{ $preview->version }}" loading="lazy" decoding="async">
                        @if($preview->admin_note)<p class="muted adm-block-gap">{{ $preview->admin_note }}</p>@endif
                        @if($preview->customer_note)<p class="muted adm-meta">Catatan pelanggan: {{ $preview->customer_note }}</p>@endif
                    </div>
                @endforeach

                @if($unit->status === 'confirmed' && $unit->progress === 'waiting')
                    <form class="adm-stack" method="post" action="{{ route('admin.rental.design.store', $unit) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="adm-field-grid">
                            <div class="adm-field">
                                <label for="design-image-{{ $unit->id }}">Gambar pratinjau (JPG, PNG, WebP; maksimal 5 MB)</label>
                                <input id="design-image-{{ $unit->id }}" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
                            </div>
                            <div class="adm-field">
                                <label for="design-note-{{ $unit->id }}">Catatan admin</label>
                                <textarea id="design-note-{{ $unit->id }}" placeholder="Contoh: Versi 1 - bunga putih, pita hitam, tulisan &quot;Turut berduka cita&quot;." name="admin_note" maxlength="2000"></textarea>
                            </div>
                        </div>
                        <div><button class="btn secondary" type="submit">Unggah pratinjau</button></div>
                    </form>
                @endif
            </div>
        </details>

        @if($unit->status !== 'cancelled' && $unit->progress === 'handed_over')
            <p class="muted adm-mt-3">Papan sudah diserahkan dan belum ditandai kembali. Tandai pengembalian lewat bagian perkembangan lebih dulu; setelah itu unit dapat dibatalkan bila memang perlu.</p>
        @elseif($unit->status !== 'cancelled')
            <details class="adm-card adm-card--flat adm-mt-3" data-adm-reveal="details" @if($errors->any()) open @endif>
                <summary>Batalkan unit</summary>
                <form class="adm-stack adm-pt-3" method="post" action="{{ route('admin.rental.cancel', $unit) }}">
                    @csrf
                    <p class="adm-help">Stok dilepas saat dibatalkan. Jika kesalahan penjual, pembayaran harus dikembalikan; catat pengembalian setelah dana benar-benar dikirim. Bukti yang masih menunggu tetap perlu diperiksa.</p>
                    <div class="adm-field-grid">
                        <div class="adm-field">
                            <label for="cancel-responsibility-{{ $unit->id }}">Pihak penyebab</label>
                            <select id="cancel-responsibility-{{ $unit->id }}" name="responsibility" required>
                                <option value="">Pilih</option>
                                <option value="seller">Kesalahan penjual</option>
                                <option value="customer">Pelanggan</option>
                                <option value="other">Lainnya / perlu penanganan manual</option>
                            </select>
                        </div>
                        <div class="adm-field">
                            <label for="cancel-reason-{{ $unit->id }}">Alasan pembatalan</label>
                            <input id="cancel-reason-{{ $unit->id }}" placeholder="Contoh: Pelanggan membatalkan lewat WhatsApp pada 25 Nov." name="reason" required maxlength="500">
                        </div>
                    </div>
                    <div><button class="btn secondary" type="submit">Batalkan unit</button></div>
                </form>
            </details>
        @else
            <p class="muted adm-mt-3">Alasan: {{ $unit->cancel_reason }}</p>
            @if($unit->cancel_responsibility === 'seller')
                <p class="adm-note adm-note--warn adm-mt-3">Kesalahan penjual: kembalikan pembayaran pelanggan. Jumlah terverifikasi yang belum dikembalikan: Rp {{ number_format($unit->payments->where('status', 'verified')->sum('amount') - $unit->payments->sum(fn ($p) => $p->refunds->sum('amount')), 0, ',', '.') }}. Periksa juga bukti yang masih menunggu.</p>
            @endif
        @endif
    </article>
@endforeach
</div>
@endsection
