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
 * Pengingat tidak mengubah status apa pun: ia hanya memberi tahu pihak yang
 * seharusnya bergerak, tanpa mengambil alih keputusan bisnis.
 */
class RentalReminders
{
    /** Bukti pembayaran dianggap menganggur setelah sekian jam. */
    public const PROOF_WAITING_HOURS = 6;

    /**
     * @return array{proof:int, due:int, grace:int, grace_admin:int, design:int, design_escalation:int}
     */
    public function run(): array
    {
        $counts = ['proof' => 0, 'due' => 0, 'grace' => 0, 'grace_admin' => 0, 'design' => 0, 'design_escalation' => 0];
        $adminsReachable = User::where('role', 'admin')->where('is_active', true)->exists();

        if ($adminsReachable) $this->remindProofs($counts);
        $this->remindPaymentDue($counts);
        $this->remindGrace($counts, $adminsReachable);
        $this->remindDesign($counts, $adminsReachable);

        return $counts;
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
            ->whereDate('starts_on', '<=', CarbonImmutable::now('Asia/Jakarta')->toDateString())
            ->chunkById(200, function ($units) use (&$counts, $policy, $adminsReachable) {
                foreach ($units as $unit) {
                    if (! $policy->withinGrace($unit)) continue;
                    $payable = $unit->payableAmount();
                    if ($payable === null || $unit->verifiedNet() >= $payable) continue;
                    $deadline = $policy->graceDeadline($unit)->format('d/m/Y H:i');
                    $proofWaiting = $unit->payments->contains(fn ($payment) => $payment->status === 'pending');
                    if ($proofWaiting && $adminsReachable && $this->claim($unit, 'payment_grace_admin', 'admins')) {
                        app(RentalNotifications::class)->admins(
                            $unit->order,
                            'Bukti terlambat menunggu verifikasi',
                            'Bukti unit '.$unit->product_name.' harus diverifikasi paling lambat '.$deadline.' WIB agar unit dapat dilanjutkan.'
                        );
                        $counts['grace_admin']++;
                    }
                    if ($this->customerReachable($unit) && $this->claim($unit, 'payment_grace', 'customer')) {
                        app(RentalNotifications::class)->customer(
                            $unit->order,
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
                    app(RentalNotifications::class)->admins(
                        $unit->order,
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
            ->whereDate('starts_on', $tomorrow)
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
                    app(RentalNotifications::class)->customer(
                        $unit->order,
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
            ->whereDate('starts_on', '>=', $today->toDateString())
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
                        app(RentalNotifications::class)->customer(
                            $unit->order,
                            'Preview desain menunggu',
                            'Preview desain unit '.$unit->product_name.' masih menunggu persetujuanmu. '
                                .'Pengerjaan belum bisa dimulai sebelum desain disetujui.'
                        );
                        $counts['design']++;
                    }

                    if ($adminsReachable && $deadlines->reached($unit) && $this->claim($unit, 'design_escalation', 'admins')) {
                        app(RentalNotifications::class)->admins(
                            $unit->order,
                            'Pelanggan belum merespons desain',
                            'Preview unit '.$unit->product_name.' belum direspons pelanggan. Hubungi pelanggan atau putuskan langkah berikutnya.'
                        );
                        $counts['design_escalation']++;
                    }
                }
            });
    }

    private function customerReachable(RentalOrderUnit $unit): bool
    {
        $order = $unit->order;

        return $order && $order->customer && $order->customer->is_active;
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
