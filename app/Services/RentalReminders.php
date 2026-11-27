<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use App\Models\RentalPayment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Pengingat untuk dua pihak yang saling menunggu.
 *
 * Setiap pengingat hanya dikirim sekali per unit, per jenis, per penerima,
 * per hari. Kuncinya ada di tabel `rental_unit_reminders` (unik), sehingga
 * perintah ini aman dijalankan berkali-kali dan boleh dijadwalkan rapat.
 *
 * Jenisnya: bukti bayar menganggur, pelunasan H-1, toleransi H+1, respons dan
 * eskalasi desain, antrean ongkos dua arah, acara yang sudah lewat, serta unit
 * yang belum ditandai kembali.
 *
 * Pengingat tidak mengubah status apa pun: ia hanya memberi tahu pihak yang
 * seharusnya bergerak, tanpa mengambil alih keputusan bisnis. Penutupan otomatis
 * unit yang menggantung ditangani perintah terpisah `rental:close-events`.
 */
class RentalReminders
{
    /** Bukti pembayaran dianggap menganggur setelah sekian jam. */
    public const PROOF_WAITING_HOURS = 6;

    /** Unit yang sudah lewat sekian hari dari tanggal kembali diingatkan. */
    public const RETURN_GRACE_DAYS = 2;

    /** Antrean antar/jemput dianggap menganggur setelah sekian jam. */
    public const TRANSPORT_WAIT_HOURS = 24;

    /**
     * @return array{proof:int, due:int, grace:int, grace_admin:int, design:int, design_escalation:int, return:int, transport_quote:int, transport_approval:int, event_overdue:int}
     */
    public function run(): array
    {
        $counts = ['proof' => 0, 'due' => 0, 'grace' => 0, 'grace_admin' => 0, 'design' => 0, 'design_escalation' => 0, 'return' => 0, 'transport_quote' => 0, 'transport_approval' => 0, 'event_overdue' => 0];
        $adminsReachable = User::where('role', 'admin')->where('is_active', true)->exists();

        if ($adminsReachable) $this->remindProofs($counts);
        $this->remindPaymentDue($counts);
        $this->remindGrace($counts, $adminsReachable);
        $this->remindDesign($counts, $adminsReachable);
        $this->remindReturns($counts, $adminsReachable);
        $this->remindTransport($counts, $adminsReachable);
        $this->remindEventOverdue($counts, $adminsReachable);

        return $counts;
    }

    /**
     * Antrean antar/jemput yang tidak pernah dibereskan: admin belum mengirim
     * penawaran, atau pelanggan belum menyetujui penawaran yang sudah dikirim.
     * Keduanya membuat tagihan tidak pasti dan pembayaran tidak bisa dikirim.
     */
    private function remindTransport(array &$counts, bool $adminsReachable): void
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $waitHours = self::TRANSPORT_WAIT_HOURS;

        if ($adminsReachable) {
            RentalOrderUnit::query()->with('order')
                ->where('status', RentalOrderUnit::STATUS_CONFIRMED)
                ->where(fn ($query) => $query->where('delivery_method', 'delivery')->orWhere('return_method', 'collection'))
                ->whereNull('transport_quote')
                ->whereNull('transport_approved_at')
                ->where('created_at', '<=', $now->subHours($waitHours))
                ->chunkById(200, function ($units) use (&$counts, $waitHours) {
                    foreach ($units as $unit) {
                        if (! $this->claim($unit, 'transport_quote_needed', 'admins')) continue;
                        app(RentalNotifications::class)->adminsForUnit(
                            $unit,
                            'Ongkos belum ditawarkan',
                            'Unit '.$unit->product_name.' memakai antar/jemput tetapi belum ada penawaran ongkos lebih dari '.$waitHours.' jam. Pelanggan belum dapat membayar sebelum ongkos disetujui.'
                        );
                        $counts['transport_quote']++;
                    }
                });
        }

