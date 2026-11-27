{{--
|--------------------------------------------------------------------------
| Panel "Langkah berikutnya" per unit (putaran 43)
|--------------------------------------------------------------------------
| Dulu tindakan utama admin — memajukan pengerjaan — tersembunyi di dalam
| <details> "Riwayat dan perkembangan" di dasar kartu unit. Admin harus
| menggulir, membuka lipatan, lalu mencari tombolnya. Konfirmasi unit dan
| melanjutkan unit yang batal juga terpencar di tempat lain.
|
| Panel ini menyatukan semuanya di puncak kartu: satu kalimat "apa sekarang",
| satu tombol utama, plus alasan bila tombol itu belum bisa dipakai. Riwayat
| dan stepper tetap ada di lipatan bawah, tanpa tombol duplikat.
--}}
@php
    $nextStepLabels = [
        'waiting' => 'Mulai pengerjaan',
        'production' => 'Tandai siap diserahkan',
        'ready' => 'Tandai sudah diserahkan',
        'handed_over' => 'Tandai sudah dikembalikan',
    ];
    $nextStepHints = [
        'waiting' => 'Butuh pratinjau desain terbaru yang disetujui pelanggan, atau keputusan "lanjut dengan catatan" setelah tenggat respons desain lewat.',
        'production' => 'Tandai setelah papan benar-benar selesai dikerjakan; pelanggan langsung menerima kabar.',
        'ready' => 'Serah terima butuh desain yang disetujui dan pembayaran lunas terverifikasi.',
        'handed_over' => 'Tandai setelah papan kembali diterima. Setelah ini unit dianggap selesai.',
    ];
    $nextStep = $unit->status === 'confirmed' ? ($nextStepLabels[$unit->progress] ?? null) : null;

    // Alasan tombol belum bisa dipakai, dihitung dari aturan yang sama dengan
    // RentalProgressController supaya admin tidak menekan tombol lalu ditolak.
    $nextStepBlocker = null;
    $nextStepAnchor = null;
    $nextStepAnchorLabel = null;
    if ($nextStep !== null) {
        $tagihan = $unit->payableAmount();
        if ($unit->progress === 'waiting' && ! $unit->designApprovedForProduction()) {
            $nextStepBlocker = 'Pratinjau desain belum disetujui pelanggan.';
            [$nextStepAnchor, $nextStepAnchorLabel] = ['desain', 'Buka pratinjau desain'];
        } elseif ($unit->progress === 'ready') {
            if (! $unit->designApprovedForProduction()) {
                $nextStepBlocker = 'Desain belum disetujui pelanggan.';
                [$nextStepAnchor, $nextStepAnchorLabel] = ['desain', 'Buka pratinjau desain'];
            } elseif ($tagihan === null) {
                $nextStepBlocker = 'Tagihan belum final karena ongkos belum disetujui.';
                [$nextStepAnchor, $nextStepAnchorLabel] = ['ongkos', 'Buka ongkos dan tagihan'];
            } elseif ($unit->verifiedNet() < $tagihan) {
                $nextStepBlocker = 'Pembayaran belum lunas: kurang Rp '.number_format(max(0, $tagihan - $unit->verifiedNet()), 0, ',', '.');
                [$nextStepAnchor, $nextStepAnchorLabel] = ['pembayaran', 'Buka pembayaran'];
            }
        }
    }

    $deadlinePolicy = app(\App\Services\RentalPaymentDeadline::class);
    $bisaLanjut = $unit->status === 'cancelled' && $unit->status_before_cancel
        && ($unit->cancel_code === 'payment_timeout'
            ? $deadlinePolicy->withinGrace($unit)
            : now('Asia/Jakarta')->toDateString() <= $unit->starts_on->toDateString());
@endphp