        RentalOrderUnit::query()->with('order')
            ->where('status', RentalOrderUnit::STATUS_CONFIRMED)
            ->whereNotNull('transport_quote')
            ->whereNull('transport_approved_at')
            ->chunkById(200, function ($units) use (&$counts, $now, $waitHours) {
                foreach ($units as $unit) {
                    if (! $this->customerReachable($unit)) continue;
                    $quote = $unit->transportQuoteEvents()->where('action', 'quote')->latest('id')->first();
                    if (! $quote || $quote->created_at->greaterThan($now->subHours($waitHours))) continue;
                    if (! $this->claim($unit, 'transport_approval', 'customer')) continue;
                    app(RentalNotifications::class)->customerForUnit(
                        $unit,
                        'Ongkos menunggu persetujuan',
                        'Penawaran ongkos unit '.$unit->product_name.' sudah dikirim lebih dari '.$waitHours.' jam. Setujui atau tolak dengan alasan agar pesanan dapat diproses.'
                    );
                    $counts['transport_approval']++;
                }
            });
    }

    /**
     * Unit yang tanggal acaranya sudah lewat tetapi belum selesai dikerjakan.
     * Pengingat ini menahan antrean "aktif" agar tidak menumpuk tanpa tindakan.
     */
    private function remindEventOverdue(array &$counts, bool $adminsReachable): void
    {
        if (! $adminsReachable) return;
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->toDateString();
        RentalOrderUnit::query()->with('order')
            ->where('status', RentalOrderUnit::STATUS_CONFIRMED)
            ->whereIn('progress', ['waiting', 'production', 'ready'])
            ->where('event_on', '<', $today)
            ->chunkById(200, function ($units) use (&$counts) {
                foreach ($units as $unit) {
                    if (! $this->claim($unit, 'event_overdue', 'admins')) continue;
                    app(RentalNotifications::class)->adminsForUnit(
                        $unit,
                        'Tanggal acara sudah lewat',
                        'Unit '.$unit->product_name.' masih berstatus "'.\App\Support\RentalLabels::progress($unit->progress).'" padahal tanggal acaranya sudah lewat. Periksa unit, lalu lanjutkan, tutup, atau batalkan dengan alasan.'
                    );
                    $counts['event_overdue']++;
                }
            });
    }

    /**
     * Unit yang sudah diserahkan tetapi belum ditandai kembali beberapa hari
     * setelah tanggal kembali. Pengingat hanya untuk admin karena penandaan
     * pengembalian adalah tindakan operasional.
     */
    private function remindReturns(array &$counts, bool $adminsReachable): void
    {
        if (! $adminsReachable) return;
        $threshold = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->subDays(self::RETURN_GRACE_DAYS)->toDateString();
        RentalOrderUnit::query()->with('order')
            ->where('status', RentalOrderUnit::STATUS_CONFIRMED)
            ->where('progress', 'handed_over')
            ->where('ends_on', '<=', $threshold)
            ->chunkById(200, function ($units) use (&$counts) {
                foreach ($units as $unit) {
                    if (! $this->claim($unit, 'return_pending', 'admins')) continue;
                    app(RentalNotifications::class)->adminsForUnit(
                        $unit,
                        'Unit belum ditandai kembali',
                        'Unit '.$unit->product_name.' sudah lewat '.self::RETURN_GRACE_DAYS.' hari dari tanggal kembali tetapi belum ditandai dikembalikan. Tandai agar pelanggan dapat memberi rating.'
                    );
                    $counts['return']++;
                }
            });
    }

    /**
     * Unit yang dibatalkan karena tenggat tetapi masih dalam toleransi H+1
     * pukul 12.00 WIB. Pelanggan butuh pengingat untuk mengirim bukti; admin
     * butuh pengingat untuk memverifikasi sebelum batas itu lewat.
     */
    private function remindGrace(array &$counts, bool $adminsReachable): void
    {
        $policy = app(RentalPaymentDeadline::class);
        RentalOrderUnit::query()->with(['order', 'payments'])
            ->where('status', RentalOrderUnit::STATUS_CANCELLED)
            ->where('cancel_code', 'payment_timeout')
            ->whereNull('deposit_forfeited_at')
            ->where('starts_on', '<=', CarbonImmutable::now('Asia/Jakarta')->toDateString())
            ->chunkById(200, function ($units) use (&$counts, $policy, $adminsReachable) {
                foreach ($units as $unit) {
                    if (! $policy->withinGrace($unit)) continue;
                    $payable = $unit->payableAmount();
                    if ($payable === null || $unit->verifiedNet() >= $payable) continue;
                    $deadline = $policy->graceDeadline($unit)->format('d/m/Y H:i');
                    $proofWaiting = $unit->payments->contains(fn ($payment) => $payment->status === 'pending');
                    if ($proofWaiting && $adminsReachable && $this->claim($unit, 'payment_grace_admin', 'admins')) {
                        app(RentalNotifications::class)->adminsForUnit(
                            $unit,
                            'Bukti terlambat menunggu verifikasi',
                            'Bukti unit '.$unit->product_name.' harus diverifikasi paling lambat '.$deadline.' WIB agar unit dapat dilanjutkan.'
                        );
                        $counts['grace_admin']++;
                    }
                    if ($this->customerReachable($unit) && $this->claim($unit, 'payment_grace', 'customer')) {
                        app(RentalNotifications::class)->customerForUnit(
                            $unit,
                            'Batas melanjutkan unit',
                            'Unit '.$unit->product_name.' dibatalkan karena tenggat. Kirim bukti dan minta verifikasi paling lambat '
                                .$deadline.' WIB agar unit dapat dilanjutkan; sesudah itu DP hangus.'
                        );
                        $counts['grace']++;
                    }
                }
            });
    }

    /** Bukti pembayaran yang menunggu verifikasi terlalu lama. */
    private function remindProofs(array &$counts): void
    {
        $threshold = now()->subHours(self::PROOF_WAITING_HOURS);
        RentalPayment::query()->with('unit.order')
            ->where('status', 'pending')
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '<=', $threshold)
            ->chunkById(200, function ($payments) use (&$counts) {
                foreach ($payments as $payment) {
                    $unit = $payment->unit;
                    if (! $unit || ! $unit->order) continue;
                    if (! $this->claim($unit, 'payment_proof_pending', 'admins')) continue;
                    app(RentalNotifications::class)->adminsForUnit(
                        $unit,
                        'Bukti menunggu verifikasi',
                        'Bukti Rp '.number_format((int) $payment->amount, 0, ',', '.').' untuk unit '.$unit->product_name
                            .' sudah menunggu lebih dari '.self::PROOF_WAITING_HOURS.' jam. Verifikasi agar serah terima tidak tertahan.'
                    );
                    $counts['proof']++;
                }
            });
    }

    /** Pelunasan H-1 untuk unit yang belum tertutup bukti tepat waktu. */
    private function remindPaymentDue(array &$counts): void
    {
        $tomorrow = CarbonImmutable::now('Asia/Jakarta')->addDay()->toDateString();
        $policy = app(RentalPaymentDeadline::class);

        RentalOrderUnit::query()->with('order')
            ->where('status', RentalOrderUnit::STATUS_CONFIRMED)
            ->where('starts_on', $tomorrow)
            ->whereNotIn('progress', ['handed_over', 'returned'])
            ->chunkById(200, function ($units) use (&$counts, $policy) {
                foreach ($units as $unit) {
                    if (! $this->customerReachable($unit)) continue;
                    $payable = $unit->payableAmount();
                    // Ongkos belum disetujui: yang ditunggu admin, bukan pelanggan.
                    if ($payable === null) continue;
                    $covered = $policy->timelyCoverage($unit);
                    if ($covered >= $payable) continue;
                    if (! $this->claim($unit, 'payment_due', 'customer')) continue;
                    app(RentalNotifications::class)->customerForUnit(
                        $unit,
                        'Pelunasan jatuh tempo malam ini',
                        'Sisa Rp '.number_format(max(0, $payable - $covered), 0, ',', '.').' untuk unit '.$unit->product_name
                            .' harus dibayar sebelum pukul 23.59 WIB hari ini agar jadwal tidak dibatalkan otomatis.'
                    );
                    $counts['due']++;
                }
            });
    }

    /** Desain yang menunggu pelanggan; admin dieskalasi saat tenggat respons lewat. */
    private function remindDesign(array &$counts, bool $adminsReachable): void
    {
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $customerThreshold = now()->subHours(RentalDesignDeadline::WAIT_HOURS);
        $deadlines = app(RentalDesignDeadline::class);

        RentalOrderUnit::query()->with(['order', 'previews'])
            ->where('status', RentalOrderUnit::STATUS_CONFIRMED)
            ->where('progress', 'waiting')
            ->where('starts_on', '>=', $today->toDateString())
            ->whereHas('previews', fn ($query) => $query->where('status', 'pending')->where('created_at', '<=', $customerThreshold))
            ->chunkById(200, function ($units) use (&$counts, $adminsReachable, $customerThreshold, $deadlines) {
                foreach ($units as $unit) {
                    // Admin sudah memutuskan melanjutkan tanpa persetujuan: tidak ada
                    // lagi pihak yang perlu diingatkan soal respons desain.
                    if ($unit->designOverrideActive()) continue;
                    $latest = $unit->previews->first();
                    // Versi terbaru harus benar-benar yang menganggur: preview lama
                    // yang tertinggal tidak boleh memicu pengingat untuk versi baru.
                    if (! $latest || $latest->status !== 'pending' || $latest->created_at->greaterThan($customerThreshold)) continue;

                    if ($this->customerReachable($unit) && $this->claim($unit, 'design_response', 'customer')) {
                        app(RentalNotifications::class)->customerForUnit(
                            $unit,
                            'Pratinjau desain menunggu',
                            'Pratinjau desain unit '.$unit->product_name.' masih menunggu persetujuanmu. '
                                .'Pengerjaan belum bisa dimulai sebelum desain disetujui.'
                        );
                        $counts['design']++;
                    }

                    if ($adminsReachable && $deadlines->reached($unit) && $this->claim($unit, 'design_escalation', 'admins')) {
                        app(RentalNotifications::class)->adminsForUnit(
                            $unit,
                            'Pelanggan belum merespons desain',
                            'Pratinjau unit '.$unit->product_name.' belum direspons pelanggan. Hubungi pelanggan atau putuskan langkah berikutnya.'
                        );
                        $counts['design_escalation']++;
                    }
                }
            });
    }

    private function customerReachable(RentalOrderUnit $unit): bool
    {
        $order = $unit->order;

        return $order && $order->customer && $order->customer->dapatMenerimaNotifikasi();
    }

    /**
     * Catat pengingat hari ini. Hanya yang berhasil mencatat baris baru yang
     * boleh mengirim notifikasi, sehingga dua proses penjadwal tidak menggandakan
     * pesan dan pengingat yang sama tidak terulang di hari yang sama.
     */
    private function claim(RentalOrderUnit $unit, string $kind, string $recipient): bool
    {
        return DB::table('rental_unit_reminders')->insertOrIgnore([
            'rental_order_unit_id' => $unit->id,
            'kind' => $kind,
            'recipient' => $recipient,
            'sent_on' => CarbonImmutable::now('Asia/Jakarta')->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }
}