<div class="adm-next {{ $nextStepBlocker ? 'adm-next--blocked' : '' }}" data-adm-reveal="next">
    <div class="adm-next__body">
        <span class="adm-kicker">Langkah berikutnya</span>

        @if($unit->status === 'pending')
            <p class="adm-next__title">Konfirmasi unit</p>
            <p class="adm-help adm-flat">Konfirmasi mencadangkan stok untuk tanggal ini. Setelah dikonfirmasi, unit masuk antrean pengerjaan.</p>
        @elseif($unit->status === 'cancelled')
            <p class="adm-next__title">{{ $bisaLanjut ? 'Lanjutkan unit yang dibatalkan' : 'Unit dibatalkan' }}</p>
            @if($bisaLanjut)
                <p class="adm-help adm-flat">Stok perlu dicek ulang. {{ $unit->status_before_cancel === 'pending' ? 'Unit kembali menunggu konfirmasi.' : 'Unit kembali dikonfirmasi bila pembayaran lunas dan stok tersedia.' }}</p>
            @else
                <p class="adm-help adm-flat">Alasan: {{ $unit->cancel_reason ?: 'tercatat di riwayat' }}. Batas melanjutkan sudah terlewati, jadi unit tidak bisa dibuka kembali.</p>
            @endif
        @elseif($nextStep)
            <p class="adm-next__title">{{ $nextStep }}</p>
            <p class="adm-help adm-flat">{{ $nextStepHints[$unit->progress] ?? '' }}</p>
            @if($nextStepBlocker)
                <p class="adm-note adm-note--warn adm-flat adm-next__blocker" id="blocker-{{ $unit->id }}">
                    {{ $nextStepBlocker }}
                    <a href="#{{ $nextStepAnchor }}-{{ $unit->id }}">{{ $nextStepAnchorLabel }}</a>
                </p>
            @endif
        @else
            <p class="adm-next__title">Tidak ada tindakan tersisa</p>
            <p class="adm-help adm-flat">Pengerjaan unit ini sudah selesai.</p>
        @endif
    </div>

    <div class="adm-next__action">
        @if($unit->status === 'pending')
            <form class="adm-stack" method="post" action="{{ route('admin.rental.confirm', $unit) }}">
                @csrf
                @if($unit->needsSpecialConfirmation())
                    <p class="adm-help adm-flat">Tanggal penerimaan jatuh pada atau sebelum batas konfirmasi; persetujuan khusus diperlukan.</p>
                    <label class="adm-switch">
                        <input type="checkbox" name="special_confirmation" value="1" required>
                        <span class="adm-switch__track" aria-hidden="true"></span>
                        <span>Saya menyetujui penanganan khusus untuk jadwal dekat.</span>
                    </label>
                @endif
                <div><button class="btn" type="submit">Konfirmasi unit</button></div>
            </form>
        @elseif($unit->status === 'cancelled' && $bisaLanjut)
            <form class="adm-stack" method="post" action="{{ route('admin.rental.resume', $unit) }}">
                @csrf
                <div class="adm-field">
                    <label for="resume-reason-{{ $unit->id }}">Alasan melanjutkan (tercatat di riwayat, terlihat pelanggan)</label>
                    <input id="resume-reason-{{ $unit->id }}" placeholder="Contoh: Pembayaran lunas dan unit tersedia kembali." name="reason" maxlength="500" required>
                </div>
                <div><button class="btn secondary" type="submit">Lanjutkan unit</button></div>
            </form>
        @elseif($nextStep)
            <form class="adm-stack" method="post" action="{{ route('admin.rental.progress.advance', $unit) }}">
                @csrf
                <input type="hidden" name="expected_progress" value="{{ $unit->progress }}">
                <div class="adm-field">
                    <label for="next-note-{{ $unit->id }}">Catatan untuk pelanggan (opsional)</label>
                    <input id="next-note-{{ $unit->id }}" placeholder="Contoh: Rangkaian selesai dirakit, siap diantar pukul 14.00." name="note" maxlength="1000">
                </div>
                <div class="adm-row adm-row--tight">
                    <button class="btn" type="submit" @if($nextStepBlocker) disabled aria-describedby="blocker-{{ $unit->id }}" @endif>{{ $nextStep }}</button>
                    <a class="adm-btn adm-btn--ghost adm-btn--sm" href="#riwayat-{{ $unit->id }}">Riwayat</a>
                </div>
            </form>
        @else
            <a class="adm-btn adm-btn--ghost adm-btn--sm" href="#riwayat-{{ $unit->id }}">Lihat riwayat</a>
        @endif
    </div>
</div>
